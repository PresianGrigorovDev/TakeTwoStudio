function calculateGenericTotal() {
    let total = 0;
    let packagePrice = 0;
    let extrasPrice = 0;

    let serviceText = "—";
    let scopeText = "—";
    let extrasText = [];

    // SERVICE (Package)
    const svc = document.querySelector('.package-option:checked');
    if (svc) {
        packagePrice = parseInt(svc.value) || 0;
        total += packagePrice;
        serviceText = svc.getAttribute('data-label');
    }

    // ALL OTHER EXTRAS (Radios and Checkboxes)
    const selectedExtras = document.querySelectorAll('.extra-option:checked');
    selectedExtras.forEach(extra => {
        const price = parseInt(extra.value) || 0;
        const label = extra.getAttribute('data-label');
        extrasPrice += price;
        total += price;

        if (extra.name.includes('обхват') || 
            extra.name.includes('lokacija') || 
            (extra.closest('.mb-5') && extra.closest('.mb-5').previousElementSibling && 
             (extra.closest('.mb-5').previousElementSibling.innerText.includes('Локация') || 
              extra.closest('.mb-5').previousElementSibling.innerText.includes('обхват')))) {
             scopeText = label;
        } else {
             if (price > 0 || extra.type === 'checkbox') {
                 extrasText.push(label);
             }
        }
    });

    // Apply promo discount
    var finalPrice = (typeof applyPromoDiscount === 'function') ? applyPromoDiscount(packagePrice, extrasPrice) : total;

    // Update UI elements
    const finalPriceElem = document.getElementById('finalPrice');
    if (finalPriceElem) {
        let startVal = parseInt(finalPriceElem.innerText) || 0;
        animateValue("finalPrice", startVal, finalPrice, 300);
    }

    // Show discount line
    var discountEl = document.getElementById('promo-discount-line');
    if (discountEl) {
        if (finalPrice < total) {
            discountEl.style.display = 'flex';
            discountEl.querySelector('.discount-amount').textContent = '-€' + Math.round(total - finalPrice);
        } else { discountEl.style.display = 'none'; }
    }

    const sumSvcElem = document.getElementById('sumService');
    if (sumSvcElem) sumSvcElem.innerText = serviceText;

    const sumScopeElem = document.getElementById('sumScope');
    if (sumScopeElem) sumScopeElem.innerText = scopeText;

    const sumExtrasElem = document.getElementById('sumExtras');
    if (sumExtrasElem) {
        sumExtrasElem.innerText = extrasText.length > 0 ? extrasText.join(", ") : "—";
    }

    // Hidden fields for form submission
    const hiddenPrice = document.getElementById('hiddenPrice');
    if (hiddenPrice) hiddenPrice.value = Math.round(finalPrice);

    const hiddenDetails = document.getElementById('hiddenDetails');
    if (hiddenDetails) {
        let fullDetails = `УСЛУГА: ${serviceText}\nОБХВАТ: ${scopeText}`;
        if (extrasText.length > 0) {
            fullDetails += `\nЕКСТРИ: ${extrasText.join(", ")}`;
        } else {
            fullDetails += `\nЕКСТРИ: Няма`;
        }
        hiddenDetails.value = fullDetails;
    }
}

let priceInterval = null;

function animateValue(id, start, end, duration) {
    if (start === end) return;
    if (priceInterval) clearInterval(priceInterval);

    const range = end - start;
    const minTimer = 10;
    const steps = duration / minTimer;

    let count = 0;
    const obj = document.getElementById(id);

    priceInterval = setInterval(function () {
        count++;
        const progress = count / steps;
        const easedProgress = 1 - Math.pow(1 - progress, 3);
        const current = start + (range * easedProgress);

        if (count >= steps) {
            if (obj) obj.innerText = Math.round(end);
            clearInterval(priceInterval);
        } else {
            if (obj) obj.innerText = Math.round(current);
        }
    }, minTimer);
}

document.addEventListener('DOMContentLoaded', () => {
    calculateGenericTotal();

    const inputs = document.querySelectorAll('.package-option, .extra-option');
    inputs.forEach(input => {
        input.addEventListener('change', calculateGenericTotal);
    });
});
