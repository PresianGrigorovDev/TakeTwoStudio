{{-- GDPR consent checkbox - required on all forms collecting personal data --}}
@php $consentControl = 'gdpr_consent_' . ($consentId ?? uniqid()); @endphp
<div class="form-check mb-3 text-start">
    <input class="form-check-input" type="checkbox" name="gdpr_consent" id="{{ $consentControl }}"
        @error('gdpr_consent', $consentErrorBag ?? 'default') aria-invalid="true" aria-describedby="{{ $consentControl }}_error" @enderror
        value="1" required>
    <label class="form-check-label" for="{{ $consentControl }}" style="font-size: 0.85rem;">
        Запознат/а съм и приемам <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Политиката за
            поверителност</a> и <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Общите условия</a>.
        Съгласявам се с обработката на личните ми данни за целите на отговор на запитването ми. <span
            class="text-danger">*</span>
    </label>
</div>
@error('gdpr_consent', $consentErrorBag ?? 'default')
    <div id="{{ $consentControl }}_error" class="text-danger small mb-2">{{ $message }}</div>
@enderror
