<?php

namespace App\Http\Controllers\Setting;

use App\Exports\DailyReportsExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        return view('setting.backup.index');
    }

    public function exportReports()
    {
        return Excel::download(new DailyReportsExport, 'Laporan_Harian_' . date('Y-m-d') . '.xlsx');
    }

    public function downloadSql(): StreamedResponse
    {
        $filename = 'backup_sentosa_bangunan_' . date('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () {
            $pdo = DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();

            echo "-- Sentosa Bangunan Database Backup\n";
            echo "-- Database: `{$dbName}`\n";
            echo "-- Generated at: " . now('Asia/Pontianak')->toDateTimeString() . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $tableObj) {
                $tableArray = (array) $tableObj;
                $table = reset($tableArray);

                echo "-- Table structure for `{$table}`\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                $createRow = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
                echo $createSql . ";\n\n";

                $rows = DB::table($table)->get();
                if ($rows->isNotEmpty()) {
                    echo "-- Dumping data for `{$table}`\n";
                    foreach ($rows->chunk(100) as $chunk) {
                        $columns = array_keys((array) $chunk->first());
                        $colList = '`' . implode('`, `', $columns) . '`';
                        $valuesSql = [];

                        foreach ($chunk as $row) {
                            $vals = [];
                            foreach ((array) $row as $val) {
                                if ($val === null) {
                                    $vals[] = 'NULL';
                                } else {
                                    $vals[] = $pdo->quote((string) $val);
                                }
                            }
                            $valuesSql[] = '(' . implode(', ', $vals) . ')';
                        }

                        echo "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valuesSql) . ";\n";
                    }
                    echo "\n";
                }
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }
}
