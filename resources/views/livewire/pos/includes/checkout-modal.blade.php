<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">
                    <i class="bi bi-cart-check text-primary"></i> Confirm Penjualan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="checkout-form" action="{{ route('app.pos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if (session()->has('checkout_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-body">
                                <span>{{ session('checkout_message') }}</span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-7">
                            <input type="hidden" value="{{ $customer_id }}" name="customer_id">
                            <input type="hidden" value="{{ $global_tax }}" name="tax_percentage">
                            <input type="hidden" value="{{ $global_discount }}" name="discount_percentage">
                            <input type="hidden" value="{{ $shipping }}" name="shipping_amount">
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="total_amount">Total Pembelian <span class="text-danger">*</span></label>
                                        <input id="total_amount" type="text" class="form-control" name="total_amount" value="{{ $total_amount }}" readonly required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="paid_amount">Yang Dibayarkan <span class="text-danger">*</span></label>
                                        <input id="paid_amount" type="text" class="form-control" name="paid_amount" value="0" required autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Metode Pembayaran<span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Bank Transfer">Transfer Bank</option>
                                    <option value="Qris">QRIS</option>
                                    <option value="Gojek">Gojek</option>
                                    <option value="Shopee">Shopee</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="note">Catatan</label>
                                <textarea name="note" id="note" rows="5" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Total Products</th>
                                        <td>
                                                <span class="badge badge-success">
                                                    {{ Cart::instance($cart_instance)->count() }}
                                                </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Pajak Pesanan ({{ $global_tax }}%)</th>
                                        <td>(+) {{ format_currency(Cart::instance($cart_instance)->tax()) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Diskon ({{ $global_discount }}%)</th>
                                        <td>(-) {{ format_currency(Cart::instance($cart_instance)->discount()) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Biaya Pengiriman</th>
                                        <input type="hidden" value="{{ $shipping }}" name="shipping_amount">
                                        <td>(+) {{ format_currency($shipping) }}</td>
                                    </tr>
                                    <tr class="text-primary">
                                        <th>Grand Total</th>
                                        @php
                                            $total_with_shipping = Cart::instance($cart_instance)->total() + (float) $shipping
                                        @endphp
                                        <th id="grand-total">
                                            {{ format_currency($total_with_shipping) }}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Yang Dibayarkan</th>
                                        <td id="paid_byuser">0</td>
                                    </tr>
                                    <tr>
                                        <th>Kembalian</th>
                                        <td id="change">0</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Force remove any Rp prefix/suffix dari CSS */
#paid_amount::before, 
#paid_amount::after {
    content: '' !important;
    display: none !important;
}

#paid_amount {
    text-align: right !important;
}
</style>

<script>
// POS Checkout Calculator - AGGRESSIVE NUMBER FORMAT ONLY
document.addEventListener('DOMContentLoaded', function() {
    const paidAmountInput = document.getElementById('paid_amount');
    const paidByUserDisplay = document.getElementById('paid_byuser');
    const changeDisplay = document.getElementById('change');
    
    // Function untuk AGGRESSIVELY clean input - hapus semua selain angka
    function cleanInput(str) {
        if (!str) return '';
        // HAPUS SEMUA SELAIN ANGKA
        return str.toString().replace(/[^\d]/g, '');
    }
    
    // Function untuk parse currency Indonesia
    function parseCurrency(str) {
        if (!str) return 0;
        
        // HARDCORE CLEANING - hapus SEMUA selain angka
        let cleaned = str.replace(/[^\d]/g, '');
        const result = parseInt(cleaned) || 0;
        console.log('Parsed:', str, '→', result);
        return result;
    }
    
    // Function untuk format number saja (tanpa Rp dan tanpa desimal)
    function formatNumber(amount) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(Math.round(amount));
    }
    
    // Ambil grand total
    function getGrandTotal() {
        const grandTotalElement = document.getElementById('grand-total');
        if (!grandTotalElement) {
            console.error('Element grand-total tidak ditemukan!');
            return 0;
        }
        
        const grandTotalStr = grandTotalElement.textContent.trim();
        console.log('Grand total element text:', grandTotalStr);
        
        const total = parseCurrency(grandTotalStr);
        console.log('Parsed grand total:', total);
        return total;
    }
    
    // AGGRESSIVE CLEANUP FUNCTION
    function forceCleanInput() {
        let value = paidAmountInput.value;
        let cleanedValue = cleanInput(value);
        
        if (!cleanedValue) {
            paidAmountInput.value = '0';
        } else {
            // Format dengan separator ribuan
            paidAmountInput.value = parseInt(cleanedValue).toLocaleString('id-ID');
        }
        
        // Force trigger calculation
        updateCalculation();
    }
    
    // Function untuk update calculation
    function updateCalculation() {
        console.log('=== Update Calculation Started ===');
        
        const paidAmountStr = paidAmountInput.value;
        console.log('Input paid amount:', paidAmountStr);
        
        const paidAmount = parseCurrency(paidAmountStr);
        const grandTotal = getGrandTotal();
        
        console.log('Paid amount:', paidAmount);
        console.log('Grand total:', grandTotal);
        
        // Update display - hanya angka tanpa Rp
        paidByUserDisplay.textContent = formatNumber(paidAmount);
        
        // Calculate change
        const change = paidAmount - grandTotal;
        console.log('Change calculation:', paidAmount, '-', grandTotal, '=', change);
        
        // Update change display - hanya angka tanpa Rp
        if (change >= 0) {
            changeDisplay.textContent = formatNumber(change);
            changeDisplay.className = 'text-success';
        } else {
            changeDisplay.textContent = formatNumber(Math.abs(change));
            changeDisplay.className = 'text-danger';
        }
        
        console.log('=== Update Calculation Finished ===');
    }
    
    // SUPER AGGRESSIVE EVENT LISTENERS
    const events = ['input', 'change', 'paste', 'keyup', 'keydown', 'blur', 'focus'];
    
    events.forEach(eventName => {
        paidAmountInput.addEventListener(eventName, function(e) {
            // Delay sedikit untuk memastikan value sudah berubah
            setTimeout(() => {
                forceCleanInput();
            }, 10);
        });
    });
    
    // Initial setup - PAKSA set ke 0
    setTimeout(() => {
        paidAmountInput.value = '0';
        updateCalculation();
    }, 100);
    
    // Auto focus saat modal dibuka
    $('#checkoutModal').on('shown.bs.modal', function () {
        // PAKSA RESET
        setTimeout(() => {
            paidAmountInput.value = '0';
            updateCalculation();
            paidAmountInput.focus();
            paidAmountInput.select();
        }, 200);
    });
    
    // Continuous monitoring - check every 500ms untuk memastikan no Rp
    setInterval(() => {
        if (paidAmountInput.value.includes('Rp') || paidAmountInput.value.includes('rp')) {
            console.log('DETECTED Rp! CLEANING...');
            forceCleanInput();
        }
    }, 500);
    
    // Validasi submit
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const paidAmount = parseCurrency(paidAmountInput.value);
        const grandTotal = getGrandTotal();
        
        if (paidAmount < grandTotal) {
            e.preventDefault();
            alert('Jumlah pembayaran tidak boleh kurang dari grand total!');
            paidAmountInput.focus();
            return false;
        }
    });
});
</script>