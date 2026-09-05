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
            'X-CSRF-TOKEN': ((btn.closest('form') || document).querySelector('input[name="_token"]') || {}).value || '',
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
