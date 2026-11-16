<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Exports\DailyReportsExport;
use Maatwebsite\Excel\Facades\Excel;

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
}