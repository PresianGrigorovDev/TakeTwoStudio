<div class="modal fade" id="quickLeadModal" tabindex="-1" aria-labelledby="quickLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: #1a1a1a; color: #fff; border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning" id="quickLeadModalLabel">Бърза Оферта за 30 секунди</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Затвори"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-4">Оставете вашите данни и ние ще се свържем с вас с персонална оферта и свободна дата.</p>
                
                <form action="{{ url('/submit-contact') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="quick_name" class="form-label small fw-bold">Вашето Име *</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="quick_name" name="name" placeholder="напр. Иван Иванов" required>
                    </div>
                    <div class="mb-3">
                        <label for="quick_phone" class="form-label small fw-bold">Телефон за връзка *</label>
                        <input type="tel" class="form-control bg-dark text-white border-secondary" id="quick_phone" name="phone" placeholder="напр. 0888 123 456" required>
                    </div>
                    <div class="mb-3">
                        <label for="quick_service" class="form-label small fw-bold">Вид Услуга</label>
                        <select class="form-select bg-dark text-white border-secondary" id="quick_service" name="orderType">
                            <option value="Сватбено заснемане">Сватбена фотография / видео</option>
                            <option value="Абитуриентски бал">Абитуриентски бал</option>
                            <option value="Свето Кръщение">Свето Кръщение</option>
                            <option value="Портретна фотосесия">Портретна фотосесия</option>
                            <option value="Друго събитие">Друго събитие</option>
                        </select>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="gdpr_consent" id="quick_gdpr" value="1" required checked>
                        <label class="form-check-input-label small text-muted" for="quick_gdpr">
                            Съгласен съм с <a href="{{ route('legal.privacy') }}" class="text-warning text-decoration-none" target="_blank">Политиката за поверителност</a>
                        </label>
                    </div>
                    <button type="submit" class="btn w-100 py-3 fw-bold text-dark" style="background: linear-gradient(135deg, #d4af37 0%, #aa820a 100%); border-radius: 30px;">
                        Вземи Оферта Сега
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
