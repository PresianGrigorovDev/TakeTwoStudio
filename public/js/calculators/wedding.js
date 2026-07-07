function updateWeddingUI() {
    const videoOp = document.querySelector('input[name="operators"]:checked');
    const photoOp = document.querySelector('input[name="photographers"]:checked');
    
    // Safety check if elements exist
    if (!videoOp || !photoOp) return;

    const hasVideo = parseInt(videoOp.value) > 0;
    const hasPhoto = parseInt(photoOp.value) > 0;

    // Helper to enable/disable specific input
    const setEnabled = (el, enabled) => {
        if (!el) return;
        el.disabled = !enabled;
        const label = document.querySelector(`label[for="${el.id}"]`);
        if (label) {
            label.style.opacity = enabled ? "1" : "0.5";
            label.style.pointerEvents = enabled ? "auto" : "none";
        }
        if (!enabled && el.type !== 'radio' && el.checked) {
            el.checked = false;
        }
    };

    // Toggle Video Extras
    const videoExtras = document.querySelectorAll('input[data-category="video_extra"]');
    videoExtras.forEach(el => setEnabled(el, hasVideo));

    // Toggle Photo Extras
    const photoExtras = document.querySelectorAll('input[data-category="photo_extra"]');
    photoExtras.forEach(el => setEnabled(el, hasPhoto));
    
    // Delivery - Album specific
    const deliveryInputs = document.querySelectorAll('input[name="recording"]');
    deliveryInputs.forEach(el => {
        const labelText = el.getAttribute('data-label') || '';
        if (labelText.toLowerCase().includes('албум')) {
            setEnabled(el, hasPhoto);
            if (!hasPhoto && el.checked) {
                // Fallback to first option (usually Cloud)
                const first = document.querySelector('input[name="recording"]');
                if (first) first.checked = true;
            }
        }
    });
}

// Tracking previous valid state
let prevVideoId = null;
let prevPhotoId = null;

function calculateWeddingTotal() {
    // First update UI state (gray out items)
    updateWeddingUI();

    let total = 0;
    let teamText = [];
    let photoText = [];
    let videoText = [];

    const videoOp = document.querySelector('input[name="operators"]:checked');
    const photoOp = document.querySelector('input[name="photographers"]:checked');

    if (!videoOp || !photoOp) return;

    const hasVideo = parseInt(videoOp.value) > 0;
    const hasPhoto = parseInt(photoOp.value) > 0;

    // --- VALIDATION: At least one service must be selected ---
    if (!hasVideo && !hasPhoto) {
        // Show validation modal
        if (typeof bootstrap !== 'undefined') {
            const modalEl = document.getElementById('validationModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        } else {
            alert("Трябва да изберете поне една основна услуга (Видео или Фотография)!");
        }

        // Revert to previous valid state
        if (prevVideoId) {
            document.getElementById(prevVideoId).checked = true;
        } else {
            // Fallback: select first non-zero video op if everything fails
            const firstOp = document.querySelectorAll('input[name="operators"]')[1];
            if (firstOp) firstOp.checked = true;
        }
        
        if (prevPhotoId) {
            document.getElementById(prevPhotoId).checked = true;
        }

        // Re-run calculation with reverted values
        updateWeddingUI();
        calculateWeddingTotal();
        return;
    }

    // Update tracking
    if (hasVideo) prevVideoId = videoOp.id;
    if (hasPhoto) prevPhotoId = photoOp.id;

    let packagePrice = 0;
    let extrasPrice = 0;

    // Select all checked calc inputs
    const inputs = document.querySelectorAll('#calcForm input:checked');

    inputs.forEach(input => {
        const price = parseFloat(input.getAttribute('data-price')) || 0;
        const category = input.getAttribute('data-category');
        const label = input.getAttribute('data-label') || '';
        const val = parseInt(input.value);

        if (category === 'team') {
             packagePrice += price;
        } else {
             extrasPrice += price;
        }

        // Add price
        total += price;

        if (category === 'team' && val > 0) {
             let cleanLabel = label.replace('Видео: ', 'Видео: ').replace('Фото: ', 'Фото: ').replace(' човек', '').replace('а', ''); 
             teamText.push(cleanLabel);
        } else if (category === 'photo_extra') {
             photoText.push(label);
        } else if (category === 'video_extra') {
             if (price !== 0 || label.includes('Филм') || label.includes('4K') || label.includes('Res')) {
                  videoText.push(label.replace('Филм: ', '').replace('Res: ', ''));
             }
        } else if (category === 'delivery') {
             if (label.toLowerCase().includes('албум')) {
                 if(price > 0) photoText.push("Албум");
             } else {
                 if(price > 0) videoText.push(label.replace('Запис: ', ''));
             }
        }
    });

    // --- Apply promo discount (if code was applied) ---
    var finalPrice = (typeof applyPromoDiscount === 'function') ? applyPromoDiscount(packagePrice, extrasPrice) : total;

    // --- ВИЗУАЛИЗАЦИЯ ---
    const finalPriceEl = document.getElementById('finalPrice');
    let startVal = parseInt(finalPriceEl.innerText) || 0;
    animateValue("finalPrice", startVal, finalPrice, 300);

    // Show discount line if active
    var discountEl = document.getElementById('promo-discount-line');
    if (discountEl) {
        if (finalPrice < total) {
            discountEl.style.display = 'flex';
            discountEl.querySelector('.discount-amount').textContent = '-€' + Math.round(total - finalPrice);
        } else {
            discountEl.style.display = 'none';
        }
    }

    // Text updates
    let teamHtml = teamText.length > 0 ? teamText.join(", ") : "-";
    document.getElementById('sumTeam').innerHTML =
        `<span style="text-align:right; font-size:13px; max-width:160px; line-height:1.2;">${teamHtml}</span>`;

    let photoHtml = photoText.length > 0 ? photoText.join(", ") : "-";
    document.getElementById('sumPhoto').innerHTML =
        `<span style="text-align:right; font-size:13px; max-width:160px; line-height:1.2;">${photoHtml}</span>`;

    let videoHtml = videoText.length > 0 ? videoText.join(", ") : "-";
    document.getElementById('sumVideo').innerHTML =
        `<span style="text-align:right; font-size:13px; max-width:160px; line-height:1.2;">${videoHtml}</span>`;

    // Hidden inputs – send discounted final price
    document.getElementById('hiddenPrice').value = Math.round(finalPrice);
    
    let fullDescription = "";
    if (teamText.length > 0) fullDescription += "ЕКИП: " + teamText.join(" + ") + "\n";
    if (photoText.length > 0) fullDescription += "ФОТО: " + photoText.join(", ") + "\n";
    if (videoText.length > 0) fullDescription += "ВИДЕО: " + videoText.join(", ");
    
    document.getElementById('hiddenDetails').value = fullDescription;
}

let priceInterval = null;

function animateValue(id, start, end, duration) {
    if (start === end) return;
    // Clear existing interval if any
    if (priceInterval) clearInterval(priceInterval);

    const range = end - start;
    const minTimer = 10; // 10ms step
    let stepTime = Math.abs(Math.floor(duration / (range / (end > start ? 1 : -1))));
    
    const steps = duration / minTimer; 
    const increment = range / steps;
    
    let current = start;
    let count = 0;
    
    const obj = document.getElementById(id);
    const objBgn = document.getElementById(id + "Bgn"); // Търсим елемента за левовете

    priceInterval = setInterval(function () {
        count++;
        
        // Progress from 0 to 1
        const progress = count / steps;
        // Ease-out Cubic: 1 - (1 - t)^3
        const easedProgress = 1 - Math.pow(1 - progress, 3);
        
        current = start + (range * easedProgress);
        
        if (count >= steps) {
            current = end;
            clearInterval(priceInterval);
        }
        
        obj.innerText = Math.round(current);
        if (objBgn) {
            objBgn.innerText = (current * 1.95583).toFixed(2);
        }
    }, minTimer);
}

// Make globally available for inline HTML listeners
window.calculateWeddingTotal = calculateWeddingTotal;
window.updateWeddingUI = updateWeddingUI;

// Initial call to set state correctly
window.addEventListener('DOMContentLoaded', () => {
     updateWeddingUI();
     calculateWeddingTotal();
});
