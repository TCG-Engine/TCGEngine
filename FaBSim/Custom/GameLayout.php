<style>
:root{
  --fab-gold:#d6aa4d;
  --fab-gold-soft:rgba(214,170,77,.3);
  --fab-panel:rgba(13,16,18,.88);
  --fab-slot:rgba(255,255,255,.045);
  --fab-line:rgba(255,255,255,.1);
  --fab-card-size:clamp(68px,4.8vw,92px);
}
html,body{margin:0;overflow:hidden;background:#090d0f;color:#f3eee5;font-family:Inter,Segoe UI,sans-serif}
.fab-board{position:fixed;inset:48px 0 0;background:
  linear-gradient(180deg,transparent calc(50% - 2px),rgba(211,170,78,.13) calc(50% - 1px),rgba(211,170,78,.13) calc(50% + 1px),transparent calc(50% + 2px)),
  radial-gradient(ellipse at 50% 50%,rgba(49,72,71,.42),transparent 55%),
  linear-gradient(135deg,#111a1d,#071014 48%,#11191a);
  border-top:1px solid rgba(214,170,77,.18)
}
.fab-board:before,.fab-board:after{content:"";position:absolute;left:0;right:0;height:50%;pointer-events:none}
.fab-board:before{top:0;background:linear-gradient(180deg,rgba(0,0,0,.34),transparent 55%)}
.fab-board:after{bottom:0;background:linear-gradient(0deg,rgba(0,0,0,.3),transparent 55%)}

.fab-zone{position:fixed;z-index:20;box-sizing:border-box;width:calc(var(--fab-card-size) + 10px);height:calc(var(--fab-card-size) + 10px);border:1px solid var(--fab-line);border-radius:9px;background:var(--fab-slot);padding:16px 4px 4px;overflow:visible;box-shadow:inset 0 0 22px rgba(0,0,0,.16)}
.fab-zone:has([data-mzid]){border-color:transparent;background:transparent;box-shadow:none}
.fab-zone:has([data-mzid]):before{display:none}
.fab-zone:before{content:attr(data-label);position:absolute;z-index:2;top:4px;left:7px;color:rgba(241,231,211,.66);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.11em;pointer-events:none}
.fab-zone>div,.fab-zone>div>span{max-height:100%}
.fab-zone img{width:var(--fab-card-size)!important;height:var(--fab-card-size)!important;object-fit:cover!important;border-radius:5px!important;box-shadow:0 3px 10px rgba(0,0,0,.62)}
.fab-zone [id$="Wrapper"]{height:100%;overflow:visible!important}
.fab-zone [id^="my"],.fab-zone [id^="their"]{align-items:flex-end}
#chatWidget{top:5px!important;bottom:auto!important;left:70px!important;z-index:90!important}

/* Hands anchor the composition; cards fan across the center without large zone boxes. */
#myHandSlot,#theirHandSlot{left:31%;right:31%;width:auto;height:126px;padding-top:8px;border-color:transparent;background:transparent;box-shadow:none}
#myHandSlot{bottom:-50px}#theirHandSlot{top:8px}
#myHandSlot:before,#theirHandSlot:before{display:none}
#myHandWrapper,#theirHandWrapper{width:100%;height:100%;overflow:visible!important}
#myHand,#theirHand{color:transparent;font-size:0}
#myHand,#theirHand{display:flex!important;flex-direction:row!important;flex-wrap:nowrap!important;align-items:flex-start!important;justify-content:center!important;gap:clamp(3px,.45vw,8px);width:100%;height:100%;overflow:visible!important}
#myHand>[data-mzid],#theirHand>[data-mzid]{color:#f3eee5;font-size:initial}
#myHandSlot img,#theirHandSlot img{width:clamp(68px,5.2vw,98px)!important;height:clamp(68px,5.2vw,98px)!important}
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
#theirHeroSlot{top:20%}#myHeroSlot{bottom:20%}
#myWeaponsSlot,#theirWeaponsSlot{left:calc(50% - 180px);width:360px;height:calc(var(--fab-card-size) + 10px);padding:8px 0 0;border:0;background:transparent;box-shadow:none}
#theirWeaponsSlot{top:20%}#myWeaponsSlot{bottom:20%}
#myWeaponsSlot:before,#theirWeaponsSlot:before{display:none}
#myWeapons,#theirWeapons{justify-content:space-between!important;align-items:center!important;width:100%;color:transparent;font-size:0}
#myWeapons>[data-mzid],#theirWeapons>[data-mzid]{color:#f3eee5;font-size:initial}
#myWeapons>[data-mzid]:only-child,#theirWeapons>[data-mzid]:only-child{margin-right:auto!important}
#myArsenalSlot,#theirArsenalSlot{left:calc(50% - (var(--fab-card-size) + 10px)/2)}
#theirArsenalSlot{top:5%}#myArsenalSlot{bottom:5%}

/* Allies and arena permanents sit just off the central character column. */
#myArenaSlot,#theirArenaSlot{width:22%;height:calc(var(--fab-card-size) + 10px);border-color:rgba(255,255,255,.055);background:rgba(1,8,10,.17)}
#myArenaSlot:not(:has([data-mzid])),#theirArenaSlot:not(:has([data-mzid])){width:calc(var(--fab-card-size) + 10px)}
#theirArenaSlot{right:52%;top:34%}#myArenaSlot{left:52%;bottom:34%}

/* Utility mirrors equipment: banish/pitch/graveyard in one column, deck beside pitch. */
#theirBanishSlot{right:calc(var(--fab-card-size) + 24px);top:4%}
#theirPitchSlot{right:calc(var(--fab-card-size) + 24px);top:calc(4% + var(--fab-card-size) + 8px)}
#theirDeckSlot{right:12px;top:calc(4% + var(--fab-card-size) + 8px)}
#theirGraveyardSlot{right:calc(var(--fab-card-size) + 24px);top:calc(4% + var(--fab-card-size)*2 + 16px)}
#myGraveyardSlot{right:calc(var(--fab-card-size) + 24px);bottom:calc(4% + var(--fab-card-size)*2 + 16px)}
#myPitchSlot{right:calc(var(--fab-card-size) + 24px);bottom:calc(4% + var(--fab-card-size) + 8px)}
#myDeckSlot{right:12px;bottom:calc(4% + var(--fab-card-size) + 8px)}
#myBanishSlot{right:calc(var(--fab-card-size) + 24px);bottom:4%}

/* Counters belong to the objects they describe instead of occupying board zones. */
.fab-stat{display:grid;place-items:center;width:36px;height:36px;padding:0;border:2px solid rgba(214,170,77,.75);border-radius:50%;background:rgba(7,9,10,.94);box-shadow:0 3px 12px rgba(0,0,0,.7);font-size:18px;font-weight:900;line-height:1}
.fab-stat:before{display:none}
.fab-stat>div{display:grid;place-items:center;width:100%;height:100%}
.fab-stat[data-life]:after{content:attr(data-life);display:grid;place-items:center;position:absolute;inset:0;color:#f4eee1;font-size:18px;font-weight:900;line-height:1}
#myHealthSlot{left:calc(50% + var(--fab-card-size)/2 - 8px);bottom:calc(20% - 8px);z-index:27}
#theirHealthSlot{left:calc(50% + var(--fab-card-size)/2 - 8px);top:calc(20% + var(--fab-card-size) - 28px);z-index:27}
#myResourcesSlot{right:calc(var(--fab-card-size) + 14px);bottom:calc(4% + var(--fab-card-size) + 2px);z-index:27}
#theirResourcesSlot{right:calc(var(--fab-card-size) + 14px);top:calc(4% + var(--fab-card-size)*2 - 28px);z-index:27}
#myActionPointsSlot,#theirActionPointsSlot{width:44px;height:44px;border-radius:10px;font-size:18px;z-index:27}
#myActionPointsSlot{right:calc(var(--fab-card-size)*2 + 39px);bottom:calc(4% + var(--fab-card-size)/2 - 14px)}
#theirActionPointsSlot{right:calc(var(--fab-card-size)*2 + 39px);top:calc(4% + var(--fab-card-size)/2 - 14px)}
#theirActionPointsSlot .widget-button-pass{display:none!important}
.fab-stat .widget-button-pass{position:fixed;right:18px;top:calc(50% + 10px);width:104px;padding:9px 12px;border:1px solid #cf7376;border-radius:8px;background:#8c1d23;font-size:13px;font-weight:800;box-shadow:0 4px 14px rgba(0,0,0,.45)}

/* One shared combat-chain window, fed by both controller-specific serialized arrays. */
.fab-overlay-toolbar{position:fixed;z-index:72;top:55px;left:50%;transform:translateX(-50%);display:flex;gap:7px}
.fab-overlay-button{border:1px solid var(--fab-gold-soft);border-radius:7px;background:rgba(11,14,16,.9);color:#eee5d4;padding:7px 12px;font:700 12px/1 Inter,Segoe UI,sans-serif;letter-spacing:.04em;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,.3)}
.fab-overlay-button:hover,.fab-overlay-button[aria-expanded="true"]{color:#17130c;background:var(--fab-gold);border-color:#f0cb7b}
.fab-overlay-button .fab-count{display:inline-grid;place-items:center;min-width:17px;height:17px;margin-left:5px;border-radius:9px;background:rgba(255,255,255,.14);font-size:10px}
.fab-floating-window{position:fixed;z-index:75;left:50%;top:31.5vh;transform:translateX(-50%);width:min(1480px,78vw);height:clamp(230px,34vh,310px);min-height:0;border:1px solid rgba(214,170,77,.48);border-radius:13px;background:linear-gradient(145deg,rgba(25,27,27,.98),rgba(9,12,14,.98));box-shadow:0 22px 70px rgba(0,0,0,.72);overflow:hidden;opacity:1;transition:opacity 120ms ease-out,visibility 0s linear 0s}
.fab-floating-window[hidden]{display:none}
body.fab-lunge-active .fab-floating-window:not([hidden]){opacity:0;visibility:hidden;pointer-events:none;transition:opacity 90ms ease-in,visibility 0s linear 90ms}
.fab-window-header{height:42px;display:flex;align-items:center;justify-content:space-between;padding:0 13px;border-bottom:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.035)}
.fab-window-title{font-size:19px;font-weight:800;letter-spacing:.01em}
.fab-window-subtitle{margin-left:8px;color:#a9a49a;font-size:11px;font-weight:500}
.fab-window-close{width:27px;height:27px;border:1px solid rgba(255,255,255,.13);border-radius:50%;background:#292b2b;color:#eee;cursor:pointer}
.fab-chain-close{position:absolute;z-index:4;top:7px;right:8px;width:24px;height:24px;font-size:14px}
.fab-combat-progress{display:grid;grid-template-columns:repeat(7,minmax(58px,82px));justify-content:center;gap:5px;padding:7px 40px 6px;border-bottom:1px solid rgba(255,255,255,.07);background:rgba(0,0,0,.18)}
.fab-combat-step{position:relative;display:flex;align-items:center;justify-content:center;gap:5px;min-width:0;height:28px;padding:0 6px;border:1px solid rgba(255,255,255,.08);border-radius:15px;color:#767a78;text-align:center;font-size:8px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;transition:background .16s,border-color .16s,color .16s,transform .16s,box-shadow .16s}
.fab-step-glyph{display:grid;place-items:center;width:16px;height:16px;border:1px solid rgba(255,255,255,.12);border-radius:50%;color:#9b9d99;font-size:10px;line-height:1}
.fab-combat-step:not(:last-child):after{content:"";position:absolute;z-index:2;top:50%;right:-6px;width:7px;height:1px;background:rgba(255,255,255,.14)}
.fab-combat-step.is-complete{color:#aaa89f;border-color:rgba(214,170,77,.18);background:rgba(214,170,77,.055)}
.fab-combat-step.is-complete .fab-step-glyph{color:#69c887;border-color:rgba(105,200,135,.4)}
.fab-combat-step.is-active{color:#fff3d5;border-color:#f0cb7b;background:rgba(214,170,77,.2);transform:translateY(-1px);box-shadow:0 0 7px rgba(240,203,123,.8),0 0 19px rgba(214,170,77,.4),inset 0 0 9px rgba(240,203,123,.12)}
.fab-combat-step.is-active .fab-step-glyph{color:#17130c;border-color:#f4d995;background:#edc45f;box-shadow:0 0 8px rgba(240,203,123,.85)}
.fab-combat-status{min-height:15px;padding:4px 38px 2px;color:#c9c3b7;font-size:10px;text-align:center}
.fab-combat-status strong{color:#f1d17c}
.fab-chain-flow{position:relative;display:flex;align-items:center;justify-content:center;gap:0;height:calc(100% - 64px);min-height:148px;padding:6px 38px 12px;box-sizing:border-box}
.fab-chain-side{display:flex;align-items:center;min-width:0;color:transparent;font-size:0}
.fab-chain-side:empty{display:none}
.fab-chain-side [data-mzid]{position:relative!important;margin:0 17px!important;color:#f3eee5;font-size:initial}
.fab-chain-side [data-mzid]:before,.fab-chain-side [data-mzid].fab-chain-last:after{content:"";position:absolute;z-index:-1;top:50%;width:29px;height:15px;transform:translateY(-50%);background:radial-gradient(ellipse at 31% 50%,transparent 0 4px,rgba(205,166,91,.78) 4.5px 6px,transparent 6.5px),radial-gradient(ellipse at 69% 50%,transparent 0 4px,rgba(205,166,91,.78) 4.5px 6px,transparent 6.5px);filter:drop-shadow(0 1px 2px rgba(0,0,0,.9));pointer-events:none}
.fab-chain-side [data-mzid]:before{left:-32px}
.fab-chain-side [data-mzid].fab-chain-last:after{right:-32px}
#myCombatChainSlot,#theirCombatChainSlot{position:relative;inset:auto;width:auto;height:auto;min-height:112px;min-width:0;padding:0;border:0;background:transparent;box-shadow:none;overflow:visible}
#myCombatChainSlot:before,#theirCombatChainSlot:before{display:none}
#myCombatChainWrapper,#theirCombatChainWrapper,#myCombatChain,#theirCombatChain{width:auto!important;height:auto!important;overflow:visible!important}
#myCombatChainSlot img,#theirCombatChainSlot img{width:92px!important;height:92px!important;aspect-ratio:1/1!important;object-fit:cover!important}
#myCombatChainSlot [data-mzid],#theirCombatChainSlot [data-mzid],#myCombatChainSlot a,#theirCombatChainSlot a{width:92px!important;height:92px!important;vertical-align:top}
.fab-empty-layer{display:grid;place-items:center;min-height:142px;padding:20px;color:#9d9b96;text-align:center}
.fab-empty-layer strong{display:block;color:#ddd4c4;margin-bottom:7px}

@media (max-width:900px){
  :root{--fab-card-size:64px}
  #myHandSlot,#theirHandSlot{left:27%;right:27%}
  #myEquipmentSlot{left:4px;transform:scale(.78);transform-origin:left bottom}
  #theirEquipmentSlot{left:4px;transform:scale(.78);transform-origin:left top}
  #myWeaponsSlot,#theirWeaponsSlot{left:calc(50% - 145px);width:290px}
  .fab-floating-window{top:29vh;width:88vw;height:38vh}
  .fab-combat-progress{grid-template-columns:repeat(7,34px);gap:3px;padding-left:34px;padding-right:34px}
  .fab-combat-step{padding:0}.fab-step-label{display:none}
}

@media (prefers-reduced-motion:reduce){
  .fab-floating-window{transition:none}
}
</style>

<div class="fab-board" aria-hidden="true"></div>
<?php
$zones = [
  'theirHand' => 'Hand', 'theirArena' => 'Arena', 'theirHero' => 'Hero',
  'theirWeapons' => 'Weapons', 'theirEquipment' => 'Equipment', 'theirDeck' => 'Deck',
  'theirGraveyard' => 'Graveyard', 'theirArsenal' => 'Arsenal', 'theirPitch' => 'Pitch',
  'theirBanish' => 'Banish', 'myHand' => 'Hand', 'myArena' => 'Arena', 'myHero' => 'Hero',
  'myWeapons' => 'Weapons', 'myEquipment' => 'Equipment', 'myDeck' => 'Deck',
  'myGraveyard' => 'Graveyard', 'myArsenal' => 'Arsenal', 'myPitch' => 'Pitch', 'myBanish' => 'Banish',
];
foreach ($zones as $zone => $label) {
  echo '<div id="' . $zone . 'Slot" class="fab-zone" data-label="' . $label . '"><div id="' . $zone . '"></div></div>';
}
?>

<div id="myHealthSlot" class="fab-zone fab-stat" data-label="Life"><div id="myHealth"></div></div>
<div id="myResourcesSlot" class="fab-zone fab-stat" data-label="Pitch"><div id="myResources"></div></div>
<div id="myActionPointsSlot" class="fab-zone fab-stat" data-label="AP"><div id="myActionPoints"></div></div>
<div id="theirHealthSlot" class="fab-zone fab-stat" data-label="Life"><div id="theirHealth"></div></div>
<div id="theirResourcesSlot" class="fab-zone fab-stat" data-label="Pitch"><div id="theirResources"></div></div>
<div id="theirActionPointsSlot" class="fab-zone fab-stat" data-label="AP"><div id="theirActionPoints"></div></div>

<div class="fab-overlay-toolbar" aria-label="Shared game windows">
  <button id="fabCombatToggle" class="fab-overlay-button" type="button" aria-expanded="false" onclick="FaBToggleWindow('fabCombatWindow')">Combat Chain <span id="fabCombatCount" class="fab-count">0</span></button>
  <button id="fabLayersToggle" class="fab-overlay-button" type="button" aria-expanded="false" onclick="FaBToggleWindow('fabLayersWindow')">Layers <span id="fabLayersCount" class="fab-count">0</span></button>
</div>

<section id="fabCombatWindow" class="fab-floating-window" aria-label="Combat chain" hidden>
  <button class="fab-window-close fab-chain-close" type="button" aria-label="Close combat chain" onclick="FaBToggleWindow('fabCombatWindow', false)">×</button>
  <div id="fabCombatProgress" class="fab-combat-progress" aria-label="Combat progress">
    <div class="fab-combat-step" data-fab-step="LAYER"><span class="fab-step-glyph">✦</span><span class="fab-step-label">Layer</span></div>
    <div class="fab-combat-step" data-fab-step="ATTACK"><span class="fab-step-glyph">▶</span><span class="fab-step-label">Attack</span></div>
    <div class="fab-combat-step" data-fab-step="DEFEND"><span class="fab-step-glyph">◆</span><span class="fab-step-label">Defend</span></div>
    <div class="fab-combat-step" data-fab-step="REACTION"><span class="fab-step-glyph">↯</span><span class="fab-step-label">React</span></div>
    <div class="fab-combat-step" data-fab-step="DAMAGE"><span class="fab-step-glyph">✹</span><span class="fab-step-label">Damage</span></div>
    <div class="fab-combat-step" data-fab-step="RESOLUTION"><span class="fab-step-glyph">✓</span><span class="fab-step-label">Resolve</span></div>
    <div class="fab-combat-step" data-fab-step="CLOSE"><span class="fab-step-glyph">×</span><span class="fab-step-label">Close</span></div>
  </div>
  <div id="fabCombatStatus" class="fab-combat-status">Waiting for an attack.</div>
  <div class="fab-chain-flow">
    <div id="myCombatChainSlot" class="fab-chain-side"><div id="myCombatChain"></div></div>
    <div id="theirCombatChainSlot" class="fab-chain-side"><div id="theirCombatChain"></div></div>
  </div>
</section>

<section id="fabLayersWindow" class="fab-floating-window" aria-label="Active layers" hidden>
  <header class="fab-window-header">
    <div><span class="fab-window-title">Active Layers</span><span class="fab-window-subtitle">latest resolves first</span></div>
    <button class="fab-window-close" type="button" aria-label="Close active layers" onclick="FaBToggleWindow('fabLayersWindow', false)">×</button>
  </header>
  <div id="fabLayers" class="fab-empty-layer"><div><strong>No active layers</strong>Instants, triggers, and resolving abilities appear here.</div></div>
</section>

<?php
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
  breaking_scales:'arms', crater_fist:'arms', braveforge_bracers:'arms', goliath_gauntlet:'arms', ironrot_gauntlet:'arms',
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
  [['myHealthSlot', window.myHealthData], ['theirHealthSlot', window.theirHealthData]].forEach(function(entry) {
    var slot = document.getElementById(entry[0]);
    if (slot) slot.dataset.life = String(entry[1] === undefined || entry[1] === null ? '' : entry[1]).trim();
  });
}

function FaBRefreshLungeVisibility() {
  document.body.classList.toggle('fab-lunge-active', !!document.querySelector('.tcg-card-lunge-clone'));
}

function FaBToggleWindow(id, forceOpen) {
  var panel = document.getElementById(id);
  if (!panel) return;
  var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : panel.hidden;
  if (shouldOpen) {
    var otherID = id === 'fabCombatWindow' ? 'fabLayersWindow' : 'fabCombatWindow';
    var otherPanel = document.getElementById(otherID);
    if (otherPanel) otherPanel.hidden = true;
    var otherToggle = document.getElementById(otherID === 'fabCombatWindow' ? 'fabCombatToggle' : 'fabLayersToggle');
    if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
  }
  panel.hidden = !shouldOpen;
  var toggle = document.getElementById(id === 'fabCombatWindow' ? 'fabCombatToggle' : 'fabLayersToggle');
  if (toggle) toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
}

function FaBRefreshSharedWindows() {
  var combatWindow = document.getElementById('fabCombatWindow');
  if (!combatWindow) return;
  var chainCards = Array.prototype.slice.call(combatWindow.querySelectorAll('[data-mzid]'));
  chainCards.forEach(function(card) { card.classList.remove('fab-chain-last'); });
  if (chainCards.length) chainCards[chainCards.length - 1].classList.add('fab-chain-last');
  var count = chainCards.length;
  var badge = document.getElementById('fabCombatCount');
  if (badge) badge.textContent = String(count);
  var oldCount = Number(combatWindow.dataset.cardCount || 0);
  combatWindow.dataset.cardCount = String(count);
  if (count > 0 && oldCount === 0) FaBToggleWindow('fabCombatWindow', true);
  if (count === 0 && oldCount > 0) FaBToggleWindow('fabCombatWindow', false);
  FaBRefreshCombatProgress();
}

function FaBReadCombatState() {
  var raw = window.GameStateData;
  if (raw && typeof raw === 'object') raw = raw.Value !== undefined ? raw.Value : raw.value;
  if (typeof raw !== 'string' || raw.trim() === '') return {};
  try { return JSON.parse(raw); } catch (_) {}
  try { return JSON.parse(decodeURIComponent(raw)); } catch (_) { return {}; }
}

function FaBRefreshCombatProgress() {
  var state = FaBReadCombatState();
  var active = String(state.combatStep || 'NONE').toUpperCase();
  var order = ['LAYER', 'ATTACK', 'DEFEND', 'REACTION', 'DAMAGE', 'RESOLUTION', 'CLOSE'];
  var activeIndex = order.indexOf(active);
  document.querySelectorAll('[data-fab-step]').forEach(function(node) {
    var index = order.indexOf(node.dataset.fabStep);
    node.classList.toggle('is-active', index === activeIndex);
    node.classList.toggle('is-complete', activeIndex > index);
  });

  var status = document.getElementById('fabCombatStatus');
  if (!status) return;
  var player = Number(window.PriorityPlayerData || 0);
  var attackName = String(state.lastAttackName || 'Attack');
  var windowName = String(state.window || '').toUpperCase();
  var messages = {
    LAYER: '<strong>' + attackName + '</strong> is on the stack. Players may respond with instants.',
    ATTACK: '<strong>' + attackName + '</strong> became attacking. Player ' + player + ' has priority.',
    DEFEND: windowName === 'DEFEND_DECLARE'
      ? 'Player ' + Number(state.defender || player) + ': declare any number of legal defending cards, then Pass.'
      : 'Defenders are locked in (' + Number(state.defenseValue || 0) + ' defense). Player ' + player + ' has priority.',
    REACTION: 'Reaction step: Player ' + player + ' has priority for legal reactions and instants.',
    DAMAGE: Number(state.attackPower || 0) + ' attack − ' + Number(state.defenseValue || 0) + ' defense = <strong>' + Number(state.damageDealt || 0) + ' damage</strong>.',
    RESOLUTION: 'Chain link ' + Number(state.chainLink || 0) + ' resolved. Play another attack or pass to close the chain.',
    CLOSE: 'The combat chain is closing and remaining cards are returning to their rules-defined zones.'
  };
  status.innerHTML = messages[active] || 'Waiting for an attack.';
  var panel = document.getElementById('fabCombatWindow');
  if (panel) panel.dataset.combatStep = active;
}

window.RenderFaBLayers = function(html, count) {
  var layers = document.getElementById('fabLayers');
  var badge = document.getElementById('fabLayersCount');
  var layerCount = Math.max(0, Number(count) || 0);
  if (layers) layers.innerHTML = html || '<div><strong>No active layers</strong>Instants, triggers, and resolving abilities appear here.</div>';
  if (badge) badge.textContent = String(layerCount);
  FaBToggleWindow('fabLayersWindow', layerCount > 0);
};

document.addEventListener('DOMContentLoaded', function() {
  var combatWindow = document.getElementById('fabCombatWindow');
  if (!combatWindow) return;
  ['myCombatChainSlot', 'theirCombatChainSlot'].forEach(function(slotID) {
    var chainSlot = document.getElementById(slotID);
    if (chainSlot) new MutationObserver(FaBRefreshSharedWindows).observe(chainSlot, {childList:true, subtree:true});
  });
  ['myEquipmentSlot', 'theirEquipmentSlot'].forEach(function(slotID) {
    var slot = document.getElementById(slotID);
    if (slot) new MutationObserver(function() { FaBArrangeEquipment(slotID.replace('Slot', '')); }).observe(slot, {childList:true, subtree:true});
  });
  ['myHealthSlot', 'theirHealthSlot'].forEach(function(slotID) {
    var healthSlot = document.getElementById(slotID);
    if (healthSlot) new MutationObserver(FaBRefreshLifeTotals).observe(healthSlot, {childList:true, subtree:true});
  });
  new MutationObserver(FaBRefreshLungeVisibility).observe(document.body, {childList:true});
  FaBArrangeEquipment('myEquipment');
  FaBArrangeEquipment('theirEquipment');
  FaBRefreshSharedWindows();
  FaBRefreshLifeTotals();
  FaBRefreshLungeVisibility();
});
</script>
