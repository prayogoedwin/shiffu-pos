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
                                        <input id="paid_amount" type="text" class="form-control" name="paid_amount" value="0" required>
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

<script>
// POS Checkout Calculator - Number Format Only
document.addEventListener('DOMContentLoaded', function() {
    const paidAmountInput = document.getElementById('paid_amount');
    const paidByUserDisplay = document.getElementById('paid_byuser');
    const changeDisplay = document.getElementById('change');
    
    // Function untuk parse currency Indonesia
    function parseCurrency(str) {
        if (!str) return 0;
        
        // Hapus semua karakter kecuali digit, titik, dan koma
        let cleaned = str.replace(/[^\d.,]/g, '');
        console.log('Cleaning:', str, '→', cleaned);
        
        // Jika ada koma, split berdasarkan koma (desimal)
        if (cleaned.includes(',')) {
            const parts = cleaned.split(',');
            const integer = parts[0].replace(/\./g, ''); // Hapus titik ribuan
            const decimal = parts[1] || '0';
            cleaned = integer + '.' + decimal;
        } else {
            // Tidak ada koma, hapus semua titik (ribuan)
            cleaned = cleaned.replace(/\./g, '');
        }
        
        const result = parseFloat(cleaned) || 0;
        console.log('Final parsed:', result);
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
    
    // Event listeners
    paidAmountInput.addEventListener('input', function(e) {
        console.log('Input event triggered');
        
        // Hapus semua karakter selain angka
        let value = e.target.value.replace(/[^\d]/g, '');
        
        // Jika kosong, set ke '0'
        if (!value) {
            e.target.value = '0';
        } else {
            // Format hanya angka dengan pemisah ribuan (tanpa Rp)
            const formatted = parseInt(value).toLocaleString('id-ID');
            e.target.value = formatted;
        }
        
        // Update calculation
        updateCalculation();
    });
    
    paidAmountInput.addEventListener('keyup', updateCalculation);
    paidAmountInput.addEventListener('focus', updateCalculation);
    
    // Initial calculation dan cleanup
    paidAmountInput.value = '0'; // Pastikan initial value hanya '0'
    updateCalculation();
    
    // Auto focus saat modal dibuka
    $('#checkoutModal').on('shown.bs.modal', function () {
        // Reset input ke nilai 0 saja (tanpa Rp)
        paidAmountInput.value = '0';
        updateCalculation();
        paidAmountInput.focus();
        paidAmountInput.select();
    });
    
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