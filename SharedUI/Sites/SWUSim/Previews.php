<?php
// Previews — the public gallery of PREVIEW-set cards that are actually playable right now.
//
// The card list is the contents of SWUSim/PreviewsImplemented/, which is a MIRROR maintained by
// SWUSim/DevTools/sync-preview-images.php (see the swusim-sync-preview-images skill): it holds the mock_
// art of exactly those preview cards the engine implements. Deliberately a directory listing rather than
// a re-derivation of implementation status here — the tool owns that judgement, so this page cannot claim
// a card is playable when it isn't.
//
// ⚠ That directory is gitignored (it duplicates the tracked WebpImages/mock_* art), so it must be
// regenerated after deploy or this page shows its empty state.
//
// __DIR__-relative includes: this page can be reached directly, so the cwd is not this dir.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Header.php';

$previewDir = __DIR__ . '/../../../SWUSim/PreviewsImplemented';
$previewUrl = '/TCGEngine/SWUSim/PreviewsImplemented';

// Collect one entry per CARD (not per file) — a leader contributes both a front and a "_back" face.
$previewCards = [];
foreach (@scandir($previewDir) ?: [] as $file) {
    if (!preg_match('/^mock_(.+)\.webp$/', $file, $m)) continue;
    $isBack = (bool)preg_match('/_back$/', $m[1]);
    $cid    = preg_replace('/_back$/', '', $m[1]);
    if ($cid === '') continue;
    if (!isset($previewCards[$cid])) $previewCards[$cid] = ['front' => null, 'back' => null];
    $previewCards[$cid][$isBack ? 'back' : 'front'] = "$previewUrl/$file";
}

// Sort by set then card number so a card sits next to its neighbours as printed.
uksort($previewCards, function ($a, $b) {
    $as = explode('_', $a); $bs = explode('_', $b);
    return [$as[0], $as[1] ?? ''] <=> [$bs[0], $bs[1] ?? ''];
});

// Group set -> card-kind. Leaders and Bases are one-per-deck and read differently from the rest, so they
// get their own bands; everything else (Units, Events, Upgrades, and the token cards) shares the third.
// Keys double as the displayed heading, and the insertion order here is the display order.
// label => [matched $typeData value ('*' = everything else), art orientation]. Leader and Base art is
// LANDSCAPE (450x320) while unit/event/upgrade art is PORTRAIT (450x622), so the landscape bands get
// wider grid tracks — at a portrait-sized 180px track a landscape card renders postage-stamp small.
const PREVIEW_GROUPS = [
    'Leaders'                 => ['Leader', 'wide'],
    'Bases'                   => ['Base',   'wide'],
    'Units, Events, Upgrades' => ['*',      'tall'],
];
$previewsBySet = [];
foreach ($previewCards as $cid => $faces) {
    $type  = $typeData[$cid] ?? '';
    $group = 'Units, Events, Upgrades';
    foreach (PREVIEW_GROUPS as $label => [$matchType, $_orient]) {
        if ($matchType !== '*' && $type === $matchType) { $group = $label; break; }
    }
    $previewsBySet[explode('_', $cid)[0]][$group][$cid] = $faces;
}

// Titles come from the dictionaries (mock entries are merged in at generation time), never from the
// filename — the CardID is the key, the printed name is data.
$previewTitle = function (string $cid) use (&$titleData, &$subtitleData): string {
    $t = $titleData[$cid] ?? $cid;
    $s = $subtitleData[$cid] ?? '';
    return $s !== '' ? "$t — $s" : $t;
};
// A leader tile shows both faces, and the deployed face is a differently-named card (HMW_004 Grand Moff
// Tarkin deploys as The Death Star), so name it rather than leaving the second image unlabelled.
$previewDeployedName = function (string $cid): ?string {
    $n = function_exists('CardLeaderUnitTitle') ? CardLeaderUnitTitle($cid) : null;
    return ($n !== null && $n !== '') ? $n : null;
};
$previewMeta = function (string $cid) use (&$typeData, &$costData, &$arenaData): string {
    $bits = [];
    if (!empty($typeData[$cid]))  $bits[] = $typeData[$cid];
    if (isset($costData[$cid]) && $costData[$cid] !== '') $bits[] = 'Cost ' . intval($costData[$cid]);
    if (!empty($arenaData[$cid])) $bits[] = $arenaData[$cid];
    return implode(' · ', $bits);
};
$previewCardCount = count($previewCards);
?>
<div class="row-wrapper swu-preview-wrapper">
  <div class="card ga-glass-card swu-preview-intro">
    <h2>Previews</h2>
    <p>
      Cards from upcoming sets that are already playable here. Preview cards use fan-made placeholder
      images and unofficial text, and are replaced once official card data is released — so both the art
      and the wording can still change.
    </p>
    <?php if ($previewCardCount === 0): ?>
      <p class="swu-preview-empty">No preview cards are available yet. Check back soon.</p>
    <?php endif; ?>
  </div>

  <?php foreach ($previewsBySet as $set => $groups): ?>
    <?php
      $setCount = array_sum(array_map('count', $groups));
      // Slug the set code for element ids: each set gets its OWN tablist, so the ids must not
      // collide once a second set (IC27) lands.
      $setId = preg_replace('/[^A-Za-z0-9]/', '', (string)$set);
      // Only groups that actually have cards become tabs — a set with no bases yet shows no Bases tab.
      $tabs = [];
      foreach (PREVIEW_GROUPS as $label => [$_matchType, $orient]) {
        if (!empty($groups[$label])) $tabs[$label] = $orient;
      }
      if (!$tabs) continue;
      $firstTab = array_key_first($tabs);
    ?>
    <details class="card ga-glass-card swu-preview-set" open>
      <summary class="swu-preview-summary">
        <h2><?php echo htmlspecialchars($set, ENT_QUOTES); ?>
          <span class="swu-preview-count"><?php echo $setCount; ?> card<?php echo $setCount === 1 ? '' : 's'; ?></span>
        </h2>
      </summary>

      <div class="swu-preview-tabs" role="tablist" aria-label="<?php echo htmlspecialchars($set . ' card types', ENT_QUOTES); ?>">
        <?php foreach ($tabs as $label => $orient): ?>
          <?php $panelId = 'pv-' . $setId . '-' . preg_replace('/[^A-Za-z0-9]/', '', $label); ?>
          <button type="button" role="tab"
                  class="swu-preview-tab<?php echo $label === $firstTab ? ' is-active' : ''; ?>"
                  aria-selected="<?php echo $label === $firstTab ? 'true' : 'false'; ?>"
                  aria-controls="<?php echo $panelId; ?>"
                  data-pv-panel="<?php echo $panelId; ?>">
            <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
            <span class="swu-preview-tab-count"><?php echo count($groups[$label]); ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <?php foreach ($tabs as $label => $orient): ?>
        <?php $panelId = 'pv-' . $setId . '-' . preg_replace('/[^A-Za-z0-9]/', '', $label); ?>
        <section id="<?php echo $panelId; ?>" role="tabpanel"
                 class="swu-preview-band<?php echo $label === $firstTab ? ' is-active' : ''; ?>">
        <div class="swu-preview-grid">
          <?php foreach ($groups[$label] as $cid => $faces): ?>
            <?php $meta = $previewMeta($cid); ?>
            <figure class="swu-preview-card">
              <?php foreach (['front', 'back'] as $face): ?>
                <?php if (!empty($faces[$face])): ?>
                  <?php /* Same hover preview as the game board: ShowCardDetail reads the IMG out of
                           the element it is handed, so each face needs its own wrapper to preview
                           the face you are actually pointing at. */ ?>
                  <span class="swu-preview-face" onmouseover="ShowCardDetail(event, this)" onmouseout="HideCardDetail()">
                    <img src="<?php echo htmlspecialchars($faces[$face], ENT_QUOTES); ?>"
                         alt="<?php echo htmlspecialchars($previewTitle($cid), ENT_QUOTES); ?>"
                         loading="lazy">
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
              <figcaption>
                <span class="swu-preview-name"><?php echo htmlspecialchars($previewTitle($cid), ENT_QUOTES); ?></span>
                <?php $deployed = $previewDeployedName($cid); ?>
                <?php if ($deployed !== null && !empty($faces['back'])): ?>
                  <span class="swu-preview-meta">deploys as <?php echo htmlspecialchars($deployed, ENT_QUOTES); ?></span>
                <?php endif; ?>
                <?php if ($meta !== ''): ?>
                  <span class="swu-preview-meta"><?php echo htmlspecialchars($meta, ENT_QUOTES); ?></span>
                <?php endif; ?>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
        </section>
      <?php endforeach; ?>
    </details><!-- /.swu-preview-set -->
  <?php endforeach; ?>

  <script>
    // Tab switching, delegated from the document so every set's tablist is handled by one
    // listener — including sets that do not exist yet (IC27). Panels are hidden with a class,
    // not inline styles, so the CSS stays the single source of truth for what "active" looks like.
    document.addEventListener('click', function (e) {
      var tab = e.target.closest('.swu-preview-tab');
      if (!tab) return;
      var tablist = tab.parentElement;
      var set = tab.closest('.swu-preview-set');
      if (!set) return;
      tablist.querySelectorAll('.swu-preview-tab').forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      // Scope to THIS set's panels; :scope keeps a nested set (if one is ever added) from matching.
      set.querySelectorAll(':scope > .swu-preview-band').forEach(function (panel) {
        panel.classList.toggle('is-active', panel.id === tab.dataset.pvPanel);
      });
    });
  </script>
</div>

<style>
/* .card + .ga-glass-card supply the frosted grey panel (--surface-raised) the main-menu cards use — the
   rule lives in css/swusim-overrides.css so it applies on every page, not just MainMenu. */
.swu-preview-wrapper { display: flex; flex-direction: column; gap: 20px; }
.swu-preview-intro, .swu-preview-set { padding: 24px; border-radius: 12px; }
.swu-preview-intro h2, .swu-preview-set h2 { margin: 0 0 8px; }
.swu-preview-intro p { color: var(--text-muted); max-width: 70ch; margin: 0; }
.swu-preview-empty { margin-top: 12px !important; }
.swu-preview-count { color: var(--text-muted); font-weight: 400; font-size: 14px; margin-left: 6px; }
.swu-preview-set h3 {
  margin: 20px 0 0;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  border-bottom: 1px solid rgba(var(--accent-rgb), 0.28);
  padding-bottom: 6px;
}
/* ── Collapsible set + tabbed card types ──────────────────────────────────────────────────────
   Each set is a <details> so the collapse is NATIVE: keyboard- and screen-reader-accessible,
   works with no JS, and cannot end up with an affordance that looks clickable but is not. The
   whole summary row is the hit target, not a label beside it. */
.swu-preview-set > summary {
  cursor: pointer; list-style: none;
  display: flex; align-items: center; gap: 10px;
}
.swu-preview-set > summary::-webkit-details-marker { display: none; }   /* Safari's default triangle */
.swu-preview-set > summary::before {
  content: ''; flex: 0 0 auto; width: 9px; height: 9px;
  border-right: 2px solid var(--accent); border-bottom: 2px solid var(--accent);
  transform: rotate(-45deg); transition: transform 140ms ease;
}
.swu-preview-set[open] > summary::before { transform: rotate(45deg); }
.swu-preview-set > summary h2 { margin: 0; }
.swu-preview-set > summary:hover h2 { color: var(--accent); }

.swu-preview-tabs {
  display: flex; flex-wrap: wrap; gap: 6px;
  margin: 14px 0 0; padding-bottom: 10px;
  border-bottom: 1px solid rgba(var(--accent-rgb), 0.28);
}
.swu-preview-tab {
  cursor: pointer; border: 1px solid rgba(var(--accent-rgb), 0.30); border-radius: 6px;
  background: transparent; color: var(--text-muted);
  padding: 6px 12px; font: 700 12px var(--swu-font-label, inherit);
  letter-spacing: 0.06em; text-transform: uppercase;
  transition: background 140ms, color 140ms, border-color 140ms;
}
.swu-preview-tab:hover { color: var(--text); border-color: rgba(var(--accent-rgb), 0.55); }
.swu-preview-tab.is-active {
  background: rgba(var(--accent-rgb), 0.16); border-color: var(--accent); color: var(--text);
}
/* .ga-glass-card * forces --text on descendants, so the count pill needs the specificity. */
.swu-preview-tab .swu-preview-tab-count { color: var(--text-muted); font-weight: 400; margin-left: 6px; }
.swu-preview-tab.is-active .swu-preview-tab-count { color: var(--text); }

/* One panel at a time. display:none rather than visibility/height so the hidden panels cost no
   layout — the page is now one band tall regardless of how many cards a set has. */
.swu-preview-band { display: none; }
.swu-preview-band.is-active { display: block; }

/* Uniform HEIGHT, width follows the art's own ratio — so a landscape leader front (450x320) and its
   portrait leader-unit back (450x622) line up instead of differing by ~2x and leaving the band ragged.
   This also keeps the cross-browser property the previous explicit-width grid was protecting: the
   images never depend on a percentage/auto height, which resolves differently in Firefox/WebKit than
   Chromium (see the CSS percentage-height flex note). A fixed px height needs no parent height chain.
   Cards are small on purpose — hover scales one up to read it.
   The figure wraps: both faces sit on one row, and the caption is forced onto its own full-width line
   (flex-basis 100%), so a two-faced leader stays exactly as tall as a one-faced unit. */
.swu-preview-grid { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 18px; margin-top: 14px; }
.swu-preview-card { margin: 0; display: flex; flex-wrap: wrap; align-items: flex-start; gap: 6px; }
.swu-preview-card img {
  height: var(--swu-preview-h, 170px); width: auto; max-width: none; display: block;
  border-radius: 8px; transition: transform 140ms ease, box-shadow 140ms ease;
}
/* width:0 + min-width:100% keeps the caption from driving the figure's width: a long name would
   otherwise set the figure's max-content width and blow ragged gaps through the band. width:0
   contributes nothing to intrinsic sizing; min-width:100% then fills whatever the ART decided. */
.swu-preview-card figcaption {
  flex: 1 0 100%; width: 0; min-width: 100%;
  display: flex; flex-direction: column; gap: 2px; overflow-wrap: anywhere;
}

/* Hover preview is the GAME's: ShowCardDetail/HideCardDetail from Core/jsInclude.js, the same
   floating panel the board uses (and the same long-press behaviour on touch). Previously this was
   a CSS transform: scale(2.1) in place, which read as jarring next to the board. Only the face
   wrapper is styled here — the preview itself is entirely jsInclude's. */
/* A <span>, not an <a>, and no cursor override: hovering previews, clicking does nothing, so the
   element should promise nothing. (An href-less <a> plus cursor:zoom-in advertised a click that
   was never wired.) */
.swu-preview-face { display: block; line-height: 0; }
@media (hover: none) {   /* touch: no hover, so start from slightly larger art (long-press still previews) */
  .swu-preview-card img { height: var(--swu-preview-h-touch, 210px); }
}
.swu-preview-name { font-size: 13px; line-height: 1.3; }
/* .ga-glass-card * forces --text on descendants, so the muted meta line needs to win on specificity. */
.swu-preview-card .swu-preview-meta { font-size: 12px; color: var(--text-muted); }

/* menuStyles.css used to pin .disclaimer to `position: absolute; bottom: 15px`, which landed it on top
   of the content of any page that scrolls — this page needed a local escape. That default is now
   `position: static` globally, so only the extra breathing room below the last card band is still ours. */
.disclaimer { margin-top: 28px; }
</style>

<?php
include_once __DIR__ . '/Disclaimer.php';
?>

<?php /* Card-detail hover preview, borrowed wholesale from the game board. jsInclude.js needs two
         things a game page normally provides: the #cardDetail panel it renders into, and #folderPath,
         which it reads to pick the hover delay (SWUSim = 850ms — matching the board is the point).
         Its load-time listeners are all card-detail specific (mousemove / touch long-press / dragstart),
         so they are inert on a static page. */ ?>
<input type="hidden" id="folderPath" value="SWUSim">
<div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
<script src="/TCGEngine/Core/jsInclude.js?v=<?php echo @filemtime(__DIR__ . '/../../../Core/jsInclude.js') ?: '1'; ?>"></script>
</body>
</html>
