function calculateGraduationTotal() {
    let total = 0;
    let pkgName = '—';
    let extrasNames = [];
    let detailsArr = [];

    // 1. Package
    const selectedPackage = document.querySelector('.package-option:checked');
    if (selectedPackage) {
        total += parseInt(selectedPackage.value) || 0;
        pkgName = selectedPackage.getAttribute('data-label') || 'Пакет';
        detailsArr.push(pkgName);
    }

    // 2. Extras
    document.querySelectorAll('.extra-option:checked').forEach(extra => {
        const price = parseInt(extra.value) || 0;
        const label = extra.getAttribute('data-label');
        total += price;
        if (label) {
            if (price > 0) {
                extrasNames.push(label);
                detailsArr.push(label);
            }
        }
    });

    // Update UI
    const sumPkg = document.getElementById('sumPackage');
    if (sumPkg) sumPkg.innerText = pkgName;

    const sumExtras = document.getElementById('sumExtras');
    if (sumExtras) {
        const parent = sumExtras.parentElement;
        if (extrasNames.length > 0) {
            sumExtras.innerText = extrasNames.join(', ');
            sumExtras.style.color = '#fff';
            if (parent) {
                parent.style.flexDirection = 'column';
                parent.style.alignItems = 'flex-end';
                parent.style.textAlign = 'right';
            }
        } else {
            sumExtras.innerText = '—';
            sumExtras.style.color = '#aaa';
            if (parent) {
                parent.style.flexDirection = 'row';
                parent.style.alignItems = 'center';
            }
        }
    }

    const finalPriceElem = document.getElementById('finalPrice');
    if (finalPriceElem) {
        animateGradValue('finalPrice', parseInt(finalPriceElem.innerText) || 0, total, 300);
    }

    const hiddenPrice = document.getElementById('hiddenPrice');
    if (hiddenPrice) hiddenPrice.value = total;

    const hiddenDetails = document.getElementById('hiddenDetails');
    if (hiddenDetails) hiddenDetails.value = detailsArr.join(', ');
}

let gradPriceInterval = null;

function animateGradValue(id, start, end, duration) {
    if (start === end) return;
    if (gradPriceInterval) clearInterval(gradPriceInterval);

    const obj = document.getElementById(id);
    const objBgn = document.getElementById(id + 'Bgn');
    const steps = duration / 10;
    let count = 0;

    gradPriceInterval = setInterval(function () {
        count++;
        const progress = count / steps;
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = start + (end - start) * eased;

        if (count >= steps) {
            obj.innerHTML = Math.round(end);
            if (objBgn) objBgn.innerHTML = (end * 1.9558).toFixed(2);
            clearInterval(gradPriceInterval);
        } else {
            obj.innerHTML = Math.round(current);
            if (objBgn) objBgn.innerHTML = (current * 1.9558).toFixed(2);
        }
    }, 10);
}

document.addEventListener('DOMContentLoaded', () => {
    calculateGraduationTotal();
    document.querySelectorAll('.package-option, .extra-option').forEach(input => {
        input.addEventListener('change', calculateGraduationTotal);
    });
});
