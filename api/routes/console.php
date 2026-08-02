<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('bbh:backup-db {--output= : Custom backup file path}', function () {
    if (config('database.default') !== 'mysql') {
        $this->error('Backup otomatis saat ini hanya mendukung koneksi mysql.');

        return Command::FAILURE;
    }

    $connection = config('database.connections.mysql');
    $output = $this->option('output') ?: storage_path('app/backups/bbh-farm-'.now()->format('Ymd-His').'.sql');

    if (! is_dir(dirname($output))) {
        mkdir(dirname($output), 0755, true);
    }

    $command = [
        'mysqldump',
        '--host='.$connection['host'],
        '--port='.(string) $connection['port'],
        '--user='.$connection['username'],
        '--single-transaction',
        '--quick',
        '--result-file='.$output,
        $connection['database'],
    ];

    $env = [];
    if (($connection['password'] ?? '') !== '') {
        $env['MYSQL_PWD'] = (string) $connection['password'];
    }

    $process = new Process($command, base_path(), $env, null, 120);
    $process->run();

    if (! $process->isSuccessful()) {
        $this->error('Backup database gagal. Pastikan mysqldump tersedia di server.');
        $this->line($process->getErrorOutput());

        return Command::FAILURE;
    }

    $this->info('Backup database berhasil dibuat: '.$output);

    return Command::SUCCESS;
})->purpose('Backup database MySQL BBH Farm ke file SQL');
