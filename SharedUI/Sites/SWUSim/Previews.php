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
    <?php $setCount = array_sum(array_map('count', $groups)); ?>
    <div class="card ga-glass-card swu-preview-set">
      <h2><?php echo htmlspecialchars($set, ENT_QUOTES); ?>
        <span class="swu-preview-count"><?php echo $setCount; ?> card<?php echo $setCount === 1 ? '' : 's'; ?></span>
      </h2>

      <?php foreach (PREVIEW_GROUPS as $label => [$_matchType, $orient]): ?>
        <?php if (empty($groups[$label])) continue;   // a set with no leaders previewed yet shows no band ?>
        <h3><?php echo htmlspecialchars($label, ENT_QUOTES); ?></h3>
        <div class="swu-preview-grid swu-preview-grid--<?php echo $orient; ?>">
          <?php foreach ($groups[$label] as $cid => $faces): ?>
            <?php $meta = $previewMeta($cid); ?>
            <figure class="swu-preview-card">
              <?php foreach (['front', 'back'] as $face): ?>
                <?php if (!empty($faces[$face])): ?>
                  <img src="<?php echo htmlspecialchars($faces[$face], ENT_QUOTES); ?>"
                       alt="<?php echo htmlspecialchars($previewTitle($cid), ENT_QUOTES); ?>"
                       loading="lazy">
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
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
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
.swu-preview-set h3:first-of-type { margin-top: 12px; }

/* Explicit-width grid rather than flex stretching: a percentage/auto height on the card images resolves
   differently in Firefox/WebKit than Chromium (see the CSS percentage-height flex note), so the images
   size from their own intrinsic ratio and the track width only. */
.swu-preview-grid { display: grid; gap: 18px; margin-top: 14px; }
.swu-preview-grid--tall { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
.swu-preview-grid--wide { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
.swu-preview-card { margin: 0; display: flex; flex-direction: column; gap: 6px; }
.swu-preview-card img { width: 100%; max-width: 100%; height: auto; display: block; border-radius: 10px; }
.swu-preview-card figcaption { display: flex; flex-direction: column; gap: 2px; }
.swu-preview-name { font-size: 13px; line-height: 1.3; }
/* .ga-glass-card * forces --text on descendants, so the muted meta line needs to win on specificity. */
.swu-preview-card .swu-preview-meta { font-size: 12px; color: var(--text-muted); }

/* menuStyles.css pins .disclaimer to `position: absolute; bottom: 15px`, which is fine on a menu that
   fits the viewport but lands ON TOP of the content of any page that scrolls. Let it flow at the end of
   this page instead. Scoped here rather than changed globally — the menu relies on the absolute form. */
.disclaimer { position: relative; bottom: auto; margin-top: 28px; }
</style>

<?php
include_once __DIR__ . '/Disclaimer.php';
?>
</body>
</html>
