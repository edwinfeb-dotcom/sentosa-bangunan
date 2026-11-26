<?php

namespace App\Console\Commands;

use App\Http\Controllers\Setting\BackupController;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--keep=14 : Jumlah file backup harian terbaru yang disimpan}';

    protected $description = 'Buat cadangan SQL database Sentosa Bangunan ke storage/app/backups dengan retensi otomatis.';

    public function handle(): int
    {
        $keep = max(1, (int) $this->option('keep'));
        $filePath = BackupController::createBackupFile($keep);

        $this->info('Backup database berhasil disimpan: ' . basename($filePath));
        return self::SUCCESS;
    }
}
