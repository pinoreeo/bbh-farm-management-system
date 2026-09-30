<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BirthEvent;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use App\Models\ColonyPen;
use App\Models\HealthTreatment;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use App\Models\RsaKey;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBrowseController extends Controller
{
    private const RESOURCES = [
        'users' => [User::class, [], ['name', 'role', 'is_active', 'last_login_at'], ['name', 'first_name', 'last_name', 'email', 'role']],
        'animals' => [Animal::class, ['breed', 'currentPen'], ['tag_number', 'breed.breed_name', 'generation', 'sex', 'origin_type', 'life_status', 'birth_date'], ['tag_number', 'breed.breed_name', 'generation', 'sex', 'origin_type', 'life_status', 'exit_status', 'reproductive_status']],
        'weight-records' => [WeightRecord::class, ['animal'], ['animal.tag_number', 'record_date', 'weight_kg', 'animal.birth_date', 'notes'], ['animal.tag_number', 'record_date', 'weight_kg', 'notes']],
        'pens' => [ColonyPen::class, [], ['pen_code', 'colony_code', 'colony_name', 'colony_phase', 'location', 'capacity', 'is_active'], ['pen_code', 'colony_code', 'colony_name', 'colony_phase', 'location']],
        'pen-movements' => [AnimalPenMovement::class, ['animal', 'fromPen', 'toPen'], ['animal.tag_number', 'fromPen.pen_code', 'toPen.pen_code', 'movement_date', 'reason'], ['animal.tag_number', 'fromPen.pen_code', 'toPen.pen_code', 'movement_date', 'reason']],
        'breeding-periods' => [BreedingPeriod::class, ['colonyPen', 'maleAnimal'], ['period_code', 'colonyPen.pen_code', 'start_date', 'end_date', 'maleAnimal.tag_number', 'status'], ['period_code', 'colonyPen.pen_code', 'maleAnimal.tag_number', 'status']],
        'breeding-females' => [BreedingFemale::class, ['breedingPeriod', 'femaleAnimal'], ['breedingPeriod.period_code', 'femaleAnimal.tag_number', 'entry_date', 'mating_date', 'expected_birth_date', 'cycle_stage', 'exit_date', 'exit_reason'], ['breedingPeriod.period_code', 'femaleAnimal.tag_number', 'entry_date', 'mating_date', 'cycle_stage', 'exit_reason']],
        'pregnancy-checks' => [BreedingPeriod::class, ['colonyPen', 'maleAnimal'], ['period_code', 'colonyPen.pen_code', 'maleAnimal.tag_number', 'start_date', 'end_date', 'status'], ['period_code', 'colonyPen.pen_code', 'maleAnimal.tag_number', 'status']],
        'birth-events' => [BirthEvent::class, ['sire', 'dam'], ['sire.tag_number', 'dam.tag_number', 'birth_date', 'birth_time', 'offspring_count', 'birth_process', 'birth_place'], ['sire.tag_number', 'dam.tag_number', 'birth_date', 'birth_process', 'birth_place']],
        'offspring-births' => [OffspringBirth::class, ['birthEvent', 'offspringAnimal'], ['birthEvent.birth_date', 'offspringAnimal.tag_number', 'birth_weight_kg', 'offspring_grade', 'birth_status', 'notes'], ['offspringAnimal.tag_number', 'offspring_grade', 'birth_status', 'notes']],
        'health-treatments' => [HealthTreatment::class, ['animal'], ['animal.tag_number', 'treatment_date', 'treatment_group', 'symptoms', 'diagnosis', 'product_name', 'dosage', 'next_control_date'], ['animal.tag_number', 'treatment_group', 'symptoms', 'diagnosis', 'product_name', 'dosage']],
        'vaccinations' => [Vaccination::class, ['animal'], ['animal.tag_number', 'category_name', 'vaccination_date', 'product_name', 'dosage', 'administration_route'], ['animal.tag_number', 'category_name', 'product_name', 'dosage', 'administration_route']],
        'postnatal-care' => [PostnatalCareRecord::class, ['birthEvent', 'targetAnimal'], ['birth_event_id', 'targetAnimal.tag_number', 'care_date', 'administration_method', 'volume_ml', 'navel_iodine_status', 'vitamin_ade_ml', 'vitamin_b_complex_ml', 'intracin_ml'], ['targetAnimal.tag_number', 'administration_method', 'navel_iodine_status']],
        'certificates' => [Certificate::class, ['animal', 'certificateType'], ['certificate_number', 'animal.tag_number', 'certificateType.type_name', 'issue_date', 'status'], ['certificate_number', 'animal.tag_number', 'certificateType.type_name', 'status']],
        'certificate-logs' => [CertificateVerificationLog::class, ['certificate'], ['certificate.certificate_number', 'verification_time', 'verification_time', 'verification_method', 'is_valid', 'failure_reason', 'ip_address'], ['certificate.certificate_number', 'verification_method', 'failure_reason', 'ip_address']],
        'activity-logs' => [AdminActivityLog::class, ['admin'], ['created_at', 'created_at', 'admin_name', 'module', 'action', 'description', 'status_code', 'ip_address'], ['admin_name', 'admin_email', 'module', 'action', 'description', 'ip_address']],
        'rsa-keys' => [RsaKey::class, ['user'], ['key_identifier', 'user.name', 'algorithm', 'key_length', 'fingerprint_sha256', 'key_status'], ['key_identifier', 'user.name', 'user.email', 'algorithm', 'fingerprint_sha256', 'key_status']],
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);
        abort_if(in_array($resource, ['users', 'activity-logs'], true) && $request->user()?->role !== 'super_admin', 403);

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'account_status' => ['nullable', 'in:active,inactive'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $query = $this->queryFor($resource, $request);
        $sortColumns = self::RESOURCES[$resource][2];
        $sortIndex = $request->filled('sort') ? $request->integer('sort') : null;
        if ($sortIndex !== null && isset($sortColumns[$sortIndex])) {
            $this->sortBy($query, $sortColumns[$sortIndex], $request->string('direction', 'asc')->toString());
        } else {
            $query->orderByDesc($query->getModel()->qualifyColumn('id'));
        }
        $query->orderByDesc($query->getModel()->qualifyColumn('id'));

        $page = $query->paginate(10);
        $payload = $page->toArray();

        if (in_array($resource, ['certificate-logs', 'activity-logs'], true)) {
            $dateColumn = $resource === 'certificate-logs' ? 'verification_time' : 'created_at';
            $model = self::RESOURCES[$resource][0];
            $oldest = $model::query()->min($dateColumn);
            $newest = $model::query()->max($dateColumn);
            $payload['filter_years'] = is_string($oldest) && is_string($newest)
                ? range((int) substr($newest, 0, 4), (int) substr($oldest, 0, 4))
                : [];
            $payload['filter_months'] = range(1, 12);
        }

        return response()->json($payload);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $slugs = [
            'animals', 'pens', 'breeding-periods', 'breeding-females', 'birth-events',
            'offspring-births', 'weight-records', 'health-treatments', 'vaccinations', 'certificates',
        ];
        if ($request->user()?->role === 'super_admin') {
            array_push($slugs, 'users', 'rsa-keys');
        }

        $offset = ($request->integer('page', 1) - 1) * 10;
        $remaining = 10;
        $total = 0;
        $items = [];

        foreach ($slugs as $slug) {
            $query = $this->queryFor($slug, $request);
            $count = $query->count();
            $total += $count;

            if ($remaining > 0 && $offset < $count) {
                $rows = $query->orderByDesc($query->getModel()->qualifyColumn('id'))
                    ->skip($offset)->take($remaining)->get();
                foreach ($rows as $row) {
                    $items[] = ['slug' => $slug, 'item' => $row];
                }
                $remaining -= $rows->count();
            }

            $offset = max(0, $offset - $count);
        }

        return response()->json([
            'data' => $items,
            'total' => $total,
            'per_page' => 10,
            'current_page' => $request->integer('page', 1),
        ]);
    }

    /** @return Builder<covariant \Illuminate\Database\Eloquent\Model> */
    private function queryFor(string $resource, Request $request): Builder
    {
        [$model, $relations, , $searchColumns] = self::RESOURCES[$resource];
        $query = $model::query()->with($relations);

        if ($resource === 'pens') {
            $query->withCount('animals');
        } elseif (in_array($resource, ['breeding-periods', 'pregnancy-checks'], true)) {
            $query->withCount('females');
        } elseif ($resource === 'rsa-keys' && $request->user()?->role !== 'super_admin') {
            $query->where('user_id', $request->user()?->id);
        }

        if ($resource === 'users' && in_array($request->query('account_status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->query('account_status') === 'active');
        }

        foreach (['sex', 'life_status', 'exit_status'] as $field) {
            if ($resource === 'animals' && $request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        if ($resource === 'pens' && $request->filled('colony_phase')) {
            $query->where('colony_phase', $request->query('colony_phase'));
        }

        if ($request->filled('q')) {
            $terms = array_filter(preg_split('/\s+/u', trim((string) $request->query('q'))) ?: []);
            if ($terms !== []) {
                $query->where(function (Builder $matches) use ($searchColumns, $terms): void {
                    foreach ($terms as $term) {
                        foreach ($searchColumns as $column) {
                            if (str_contains($column, '.')) {
                                [$relation, $field] = explode('.', $column, 2);
                                $matches->orWhereHas($relation, fn (Builder $related) => $related->where($field, 'like', "%{$term}%"));
                            } else {
                                $matches->orWhere($column, 'like', "%{$term}%");
                            }
                        }
                    }
                });
            }
        }

        $dateColumn = match ($resource) {
            'animals' => 'birth_date', 'weight-records' => 'record_date', 'pen-movements' => 'movement_date',
            'breeding-periods', 'pregnancy-checks' => 'start_date', 'breeding-females' => 'entry_date',
            'birth-events' => 'birth_date', 'health-treatments' => 'treatment_date',
            'vaccinations' => 'vaccination_date', 'postnatal-care' => 'care_date',
            'certificates' => 'issue_date', 'certificate-logs' => 'verification_time',
            'activity-logs' => 'created_at', default => null,
        };

        if ($dateColumn !== null) {
            if (in_array($resource, ['certificate-logs', 'activity-logs'], true) && ! $request->filled('year')) {
                $newest = $query->getModel()::query()->max($dateColumn);
                if (is_string($newest)) {
                    $query->whereYear($dateColumn, (int) substr($newest, 0, 4));
                }
            }
            if ($request->filled('date_from')) {
                $query->whereDate($dateColumn, '>=', $request->query('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate($dateColumn, '<=', $request->query('date_to'));
            }
            if ($request->filled('year')) {
                $query->whereYear($dateColumn, (int) $request->query('year'));
            }
            if ($request->filled('month')) {
                $query->whereMonth($dateColumn, (int) $request->query('month'));
            }
        }

        return $query;
    }

    /** @param Builder<covariant \Illuminate\Database\Eloquent\Model> $query */
    private function sortBy(Builder $query, string $column, string $direction): void
    {
        if (! str_contains($column, '.')) {
            $query->orderBy($query->getModel()->qualifyColumn($column), $direction);

            return;
        }

        [$relationName, $field] = explode('.', $column, 2);
        $relation = $query->getModel()->{$relationName}();
        if (! $relation instanceof BelongsTo) {
            return;
        }

        $related = $relation->getRelated();
        $query->orderBy(
            DB::table($related->getTable())
                ->select($field)
                ->whereColumn($related->qualifyColumn($relation->getOwnerKeyName()), $relation->getQualifiedForeignKeyName())
                ->limit(1),
            $direction
        );
    }
}
