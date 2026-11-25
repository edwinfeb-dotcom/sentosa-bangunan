<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function index(Request $request)
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

        // Group by Product
        $byProduct = $saleItems->groupBy('product_id')->map(function ($rows) {
            $product = $rows->first()->product;
            $qty = $rows->sum('qty');
            $omzet = (float) $rows->sum('subtotal');
            $hpp = $product ? ((float) $product->purchase_price * $qty) : 0;
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

        $summary = [
            'total_qty' => $byProduct->sum('qty'),
            'total_omzet' => $byProduct->sum('omzet'),
            'total_hpp' => $byProduct->sum('hpp'),
            'total_profit' => $byProduct->sum('profit'),
        ];

        $categories = Category::orderBy('name')->get();

        return view('reports.sales.index', compact(
            'byProduct', 'byCategory', 'summary', 'categories',
            'startDate', 'endDate', 'categoryId'
        ));
    }
}
