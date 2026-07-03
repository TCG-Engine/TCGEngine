<?php
// GameLayout.php — SWUDeck deck-builder board layout (desktop/tablet).
// Emits the STATIC positioned zone slots; NextTurnRender.php fills each slot's
// inner `<zone>Wrapper` by id (the SWUSim slot model). Phones are routed to the
// vertical-stack layout in GameLayoutMobile.php.
//
// Slot coordinates reproduce the historical absolute-percent layout that used to be
// baked inline in NextTurnRender.php, so desktop rendering is unchanged.
require_once __DIR__ . '/GameLayoutDevice.php';

// Signal the shared UILibraries to skip its legacy MobileDeckEditorLayout() JS reflow:
// SWUDeck now lays itself out natively per-device in PHP, so the reflow would fight it.
echo("<script>window.SWUDeckSlotLayout = true;</script>");

// Chamfered cyan-HUD buttons for the deck builder — the same visual language as the
// SWUSim board (recipe 2: closed chamfer drawn with two negative-z pseudos, so it works
// on engine/generated buttons we can't add a <span> to). Emitted BEFORE the mobile
// routing so both desktop and mobile pick it up. SWUDeck-scoped (only loads on this page).
echo(<<<'HTML'
<style>
  /* Base: strip the stock look, draw the chamfer with ::before (cyan rim) + ::after (fill). */
  .widget-button, .widget-button-selected, .panelTab,
  .flex-container > .flex-item:first-child button {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; clip-path: none !important;
    padding: 4px 11px !important; margin: 2px 3px !important;
    color: rgba(205,238,255,0.96) !important; font-weight: 600 !important;
    text-transform: uppercase !important; letter-spacing: 0.05em !important;
    text-shadow: 0 0 5px rgba(120,200,255,0.35) !important;
    filter: drop-shadow(0 0 3px rgba(110,190,255,0.30)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
    cursor: pointer !important;
  }
  .widget-button::before, .widget-button-selected::before, .panelTab::before,
  .flex-container > .flex-item:first-child button::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px) !important;
    background: rgba(140,210,255,0.80) !important;
  }
  .widget-button::after, .widget-button-selected::after, .panelTab::after,
  .flex-container > .flex-item:first-child button::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px) !important;
    background: rgba(20,42,70,0.95) !important;
  }
  /* Hover — brighter rim + lift */
  .widget-button:hover, .panelTab:hover,
  .flex-container > .flex-item:first-child button:hover {
    color: #fff !important; filter: drop-shadow(0 0 8px rgba(125,205,255,0.6)) !important; transform: translateY(-1px) !important;
  }
  .widget-button:hover::before, .panelTab:hover::before,
  .flex-container > .flex-item:first-child button:hover::before { background: rgba(180,228,255,1) !important; }
  /* Selected (active sort/stat) — bright rim, slightly lit fill */
  .widget-button-selected { color: #fff !important; }
  .widget-button-selected::before { background: rgba(180,228,255,1) !important; }
  .widget-button-selected::after  { background: rgba(30,64,104,0.96) !important; }
  /* Press-in */
  .widget-button:active { transform: translateY(1px) !important; }
  .widget-button:active::after,
  .flex-container > .flex-item:first-child button:active::after { background: rgba(12,26,46,0.98) !important; }
  /* Sort control — cyan-HUD SKIN over the base widget dropdown. Structure + neutral default
     live in Core/UILibraries (reusable by any app); here we only re-color the popup so it
     matches the visibility/version menus. Trigger chamfer comes from the .widget-button rule
     above. !important so this wins over the base regardless of stylesheet order. */
  #mySortWrapper, #mySortSlot { overflow: visible !important; }  /* let the popup escape the zone */
  .widget-dd-menu {
    background: rgba(14,26,44,0.98) !important; border: 1px solid rgba(140,210,255,0.55) !important;
    border-radius: 0 !important; box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(120,200,255,0.22) !important;
  }
  .widget-dd-item { color: rgba(205,238,255,0.92) !important; }
  .widget-dd-item:hover { background: rgba(30,64,104,0.9) !important; }
  .widget-dd-item.is-active { color: #fff !important; }
  /* Toolbar buttons: uniform height. Plain buttons (Home/Edit/Stats/Print/Refresh) are
     direct flex children and stretch to ~41px; the dropdown triggers (Private / Current
     Version) sit inside inline-block wrappers and don't, so they came out ~25px. Pin a
     single height + vertically center content so they all match. */
  .flex-container > .flex-item:first-child button {
    height: 30px !important; align-self: center !important; box-sizing: border-box !important;
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
  }
  /* Dropdown menus (visibility + version popups) — cyan-HUD panel to match the buttons. */
  #visibilityDropdownMenu, #versionDropdownMenu {
    background: rgba(14,26,44,0.98) !important; border: 1px solid rgba(140,210,255,0.55) !important;
    border-radius: 0 !important;
    box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(120,200,255,0.22) !important;
  }
  #visibilityDropdownMenu > div, #versionDropdownMenu > div { color: rgba(205,238,255,0.92) !important; }
  #visibilityDropdownMenu > div:hover, #versionDropdownMenu > div:hover { background: rgba(30,64,104,0.9) !important; }

  /* Control + filter labels — were dark/black on the board. Match the button text:
     cyan-HUD, all-caps, soft glow. (Menu items stay normal-case for readability.) */
  #myDeckWrapper, #myStatsWrapper, #mySortWrapper,
  label[for="legalFilterCheckbox"], label[for="customFilterCheckbox"] {
    color: rgba(205,238,255,0.92) !important; font-weight: 600 !important;
    font-family: Arial, Helvetica, sans-serif !important;
    text-transform: uppercase !important; letter-spacing: 0.04em !important;
    text-shadow: 0 0 5px rgba(120,200,255,0.30) !important;
  }
  .widget-dd-item { text-transform: none !important; }  /* menu items normal-case; trigger label stays UPPERCASE like the buttons */
  .filterBar::placeholder { color: rgba(160,195,225,0.6) !important; }

  /* Custom cyan-HUD checkboxes (Filter Legal / Filter Aspect) — SWUDeck only. */
  #legalFilterCheckbox, #customFilterCheckbox {
    -webkit-appearance: none !important; appearance: none !important;
    width: 16px !important; height: 16px !important; margin: 0 6px 0 0 !important; padding: 0 !important;
    background: rgba(20,42,70,0.9) !important; border: 1px solid rgba(140,210,255,0.6) !important;
    border-radius: 0 !important; cursor: pointer; position: relative; vertical-align: middle; flex-shrink: 0;
    transition: box-shadow 120ms, background 120ms;
  }
  #legalFilterCheckbox:hover, #customFilterCheckbox:hover { box-shadow: 0 0 6px rgba(120,200,255,0.45) !important; }
  #legalFilterCheckbox:checked, #customFilterCheckbox:checked {
    background: rgba(30,64,104,0.95) !important; box-shadow: 0 0 5px rgba(120,200,255,0.35) !important;
  }
  #legalFilterCheckbox:checked::after, #customFilterCheckbox:checked::after {
    content: '' !important; position: absolute; left: 4px; top: 1px; width: 5px; height: 9px;
    border: solid rgba(180,228,255,1); border-width: 0 2px 2px 0; transform: rotate(45deg);
  }
  /* Scoot the Filter Legal / Filter Aspect row right a touch (targets the row via the checkbox it holds). */
  #myCardPaneWrapper div:has(> div > #legalFilterCheckbox) { padding-left: 10px !important; }

  /* Card pane — thin cyan HUD frame with a faint glow around the CARD GRID only, so it
     begins below the filter bar / tabs / Filter Legal checkbox (which stay unframed at top). */
  #my_CardPane_content {
    display: block !important; box-sizing: border-box !important; margin-top: 5px !important; padding: 5px !important;
    border: 2px solid rgba(150,215,255,0.75) !important;
    box-shadow: 0 0 16px rgba(120,200,255,0.5), inset 0 0 12px rgba(120,200,255,0.18) !important;
  }
</style>
HTML);

if (SWUDeckIsMobileRequest()) { include __DIR__ . '/GameLayoutMobile.php'; return; }
?>
<style>
  /* Fixed-height, scrollable zones: the wrapper must fill its positioned slot so its OWN
     overflow scrolls (the slot defines the height via top/bottom). The BindTo render gives
     each wrapper `overflow-y:auto`; these rules give it the height to scroll within. */
  #swuDeckBoard #myCardPaneSlot,
  #swuDeckBoard #myMainDeckSlot { overflow: hidden; }
  #myCardPaneWrapper,
  #myMainDeckWrapper { height: 100%; overflow-y: auto; box-sizing: border-box; }
  #myCardPaneWrapper { width: 100%; }
</style>
<div id="swuDeckBoard" style="position:absolute; left:0; top:0; right:0; bottom:0; z-index:11;">
  <!-- Slots carry only position; the generator's BindTo render sets each slot's .onclick and
       fills its inner `<zone>Wrapper` (the overflow/scroll container — CardPane's scroll
       position is saved/restored via ZoneScrollHandler on myCardPaneWrapper). -->
  <div id="myCardPaneSlot"  style="position:absolute; left:10px; top:10px; bottom:10px; width:25%;"></div>
  <div id="myLeaderSlot"    style="position:absolute; left:40%; top:10px;"></div>
  <div id="myBaseSlot"      style="position:absolute; left:62%; top:10px;"></div>
  <div id="myDeckSlot"      style="position:absolute; left:26%; top:16%;"></div>
  <div id="myStatsSlot"     style="position:absolute; left:46%; top:16%;"></div>
  <div id="mySortSlot"      style="position:absolute; left:82%; top:16%;"></div>
  <div id="myMainDeckSlot"  style="position:absolute; left:26%; top:20%; bottom:130px;"></div>
  <div id="mySideboardSlot" style="position:absolute; left:26%; bottom:5%;"></div>
</div>
