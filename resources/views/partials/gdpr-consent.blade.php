{{-- GDPR consent checkbox — required on all forms collecting personal data --}}
<div class="form-check mb-3 text-start">
    <input class="form-check-input" type="checkbox" name="gdpr_consent" id="gdpr_consent_{{ $consentId ?? uniqid() }}" value="1" required>
    <label class="form-check-label" for="gdpr_consent_{{ $consentId ?? uniqid() }}" style="font-size: 0.85rem;">
        Запознат/а съм и приемам <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Политиката за поверителност</a> и <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Общите условия</a>. Съгласявам се с обработката на личните ми данни за целите на отговор на запитването ми. <span class="text-danger">*</span>
    </label>
</div>
@error('gdpr_consent')
    <div class="text-danger small mb-2">{{ $message }}</div>
@enderror
