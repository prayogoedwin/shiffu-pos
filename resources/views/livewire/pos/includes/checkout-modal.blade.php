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
                                        <input id="paid_amount_display" type="text" class="form-control" value="0" required autocomplete="off">
                                        <input id="paid_amount" type="hidden" name="paid_amount" value="0">
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
#paid_amount_display::before, 
#paid_amount_display::after {
    content: '' !important;
    display: none !important;
}

#paid_amount_display {
    text-align: right !important;
}
</style>

<script>
// POS Checkout Calculator - FIXED VALUE HANDLING
document.addEventListener('DOMContentLoaded', function() {
    const paidAmountDisplayInput = document.getElementById('paid_amount_display'); // Display input
    const paidAmountHiddenInput = document.getElementById('paid_amount'); // Hidden input untuk backend
    const paidByUserDisplay = document.getElementById('paid_byuser');
    const changeDisplay = document.getElementById('change');
    
    // Function untuk AGGRESSIVELY clean input - hapus semua selain angka
    function cleanToNumber(str) {
        if (!str) return 0;
        // HAPUS SEMUA SELAIN ANGKA dan convert ke number
        const cleaned = str.toString().replace(/[^\d]/g, '');
        return parseInt(cleaned) || 0;
    }
    
    // Function untuk format number dengan separator ribuan
    function formatNumberDisplay(amount) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(Math.round(amount));
    }
    
    // Function untuk parse currency Indonesia
    function parseCurrency(str) {
        if (!str) return 0;
        
        // HARDCORE CLEANING - hapus SEMUA selain angka
        let cleaned = str.replace(/[^\d]/g, '');
        const result = parseInt(cleaned) || 0;
        console.log('Parsed currency:', str, '→', result);
        return result;
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
    
    // MAIN FUNCTION untuk update semua
    function updateAll() {
        console.log('=== UPDATE ALL STARTED ===');
        
        // Ambil nilai dari display input
        const displayValue = paidAmountDisplayInput.value;
        console.log('Display input value:', displayValue);
        
        // Clean ke number
        const actualNumber = cleanToNumber(displayValue);
        console.log('Actual number:', actualNumber);
        
        // Update hidden input untuk backend
        paidAmountHiddenInput.value = actualNumber;
        console.log('Hidden input set to:', paidAmountHiddenInput.value);
        
        // Update display di tabel
        paidByUserDisplay.textContent = formatNumberDisplay(actualNumber);
        
        // Calculate change
        const grandTotal = getGrandTotal();
        const change = actualNumber - grandTotal;
        console.log('Change calculation:', actualNumber, '-', grandTotal, '=', change);
        
        // Update change display
        if (change >= 0) {
            changeDisplay.textContent = formatNumberDisplay(change);
            changeDisplay.className = 'text-success';
        } else {
            changeDisplay.textContent = formatNumberDisplay(Math.abs(change));
            changeDisplay.className = 'text-danger';
        }
        
        console.log('=== UPDATE ALL FINISHED ===');
    }
    
    // Function untuk format display input
    function formatDisplayInput() {
        const currentValue = paidAmountDisplayInput.value;
        const numberValue = cleanToNumber(currentValue);
        
        if (numberValue === 0) {
            paidAmountDisplayInput.value = '0';
        } else {
            paidAmountDisplayInput.value = formatNumberDisplay(numberValue);
        }
        
        updateAll();
    }
    
    // EVENT LISTENERS untuk display input
    const events = ['input', 'change', 'paste', 'keyup', 'blur'];
    
    events.forEach(eventName => {
        paidAmountDisplayInput.addEventListener(eventName, function(e) {
            console.log(`Event ${eventName} triggered with value:`, e.target.value);
            setTimeout(() => {
                formatDisplayInput();
            }, 10);
        });
    });
    
    // Initial setup
    setTimeout(() => {
        paidAmountDisplayInput.value = '0';
        paidAmountHiddenInput.value = '0';
        updateAll();
    }, 100);
    
    // Auto focus saat modal dibuka
    $('#checkoutModal').on('shown.bs.modal', function () {
        setTimeout(() => {
            paidAmountDisplayInput.value = '0';
            paidAmountHiddenInput.value = '0';
            updateAll();
            paidAmountDisplayInput.focus();
            paidAmountDisplayInput.select();
        }, 200);
    });
    
    // Continuous monitoring untuk clean up Rp
    setInterval(() => {
        if (paidAmountDisplayInput.value.includes('Rp') || paidAmountDisplayInput.value.includes('rp')) {
            console.log('DETECTED Rp! CLEANING...');
            formatDisplayInput();
        }
    }, 500);
    
    // Validasi submit
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        // Update hidden input sebelum submit
        const actualValue = cleanToNumber(paidAmountDisplayInput.value);
        paidAmountHiddenInput.value = actualValue;
        
        console.log('=== SUBMIT DEBUG ===');
        console.log('Display value:', paidAmountDisplayInput.value);
        console.log('Cleaned number:', actualValue);
        console.log('Hidden input value:', paidAmountHiddenInput.value);
        console.log('Form will send to backend:', actualValue);
        console.log('===================');
        
        // Debug semua form data
        const formData = new FormData(document.getElementById('checkout-form'));
        console.log('ALL FORM DATA:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ':', value);
        }
        
        const grandTotal = getGrandTotal();
        
        if (actualValue < grandTotal) {
            e.preventDefault();
            alert('Jumlah pembayaran tidak boleh kurang dari grand total!');
            paidAmountDisplayInput.focus();
            return false;
        }
        
        // Tambahkan konfirmasi untuk debugging
        if (!confirm(`Konfirmasi:\n- Yang Dibayar: ${actualValue}\n- Total: ${grandTotal}\n\nLanjutkan?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>