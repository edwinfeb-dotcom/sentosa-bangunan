<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PublicOrder;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->buildReportData($request);
        $categories = Category::orderBy('name')->get();

        return view('reports.sales.index', array_merge($data, compact('categories')));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildReportData($request);
        $filename = 'Laporan_Penjualan_' . $data['startDate'] . '_sd_' . $data['endDate'] . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Produk', 'Kategori', 'Satuan', 'Qty Terjual', 'Omzet (Rp)', 'HPP Modal (Rp)', 'Laba Kotor (Rp)', 'Margin (%)']);
            foreach ($data['byProduct'] as $row) {
                fputcsv($out, [
                    $row['product_name'],
                    $row['category_name'],
                    $row['unit'],
                    $row['qty'],
                    $row['omzet'],
                    $row['hpp'],
                    $row['profit'],
                    $row['margin_pct'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildReportData(Request $request): array
    {
        $now = Carbon::now('Asia/Pontianak');
        $startDate = $request->get('start_date', $now->copy()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', $now->toDateString());
        $categoryId = $request->get('category_id');

        $itemsQuery = SaleItem::with(['product.category', 'sale'])
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                  ->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
            });

        if ($categoryId) {
            $itemsQuery->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
        }

        $saleItems = $itemsQuery->get();

        // Group by Product (menggunakan hpp_total yang akurat untuk eceran maupun grosir)
        $byProduct = $saleItems->groupBy('product_id')->map(function ($rows) {
            $product = $rows->first()->product;
            $qty = $rows->sum('qty');
            $omzet = (float) $rows->sum('subtotal');
            $hpp = (float) $rows->sum(fn ($r) => $r->hpp_total);
            $profit = $omzet - $hpp;

            return [
                'product_name' => $product->name ?? 'Produk Terhapus',
                'unit' => $product->unit ?? '-',
                'category_name' => $product->category->name ?? 'Umum',
                'qty' => $qty,
                'omzet' => $omzet,
                'hpp' => $hpp,
                'profit' => $profit,
                'margin_pct' => $omzet > 0 ? round(($profit / $omzet) * 100, 1) : 0,
            ];
        })->sortByDesc('omzet')->values();

        // Group by Category
        $byCategory = $byProduct->groupBy('category_name')->map(function ($rows, $catName) {
            $omzet = $rows->sum('omzet');
            $profit = $rows->sum('profit');
            return [
                'category_name' => $catName,
                'qty' => $rows->sum('qty'),
                'omzet' => $omzet,
                'hpp' => $rows->sum('hpp'),
                'profit' => $profit,
            ];
        })->sortByDesc('omzet')->values();

        $byPayment = Payment::query()
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                  ->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
            })
            ->selectRaw('method, COUNT(*) as trx_count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('method')
            ->get();

        $onlineSummary = PublicOrder::whereNotIn('status', ['cancelled'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_omzet')
            ->first();

        $summary = [
            'total_qty' => $byProduct->sum('qty'),
            'total_omzet' => $byProduct->sum('omzet'),
            'total_hpp' => $byProduct->sum('hpp'),
            'total_profit' => $byProduct->sum('profit'),
            'online_count' => (int) ($onlineSummary->order_count ?? 0),
            'online_omzet' => (float) ($onlineSummary->total_omzet ?? 0),
        ];

        return compact(
            'byProduct', 'byCategory', 'byPayment', 'summary',
            'startDate', 'endDate', 'categoryId'
        );
    }
}
