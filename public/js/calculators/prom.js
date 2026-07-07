function calculatePromTotal() {
    let total = 0;
    let packagePrice = 0;
    let extrasPrice = 0;
    let pkgName = "—";
    let extrasNames = [];
    let detailsArr = [];

    // 1. Package
    const selectedPackage = document.querySelector('.package-option:checked');
    if (selectedPackage) {
        packagePrice = parseInt(selectedPackage.value) || 0;
        total += packagePrice;
        pkgName = selectedPackage.getAttribute('data-label') || "Пакет";
        detailsArr.push(pkgName);
        
        // LUXURY logic: if package name or label has 'lux', we might want to hide the USB extra
        // Since we don't have IDs like 'lux' anymore, we check the label's class
        const labelElem = document.querySelector(`label[for="${selectedPackage.id}"]`);
        const isLux = labelElem && (labelElem.classList.contains('lux') || labelElem.classList.contains('lux-pack'));
        
        // Update Luxury indicator if needed
        const extras = document.querySelectorAll('.extra-option');
        extras.forEach(extra => {
            const extraLabel = extra.getAttribute('data-label') || "";
            if (extraLabel.toLowerCase().includes('флашка') || extraLabel.toLowerCase().includes('usb')) {
                const parent = extra.closest('.col-md-12, .col-md-6, .col-lg-12');
                if (parent) {
                    if (isLux) {
                        parent.style.display = 'none';
                        if (extra.checked) {
                            extra.checked = false;
                        }
                    } else {
                        parent.style.display = 'block';
                    }
                }
            }
        });
    }

    // 2. Extras
    const selectedExtras = document.querySelectorAll('.extra-option:checked');
    selectedExtras.forEach(extra => {
        const price = parseInt(extra.value) || 0;
        const label = extra.getAttribute('data-label');
        
        // We only add to the display list if it's not a "Standard" or "Without" option (price 0)
        if (price > 0) {
            extrasNames.push(label);
            detailsArr.push(label);
        } else if (extra.type === 'checkbox') {
             // For checkboxes, even if price is 0 (free extra), we show them if checked
             extrasNames.push(label);
             detailsArr.push(label);
        }
        extrasPrice += price;
        total += price;
    });

    // UI Updates
    const sumPkgElem = document.getElementById('sumPackage');
    if (sumPkgElem) sumPkgElem.innerText = pkgName;

    const extrasElement = document.getElementById('sumExtras');
    if (extrasElement) {
        const extrasParent = extrasElement.parentElement;
        if (extrasNames.length > 0) {
            extrasElement.innerText = extrasNames.join(", ");
            if (extrasParent) {
                extrasParent.style.flexDirection = "column";
                extrasParent.style.alignItems = "flex-end";
                extrasParent.style.textAlign = "right";
            }
            extrasElement.style.color = "#fff";
        } else {
            extrasElement.innerText = "—";
            if (extrasParent) {
                extrasParent.style.flexDirection = "row";
                extrasParent.style.alignItems = "center";
            }
            extrasElement.style.color = "#aaa";
        }
    }

    // Apply promo discount
    var finalPrice = (typeof applyPromoDiscount === 'function') ? applyPromoDiscount(packagePrice, extrasPrice) : total;

    const finalPriceElem = document.getElementById('finalPrice');
    if (finalPriceElem) {
        let currentPrice = parseInt(finalPriceElem.innerText) || 0;
        animateValue("finalPrice", currentPrice, finalPrice, 300);
    }

    // Show discount line
    var discountEl = document.getElementById('promo-discount-line');
    if (discountEl) {
        if (finalPrice < total) {
            discountEl.style.display = 'flex';
            discountEl.querySelector('.discount-amount').textContent = '-€' + Math.round(total - finalPrice);
        } else { discountEl.style.display = 'none'; }
    }

    const hiddenPrice = document.getElementById('hiddenPrice');
    if (hiddenPrice) hiddenPrice.value = Math.round(finalPrice);

    const hiddenDetails = document.getElementById('hiddenDetails');
    if (hiddenDetails) hiddenDetails.value = detailsArr.join(", ");
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
    const objBgn = document.getElementById(id + "Bgn");

    priceInterval = setInterval(function () {
        count++;
        const progress = count / steps;
        const easedProgress = 1 - Math.pow(1 - progress, 3);
        const current = start + (range * easedProgress);
        
        if (count >= steps) {
            obj.innerHTML = Math.round(end);
            if (objBgn) objBgn.innerHTML = (end * 1.95583).toFixed(2);
            clearInterval(priceInterval);
        } else {
            obj.innerHTML = Math.round(current);
            if (objBgn) objBgn.innerHTML = (current * 1.95583).toFixed(2);
        }
    }, minTimer);
}

document.addEventListener('DOMContentLoaded', () => {
    calculatePromTotal();
    
    // Safety check: if inputs are already checked on load, trigger calculation
    const inputs = document.querySelectorAll('.package-option, .extra-option');
    inputs.forEach(input => {
        input.addEventListener('change', calculatePromTotal);
    });
});
