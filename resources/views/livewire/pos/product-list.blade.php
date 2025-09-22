<div>
    {{-- Style untuk out of stock inline --}}
    <style>
        .out-of-stock {
            position: relative;
        }
        .out-of-stock::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(128, 128, 128, 0.3);
            pointer-events: none;
        }
    </style>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <livewire:pos.filter :categories="$categories"/>
            <div class="row position-relative">
                <div wire:loading.flex class="col-12 position-absolute justify-content-center align-items-center" style="top:0;right:0;left:0;bottom:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                @forelse($products as $product)
                    <div 
                        @if($product->product_quantity > 0)
                            wire:click.prevent="selectProduct({{ $product }})" 
                            style="cursor: pointer;"
                        @else
                            onclick="showStockAlert()"
                            style="cursor: not-allowed; opacity: 0.6;"
                        @endif
                        class="col-lg-4 col-md-6 col-xl-3"
                    >
                        <div class="card border-0 shadow h-100 {{ $product->product_quantity <= 0 ? 'out-of-stock' : '' }}">
                            <div class="position-relative">
                                <img height="200" src="{{ $product->getFirstMediaUrl('images') }}" class="card-img-top" alt="Product Image">
                                
                                {{-- Badge Stock dengan kondisi warna --}}
                                @if($product->product_quantity > 0)
                                    <div class="badge badge-info mb-3 position-absolute" style="left:10px;top: 10px;">
                                        Stock: {{ $product->product_quantity }}
                                    </div>
                                @else
                                    <div class="badge badge-danger mb-3 position-absolute" style="left:10px;top: 10px;">
                                        Stok Kosong
                                    </div>
                                    {{-- Small overlay indicator --}}
                                    <div class="position-absolute" style="top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;">
                                        <div class="badge badge-danger px-3 py-2" style="font-size: 11px; font-weight: bold;">
                                            STOK HABIS
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <h6 style="font-size: 13px;" class="card-title mb-0">{{ $product->product_name }}</h6>
                                    <span class="badge badge-success">
                                        {{ $product->product_code }}
                                    </span>
                                </div>
                                <p class="card-text font-weight-bold">{{ format_currency($product->product_price) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            Products Tidak Diitemukan...
                        </div>
                    </div>
                @endforelse
            </div>
            <div @class(['mt-3' => $products->hasPages()])>
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- JavaScript untuk alert --}}
    <script>
        function showStockAlert() {
            // Jika pakai SweetAlert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stok Kosong!',
                    text: 'Produk ini sedang tidak tersedia.',
                    confirmButtonText: 'OK'
                });
            } else {
                // Fallback ke alert biasa
                alert('Stok Kosong! Produk ini sedang tidak tersedia.');
            }
        }
    </script>
    
</div>