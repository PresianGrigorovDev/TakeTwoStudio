<style>
.mobile-sticky-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 9990;
    background: rgba(18, 18, 18, 0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(212, 175, 55, 0.3);
    padding: 10px 15px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
}

@media (max-width: 767.98px) {
    .mobile-sticky-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    body {
        padding-bottom: 60px;
    }
}

.mobile-sticky-btn-calc {
    flex: 1;
    background: linear-gradient(135deg, #d4af37 0%, #aa820a 100%);
    color: #000;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 10px 14px;
    border-radius: 25px;
    text-decoration: none;
    text-align: center;
    box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
    transition: transform 0.2s ease;
}

.mobile-sticky-btn-call {
    background: #22c55e;
    color: #fff;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 2px 10px rgba(34, 197, 94, 0.3);
}
</style>

<div class="mobile-sticky-bar">
    <a href="#calculator" onclick="if(document.getElementById('calculator')){ document.getElementById('calculator').scrollIntoView({behavior: 'smooth'}); return false; }" class="mobile-sticky-btn-calc">
        ⚡ Изчисли Цена & Запази Час
    </a>
    <a href="tel:+359888000000" class="mobile-sticky-btn-call" title="Обадете се">
        <i class="fas fa-phone-alt"></i>
    </a>
</div>
