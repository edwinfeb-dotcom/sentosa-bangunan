<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\PublicOrder; // <-- TAMBAHAN UNTUK MODEL PESANAN
use Illuminate\Http\Request;
use Illuminate\Support\Str; // <-- TAMBAHAN UNTUK GENERATE KODE

class PublicCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true);

        // Fitur Pencarian
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Fitur Filter Kategori
        if ($request->has('category') && $request->category != '') {
            $query->where('category_id', $request->category);
        }

        $products = $query->latest()->paginate(12);
        $categories = Category::where('is_active', true)->get();

        return view('catalog.index', compact('products', 'categories'));
    }

    public function checkoutForm()
    {
        return view('catalog.checkout');
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'shipping_method' => 'required|in:antar,ambil',
            'address' => 'required_if:shipping_method,antar|nullable|string',
            'payment_method' => 'required|in:cash,transfer',
            'cart_data' => 'required|json',
        ]);

        $cart = json_decode($request->cart_data, true);
        
        if (empty($cart)) {
            return back()->with('error', 'Keranjang belanja kosong.');
        }

        $totalAmount = 0;
        foreach ($cart as $item) {
            $totalAmount += $item['price'] * $item['qty'];
        }

        // Simpan Pesanan
        $order = PublicOrder::create([
            'order_code' => 'PUB-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'customer_name' => $request->customer_name,
            'phone' => $request->phone,
            'shipping_method' => $request->shipping_method,
            'address' => $request->address,
            'payment_method' => $request->payment_method,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Simpan Item Pesanan
        foreach ($cart as $item) {
            $order->items()->create([
                'product_id' => $item['id'],
                'qty' => $item['qty'],
                'price_snapshot' => $item['price'],
                'subtotal' => $item['price'] * $item['qty'],
            ]);
        }

        return redirect()->route('catalog.success', $order->order_code);
    }

    public function success($order_code)
    {
        $order = PublicOrder::with('items.product')->where('order_code', $order_code)->firstOrFail();
        return view('catalog.success', compact('order'));
    }
}