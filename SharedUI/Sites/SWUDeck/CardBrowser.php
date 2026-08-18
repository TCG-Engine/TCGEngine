<?php
// SWUDeck main-menu card browser — a read-only reference grid over the whole card dictionary.
//
// Replaces an <iframe> into the SWUCardList engine root. That root existed only to draw this grid,
// and its GENERATED (gitignored) NextTurnRender.php went stale: it still emitted
// window.assetImageFolder = './SWUDeck/concat', an art tree deleted in the 2026-08-05 shared-corpus
// migration, so every image 404'd. Deleting the root removes the artifact that could go stale.
//
// Self-contained by design: it require_once's its own dependencies rather than relying on
// MainMenu.php's includes, so the regression test can render it standalone.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-card-browser-design.md
require_once __DIR__ . '/../../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/../../../AppCore/SWU/MockCardMerge.php';

// Distinct filter values, derived from the dictionary rather than typed by hand. The meta-page
// format dropdowns drifted precisely because they were hardcoded — padawan was accepted by the APIs
// but absent from both selects, so it could not be chosen at all.
function _SWUCardBrowserFacets(array $cardIDs): array
{
    $sets = $types = $aspects = [];
    foreach ($cardIDs as $id) {
        $s = (string)CardSet($id);
        $t = (string)CardType($id);
        if ($s !== '') $sets[$s] = 1;
        if ($t !== '') $types[$t] = 1;
        // CardAspect() returns a COMMA-JOINED STRING ("Vigilance,Villainy"), not an array.
        foreach (explode(',', (string)CardAspect($id)) as $a) {
            $a = trim($a);
            if ($a !== '') $aspects[$a] = 1;   // 56 cards have no aspect; they contribute nothing
        }
    }
    $sets = array_keys($sets); $types = array_keys($types); $aspects = array_keys($aspects);
    sort($sets); sort($types); sort($aspects);
    return ['sets' => $sets, 'types' => $types, 'aspects' => $aspects];
}

// One grid tile. $mocks is passed in so the file is read once, not once per card.
function _SWUCardBrowserTile(string $cardID, array $mocks): string
{
    $title    = (string)CardTitle($cardID);
    $subtitle = (string)CardSubtitle($cardID);   // null when absent
    $label    = $subtitle === '' ? $title : $title . ' — ' . $subtitle;
    // Lowercased server-side so the text filter is a plain substring test with no per-keystroke work.
    $search   = strtolower(trim($title . ' ' . $subtitle));
    $aspects  = strtolower(implode(',', array_filter(array_map('trim', explode(',', (string)CardAspect($cardID))))));
    $isMock   = isset($mocks[$cardID]) ? '1' : '0';
    $e        = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    // SWUCardImagePath() is the ONLY way an art URL is built here. It applies the mock_ filename
    // prefix and normalises legacy identifiers; a hand-built path is the bug this replaces.
    //
    // BOTH sizes are resolved through the seam server-side. The lightbox used to derive the full
    // card by string-replacing '/concat/' -> '/WebpImages/' on the tile's src — a hardcoded copy of
    // the corpus layout, i.e. the exact class of assumption the shared-corpus migration exists to
    // delete (and the reason the old SWUCardList grid 404'd). Emitting it as data-full also costs
    // nothing: the JS twin window.swuCardArtUrl is never emitted on this page, so the "use the seam
    // when available" branch never actually ran, and wiring it up would ship a ~50KB id map that a
    // page already holding the dictionary does not need.
    $src  = SWUCardImagePath($cardID, 'tile');
    $full = SWUCardImagePath($cardID, 'card');

    $html  = '<div class="cb-tile" data-id="' . $e($cardID) . '" data-title="' . $e($search) . '"'
           . ' data-set="' . $e((string)CardSet($cardID)) . '"'
           . ' data-type="' . $e(strtolower((string)CardType($cardID))) . '"'
           . ' data-full="' . $e($full) . '"'
           . ' data-aspects="' . $e($aspects) . '" data-mock="' . $isMock . '">';
    // width/height are REQUIRED, not decorative. A lazy image has no intrinsic size until it loads,
    // so without them every grid row sized to 0 — which in turn kept the images outside the
    // scrollport, so lazy loading never fired and the grid rendered blank. concat art is 450x450.
    $html .= '<img loading="lazy" width="450" height="450" src="' . $e($src) . '"'
           . ' alt="' . $e($label) . '" title="' . $e($label) . '">';
    if ($isMock === '1') $html .= '<span class="cb-mock">PREVIEW</span>';
    $html .= '</div>';
    return $html;
}

function SWUDeckRenderCardBrowser(): void
{
    $ids    = GetAllCardIds();
    // The deckbuilder's filter engine (ShouldFilter + the inlined SWUAspectMatch) lives in the
    // generated card bundle. It is ~1MB, so it is NOT linked here — the JS below fetches it on demand
    // the first time the box is used. Newest file wins, matching how every other loader picks it.
    $bundle = glob(__DIR__ . '/../../../SWUDeck/GeneratedCode/GeneratedCardDictionaries_*.js');
    rsort($bundle);
    $bundleUrl = $bundle
        ? '/TCGEngine/SWUDeck/GeneratedCode/' . basename($bundle[0]) . '?v=' . @filemtime($bundle[0])
        : '';
    $mocks  = SWULoadMockCards();
    $facets = _SWUCardBrowserFacets($ids);
    $e      = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    ?>
    <style>
      #cardBrowser { display:flex; flex-direction:column; height:100%; color:#e8f4ff;
                     font-family:inherit; box-sizing:border-box; }
      .cb-bar { flex:0 0 auto; padding:14px 50px 10px 14px; background:#002249;
                border-bottom:1px solid #0a3d6b; }
      #cbSearch { width:100%; max-width:420px; padding:8px 10px; border-radius:4px; margin:0;
                  border:1px solid #2a4b8d; background:#001635; color:#e8f4ff; font-size:14px; }
      #cbFilters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-top:10px; }
      /* width/flex/margin are OVERRIDES, not defaults: menuStyles.css styles every bare `select`
         on the site (including `margin:10px 0 20px`), which otherwise stretched these full-width
         and stacked them one per row. */
      #cbFilters select, #cbFilters button { padding:6px 8px; border-radius:4px;
                  border:1px solid #2a4b8d; background:#001635; color:#e8f4ff; font-size:13px;
                  cursor:pointer; width:auto; flex:0 0 auto; margin:0; }
      .cb-aspect { padding:3px; border:1px solid transparent; border-radius:4px; background:none;
                   line-height:0; }
      .cb-aspect img { height:26px; width:26px; opacity:0.45; transition:opacity .15s; }
      .cb-aspect.on img { opacity:1; }
      .cb-aspect.on { border-color:#33ccff; box-shadow:0 0 6px rgba(51,204,255,.5); }
      /* The grid is the ONLY scroller. #cardSearchContent is a fixed-size flex parent, and a
         percentage height would not resolve against it in Firefox/WebKit — flex:1 does, in all
         three engines. See the CSS percentage-height note in the project's cross-browser rule. */
      /* grid-auto-rows:max-content is LOAD-BEARING. With the default implicit `auto` rows, the
         browser compressed all 222 rows to fit the container's definite height: every tile computed
         to 0-73px while its 120px image was clipped by the tile's own overflow:hidden, so the grid
         rendered blank and never scrolled. Measured in Chromium: auto -> tile 73px / not scrollable,
         max-content -> tile 139px / scrollable. align-content made no difference either way. */
      .cb-grid { flex:1 1 auto; overflow-y:auto; overflow-x:hidden; padding:12px;
                 display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr));
                 grid-auto-rows:max-content; gap:8px; align-content:start; }
      .cb-tile { position:relative; cursor:pointer; border-radius:6px; overflow:hidden;
                 background:#001024; }
      /* aspect-ratio backs up the width/height attributes so a row has a definite height before the
         image loads. Without it the grid collapsed to zero-height rows in all engines. */
      .cb-tile img { width:100%; height:auto; aspect-ratio:1/1; display:block; }
      .cb-tile:hover { outline:2px solid #33ccff; }
      .cb-mock { position:absolute; top:4px; left:4px; background:#c8471b; color:#fff;
                 font-size:9px; font-weight:bold; letter-spacing:.5px; padding:2px 4px;
                 border-radius:3px; pointer-events:none; }
      .cb-empty { padding:24px; text-align:center; opacity:.7; }
      #cbCount { font-size:12px; opacity:.75; margin-left:auto; }
      #cbLightbox { display:none; position:absolute; inset:0; z-index:1002;
                    background:rgba(0,8,20,.88); align-items:center; justify-content:center; }
      #cbLightbox img { max-width:min(90%, 460px); max-height:92%; border-radius:12px;
                        box-shadow:0 0 30px rgba(51,204,255,.35); }
    </style>

    <div id="cardBrowser">
      <div class="cb-bar">
        <input type="text" id="cbSearch" placeholder="Search Cards..." autocomplete="off"
               title="Supports deckbuilder filter syntax, e.g. f=premier a=space c:rr cost&lt;=3">
        <div id="cbFilters">
          <select id="cbSet"><option value="">All sets</option>
            <?php foreach ($facets['sets'] as $s): ?>
              <option value="<?= $e($s) ?>"><?= $e($s) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="cbType"><option value="">All types</option>
            <?php foreach ($facets['types'] as $t): ?>
              <option value="<?= $e(strtolower($t)) ?>"><?= $e($t) ?></option>
            <?php endforeach; ?>
          </select>
          <?php foreach ($facets['aspects'] as $a): ?>
            <button type="button" class="cb-aspect" data-aspect="<?= $e(strtolower($a)) ?>" title="<?= $e($a) ?>">
              <img src="/TCGEngine/Assets/Images/icons/SWU/<?= $e($a) ?>.webp" alt="<?= $e($a) ?>">
            </button>
          <?php endforeach; ?>
          <select id="cbMockFilter">
            <option value="">Released + preview</option>
            <option value="1">Preview only</option>
            <option value="0">Released only</option>
          </select>
          <span id="cbCount"></span>
        </div>
      </div>

      <div class="cb-grid" id="cbGrid">
        <?php foreach ($ids as $id) echo _SWUCardBrowserTile($id, $mocks); ?>
      </div>
      <div class="cb-empty" id="cbEmpty" style="display:none;">No cards match those filters.</div>

      <!-- No src attribute: an empty src makes some browsers re-request the page. JS sets it. -->
      <div id="cbLightbox"><img id="cbLightboxImg" alt=""></div>
    </div>

    <script>
    (function () {
      var grid = document.getElementById('cbGrid');
      if (!grid) return;
      var tiles   = grid.getElementsByClassName('cb-tile');
      var search  = document.getElementById('cbSearch');
      var setSel  = document.getElementById('cbSet');
      var typeSel = document.getElementById('cbType');
      var mockSel = document.getElementById('cbMockFilter');
      var empty   = document.getElementById('cbEmpty');
      var count   = document.getElementById('cbCount');
      var aspectBtns = document.getElementsByClassName('cb-aspect');
      var activeAspects = [];

      // ── The deckbuilder's filter syntax, on demand ────────────────────────────────────────────
      // ShouldFilter(cardID, query) is the SAME parser the deck-builder pane uses — every prefix
      // (a/t/c/p/tr/r/is/unq/up/uhp/cost/hp/set/title/subtitle, f for format), every operator
      // (: = < > <= >= !=) and quoted phrases — so this box cannot drift from that one.
      // It lives in the generated card bundle, which is ~1MB. Linking it into the main menu would
      // load it for every visitor including the ones who never open this panel, so it is fetched the
      // first time someone actually types, and the plain substring match stays live until it lands.
      // NOT loaded: Core/UILibraries' ShouldFilterWithOr, which adds `or` and parentheses. That
      // bundle is the game board's and has side effects on load; `or` stays board-only for now.
      var engineReady = false, engineTried = false;
      var BUNDLE_URL = <?= json_encode($bundleUrl) ?>;

      function loadEngine() {
        if (engineTried || !BUNDLE_URL) return;
        engineTried = true;
        var sc = document.createElement('script');
        sc.src = BUNDLE_URL;
        sc.onload = function () {
          engineReady = (typeof ShouldFilter === 'function');
          apply();   // re-run: the query typed before this landed was matched by substring only
        };
        // On failure the box keeps working as a name search. A dead search box would be a worse
        // outcome than a less powerful one.
        sc.onerror = function () { engineReady = false; };
        document.head.appendChild(sc);
      }

      // Only a query that could BE syntax is worth the fetch: a plain name search is already served.
      function looksLikeSyntax(q) { return /[:=<>]/.test(q); }

      function matchesQuery(el, q, qLower) {
        if (!q) return true;
        if (engineReady) {
          var id = el.getAttribute('data-id');
          // A card the engine does not know (a preview not yet in the dictionary) must not silently
          // vanish the moment the engine loads — fall back to the substring test for that tile.
          if (typeof CardTitle !== 'function' || CardTitle(id) !== null) {
            try { return !ShouldFilter(id, q); } catch (err) { /* fall through to substring */ }
          }
        }
        return el.getAttribute('data-title').indexOf(qLower) !== -1;
      }

      function apply() {
        var raw = search.value.trim();
        var q = raw, qLower = raw.toLowerCase();
        if (q && !engineReady && looksLikeSyntax(q)) loadEngine();
        var s = setSel.value, t = typeSel.value, mk = mockSel.value, shown = 0;
        for (var i = 0; i < tiles.length; i++) {
          var el = tiles[i], ok = true;
          if (q && !matchesQuery(el, q, qLower)) ok = false;
          if (ok && s  && el.getAttribute('data-set')  !== s)  ok = false;
          if (ok && t  && el.getAttribute('data-type') !== t)  ok = false;
          if (ok && mk && el.getAttribute('data-mock') !== mk) ok = false;
          if (ok && activeAspects.length) {
            // AND semantics: a selected pair means "has BOTH", matching how players describe a
            // two-aspect card. Deck-legality syntax (AspectFilter.js) is deliberately not used here.
            var have = el.getAttribute('data-aspects').split(',');
            for (var a = 0; a < activeAspects.length; a++) {
              if (have.indexOf(activeAspects[a]) === -1) { ok = false; break; }
            }
          }
          el.style.display = ok ? '' : 'none';
          if (ok) shown++;
        }
        empty.style.display = shown ? 'none' : '';
        count.textContent = shown + ' card' + (shown === 1 ? '' : 's');
      }

      search.addEventListener('input', apply);
      setSel.addEventListener('change', apply);
      typeSel.addEventListener('change', apply);
      mockSel.addEventListener('change', apply);

      for (var b = 0; b < aspectBtns.length; b++) {
        aspectBtns[b].addEventListener('click', function () {
          var a = this.getAttribute('data-aspect');
          var at = activeAspects.indexOf(a);
          if (at === -1) { activeAspects.push(a); this.className = 'cb-aspect on'; }
          else { activeAspects.splice(at, 1); this.className = 'cb-aspect'; }
          apply();
        });
      }

      // Enlarge. concat tiles have a blank rules box, so this shows the full WebpImages art, whose
      // URL was resolved server-side through SWUCardImagePath() and carried on data-full. No
      // filename or folder rule is reproduced in this file.
      var box = document.getElementById('cbLightbox');
      var boxImg = document.getElementById('cbLightboxImg');
      grid.addEventListener('click', function (ev) {
        var tile = ev.target;
        while (tile && tile !== grid && tile.className.indexOf('cb-tile') === -1) tile = tile.parentNode;
        if (!tile || tile === grid) return;
        boxImg.src = tile.getAttribute('data-full');
        boxImg.alt = tile.getElementsByTagName('img')[0].alt;
        box.style.display = 'flex';
      });
      box.addEventListener('click', function () { box.style.display = 'none'; });

      // The popup opener calls this after its open animation — focusing a field inside a element that
      // is still display:none does nothing, so the caller owns the timing, not this file.
      window.SWUCardBrowserFocus = function () {
        if (!search) return;
        search.focus();
        search.select();
      };

      apply();
    })();
    </script>
    <?php
}
