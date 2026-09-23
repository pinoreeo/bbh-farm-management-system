<?php

use App\Http\Controllers\Api\V1\PostnatalCareRecordController;
use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\Breed;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use App\Models\RsaKey;
use App\Models\User;
use App\Services\AnimalService;
use App\Services\RsaKeyService;
use App\Support\AnimalEartag;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const TEST_DATABASE = 'bbh_farm_concurrency_test';

if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== TEST_DATABASE) {
    fwrite(STDERR, 'Probe hanya boleh dijalankan pada database MySQL '.TEST_DATABASE.".\n");
    exit(2);
}

if (($argv[1] ?? null) === 'worker') {
    $scenario = $argv[2] ?? '';
    $id = (int) ($argv[3] ?? 0);

    $result = match ($scenario) {
        'rsa' => app(RsaKeyService::class)->deactivate(RsaKey::query()->findOrFail($id)),
        'animal' => app(AnimalService::class)->store(Request::create('/', 'POST'), [
            'breed_id' => $id,
            'sex' => 'female',
            'generation' => 'F1',
            'birth_date' => '2026-05-17',
        ]),
        'postnatal' => app(PostnatalCareRecordController::class)->store(Request::create('/', 'POST', [
            'offspring_birth_id' => $id,
            'care_date' => '2026-05-18',
        ])),
        default => throw new RuntimeException('Skenario probe tidak dikenal.'),
    };

    if ($result instanceof JsonResponse) {
        echo json_encode(['status' => $result->getStatusCode()], JSON_THROW_ON_ERROR);
    } else {
        echo json_encode([
            'status' => $result['status'] ?? 500,
            'tag' => $result['data']->tag_number ?? null,
        ], JSON_THROW_ON_ERROR);
    }

    exit(0);
}

/**
 * @param  callable(): void  $holdLock
 * @param  array<int, int>  $ids
 * @return array<int, array{status: int, tag?: string|null}>
 */
function runWhileLocked(string $scenario, array $ids, callable $holdLock): array
{
    $processes = [];
    DB::beginTransaction();

    try {
        $holdLock();
        foreach ($ids as $id) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', $scenario, (string) $id], base_path(), [
                'DB_DATABASE' => TEST_DATABASE,
            ]);
            $process->setTimeout(20);
            $process->start();
            $processes[] = $process;
        }

        usleep(1_000_000);
        foreach ($processes as $process) {
            if (! $process->isRunning()) {
                throw new RuntimeException("Worker {$scenario} selesai sebelum lock dilepas: ".$process->getErrorOutput());
            }
        }

        DB::commit();
    } catch (Throwable $exception) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        throw $exception;
    }

    $results = [];
    foreach ($processes as $process) {
        $process->wait();
        if (! $process->isSuccessful()) {
            throw new RuntimeException("Worker {$scenario} gagal: ".$process->getErrorOutput());
        }
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($result)) {
            throw new RuntimeException("Worker {$scenario} tidak mengembalikan hasil.");
        }
        $results[] = $result;
    }

    return $results;
}

function animal(Breed $breed, string $tag, string $sex = 'female'): Animal
{
    return Animal::query()->create([
        'tag_number' => $tag,
        'breed_id' => $breed->id,
        'sex' => $sex,
        'generation' => 'F1',
        'birth_date' => '2026-05-17',
    ]);
}

$suffix = bin2hex(random_bytes(5));
$user = User::query()->create([
    'name' => 'Concurrency Probe',
    'email' => "concurrency-{$suffix}@example.test",
    'password' => bcrypt($suffix),
    'role' => 'admin',
    'is_active' => true,
]);
$keys = [];
for ($index = 1; $index <= 2; $index++) {
    $keys[] = RsaKey::query()->create([
        'user_id' => $user->id,
        'key_identifier' => "CONCURRENCY-{$suffix}-{$index}",
        'public_key_pem' => "probe-{$suffix}-{$index}",
        'key_length' => 2048,
        'fingerprint_sha256' => hash('sha256', "probe-{$suffix}-{$index}"),
        'is_active' => true,
        'key_status' => 'active',
    ]);
}

$rsa = runWhileLocked('rsa', [$keys[0]->id, $keys[1]->id], function () use ($user): void {
    User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
});
$rsaStatuses = array_column($rsa, 'status');
sort($rsaStatuses);
if ($rsaStatuses !== [200, 422] || RsaKey::query()->where('user_id', $user->id)->where('is_active', true)->count() !== 1) {
    throw new RuntimeException('RSA: dua penonaktifan tidak menjaga satu key aktif.');
}
echo "RSA: satu berhasil, satu ditolak, satu key tetap aktif.\n";

$breed = Breed::query()->create(['breed_name' => "Probe {$suffix}"]);
$blockingTag = app(AnimalEartag::class)->next('2026-05-17');
$animalResults = runWhileLocked('animal', [$breed->id, $breed->id], function () use ($breed, $blockingTag): void {
    animal($breed, $blockingTag);
});
$tags = array_column($animalResults, 'tag');
if (array_column($animalResults, 'status') !== [201, 201] || count(array_unique($tags)) !== 2 || in_array($blockingTag, $tags, true)) {
    throw new RuntimeException('Eartag: penyimpanan bersamaan tidak menghasilkan nomor unik.');
}
echo "Eartag: dua penyimpanan berhasil dengan nomor berbeda.\n";

$dam = animal($breed, "PROBE-DAM-{$suffix}");
$offspring = animal($breed, "PROBE-KID-{$suffix}");
$birthEvent = BirthEvent::query()->create([
    'dam_id' => $dam->id,
    'birth_date' => '2026-05-17',
    'offspring_count' => 1,
    'birth_process' => 'normal',
]);
$offspringBirth = OffspringBirth::query()->create([
    'birth_event_id' => $birthEvent->id,
    'offspring_animal_id' => $offspring->id,
    'birth_weight_kg' => 3,
    'birth_status' => 'alive',
]);

$postnatal = runWhileLocked('postnatal', [$offspringBirth->id, $offspringBirth->id], function () use ($offspring): void {
    Animal::query()->whereKey($offspring->id)->lockForUpdate()->firstOrFail();
});
$postnatalStatuses = array_column($postnatal, 'status');
sort($postnatalStatuses);
if ($postnatalStatuses !== [201, 422] || PostnatalCareRecord::query()->where('offspring_birth_id', $offspringBirth->id)->count() !== 1) {
    throw new RuntimeException('Pascakelahiran: pencatatan bersamaan tidak menghasilkan satu catatan.');
}
echo "Pascakelahiran: satu catatan tersimpan, duplikat ditolak tanpa error server.\n";
