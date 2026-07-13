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
  :root {
    --swu-control-text: rgba(190, 216, 232, 0.88);
    --swu-control-rim: rgba(103, 151, 180, 0.52);
    --swu-control-rim-hover: rgba(143, 196, 226, 0.78);
    --swu-control-fill: rgba(7, 22, 35, 0.94);
    --swu-control-fill-hover: rgba(11, 31, 47, 0.97);
    --swu-control-fill-active: rgba(15, 40, 59, 0.98);
    --swu-control-glow: rgba(91, 164, 204, 0.24);
  }

  /* SWUDeck top rail: replace the generated gray flex row with a compact HUD header.
     Primary navigation stays left; deck-state controls form a distinct group on the right. */
  .flex-container > .flex-item:first-child {
    flex: 0 0 46px !important;
    min-height: 46px !important;
    box-sizing: border-box !important;
    padding: 0 8px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    flex-wrap: nowrap !important;
    gap: 2px !important;
    overflow: visible !important;
    position: relative !important;
    z-index: 100 !important;
    background:
      linear-gradient(180deg, rgba(7,20,32,0.94), rgba(2,12,22,0.90)),
      url('/TCGEngine/Assets/Images/gamebg.jpg') center top / cover !important;
    border-bottom: 1px solid rgba(var(--accent-rgb),0.24) !important;
    box-shadow: 0 5px 18px rgba(0,0,0,0.42), inset 0 -1px 0 rgba(255,255,255,0.03) !important;
  }
  .flex-container > .flex-item:nth-child(2) { min-height: 0 !important; }
  .flex-container > .flex-item:first-child > #AssetVisibility {
    margin: 0 0 0 auto !important;
    padding: 0 0 0 10px !important;
    display: flex !important;
    align-items: center !important;
    border-left: 1px solid rgba(var(--accent-rgb),0.20);
  }
  .flex-container > .flex-item:first-child > #Versions {
    margin: 0 2px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
  }

  /* Base: strip the stock look, draw the chamfer with ::before (cyan rim) + ::after (fill). */
  .widget-button, .widget-button-selected, .panelTab,
  .flex-container > .flex-item:first-child button {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; clip-path: none !important;
    padding: 4px 11px !important; margin: 2px 3px !important;
    color: var(--swu-control-text) !important; font-weight: 600 !important;
    text-transform: uppercase !important; letter-spacing: 0.04em !important;
    text-shadow: none !important;
    filter: none !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
    cursor: pointer !important;
  }
  .widget-button::before, .widget-button-selected::before, .panelTab::before,
  .flex-container > .flex-item:first-child button::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px) !important;
    background: var(--swu-control-rim) !important;
  }
  .widget-button::after, .widget-button-selected::after, .panelTab::after,
  .flex-container > .flex-item:first-child button::after {
    content: '' !important; position: absolute !important; inset: 1px !important; z-index: -1 !important;
    clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px) !important;
    background: var(--swu-control-fill) !important;
  }
  /* Hover — brighter rim + lift */
  .widget-button:hover, .panelTab:hover,
  .flex-container > .flex-item:first-child button:hover {
    color: var(--text) !important; filter: drop-shadow(0 0 4px var(--swu-control-glow)) !important; transform: translateY(-1px) !important;
  }
  .widget-button:hover::before, .panelTab:hover::before,
  .flex-container > .flex-item:first-child button:hover::before { background: var(--swu-control-rim-hover) !important; }
  .widget-button:hover::after, .panelTab:hover::after,
  .flex-container > .flex-item:first-child button:hover::after { background: var(--swu-control-fill-hover) !important; }
  /* Selected (active sort/stat) — bright rim, slightly lit fill */
  .widget-button-selected { color: var(--text) !important; }
  .widget-button-selected::before { background: var(--swu-control-rim-hover) !important; }
  .widget-button-selected::after  { background: var(--swu-control-fill-active) !important; }
  /* Press-in */
  .widget-button:active { transform: translateY(1px) !important; }
  .widget-button:active::after,
  .flex-container > .flex-item:first-child button:active::after { background: var(--surface-raised) !important; }
  /* Sort control — cyan-HUD SKIN over the base widget dropdown. Structure + neutral default
     live in Core/UILibraries (reusable by any app); here we only re-color the popup so it
     matches the visibility/version menus. Trigger chamfer comes from the .widget-button rule
     above. !important so this wins over the base regardless of stylesheet order. */
  #mySortWrapper, #mySortSlot { overflow: visible !important; }  /* let the popup escape the zone */
  .widget-dd-menu {
    background: var(--surface-raised) !important; border: 1px solid rgba(var(--accent-rgb),0.34) !important;
    border-radius: 0 !important; box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(var(--accent-rgb),0.08) !important;
  }
  .widget-dd-item { color: var(--text) !important; }
  .widget-dd-item:hover { background: var(--check-fill) !important; }
  .widget-dd-item.is-active { color: #fff !important; }
  /* Toolbar buttons: uniform height. Plain buttons (Home/Edit/Stats/Print/Refresh) are
     direct flex children and stretch to ~41px; the dropdown triggers (Private / Current
     Version) sit inside inline-block wrappers and don't, so they came out ~25px. Pin a
     single height + vertically center content so they all match. */
  .flex-container > .flex-item:first-child button {
    height: 28px !important; align-self: center !important; box-sizing: border-box !important;
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    padding: 3px 9px !important; margin: 0 2px !important; font-size: 13px !important;
    filter: none !important;
  }
  .flex-container > .flex-item:first-child > button:hover,
  .flex-container > .flex-item:first-child #visibilityDropdownTrigger:hover,
  .flex-container > .flex-item:first-child #versionDropdownTrigger:hover {
    filter: drop-shadow(0 0 4px var(--swu-control-glow)) !important;
  }
  @media (max-width: 1100px) {
    .flex-container > .flex-item:first-child { padding: 0 4px !important; gap: 0 !important; }
    .flex-container > .flex-item:first-child button {
      padding: 3px 6px !important;
      margin: 0 1px !important;
      font-size: 12px !important;
    }
    .flex-container > .flex-item:first-child > #AssetVisibility { padding-left: 5px !important; }
  }
  /* Dropdown menus (visibility + version popups) — cyan-HUD panel to match the buttons. */
  #visibilityDropdownMenu, #versionDropdownMenu {
    background: var(--surface-raised) !important; border: 1px solid rgba(var(--accent-rgb),0.34) !important;
    border-radius: 0 !important;
    box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(var(--accent-rgb),0.08) !important;
  }
  #visibilityDropdownMenu > div, #versionDropdownMenu > div { color: var(--text) !important; }
  #visibilityDropdownMenu > div:hover, #versionDropdownMenu > div:hover { background: var(--check-fill) !important; }

  /* Control + filter labels — were dark/black on the board. Match the button text:
     cyan-HUD, all-caps, soft glow. (Menu items stay normal-case for readability.) */
  #myDeckWrapper, #myStatsWrapper, #mySortWrapper,
  label[for="legalFilterCheckbox"], label[for="customFilterCheckbox"] {
    color: var(--swu-control-text) !important; font-weight: 600 !important;
    font-family: Arial, Helvetica, sans-serif !important;
    text-transform: uppercase !important; letter-spacing: 0.04em !important;
    text-shadow: none !important;
  }
  .widget-dd-item { text-transform: none !important; }  /* menu items normal-case; trigger label stays UPPERCASE like the buttons */
  .filterBar {
    background: rgba(7, 19, 30, 0.94) !important;
    border: 1px solid rgba(var(--accent-rgb),0.20) !important;
    color: var(--swu-control-text) !important;
    box-shadow: inset 0 1px 5px rgba(0,0,0,0.34) !important;
  }
  .filterBar:focus {
    outline: none !important;
    border-color: rgba(var(--accent-rgb),0.42) !important;
    box-shadow: inset 0 1px 5px rgba(0,0,0,0.34), 0 0 4px rgba(var(--accent-rgb),0.12) !important;
  }
  .filterBar::placeholder { color: rgba(160,195,225,0.50) !important; }

  /* Custom cyan-HUD checkboxes (Filter Legal / Filter Aspect) — SWUDeck only. */
  #legalFilterCheckbox, #customFilterCheckbox {
    -webkit-appearance: none !important; appearance: none !important;
    width: 16px !important; height: 16px !important; margin: 0 6px 0 0 !important; padding: 0 !important;
    background: var(--swu-control-fill) !important; border: 1px solid rgba(var(--accent-rgb),0.38) !important;
    border-radius: 0 !important; cursor: pointer; position: relative; vertical-align: middle; flex-shrink: 0;
    transition: box-shadow 120ms, background 120ms;
  }
  #legalFilterCheckbox:hover, #customFilterCheckbox:hover { border-color: rgba(var(--accent-rgb),0.62) !important; box-shadow: 0 0 4px rgba(var(--accent-rgb),0.18) !important; }
  #legalFilterCheckbox:checked, #customFilterCheckbox:checked {
    background: var(--swu-control-fill-active) !important; box-shadow: 0 0 4px rgba(var(--accent-rgb),0.16) !important;
  }
  #legalFilterCheckbox:checked::after, #customFilterCheckbox:checked::after {
    content: '' !important; position: absolute; left: 4px; top: 1px; width: 5px; height: 9px;
    border: solid var(--accent-strong); border-width: 0 2px 2px 0; transform: rotate(45deg);
  }
  /* Keep the filter controls flush with the compact pane-tab row. */
  #myCardPaneWrapper div:has(> div > #legalFilterCheckbox) { padding-left: 0 !important; }

  /* Card pane — subdued inset frame around the CARD GRID only, beginning below the
     fixed search and tab/filter controls. */
  #my_CardPane_content {
    display: block !important; box-sizing: border-box !important; margin-top: 5px !important; padding: 5px !important;
    border: 1px solid rgba(var(--accent-rgb),0.28) !important;
    background: rgba(1, 13, 25, 0.12) !important;
    box-shadow: inset 0 0 12px rgba(var(--accent-rgb),0.08) !important;
  }
</style>
HTML);

// DeckStats.php reuses InitialLayout.php purely for the toolbar chrome (Home/Edit/Stats/…),
// so it needs the shared cyan-HUD button styling emitted above — but NOT the deck-builder
// board. Rendering #swuDeckBoard (position:absolute; inset:0; z-index:11) would overlay the
// stats injected into #myStuff (z-index:10) and swallow every click + wheel/scroll event.
// Bail here: keep the button skin, skip the board (and the mobile board routing below).
if (!empty($suppressDeckBoard)) return;

if (SWUDeckIsMobileRequest()) { include __DIR__ . '/GameLayoutMobile.php'; return; }
?>
<style>
  /* The shared shell normally insets #myStuff by 4px inside a gray wrapper. On this
     full-bleed deck board that reads as an empty strip below the HUD rail, so let the
     starfield meet the rail directly; the rail's subtle bottom border remains the divider. */
  #myStuff.myStuff {
    inset: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
  }

  /* Fixed-height, scrollable zones: the wrapper must fill its positioned slot so its OWN
     overflow scrolls (the slot defines the height via top/bottom). The BindTo render gives
     each wrapper `overflow-y:auto`; these rules give it the height to scroll within. */
  #swuDeckBoard #myCardPaneSlot,
  #swuDeckBoard #myMainDeckSlot { overflow: hidden; }
  #myCardPaneWrapper,
  #myMainDeckWrapper { height: 100%; overflow-y: auto; box-sizing: border-box; }
  #myCardPaneWrapper { width: 100%; }

  /* Desktop layout regions follow the renderer's cardSize calculation (viewport / 13).
     The leader-unit crop and base crop share a shallow identity banner above the browser. */
  #swuDeckBoard {
    --swu-deck-card-size: calc(100vw / 13);
    --swu-identity-height: clamp(64px, 6vw, 105px);
    overflow: hidden;
  }
  #swuDeckBoard #swuIdentityBanner {
    position: absolute;
    left: 10px;
    top: 10px;
    width: 25%;
    height: var(--swu-identity-height);
    overflow: hidden;
    border: 1px solid rgba(var(--accent-rgb),0.24);
    border-radius: 8px;
    background: rgba(1,10,20,0.72);
    box-shadow: inset 0 0 20px rgba(0,0,0,0.36);
  }
  #swuDeckBoard #myLeaderSlot {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 58%;
    height: 100%;
    overflow: hidden;
    -webkit-mask-image: linear-gradient(to right, #000 0%, #000 68%, transparent 100%);
    mask-image: linear-gradient(to right, #000 0%, #000 68%, transparent 100%);
  }
  #swuDeckBoard #myBaseSlot {
    position: absolute !important;
    left: auto !important;
    right: 0 !important;
    top: 0 !important;
    width: 58%;
    height: 100%;
    overflow: hidden;
    -webkit-mask-image: linear-gradient(to left, #000 0%, #000 68%, transparent 100%);
    mask-image: linear-gradient(to left, #000 0%, #000 68%, transparent 100%);
  }
  #swuDeckBoard #swuIdentityBanner::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(90deg,
      rgba(2,12,23,0.08) 0%,
      transparent 31%,
      rgba(3,18,32,0.36) 48%,
      rgba(3,18,32,0.28) 52%,
      transparent 69%,
      rgba(2,12,23,0.08) 100%);
  }
  #swuIdentityBanner #myLeaderWrapper,
  #swuIdentityBanner #myBaseWrapper,
  #swuIdentityBanner #myLeader,
  #swuIdentityBanner #myBase,
  #swuIdentityBanner a {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
    margin: 0 !important;
  }
  #swuIdentityBanner img {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
    object-position: center;
    border: 0 !important;
  }
  #swuIdentityBanner #myLeaderSlot img { object-position: center top; }
  #swuDeckBoard #myCardPaneSlot {
    top: calc(var(--swu-identity-height) + 20px) !important;
    overflow: hidden;
  }
  #swuDeckBoard #myDeckSlot {
    left: calc(26% + 12px) !important;
    top: 10px !important;
  }
  #swuDeckBoard #myStatsSlot {
    left: 48% !important;
    top: 10px !important;
  }
  #swuDeckBoard #mySortSlot {
    left: auto !important;
    right: 10px !important;
    top: 10px !important;
  }
  /* Main deck + sideboard share one normal-flow workspace. The sideboard therefore follows
     the final main-deck row instead of being stranded against the bottom of the viewport. */
  #swuDeckBoard #swuDeckWorkspace {
    position: absolute;
    left: 26%;
    right: 10px;
    top: 50px;
    bottom: 10px;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 0 2px 8px;
    box-sizing: border-box;
  }
  #swuDeckBoard .swu-deck-section {
    position: relative;
    width: 100%;
    box-sizing: border-box;
    overflow: visible;
    border: 1px solid rgba(var(--accent-rgb),0.12);
    border-radius: 7px;
    background: linear-gradient(180deg, rgba(7,23,36,0.56), rgba(3,15,27,0.24));
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.025), inset 0 0 22px rgba(0,0,0,0.12);
  }
  #swuDeckBoard .swu-deck-section + .swu-deck-section { margin-top: 10px; }
  #swuDeckBoard .swu-deck-section-title {
    height: 24px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(var(--accent-rgb),0.09);
    color: rgba(166,198,217,0.68);
    font: 600 10px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    pointer-events: none;
  }
  #swuDeckBoard #myMainDeckSlot,
  #swuDeckBoard #mySideboardSlot {
    position: relative !important;
    inset: auto !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
  }

  /* Keep search, tabs, and filter toggles fixed; only the card grid scrolls. */
  #myCardPaneWrapper {
    overflow: hidden !important;
  }
  #myCardPane {
    display: flex !important;
    flex-direction: column;
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
    align-items: stretch;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden !important;
  }
  #myCardPane > div:first-child {
    flex: 0 0 auto;
    overflow: visible !important;
  }
  #myCardPane .swu-pane-tabs-row {
    flex-wrap: nowrap !important;
    align-items: center !important;
    gap: 0;
    position: relative;
    overflow: visible !important;
  }
  #myCardPane .swu-pane-filter-menu {
    position: relative;
    flex: 0 0 auto;
    margin-left: auto;
    z-index: 220;
  }
  #myCardPane .swu-pane-filter-trigger {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px;
    min-width: 0;
    padding: 3px 7px !important;
    margin: 2px !important;
    font-size: 12px !important;
    font-family: Arial, Helvetica, sans-serif !important;
    line-height: 18px;
    list-style: none;
    white-space: nowrap;
  }
  #myCardPane .swu-pane-filter-trigger > span { font-family: inherit !important; }
  #myCardPane .swu-pane-filter-trigger::-webkit-details-marker { display: none; }
  #myCardPane .swu-pane-filter-count {
    min-width: 15px;
    height: 15px;
    padding: 0 3px;
    box-sizing: border-box;
    border: 1px solid rgba(var(--accent-rgb),0.32);
    border-radius: 8px;
    color: var(--accent-strong);
    font: 700 10px/13px Arial, Helvetica, sans-serif;
    text-align: center;
  }
  #myCardPane .swu-pane-filter-chevron {
    color: rgba(var(--accent-rgb),0.72);
    font-size: 10px;
    transition: transform 120ms ease;
  }
  #myCardPane .swu-pane-filter-menu[open] .swu-pane-filter-chevron { transform: rotate(180deg); }
  #myCardPane .swu-pane-filter-menu[open] .swu-pane-filter-trigger {
    color: var(--text) !important;
    filter: drop-shadow(0 0 4px var(--swu-control-glow)) !important;
  }
  #myCardPane .swu-pane-filter-popover {
    position: absolute;
    top: calc(100% + 3px);
    right: 2px;
    min-width: 156px;
    padding: 4px;
    box-sizing: border-box;
    background: rgba(5, 18, 30, 0.98);
    border: 1px solid rgba(var(--accent-rgb),0.34);
    box-shadow: 0 8px 22px rgba(0,0,0,0.62), 0 0 8px rgba(var(--accent-rgb),0.10);
  }
  #myCardPane .swu-pane-filter-options {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    flex-wrap: nowrap !important;
    gap: 3px !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #myCardPane .swu-pane-filter-options > div {
    display: flex !important;
    align-items: center !important;
    min-height: 28px;
    padding: 3px 6px;
    box-sizing: border-box;
  }
  #myCardPane .swu-pane-filter-options > div:hover { background: rgba(var(--accent-rgb),0.08); }
  #myCardPane .swu-pane-filter-options label {
    margin-left: 0 !important;
    font-size: 12px !important;
    white-space: nowrap;
  }
  #myCardPane .panelTab {
    padding: 3px 6px !important;
    margin: 2px !important;
    font-size: 12px !important;
    letter-spacing: 0.03em !important;
  }
  #my_CardPane_content {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
  }
  #myMainDeckWrapper,
  #mySideboardWrapper {
    width: 100%;
    height: auto !important;
    overflow: visible !important;
    box-sizing: border-box;
  }
  #myMainDeck,
  #mySideboard {
    width: 100%;
    box-sizing: border-box;
    justify-content: flex-start !important;
    align-content: flex-start;
    padding: 6px 7px 8px;
  }
  #mySideboard > span:only-child:not([data-mzid]) { display: none !important; }

  /* Deck quantities should read as compact metadata, not large floating game counters. */
  #myMainDeck .counter-bubble,
  #mySideboard .counter-bubble {
    top: auto !important;
    right: 5px !important;
    bottom: 5px !important;
    left: auto !important;
    width: 22px !important;
    height: 22px !important;
    margin: 0 !important;
    transform: none !important;
    border: 1px solid rgba(var(--accent-rgb),0.48) !important;
    border-radius: 6px !important;
    background: rgba(5,17,27,0.94) !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.55), inset 0 0 5px rgba(var(--accent-rgb),0.08) !important;
    color: rgba(205,228,240,0.94) !important;
    font: 700 12px/20px Arial, Helvetica, sans-serif !important;
    text-shadow: none !important;
  }
  /* Card actions are the primary hover affordance. Keep previews non-interactive and
     preserve the actions above them as a final safeguard on constrained viewports. */
  #cardDetail { pointer-events: none !important; }
  #swuDeckBoard span.draggable:hover .widget-buttons { z-index: 100100 !important; }
</style>
<div id="swuDeckBoard" style="position:absolute; left:0; top:0; right:0; bottom:0; z-index:11;">
  <!-- Slots carry only position; the generator's BindTo render sets each slot's .onclick and
       fills its inner `<zone>Wrapper` (the overflow/scroll container — CardPane's scroll
       position is saved/restored via ZoneScrollHandler on myCardPaneWrapper). -->
  <div id="swuIdentityBanner">
    <div id="myLeaderSlot"></div>
    <div id="myBaseSlot"></div>
  </div>
  <div id="myCardPaneSlot"  style="position:absolute; left:10px; top:10px; bottom:10px; width:25%;"></div>
  <div id="myDeckSlot"      style="position:absolute; left:26%; top:16%;"></div>
  <div id="myStatsSlot"     style="position:absolute; left:46%; top:16%;"></div>
  <div id="mySortSlot"      style="position:absolute; left:82%; top:16%;"></div>
  <div id="swuDeckWorkspace">
    <section class="swu-deck-section" aria-label="Main deck">
      <div class="swu-deck-section-title">Main deck</div>
      <div id="myMainDeckSlot"></div>
    </section>
    <section class="swu-deck-section" aria-label="Sideboard">
      <div class="swu-deck-section-title">Sideboard</div>
      <div id="mySideboardSlot"></div>
    </section>
  </div>
</div>
<script>
(function(){
  function cardIDFromImage(img){
    if(!img) return '';
    var filename = String(img.getAttribute('src') || '').split('/').pop().split('?')[0];
    return filename.replace(/_back(?=\.(?:webp|png)$)/, '').replace(/\.(?:webp|png)$/, '');
  }
  function useIdentityCrop(slotID, useBack){
    var img = document.querySelector('#' + slotID + ' img');
    if(!img || img.dataset.swuIdentityCrop === '1') return;
    var cardID = cardIDFromImage(img);
    if(!cardID) return;
    img.dataset.swuIdentityCrop = '1';
    var cropRoot = './SWUDeck/crops/' + encodeURIComponent(cardID);
    if(useBack) {
      img.addEventListener('error', function fallbackToFrontCrop(){
        img.removeEventListener('error', fallbackToFrontCrop);
        img.src = cropRoot + '_cropped.png';
      });
      img.src = cropRoot + '_back_cropped.png';
    } else {
      img.src = cropRoot + '_cropped.png';
    }
  }
  function enhanceIdentityBanner(){
    useIdentityCrop('myLeaderSlot', true);
    useIdentityCrop('myBaseSlot', false);
  }
  function compactPaneFilters(){
    var pane = document.getElementById('myCardPane');
    var legal = document.getElementById('legalFilterCheckbox');
    if(!pane || !legal) return;
    var filterRow = legal.parentElement && legal.parentElement.parentElement;
    var tab = pane.querySelector('.panelTab');
    var tabsRow = tab && tab.parentElement;
    if(!filterRow || !tabsRow) return;
    var existingMenu = filterRow.closest('.swu-pane-filter-menu');
    if(existingMenu) {
      updatePaneFilterSummary(existingMenu);
      return;
    }
    tabsRow.classList.add('swu-pane-tabs-row');
    filterRow.classList.add('swu-pane-filter-options');

    var menu = document.createElement('details');
    menu.className = 'swu-pane-filter-menu';
    menu.open = document.documentElement.dataset.swuPaneFiltersOpen === '1';

    var trigger = document.createElement('summary');
    trigger.className = 'widget-button swu-pane-filter-trigger';
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
    trigger.innerHTML = '<span>Filters</span><span class="swu-pane-filter-count"></span><span class="swu-pane-filter-chevron" aria-hidden="true">&#9662;</span>';

    var popover = document.createElement('div');
    popover.className = 'swu-pane-filter-popover';
    popover.setAttribute('role', 'group');
    popover.setAttribute('aria-label', 'Card filters');
    popover.appendChild(filterRow);
    menu.appendChild(trigger);
    menu.appendChild(popover);
    tabsRow.appendChild(menu);

    trigger.addEventListener('click', function(){
      // A summary click runs before the native <details> toggle.
      document.documentElement.dataset.swuPaneFiltersOpen = menu.open ? '0' : '1';
    });
    menu.addEventListener('toggle', function(){
      document.documentElement.dataset.swuPaneFiltersOpen = menu.open ? '1' : '0';
      trigger.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
    });
    // PaneFilterCards rerenders this entire subtree synchronously. Capture the change
    // first so the rebuilt dropdown stays open while several filters are adjusted.
    filterRow.addEventListener('change', function(){
      document.documentElement.dataset.swuPaneFiltersOpen = '1';
      updatePaneFilterSummary(menu);
    }, true);
    updatePaneFilterSummary(menu);
  }
  function updatePaneFilterSummary(menu){
    if(!menu) return;
    var boxes = menu.querySelectorAll('input[type="checkbox"]');
    var checked = menu.querySelectorAll('input[type="checkbox"]:checked').length;
    var count = menu.querySelector('.swu-pane-filter-count');
    var trigger = menu.querySelector('.swu-pane-filter-trigger');
    if(count) count.textContent = String(checked);
    if(trigger) trigger.setAttribute('aria-label', 'Filters, ' + checked + ' of ' + boxes.length + ' active');
  }
  function bindPaneFilterDismissal(){
    if(document.documentElement.dataset.swuPaneFilterDismissal === '1') return;
    document.documentElement.dataset.swuPaneFilterDismissal = '1';
    document.addEventListener('click', function(event){
      var menu = document.querySelector('#myCardPane .swu-pane-filter-menu[open]');
      if(menu && !menu.contains(event.target)) {
        menu.open = false;
        document.documentElement.dataset.swuPaneFiltersOpen = '0';
      }
    });
    document.addEventListener('keydown', function(event){
      if(event.key !== 'Escape') return;
      var menu = document.querySelector('#myCardPane .swu-pane-filter-menu[open]');
      if(menu) {
        menu.open = false;
        document.documentElement.dataset.swuPaneFiltersOpen = '0';
        var trigger = menu.querySelector('.swu-pane-filter-trigger');
        if(trigger) trigger.focus();
      }
    });
  }
  function bindCardPaneScroll(){
    var content = document.getElementById('my_CardPane_content');
    if(!content || content.dataset.swuScrollBound === '1') return;
    content.dataset.swuScrollBound = '1';
    content.scrollTop = window.myCardPaneScrollPosition || 0;
    content.addEventListener('scroll', function(){
      window.myCardPaneScrollPosition = content.scrollTop;
    }, { passive: true });
  }
  function observeCardPane(){
    var slot = document.getElementById('myCardPaneSlot');
    if(!slot) return;
    new MutationObserver(function(){ requestAnimationFrame(function(){
      bindCardPaneScroll();
      compactPaneFilters();
    }); })
      .observe(slot, { childList: true, subtree: true });
    bindCardPaneScroll();
    compactPaneFilters();
  }
  function observeIdentityBanner(){
    var banner = document.getElementById('swuIdentityBanner');
    if(!banner) return;
    new MutationObserver(function(){ requestAnimationFrame(enhanceIdentityBanner); })
      .observe(banner, { childList: true, subtree: true });
    enhanceIdentityBanner();
  }
  function initializeLayoutEnhancements(){
    bindPaneFilterDismissal();
    observeCardPane();
    observeIdentityBanner();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeLayoutEnhancements);
  else initializeLayoutEnhancements();
})();
</script>
