{{-- Game B: "The Seating Chart Nightmare". Guests are rendered into #st-tray by public/js/igra.js; validation is rule-based (4 valid arrangements). --}}
<div class="st" id="st">
    <h2 class="st-title" id="game-h2" tabindex="-1">Настани гостите според правилата</h2>
    <details class="st-rules" id="st-rules" open>
        <summary class="mono">Правила <span class="st-rules__hint">(4)</span></summary>
        <ol class="st-rules__list">
            <li class="st-rule" data-rule="r1">Свекървата и Майката на булката НЕ могат да седят една до друга и НЕ могат да са директно една срещу друга.</li>
            <li class="st-rule" data-rule="r2">Купонджията Иван задължително трябва да е до Шаферката Елена.</li>
            <li class="st-rule" data-rule="r3">DJ-ят трябва да е на стол 1 (най-близо до пулта).</li>
            <li class="st-rule" data-rule="r4">Чичото с политиката не може да бъде до Свекървата, защото спорят.</li>
        </ol>
    </details>
    <div class="st-stage">
        <div class="st-table" id="st-table">
            <div class="st-disc" id="st-disc" aria-hidden="true"><span class="mono">МАСА 01</span></div>
            @foreach(range(1, 6) as $n)
                <button type="button" class="st-seat" data-seat="{{ $n }}" aria-label="Място {{ $n }}: свободно">
                    <span class="st-seat__num mono" aria-hidden="true">{{ $n }}</span>
                    <span class="st-seat__chip mono" aria-hidden="true"></span>
                </button>
            @endforeach
        </div>
    </div>
    <p class="st-hint mono">Докосни гост, после стол. Или го дръпни.</p>
    <div class="st-tray" id="st-tray" role="group" aria-label="Гости"></div>
    <p class="igra-msg mono" id="st-msg" role="status"></p>
    <div class="igra-actions">
        <button type="button" class="igra-btn igra-btn--ghost" id="st-reset">Изчисти масата</button>
        <button type="button" class="igra-btn igra-btn--primary" id="st-check" disabled>Потвърди местата</button>
    </div>
</div>
