<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Entities\Product;

class ProductList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'selectedCategory' => 'categoryChanged',
        'showCount'        => 'showCountChanged'
    ];

    public $categories;
    public $category_id;
    public $limit = 9;

    public function mount($categories) {
        $this->categories = $categories;
        $this->category_id = '';
    }

    public function render() {
        return view('livewire.pos.product-list', [
            'products' => Product::when($this->category_id, function ($query) {
                return $query->where('category_id', $this->category_id);
            })
            ->paginate($this->limit)
        ]);
    }

    public function categoryChanged($category_id) {
        $this->category_id = $category_id;
        $this->resetPage();
    }

    public function showCountChanged($value) {
        $this->limit = $value;
        $this->resetPage();
    }

    public function selectProduct($product) {
        // Get fresh product data dari database untuk memastikan stok terbaru
        $productModel = Product::find($product['id']);
        
        // Validasi apakah produk masih exists
        if (!$productModel) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'title' => 'Produk Tidak Ditemukan!',
                'message' => 'Produk yang dipilih tidak tersedia.'
            ]);
            return;
        }
        
        // Validasi stok kosong
        if ($productModel->product_quantity <= 0) {
            $this->dispatch('show-alert', [
                'type' => 'warning',
                'title' => 'Stok Kosong!',
                'message' => 'Produk ini sedang tidak tersedia. Stok: ' . $productModel->product_quantity
            ]);
            return;
        }
        
        // Jika stok tersedia, kirim ke parent component dengan data fresh
        $this->dispatch('productSelected', [
            'id' => $productModel->id,
            'product_name' => $productModel->product_name,
            'product_code' => $productModel->product_code,
            'product_price' => $productModel->product_price,
            'product_quantity' => $productModel->product_quantity, // Stok terbaru
            'category_id' => $productModel->category_id,
            // Tambahkan field lain yang dibutuhkan
        ]);
    }

    // Method tambahan untuk refresh data produk setelah transaksi
    public function refreshProducts() {
        $this->resetPage();
        // Atau bisa juga dispatch event untuk refresh parent component
        $this->dispatch('productsRefreshed');
    }
    
    // Method untuk handle ketika ada perubahan stok dari component lain
    public function handleStockUpdate($productId, $newStock) {
        // Bisa digunakan untuk real-time update stok tanpa refresh page
        $this->dispatch('stockUpdated', [
            'product_id' => $productId,
            'new_stock' => $newStock
        ]);
    }
}