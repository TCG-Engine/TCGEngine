<?php
$fabHomeHref = '/TCGEngine/SharedUI/Sites/FaBSim/MainMenu.php';
include __DIR__ . '/GameOver.php';
include __DIR__ . '/CombatPanel.php';
include __DIR__ . '/Playmats.php';
?>
<link rel="stylesheet" href="./FaBSim/Custom/IconTheme.css?v=<?= filemtime(__DIR__ . '/IconTheme.css') ?>">
<link rel="stylesheet" href="./FaBSim/Custom/Interaction.css?v=<?= filemtime(__DIR__ . '/Interaction.css') ?>">
<link rel="stylesheet" href="./FaBSim/Custom/Shortcuts.css?v=<?= filemtime(__DIR__ . '/Shortcuts.css') ?>">
<link rel="stylesheet" href="./FaBSim/Custom/TableControls.css?v=<?= filemtime(__DIR__ . '/TableControls.css') ?>">
<div id="fab-table">
<nav id="fab-table-tools" aria-label="Table controls">
  <a href="<?= $fabHomeHref ?>">Home</a>
  <div id="fab-events-mount"></div>
  <button type="button" id="fab-upf-home" hidden>All opponents</button>
  <button type="button" id="fabLayersToggle" data-fab-panel="stack" aria-controls="fabLayersWindow" aria-expanded="false">Stack <span id="fabLayersCount">0</span></button>
  <button type="button" id="fabCombatToggle" data-fab-panel="chain" aria-controls="fab-upf-chain-panel" aria-expanded="false">Combat chain <span id="fabCombatCount">0</span></button>
  <button type="button" data-fab-playmat-open aria-haspopup="dialog">Playmats</button>
  <button type="button" id="bug-report-button" onclick="openBugReportModal()">Report Bug</button>
</nav>
<div id="fab-selection-prompt"></div>
<p id="fab-priority-announcement" role="status"></p>
<?php include __DIR__ . '/MultiplayerLayout.php'; ?>
<div id="fab-duel-layout">
<style>
:root{
  --fab-gold:#d6aa4d;
  --fab-gold-soft:rgba(214,170,77,.3);
  --fab-panel:rgba(13,16,18,.88);
  --fab-slot:rgba(255,255,255,.045);
  --fab-line:rgba(255,255,255,.1);
  --fab-card-size:clamp(68px,4.8vw,92px);
}
html,body{margin:0;overflow:hidden;background:var(--fab-surface);color:#f3eee5;font-family:Inter,Segoe UI,sans-serif}
.fab-board{position:absolute;inset:0}
.fab-board-field{position:absolute;left:0;right:0;height:50%;pointer-events:none;box-sizing:border-box;background-size:cover;background-position:center;background-repeat:no-repeat}
.fab-board-opponent{top:0;border-bottom:1px solid var(--fab-field-line);background-color:var(--fab-field-cool);background-image:var(--fab-opponent-playmat,radial-gradient(ellipse at 50% 90%,var(--fab-light-cool),transparent 75%))}
.fab-board-local{bottom:0;background-color:var(--fab-field-warm);background-image:var(--fab-local-playmat,radial-gradient(ellipse at 50% 10%,var(--fab-light-warm),transparent 75%))}
#fab-duel-layout{
  --fab-hand-card:clamp(68px,5.2vw,98px);
  --fab-hand-inset:calc(var(--fab-hand-card) + 19px);
  --fab-arsenal-size:max(32px,calc(var(--fab-card-size)/2));
  --fab-hero-inset:max(20%,calc(var(--fab-hand-inset) + var(--fab-arsenal-size) + var(--fab-arsenal-gap)));
}

.fab-zone{position:absolute;z-index:20;box-sizing:border-box;width:calc(var(--fab-card-size) + 10px);height:calc(var(--fab-card-size) + 10px);border:1px solid var(--fab-line);border-radius:9px;background:var(--fab-slot);padding:16px 4px 4px;overflow:visible;box-shadow:inset 0 0 22px rgba(0,0,0,.16)}
.fab-zone:has([data-mzid]){border-color:transparent;background:transparent;box-shadow:none}
.fab-zone:has([data-mzid]):before{display:none}
.fab-zone:before{content:attr(data-label);position:absolute;z-index:2;top:4px;left:7px;color:rgba(241,231,211,.66);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.11em;pointer-events:none}
.fab-zone>div,.fab-zone>div>span{max-height:100%}
.fab-zone img{width:var(--fab-card-size)!important;height:var(--fab-card-size)!important;object-fit:cover!important;border-radius:5px!important;box-shadow:0 3px 10px rgba(0,0,0,.62)}
.fab-zone [id$="Wrapper"]{height:100%;overflow:visible!important}
.fab-zone [id^="my"],.fab-zone [id^="their"]{align-items:flex-end}
/* The slot already supplies its small uppercase title. Hide the renderer's plain label. */
.fab-zone:not(.fab-stat)>div>span>span:not([data-mzid]):not([id]){display:none}

/* Hands anchor the composition; cards fan across the center without large zone boxes. */
#myHandSlot,#theirHandSlot{left:31%;right:31%;width:auto;height:126px;padding-top:8px;border-color:transparent;background:transparent;box-shadow:none;z-index:24;pointer-events:none}
#myHandSlot [data-mzid],#theirHandSlot [data-mzid]{pointer-events:auto}
#myHandSlot{bottom:-50px}#theirHandSlot{top:8px}
#myHandSlot:before,#theirHandSlot:before{display:none}
#myHandWrapper,#theirHandWrapper{width:100%;height:100%;overflow:visible!important}
#myHand,#theirHand{color:transparent;font-size:0}
#myHand,#theirHand{display:flex!important;flex-direction:row!important;flex-wrap:nowrap!important;align-items:flex-start!important;justify-content:center!important;gap:clamp(3px,.45vw,8px);width:100%;height:100%;overflow:visible!important}
#myHand>[data-mzid],#theirHand>[data-mzid]{color:#f3eee5;font-size:initial}
#myHandSlot img,#theirHandSlot img{width:var(--fab-hand-card)!important;height:var(--fab-hand-card)!important}
#theirHandSlot img{filter:brightness(.72)}

/* Equipment uses one three-card column plus arms beside chest. P2 mirrors vertically. */
#myEquipmentSlot,#theirEquipmentSlot{width:calc(var(--fab-card-size)*2 + 20px);height:calc(var(--fab-card-size)*3 + 26px);padding:0;border:0;background:transparent;box-shadow:none}
#myEquipmentSlot{left:12px;bottom:4%}#theirEquipmentSlot{left:12px;top:4%}
#myEquipmentSlot:before,#theirEquipmentSlot:before{display:none}
#myEquipment,#theirEquipment{position:relative!important;display:block!important;width:100%;height:100%;overflow:visible}
#myEquipment,#theirEquipment{color:transparent;font-size:0}
#myEquipment>[data-mzid],#theirEquipment>[data-mzid]{position:absolute!important;margin:0!important}
#myEquipment>[data-mzid],#theirEquipment>[data-mzid]{color:#f3eee5;font-size:initial}
#myEquipment>[data-equipment-slot="head"]{top:0;left:0}
#myEquipment>[data-equipment-slot="chest"]{top:calc(var(--fab-card-size) + 8px);left:0}
#myEquipment>[data-equipment-slot="arms"]{top:calc(var(--fab-card-size) + 8px);left:calc(var(--fab-card-size) + 12px)}
#myEquipment>[data-equipment-slot="legs"]{top:calc(var(--fab-card-size)*2 + 16px);left:0}
#theirEquipment>[data-equipment-slot="legs"]{top:0;left:0}
#theirEquipment>[data-equipment-slot="chest"]{top:calc(var(--fab-card-size) + 8px);left:0}
#theirEquipment>[data-equipment-slot="arms"]{top:calc(var(--fab-card-size) + 8px);left:calc(var(--fab-card-size) + 12px)}
#theirEquipment>[data-equipment-slot="head"]{top:calc(var(--fab-card-size)*2 + 16px);left:0}

/* Hero in the middle, weapons flanking it, arsenal immediately beneath/above. */
#myHeroSlot,#theirHeroSlot{left:calc(50% - (var(--fab-card-size) + 10px)/2);z-index:23}
#theirHeroSlot{top:var(--fab-hero-inset)}#myHeroSlot{bottom:var(--fab-hero-inset)}
#myWeaponsSlot,#theirWeaponsSlot{left:calc(50% - 180px);width:360px;height:calc(var(--fab-card-size) + 10px);padding:8px 0 0;border:0;background:transparent;box-shadow:none}
#theirWeaponsSlot{top:var(--fab-hero-inset)}#myWeaponsSlot{bottom:var(--fab-hero-inset)}
#myWeaponsSlot:before,#theirWeaponsSlot:before{display:none}
#myWeapons,#theirWeapons{justify-content:space-between!important;align-items:center!important;width:100%;color:transparent;font-size:0}
#myWeapons>[data-mzid],#theirWeapons>[data-mzid]{color:#f3eee5;font-size:initial}
#myWeapons>[data-mzid]:only-child,#theirWeapons>[data-mzid]:only-child{margin-right:auto!important}
.fab-duel-arsenal{
  --fab-arsenal-inset:calc(var(--fab-hero-inset) - var(--fab-arsenal-size) - var(--fab-arsenal-gap));
  position:absolute;z-index:21;left:calc(50% - var(--fab-arsenal-size)/2)
}
#fab-their-arsenal{top:var(--fab-arsenal-inset)}
#fab-my-arsenal{bottom:var(--fab-arsenal-inset)}

/* Compact Arena rows sit beside the equipment, toward the center line. */
.fab-duel-arena{
  position:absolute;z-index:22;left:calc(var(--fab-card-size)*2 + 36px);right:max(55%,calc(50% + 187px));
  overflow:auto;scrollbar-width:thin
}
#fab-my-arena{top:calc(50% + 8px)}
#fab-their-arena{bottom:calc(50% + 8px)}
.fab-duel-arena [id$="Arena"]{
  width:max-content;min-width:100%;box-sizing:border-box;padding:3px;flex-wrap:nowrap!important;justify-content:flex-start!important
}

/* Equal-sized utility slots: graveyard / deck / banish, pitch beside deck. */
#myDeckSlot,#theirDeckSlot,#myGraveyardSlot,#theirGraveyardSlot,#myBanishSlot,#theirBanishSlot,#myPitchSlot,#theirPitchSlot,#myHeroSlot,#theirHeroSlot{padding:4px}
#myDeckSlot,#theirDeckSlot,#myGraveyardSlot,#theirGraveyardSlot,#myBanishSlot,#theirBanishSlot{right:12px}
#theirGraveyardSlot{top:4%}
#theirDeckSlot,#theirPitchSlot{top:calc(4% + var(--fab-card-size) + 18px)}
#theirBanishSlot{top:calc(4% + var(--fab-card-size)*2 + 36px)}
#myGraveyardSlot{bottom:calc(4% + var(--fab-card-size)*2 + 36px)}
#myDeckSlot,#myPitchSlot{bottom:calc(4% + var(--fab-card-size) + 18px)}
#myPitchSlot,#theirPitchSlot{right:calc(var(--fab-card-size) + 30px)}
#mySoulSlot{left:22%;bottom:4%}
#theirSoulSlot{left:22%;top:4%}
#myBanishSlot{bottom:4%}

/* Counters belong to the objects they describe instead of occupying board zones. */
.fab-stat{display:grid;place-items:center;width:36px;height:36px;padding:0;border:2px solid rgba(214,170,77,.75);border-radius:50%;background:rgba(7,9,10,.94);box-shadow:0 3px 12px rgba(0,0,0,.7);font-size:18px;font-weight:900;line-height:1}
.fab-stat:before{display:none}
.fab-stat>div{display:grid;place-items:center;width:100%;height:100%}
.fab-stat[data-life]:after{content:attr(data-life);display:grid;place-items:center;position:absolute;inset:0;color:#f4eee1;font-size:18px;font-weight:900;line-height:1}
#myHealthSlot{left:calc(50% - 20px);bottom:var(--fab-hero-inset);z-index:27}
#theirHealthSlot{left:calc(50% - 20px);top:calc(var(--fab-hero-inset) + var(--fab-card-size) - 15px);z-index:27}
#myChiSlot{right:calc(var(--fab-card-size)*2 + 28px);bottom:calc(4% + var(--fab-card-size) + 2px);z-index:27}
#theirChiSlot{right:calc(var(--fab-card-size)*2 + 28px);top:calc(4% + var(--fab-card-size)*2 - 28px);z-index:27}
#myResourcesSlot{right:calc(var(--fab-card-size)*1.5 + 15px);bottom:calc(4% + var(--fab-card-size)*1.5 + 3px);z-index:27}
#theirResourcesSlot{right:calc(var(--fab-card-size)*1.5 + 15px);top:calc(4% + var(--fab-card-size)*1.5 + 3px);z-index:27}
#myActionPointsSlot,#theirActionPointsSlot{position:absolute;right:calc(var(--fab-card-size) + 26px);z-index:27}
#myActionPointsSlot{bottom:calc(4% + (var(--fab-card-size) - 40px)/2)}
#theirActionPointsSlot{top:calc(4% + (var(--fab-card-size) - 40px)/2)}
#theirActionPointsSlot .widget-button-pass{display:none}

/* The duel stack window. Both layouts use the same combat-chain panel. */
.fab-floating-window{position:fixed;z-index:75;left:50%;top:31.5vh;transform:translateX(-50%);width:min(1480px,78vw);height:clamp(230px,34vh,310px);min-height:0;border:1px solid rgba(214,170,77,.48);border-radius:13px;background:linear-gradient(145deg,rgba(25,27,27,.98),rgba(9,12,14,.98));box-shadow:0 22px 70px rgba(0,0,0,.72);overflow:hidden;opacity:1;transition:opacity 120ms ease-out,visibility 0s linear 0s}
.fab-floating-window[hidden]{display:none}
body.fab-lunge-active .fab-floating-window:not([hidden]){opacity:0;visibility:hidden;pointer-events:none;transition:opacity 90ms ease-in,visibility 0s linear 90ms}
.fab-window-header{height:42px;display:flex;align-items:center;justify-content:space-between;padding:0 13px;border-bottom:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.035)}
.fab-window-title{font-size:19px;font-weight:800;letter-spacing:.01em}
.fab-window-subtitle{margin-left:8px;color:#a9a49a;font-size:11px;font-weight:500}
.fab-window-close{width:27px;height:27px;border:1px solid rgba(255,255,255,.13);border-radius:50%;background:#292b2b;color:#eee;cursor:pointer}
.fab-empty-layer{display:grid;place-items:center;min-height:142px;padding:20px;color:#9d9b96;text-align:center}
.fab-empty-layer strong{display:block;color:#ddd4c4;margin-bottom:7px}

@media (max-width:900px){
  :root{--fab-card-size:64px}
  #myHandSlot,#theirHandSlot{left:27%;right:27%}
  #myEquipmentSlot{left:4px;transform:scale(.78);transform-origin:left bottom}
  #theirEquipmentSlot{left:4px;transform:scale(.78);transform-origin:left top}
  #myWeaponsSlot,#theirWeaponsSlot{left:calc(50% - 145px);width:290px}
  .fab-duel-arena{left:calc(var(--fab-card-size) + 4px);right:max(55%,calc(50% + 150px))}
  .fab-floating-window{top:29vh;width:88vw;height:38vh}
}

@media (max-width:599px){
  #myActionPointsSlot{top:calc(50% + 8px);right:12px;bottom:auto}
  .fab-duel-arena{right:55%}
}

@media (max-height:650px){
  #fab-duel-layout{
    --fab-card-size:clamp(34px,11vh,68px);
    --fab-hand-card:clamp(36px,12vh,76px);
  }
  #myHandSlot{height:calc(var(--fab-hand-card) + 12px);bottom:0}
}

@media (prefers-reduced-motion:reduce){
  .fab-floating-window{transition:none}
}
</style>

<div class="fab-board" aria-hidden="true">
  <div class="fab-board-field fab-board-opponent"></div>
  <div class="fab-board-field fab-board-local"></div>
</div>
<?php
$zones = [
  'theirHand' => 'Hand', 'theirHero' => 'Hero',
  'theirWeapons' => 'Weapons', 'theirEquipment' => 'Equipment', 'theirDeck' => 'Deck',
  'theirGraveyard' => 'Graveyard', 'theirPitch' => 'Pitch',
  'theirSoul' => 'Soul', 'theirBanish' => 'Banish', 'myHand' => 'Hand', 'myHero' => 'Hero',
  'myWeapons' => 'Weapons', 'myEquipment' => 'Equipment', 'myDeck' => 'Deck',
  'myGraveyard' => 'Graveyard', 'myPitch' => 'Pitch', 'mySoul' => 'Soul', 'myBanish' => 'Banish',
];
foreach ($zones as $zone => $label) {
  echo '<div id="' . $zone . 'Slot" class="fab-zone" data-label="' . $label . '"><div id="' . $zone . '"></div></div>';
}
?>
<div id="fab-their-arsenal" class="fab-arsenal fab-duel-arsenal"></div>
<div id="fab-my-arsenal" class="fab-arsenal fab-duel-arsenal"></div>
<div id="fab-their-arena" class="fab-arena fab-duel-arena"></div>
<div id="fab-my-arena" class="fab-arena fab-duel-arena"></div>

<div id="myHealthSlot" class="fab-zone fab-stat" data-label="Life"><div id="myHealth"></div></div>
<div id="myChiSlot" title="Chi included in your total resources" class="fab-zone fab-stat" data-label="Chi"><div id="myChi"></div></div>
<div id="myResourcesSlot" class="fab-zone fab-stat" data-label="Pitch"><div id="myResources"></div></div>
<div id="myActionPointsSlot" class="fab-action-points"></div>
<div id="theirHealthSlot" class="fab-zone fab-stat" data-label="Life"><div id="theirHealth"></div></div>
<div id="theirChiSlot" title="Chi included in their total resources" class="fab-zone fab-stat" data-label="Chi"><div id="theirChi"></div></div>
<div id="theirResourcesSlot" class="fab-zone fab-stat" data-label="Pitch"><div id="theirResources"></div></div>
<div id="theirActionPointsSlot" class="fab-action-points"></div>

<section id="fabLayersWindow" class="fab-floating-window" aria-label="Active layers" hidden>
  <header class="fab-window-header">
    <div><span class="fab-window-title">Active Layers</span><span class="fab-window-subtitle">latest resolves first</span></div>
    <button class="fab-window-close" type="button" aria-label="Close active layers" data-fab-close="stack">×</button>
  </header>
  <div id="fabLayers" class="fab-empty-layer"><div><strong>No active layers</strong>Instants, triggers, and resolving abilities appear here.</div></div>
</section>
</div>
</div>
<?php
// NextTurn renders this layout before any game-state endpoint loads card data.
// Slot placement must use card types, not the equipment array order, which changes
// when bases transform into Evos or equipment returns from the combat chain.
require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
$fabEquipmentSlotByPrinting = [];
$fabEquipmentSlotByCardID = [];
if (function_exists('GetAllCardIds') && function_exists('CardTypes')) {
  foreach (GetAllCardIds() as $cardID) {
    $types = CardTypes($cardID);
    if (!is_array($types)) continue;
    foreach (['Head', 'Chest', 'Arms', 'Legs'] as $slotName) {
      if (in_array($slotName, $types, true)) {
        $fabEquipmentSlotByCardID[strtolower((string)$cardID)] = strtolower($slotName);
        if (function_exists('CardPrinting_id')) {
          $printingID = CardPrinting_id($cardID);
          if ($printingID !== null && $printingID !== '') {
            $fabEquipmentSlotByPrinting[strtoupper((string)$printingID)] = strtolower($slotName);
          }
        }
        break;
      }
    }
  }
}
?>
<script>
var FaBEquipmentSlotByPrinting = <?php echo json_encode($fabEquipmentSlotByPrinting, JSON_UNESCAPED_SLASHES); ?>;
var FaBEquipmentSlotByCardID = <?php echo json_encode($fabEquipmentSlotByCardID, JSON_UNESCAPED_SLASHES); ?>;
Object.assign(FaBEquipmentSlotByCardID, {
  mask_of_momentum:'head', helm_of_isens_peak:'head', arcanite_skullcap:'head', ironrot_helm:'head', hope_merchants_hood:'head',
  barkbone_strapping:'chest', tectonic_plating:'chest', courage_of_bladehold:'chest', fyendals_spring_tunic:'chest', ironrot_plate:'chest',
  breaking_scales:'arms', crater_fist:'arms', braveforge_bracers:'arms', goliath_gauntlet:'arms', ironrot_gauntlet:'arms', stubby_hammerers:'arms',
  scabskin_leathers:'legs', refraction_bolters:'legs', snapdragon_scalers:'legs', ironrot_legs:'legs', mage_master_boots:'legs'
});

function FaBEquipmentSlotFromImage(src) {
  var fileName = decodeURIComponent(String(src || '').split('/').pop().split('?')[0]);
  var cardID = fileName.replace(/\.[^.]+$/, '').toLowerCase();
  if (FaBEquipmentSlotByCardID[cardID]) return FaBEquipmentSlotByCardID[cardID];
  var printing = cardID.toUpperCase();
  if (FaBEquipmentSlotByPrinting[printing]) return FaBEquipmentSlotByPrinting[printing];
  while (printing.indexOf('-') !== -1) {
    printing = printing.substring(0, printing.lastIndexOf('-'));
    if (FaBEquipmentSlotByPrinting[printing]) return FaBEquipmentSlotByPrinting[printing];
  }
  return '';
}

function FaBArrangeEquipment(zoneID) {
  var zone = document.getElementById(zoneID);
  if (!zone) return;
  var cards = Array.prototype.slice.call(zone.querySelectorAll(':scope > [data-mzid]'));
  var fallback = ['head', 'arms', 'chest', 'legs'];
  cards.forEach(function(card, index) {
    var image = card.querySelector('img');
    var slot = FaBEquipmentSlotFromImage(image ? image.src : '') || fallback[index] || 'legs';
    card.dataset.equipmentSlot = slot;
    card.setAttribute('aria-label', slot.charAt(0).toUpperCase() + slot.slice(1) + ' equipment');
  });
}

function FaBRefreshLifeTotals() {
  document.querySelectorAll('.fab-stat').forEach(function(slot) {
    if (!slot.title) slot.title = slot.dataset.label === 'AP' ? 'Action points' : slot.dataset.label === 'Pitch' ? 'Available pitch resources' : slot.dataset.label;
  });
  [['myHealthSlot', window.myHealthData], ['theirHealthSlot', window.theirHealthData]].forEach(function(entry) {
    var slot = document.getElementById(entry[0]);
    if (slot) slot.dataset.life = String(entry[1] === undefined || entry[1] === null ? '' : entry[1]).trim();
  });
  FaBRefreshOptionalCounters();
}

function FaBRefreshOptionalCounters() {
  var seats = String(window.SeatOrderData || '12').match(/[1-4]/g) || ['1', '2'];
  var viewer = Number(document.getElementById('playerID')?.value);
  if (!viewer) viewer = Number(document.getElementById('viewerPerspective')?.value || 1);
  var opponent = Number(seats.find(function(seat) { return Number(seat) !== viewer; }));
  [['my', viewer], ['their', opponent]].forEach(function(entry) {
    var prefix = entry[0];
    var chi = document.getElementById(prefix + 'ChiSlot');
    if (chi) chi.hidden = !(Number(window[prefix + 'ChiData']) > 0);
    var soul = document.getElementById(prefix + 'SoulSlot');
    if (soul) soul.hidden = !String(window[prefix + 'SoulData'] || '').split('<|>').some(function(record) {
      var card = record.trim().split(' ')[0]; return card && card !== '-';
    });
    var actions = document.getElementById(prefix + 'ActionPointsSlot');
    // The generated Pass control lives inside this slot and must remain usable
    // in the opponent's reaction windows, even while the AP badge is invisible.
    if (actions) actions.classList.toggle('fab-ap-inactive', Number(window.TurnPlayerData) !== entry[1]);
  });
}

function FaBRefreshLungeVisibility() {
  document.body.classList.toggle('fab-lunge-active', !!document.querySelector('.tcg-card-lunge-clone'));
}

window.SelectionMessageSettings = {
  embedTargetId: 'fab-selection-prompt',
  shouldEmbed: function() {
    return window.SelectionMode?.inlineSpecs?.some(spec => /(?:Hand|Arsenal)$/.test(spec.zone));
  }
};
// Reuse the engine's selection-view hooks for the duel's my/their zone aliases.
// UPF renders absolute seat zones directly and needs no translation.
window.swuTwNormalizeSelection = function(specs) {
  var seats = String(window.SeatOrderData || '12').match(/[1-4]/g) || [];
  var input = document.getElementById('playerID');
  var viewer = input ? String(input.value) : '';
  if (seats.length !== 2 || !seats.includes(viewer)) return {inlineNormalized: specs, offViewSpecs: []};
  return {inlineNormalized: specs.map(function(spec) {
    var match = spec.zone && spec.zone.match(/^p([1-4])(.+)$/);
    if (!match || !seats.includes(match[1])) return spec;
    return Object.assign({}, spec, {zone: (match[1] === viewer ? 'my' : 'their') + match[2]});
  }), offViewSpecs: []};
};
window.swuTwRemapCardId = function(cardId) {
  var seats = String(window.SeatOrderData || '12').match(/[1-4]/g) || [];
  var input = document.getElementById('playerID');
  var viewer = input ? String(input.value) : '';
  if (seats.length !== 2 || !seats.includes(viewer)) return cardId;
  return cardId.replace(/^(my|their)/, function(prefix) {
    return 'p' + (prefix === 'my' ? viewer : seats.find(function(seat) { return seat !== viewer; }));
  });
};

document.addEventListener('DOMContentLoaded', function() {
  ['myEquipmentSlot', 'theirEquipmentSlot'].forEach(function(slotID) {
    var slot = document.getElementById(slotID);
    if (slot) new MutationObserver(function() { FaBArrangeEquipment(slotID.replace('Slot', '')); }).observe(slot, {childList:true, subtree:true});
  });
  ['myHealthSlot', 'theirHealthSlot', 'myChiSlot', 'theirChiSlot', 'mySoulSlot', 'theirSoulSlot', 'myActionPointsSlot', 'theirActionPointsSlot'].forEach(function(slotID) {
    var healthSlot = document.getElementById(slotID);
    if (healthSlot) new MutationObserver(FaBRefreshLifeTotals).observe(healthSlot, {childList:true, subtree:true});
  });
  new MutationObserver(FaBRefreshLungeVisibility).observe(document.body, {childList:true});
  FaBArrangeEquipment('myEquipment');
  FaBArrangeEquipment('theirEquipment');
  FaBRefreshLifeTotals();
  FaBRefreshLungeVisibility();
});
</script>
</div>
