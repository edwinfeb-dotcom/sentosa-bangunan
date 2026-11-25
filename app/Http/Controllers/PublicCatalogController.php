<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\PublicOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->withStock()->where('is_active', true);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', (float) $request->max_price);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price', 'asc'),
            'price_desc' => $query->orderBy('selling_price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $dbCart = [];
        if (auth()->check()) {
            $dbCart = CartItem::with(['product' => fn ($q) => $q->withStock()])
                ->where('user_id', auth()->id())
                ->get()
                ->filter(fn ($item) => $item->product && $item->product->is_active)
                ->map(fn ($item) => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float) $item->product->selling_price,
                    'unit' => $item->product->unit,
                    'stock' => (int) $item->product->current_stock,
                    'image' => $item->product->image,
                    'qty' => (int) $item->qty,
                ])
                ->values()
                ->toArray();
        }

        return view('catalog.index', compact('products', 'categories', 'sort', 'dbCart'));
    }

    public function syncCart(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['status' => 'guest']);
        }

        $items = $request->input('cart', []);
        $userId = auth()->id();

        DB::transaction(function () use ($items, $userId) {
            CartItem::where('user_id', $userId)->delete();
            foreach ($items as $item) {
                $productId = (int) ($item['id'] ?? 0);
                $qty = max(1, (int) ($item['qty'] ?? 1));
                if ($productId > 0 && Product::where('id', $productId)->where('is_active', true)->exists()) {
                    CartItem::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'qty' => $qty,
                    ]);
                }
            }
        });

        return response()->json(['status' => 'synced']);
    }

    public function checkoutForm()
    {
        $user = auth()->user();
        $customer = $user?->customer;
        $dbCart = [];

        if ($user) {
            $dbCart = CartItem::with(['product' => fn ($q) => $q->withStock()])
                ->where('user_id', $user->id)
                ->get()
                ->filter(fn ($item) => $item->product && $item->product->is_active)
                ->map(fn ($item) => [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float) $item->product->selling_price,
                    'unit' => $item->product->unit,
                    'stock' => (int) $item->product->current_stock,
                    'qty' => (int) $item->qty,
                ])
                ->values()
                ->toArray();
        }

        return view('catalog.checkout', compact('user', 'customer', 'dbCart'));
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'shipping_method' => 'required|in:antar,ambil',
            'address' => 'required_if:shipping_method,antar|nullable|string|max:1000',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string|max:500',
            'cart_data' => 'required|json',
        ], [
            'customer_name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'address.required_if' => 'Alamat pengiriman wajib diisi jika memilih layanan antar.',
        ]);

        $cart = json_decode($request->cart_data, true);
        if (empty($cart) || !is_array($cart)) {
            return back()->with('error', 'Keranjang belanja kosong.')->withInput();
        }

        $validatedItems = [];
        $subtotalAmount = 0;

        foreach ($cart as $item) {
            $product = Product::withStock()->where('is_active', true)->find($item['id'] ?? 0);
            $qty = max(1, (int) ($item['qty'] ?? 0));
            if (!$product) {
                return back()->with('error', 'Salah satu produk di keranjang sudah tidak tersedia.')->withInput();
            }
            if ($product->current_stock < $qty) {
                return back()->with('error', "Stok {$product->name} tidak mencukupi (tersisa {$product->current_stock} {$product->unit}).")->withInput();
            }
            $lineSubtotal = (float) $product->selling_price * $qty;
            $subtotalAmount += $lineSubtotal;
            $validatedItems[] = [
                'product' => $product,
                'qty' => $qty,
                'price_snapshot' => (float) $product->selling_price,
                'subtotal' => $lineSubtotal,
            ];
        }

        // Ongkir validation: gratis ambil di toko atau belanja >= Rp 1.000.000, selain itu Rp 25.000
        $shippingCost = 0;
        if ($request->shipping_method === 'antar' && $subtotalAmount < 1000000) {
            $shippingCost = 25000;
        }
        $totalAmount = $subtotalAmount + $shippingCost;

        $order = DB::transaction(function () use ($request, $validatedItems, $shippingCost, $totalAmount) {
            $order = PublicOrder::create([
                'user_id' => auth()->id(),
                'order_code' => 'PUB-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'customer_name' => $request->customer_name,
                'phone' => $request->phone,
                'shipping_method' => $request->shipping_method,
                'address' => $request->shipping_method === 'antar' ? $request->address : 'Ambil di Toko Sentosa Bangunan',
                'shipping_cost' => $shippingCost,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($validatedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'qty' => $item['qty'],
                    'price_snapshot' => $item['price_snapshot'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            if (auth()->check()) {
                CartItem::where('user_id', auth()->id())->delete();
            }

            return $order;
        });

        return redirect()->route('catalog.success', $order->order_code);
    }

    public function success($order_code)
    {
        $order = PublicOrder::with('items.product')->where('order_code', $order_code)->firstOrFail();
        return view('catalog.success', compact('order'));
    }

    public function track(Request $request)
    {
        $order = null;
        $notFound = false;
        $orderCode = trim((string) $request->get('order_code', ''));

        if ($orderCode !== '') {
            $order = PublicOrder::with('items.product')
                ->where('order_code', $orderCode)
                ->first();

            if (!$order) {
                $notFound = true;
            }
        }

        return view('catalog.track', compact('order', 'orderCode', 'notFound'));
    }

    public function qrPoster()
    {
        $catalogUrl = route('catalog.index');
        $productCount = Product::where('is_active', true)->count();

        return view('catalog.qr-poster', compact('catalogUrl', 'productCount'));
    }
}
