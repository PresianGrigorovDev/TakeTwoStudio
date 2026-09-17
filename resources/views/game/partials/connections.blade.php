{{-- Game A: "The Prom Connections" (NYT Connections style). Tiles and bands are rendered by public/js/igra.js. --}}
<div class="cx" id="cx">
    <div class="cx-head">
        <h2 class="cx-title" id="game-h2" tabindex="-1">Открий четирите връзки</h2>
        <div class="cx-lives" id="cx-lives" role="img" aria-label="Оставащи опити: 4">
            <span class="cx-life"></span><span class="cx-life"></span><span class="cx-life"></span><span class="cx-life"></span>
        </div>
    </div>
    <p class="cx-hint mono">16 плочки, скрити в 4 групи по 4. Избери 4 с нещо общо и натисни „Провери връзката“.</p>
    <div class="cx-grid" id="cx-grid" role="group" aria-label="Плочки"></div>
    <p class="igra-msg mono" id="cx-msg" role="status"></p>
    <div class="igra-actions">
        <button type="button" class="igra-btn igra-btn--ghost" id="cx-shuffle">Размести</button>
        <button type="button" class="igra-btn igra-btn--ghost" id="cx-clear">Изчисти</button>
        <button type="button" class="igra-btn igra-btn--primary" id="cx-check" disabled>Провери връзката</button>
    </div>
    <div class="cx-lose" id="cx-lose" hidden>
        <p class="cx-lose__title mono">&gt; Връзката е прекъсната.</p>
        <p class="cx-lose__text">Опитите свършиха. Категориите са разкрити по-горе.</p>
        <button type="button" class="igra-btn igra-btn--primary" id="cx-retry">Опитай отново</button>
    </div>
</div>
