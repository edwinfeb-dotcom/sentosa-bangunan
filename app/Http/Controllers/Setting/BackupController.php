<?php

namespace App\Http\Controllers\Setting;

use App\Exports\DailyReportsExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    private const BACKUP_DIR = 'app/backups';

    public function index()
    {
        $dir = storage_path(self::BACKUP_DIR);
        $files = collect();

        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.sql') as $path) {
                $files->push([
                    'name' => basename($path),
                    'size' => filesize($path),
                    'modified_at' => date('d/m/Y H:i', filemtime($path)),
                    'timestamp' => filemtime($path),
                ]);
            }
        }

        $backupFiles = $files->sortByDesc('timestamp')->values();

        return view('setting.backup.index', compact('backupFiles'));
    }

    public function exportReports()
    {
        return Excel::download(new DailyReportsExport, 'Laporan_Harian_' . date('Y-m-d') . '.xlsx');
    }

    public function downloadSql(): StreamedResponse
    {
        $filename = 'backup_sentosa_bangunan_' . date('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            self::writeSqlDumpToStream($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function runBackupNow()
    {
        $path = self::createBackupFile(14);
        return back()->with('success', 'Cadangan database berhasil disimpan di server: ' . basename($path));
    }

    public function downloadBackupFile(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        $path = storage_path(self::BACKUP_DIR . DIRECTORY_SEPARATOR . $safe);
        abort_unless(str_ends_with($safe, '.sql') && is_file($path), 404);

        return response()->download($path);
    }

    public function deleteBackupFile(string $filename)
    {
        $safe = basename($filename);
        $path = storage_path(self::BACKUP_DIR . DIRECTORY_SEPARATOR . $safe);
        if (str_ends_with($safe, '.sql') && is_file($path)) {
            @unlink($path);
        }

        return back()->with('success', "File backup {$safe} berhasil dihapus.");
    }

    public static function createBackupFile(int $keep = 14): string
    {
        $dir = storage_path(self::BACKUP_DIR);
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir . DIRECTORY_SEPARATOR . 'auto_backup_' . date('Y-m-d_His') . '.sql';
        $stream = fopen($filePath, 'w');
        self::writeSqlDumpToStream($stream);
        fclose($stream);

        // Terapkan retensi file agar disk server tidak penuh
        $existing = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        usort($existing, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($existing, max(1, $keep)) as $oldFile) {
            @unlink($oldFile);
        }

        return $filePath;
    }

    private static function writeSqlDumpToStream($stream): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        fwrite($stream, "-- Sentosa Bangunan Database Backup\n");
        fwrite($stream, "-- Database: `{$dbName}`\n");
        fwrite($stream, '-- Generated at: ' . now('Asia/Pontianak')->toDateTimeString() . "\n\n");
        fwrite($stream, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $tableObj) {
            $tableArray = (array) $tableObj;
            $table = reset($tableArray);

            fwrite($stream, "-- Table structure for `{$table}`\n");
            fwrite($stream, "DROP TABLE IF EXISTS `{$table}`;\n");
            $createRow = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
            fwrite($stream, $createSql . ";\n\n");

            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                fwrite($stream, "-- Dumping data for `{$table}`\n");
                foreach ($rows->chunk(100) as $chunk) {
                    $columns = array_keys((array) $chunk->first());
                    $colList = '`' . implode('`, `', $columns) . '`';
                    $valuesSql = [];

                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ((array) $row as $val) {
                            $vals[] = $val === null ? 'NULL' : $pdo->quote((string) $val);
                        }
                        $valuesSql[] = '(' . implode(', ', $vals) . ')';
                    }

                    fwrite($stream, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valuesSql) . ";\n");
                }
                fwrite($stream, "\n");
            }
        }

        fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
    }
}
