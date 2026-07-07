{{--
    Promo Code Input for Calculators
    Usage: @include('partials.promo-code-input')
    Requires the calculator form to have id="calcForm" and a JS function
    that updates the displayed price.
--}}
<div class="promo-code-input-section mt-4" id="promo-code-section">
    <div class="promo-input-label">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        Имаш промо код?
    </div>
    <div class="promo-input-row">
        <input type="text"
               id="promo-code-input"
               placeholder=""
               class="promo-code-field"
               autocomplete="off"
               style="text-transform: uppercase;"
               maxlength="50">
        <button type="button"
                id="promo-code-apply-btn"
                class="promo-apply-btn"
                onclick="applyPromoCode()">
            Приложи
        </button>
    </div>
    <div id="promo-code-feedback" class="promo-code-feedback" style="display:none;"></div>
    {{-- Hidden input sent with the form --}}
    <input type="hidden" name="promo_code" id="promo-code-hidden">
</div>

<style>
.promo-code-input-section {
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 16px;
}
.promo-input-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(255,255,255,0.5);
    margin-bottom: 8px;
}
.promo-input-row {
    display: flex;
    gap: 8px;
}
.promo-code-field {
    flex: 1;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px;
    color: #fff;
    padding: 8px 12px;
    font-size: 0.9rem;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    letter-spacing: 0.08em;
    transition: border-color 0.2s;
}
.promo-code-field:focus {
    outline: none;
    border-color: #f5a623;
    background: rgba(245,166,35,0.06);
}
.promo-code-field.is-valid   { border-color: #22c55e; }
.promo-code-field.is-invalid { border-color: #ef4444; }

.promo-apply-btn {
    background: #f5a623;
    color: #111;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s, transform 0.15s;
}
.promo-apply-btn:hover    { background: #ffc144; transform: scale(1.03); }
.promo-apply-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.promo-apply-btn.applied  { background: #22c55e; color: #fff; }

.promo-code-feedback {
    margin-top: 6px;
    font-size: 0.78rem;
    padding: 6px 10px;
    border-radius: 6px;
}
.promo-code-feedback.success {
    background: rgba(34,197,94,0.12);
    color: #22c55e;
    border: 1px solid rgba(34,197,94,0.3);
}
.promo-code-feedback.error {
    background: rgba(239,68,68,0.12);
    color: #ef4444;
    border: 1px solid rgba(239,68,68,0.3);
}
</style>

<script>
var _appliedPromoDiscount = { type: null, value: 0, active: false };

function applyPromoCode() {
    var input   = document.getElementById('promo-code-input');
    var btn     = document.getElementById('promo-code-apply-btn');
    var feedback = document.getElementById('promo-code-feedback');
    var hidden  = document.getElementById('promo-code-hidden');
    var code    = input.value.trim().toUpperCase();

    if (!code) {
        showPromoFeedback('Моля, въведете промо код.', 'error');
        return;
    }

    btn.disabled = true;
    btn.textContent = '…';

    fetch('{{ route("promo.validate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ code: code })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.valid) {
            _appliedPromoDiscount = {
                type:   data.discount_type,
                value:  data.discount_value,
                active: true
            };
            hidden.value = code;
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            btn.textContent = '✓ Приложен';
            btn.classList.add('applied');
            input.disabled = true;
            showPromoFeedback(data.message, 'success');

            // Trigger recalculation in the active calculator
            if (typeof calculateWeddingTotal    === 'function') calculateWeddingTotal();
            else if (typeof calculatePromTotal  === 'function') calculatePromTotal();
            else if (typeof calculateBaptismTotal === 'function') calculateBaptismTotal();
            else if (typeof calculateGraduationTotal === 'function') calculateGraduationTotal();
            else if (typeof calculateEventTotal === 'function') calculateEventTotal();
            else if (typeof calculateFamilyTotal === 'function') calculateFamilyTotal();
            else if (typeof calculatePortraitTotal === 'function') calculatePortraitTotal();
            else if (typeof calculateAutomotiveTotal === 'function') calculateAutomotiveTotal();
            else if (typeof calculateArchitecturalTotal === 'function') calculateArchitecturalTotal();
        } else {
            _appliedPromoDiscount = { type: null, value: 0, active: false };
            hidden.value = '';
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            btn.textContent = 'Приложи';
            showPromoFeedback(data.message, 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Приложи';
        showPromoFeedback('Грешка при проверката на кода. Опитайте отново.', 'error');
    });
}

function showPromoFeedback(msg, type) {
    var el = document.getElementById('promo-code-feedback');
    el.textContent = msg;
    el.className = 'promo-code-feedback ' + type;
    el.style.display = 'block';
}

/**
 * Call this from each calculator's total function to apply the discount.
 * @param {number} packagePrice - the pre-discount price of the main package(s)
 * @param {number} extrasPrice - the price of add-ons/extras (not discounted)
 * @returns {number} final total price
 */
function applyPromoDiscount(packagePrice, extrasPrice = 0) {
    if (!_appliedPromoDiscount.active) return packagePrice + extrasPrice;
    
    var discountedPackage = packagePrice;
    if (_appliedPromoDiscount.type === 'percent') {
        discountedPackage = Math.max(0, packagePrice - (packagePrice * _appliedPromoDiscount.value / 100));
    } else {
        discountedPackage = Math.max(0, packagePrice - _appliedPromoDiscount.value);
    }
    
    return discountedPackage + extrasPrice;
}

// Auto-apply from popup if code was copied
(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var code = urlParams.get('promo');
    if (code) {
        var input = document.getElementById('promo-code-input');
        if (input) {
            input.value = code.toUpperCase();
            applyPromoCode();
        }
    }
})();
</script>
