<?php
// GameLayout.php â€” AzukiDeck deck-builder board layout (desktop/tablet).
// Emits the STATIC positioned zone slots; NextTurnRender.php fills each slot's
// inner `<zone>Wrapper` by id (the SWUSim slot model). Phones are routed to the
// vertical-stack layout in GameLayoutMobile.php.
//
// Slot coordinates reproduce the historical absolute-percent layout that used to be
// baked inline in NextTurnRender.php, so desktop rendering is unchanged.
require_once __DIR__ . '/GameLayoutDevice.php';
require_once __DIR__ . '/../../Core/Versioning/AssetVersioningLayout.php';
require_once __DIR__ . '/../../AzukiSim/Custom/Stats.php';
RenderAssetVersioningUI('AzukiDeck');

// Signal the shared UILibraries to skip its legacy MobileDeckEditorLayout() JS reflow:
// AzukiDeck now lays itself out natively per-device in PHP, so the reflow would fight it.
echo("<script>window.SWUDeckSlotLayout = true; window.AzukiDeckSlotLayout = true;</script>");

// Base editor controls. GalleryDark.css is loaded after the layout and replaces this
// legacy skin with the Azuki Gallery Dark application treatment on desktop and mobile.
echo(<<<'HTML'
<style>
  :root {
    --swu-control-text: #f2eee5;
    --swu-control-rim: #3a3a41;
    --swu-control-rim-hover: #66666f;
    --swu-control-fill: #1c1c21;
    --swu-control-fill-hover: #29292f;
    --swu-control-fill-active: #ee3b4c;
    --swu-control-glow: rgba(238,59,76,.24);
  }

  /* AzukiDeck top rail: replace the generated gray flex row with a compact HUD header.
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
    background: #030303 !important;
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
  #azukiDeckNameControl {
    display: flex;
    flex: 1 1 auto;
    align-items: center;
    gap: 3px;
    min-width: 0;
    margin: 0 3px 0 7px;
    color: var(--swu-control-text);
  }
  #azukiDeckNameLabel {
    min-width: 0;
    overflow: hidden;
    color: rgba(213, 233, 244, 0.94);
    font: 600 13px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.025em;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .flex-container > .flex-item:first-child #azukiDeckRenameButton {
    flex: 0 0 24px !important;
    width: 24px !important;
    min-width: 24px !important;
    height: 24px !important;
    padding: 5px !important;
    margin: 0 !important;
  }
  #azukiDeckRenameButton svg {
    display: block;
    width: 13px;
    height: 13px;
    fill: currentColor;
  }

  /* Base: strip the stock look, draw the chamfer with ::before (cyan rim) + ::after (fill). */
  .widget-button, .widget-button-selected, .panelTab,
  .flex-container > .flex-item:first-child button {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; clip-path: none !important;
    padding: 4px 11px !important; margin: 2px 3px !important;
    color: var(--swu-control-text) !important; font-weight: 600 !important;
    font-family: Arial, Helvetica, sans-serif !important;
    text-transform: uppercase !important; letter-spacing: 0.04em !important;
    text-shadow: none !important;
    filter: none !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
    cursor: pointer !important;
  }
  .flex-container > .flex-item:first-child button > * { font-family: inherit !important; }
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
  /* Hover â€” brighter rim + lift */
  .widget-button:hover, .panelTab:hover,
  .flex-container > .flex-item:first-child button:hover {
    color: var(--text) !important; filter: drop-shadow(0 0 4px var(--swu-control-glow)) !important; transform: translateY(-1px) !important;
  }
  .widget-button:hover::before, .panelTab:hover::before,
  .flex-container > .flex-item:first-child button:hover::before { background: var(--swu-control-rim-hover) !important; }
  .widget-button:hover::after, .panelTab:hover::after,
  .flex-container > .flex-item:first-child button:hover::after { background: var(--swu-control-fill-hover) !important; }
  /* Selected (active sort/stat) â€” bright rim, slightly lit fill */
  .widget-button-selected { color: var(--text) !important; }
  .widget-button-selected::before { background: var(--swu-control-rim-hover) !important; }
  .widget-button-selected::after  { background: var(--swu-control-fill-active) !important; }
  /* Press-in */
  .widget-button:active { transform: translateY(1px) !important; }
  .widget-button:active::after,
  .flex-container > .flex-item:first-child button:active::after { background: var(--surface-raised) !important; }
  /* Sort control â€” cyan-HUD SKIN over the base widget dropdown. Structure + neutral default
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
    #azukiDeckNameControl { margin-left: 4px; }
    #azukiDeckNameLabel { font-size: 12px; }
    .flex-container > .flex-item:first-child > #AssetVisibility { padding-left: 5px !important; }
  }
  /* Dropdown menus (visibility + version popups) â€” cyan-HUD panel to match the buttons. */
  #visibilityDropdownMenu, #versionDropdownMenu {
    background: var(--surface-raised) !important; border: 1px solid rgba(var(--accent-rgb),0.34) !important;
    border-radius: 0 !important;
    box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(var(--accent-rgb),0.08) !important;
  }
  #visibilityDropdownMenu > div, #versionDropdownMenu > div { color: var(--text) !important; }
  #visibilityDropdownMenu > div:hover, #versionDropdownMenu > div:hover { background: var(--check-fill) !important; }

  /* Control + filter labels â€” were dark/black on the board. Match the button text:
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

  /* Custom cyan-HUD checkboxes (Filter Legal / Filter Aspect) â€” AzukiDeck only. */
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

  /* Card pane â€” subdued inset frame around the CARD GRID only, beginning below the
     fixed search and tab/filter controls. */
  #my_CardPane_content {
    display: block !important; box-sizing: border-box !important; margin-top: 5px !important; padding: 5px !important;
    border: 1px solid rgba(var(--accent-rgb),0.28) !important;
    background: rgba(1, 13, 25, 0.12) !important;
    box-shadow: inset 0 0 12px rgba(var(--accent-rgb),0.08) !important;
  }

  #azukiDeckMatchesButton {
    gap: 6px !important;
  }
  #azukiDeckMatchesCount {
    display: inline-grid;
    min-width: 17px;
    height: 17px;
    padding: 0 4px;
    place-items: center;
    box-sizing: border-box;
    color: rgba(219,238,248,0.94);
    background: rgba(var(--accent-rgb),0.13);
    border: 1px solid rgba(var(--accent-rgb),0.28);
    border-radius: 9px;
    font-size: 9px;
    line-height: 15px;
  }
  #azukiDeckMatchHistoryModal[hidden] { display: none !important; }
  #azukiDeckMatchHistoryModal {
    position: fixed;
    inset: 0;
    z-index: 2147482000;
    display: grid;
    place-items: center;
    padding: 18px;
    box-sizing: border-box;
    background: rgba(0,7,14,0.76);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
  }
  .azuki-deck-history-panel {
    width: min(720px,100%);
    max-height: min(760px,calc(100dvh - 36px));
    overflow: hidden;
    display: flex;
    flex-direction: column;
    color: rgba(223,235,241,0.94);
    background:
      radial-gradient(circle at 90% 0,rgba(var(--accent-rgb),0.10),transparent 34%),
      linear-gradient(150deg,rgba(8,25,42,0.99),rgba(3,15,28,0.99));
    border: 1px solid rgba(var(--accent-rgb),0.38);
    border-radius: 11px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.62),inset 0 1px 0 rgba(255,255,255,0.035);
    font-family: Arial,Helvetica,sans-serif;
  }
  .azuki-deck-history-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 22px 15px;
    border-bottom: 1px solid rgba(var(--accent-rgb),0.16);
  }
  .azuki-deck-history-kicker {
    margin: 0 0 5px;
    color: rgba(var(--accent-rgb),0.78);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }
  #azukiDeckMatchHistoryTitle {
    margin: 0;
    color: rgba(234,244,249,0.98);
    font-size: 21px;
    font-weight: 600;
    letter-spacing: 0.015em;
  }
  #azukiDeckMatchHistoryClose {
    width: 31px !important;
    min-width: 31px !important;
    height: 31px !important;
    margin: 0 !important;
    padding: 0 !important;
    color: rgba(196,218,230,0.82) !important;
    background: rgba(7,22,35,0.88) !important;
    border: 1px solid rgba(var(--accent-rgb),0.26) !important;
    border-radius: 5px !important;
    font-size: 20px !important;
    cursor: pointer;
  }
  #azukiDeckMatchHistoryClose:hover,
  #azukiDeckMatchHistoryClose:focus-visible {
    color: #fff !important;
    border-color: rgba(var(--accent-rgb),0.58) !important;
    outline: none;
  }
  .azuki-deck-history-summary {
    display: grid;
    grid-template-columns: repeat(4,minmax(0,1fr));
    border-bottom: 1px solid rgba(var(--accent-rgb),0.15);
  }
  .azuki-deck-history-stat {
    padding: 14px 18px;
  }
  .azuki-deck-history-stat + .azuki-deck-history-stat {
    border-left: 1px solid rgba(var(--accent-rgb),0.12);
  }
  .azuki-deck-history-stat small {
    display: block;
    margin-bottom: 5px;
    color: rgba(166,194,210,0.62);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
  .azuki-deck-history-stat strong {
    color: rgba(229,240,246,0.96);
    font-size: 21px;
    font-weight: 650;
  }
  .azuki-deck-history-stat.is-win strong { color: #66d5a9; }
  .azuki-deck-history-stat.is-loss strong { color: #ef858d; }
  .azuki-deck-history-list {
    min-height: 120px;
    overflow-y: auto;
    overscroll-behavior: contain;
  }
  .azuki-deck-history-row {
    display: grid;
    grid-template-columns: 34px minmax(130px,1fr) minmax(110px,.8fr) 80px;
    gap: 12px;
    align-items: center;
    min-height: 58px;
    padding: 9px 20px;
    box-sizing: border-box;
  }
  .azuki-deck-history-row + .azuki-deck-history-row {
    border-top: 1px solid rgba(var(--accent-rgb),0.10);
  }
  .azuki-deck-history-result {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 800;
  }
  .azuki-deck-history-row.is-win .azuki-deck-history-result {
    color: #63dbae;
    background: rgba(24,111,82,0.40);
  }
  .azuki-deck-history-row.is-loss .azuki-deck-history-result {
    color: #f19097;
    background: rgba(126,37,50,0.45);
  }
  .azuki-deck-history-opponent,
  .azuki-deck-history-meta {
    display: flex;
    min-width: 0;
    flex-direction: column;
  }
  .azuki-deck-history-opponent strong {
    overflow: hidden;
    color: rgba(228,239,245,0.94);
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .azuki-deck-history-row small,
  .azuki-deck-history-meta span {
    color: rgba(157,184,199,0.62);
    font-size: 10px;
  }
  .azuki-deck-history-date {
    color: rgba(171,198,212,0.72);
    font-size: 10px;
    text-align: right;
  }
  .azuki-deck-history-empty {
    padding: 42px 22px;
    color: rgba(165,192,207,0.70);
    font-size: 12px;
    line-height: 1.5;
    text-align: center;
  }
  .azuki-deck-history-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 20px;
    border-top: 1px solid rgba(var(--accent-rgb),0.14);
    background: rgba(2,13,23,0.66);
  }
  .azuki-deck-history-footer span {
    color: rgba(151,181,198,0.62);
    font-size: 10px;
  }
  .azuki-deck-history-footer a {
    color: rgba(190,220,236,0.90);
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    text-transform: uppercase;
  }
  .azuki-deck-history-footer a:hover { color: #fff; }
  @media (max-width: 640px) {
    #azukiDeckMatchesButton {
      width: 100% !important;
      margin: 0 !important;
      justify-content: space-between !important;
    }
    .azuki-deck-history-panel { max-height: calc(100dvh - 20px); }
    .azuki-deck-history-header { padding: 16px; }
    .azuki-deck-history-summary { grid-template-columns: repeat(2,minmax(0,1fr)); }
    .azuki-deck-history-stat:nth-child(3) { border-left: 0; border-top: 1px solid rgba(var(--accent-rgb),0.12); }
    .azuki-deck-history-stat:nth-child(4) { border-top: 1px solid rgba(var(--accent-rgb),0.12); }
    .azuki-deck-history-row {
      grid-template-columns: 30px minmax(110px,1fr) 76px;
      gap: 9px;
      padding: 9px 13px;
    }
    .azuki-deck-history-meta { display: none; }
    .azuki-deck-history-footer { padding: 11px 14px; }
  }
</style>
HTML);

// InitialLayout.php is generated, so persistent editor-only toolbar controls are
// installed here. Only the asset owner sees the rename action; the shared endpoint
// repeats that ownership check before saving.
if(isset($assetData) && (string)($assetData['assetOwner'] ?? '') === (string)LoggedInUser()) {
  $azukiDeckName = trim((string)($assetData['assetName'] ?? ''));
  if($azukiDeckName === '') $azukiDeckName = 'Deck #' . $gameName;
  $azukiDeckNameJson = json_encode($azukiDeckName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  $azukiDeckIDJson = json_encode((string)$gameName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  $azukiDeckMatchHistory = AzukiLoadDeckMatchHistory(LoggedInUser(), $gameName, 20);
  $azukiDeckMatchHistoryJson = json_encode(
    $azukiDeckMatchHistory,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
  );
  if($azukiDeckMatchHistoryJson === false) {
    $azukiDeckMatchHistoryJson = '{"wins":0,"losses":0,"draws":0,"matches":[]}';
  }
  $azukiDeckPlaybookConfigJson = json_encode([
    'deckID' => (string)$gameName,
    'endpoint' => '/TCGEngine/AzukiDeck/Playbook.php',
    'cardImageBase' => '/TCGEngine/AzukiSim/WebpImages/',
    'assetVersion' => (string)max(
      intval(@filemtime(__DIR__ . '/Playbook.css')),
      intval(@filemtime(__DIR__ . '/Playbook.js'))
    )
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
  echo(<<<HTML
<script>
window.AzukiDeckPlaybookConfig = {$azukiDeckPlaybookConfigJson};
(function () {
  var deckID = {$azukiDeckIDJson};
  var currentName = {$azukiDeckNameJson};
  var matchHistory = {$azukiDeckMatchHistoryJson};

  function installRenameButton() {
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if (!toolbar || document.getElementById('azukiDeckRenameButton')) return;

    var control = document.createElement('div');
    control.id = 'azukiDeckNameControl';
    control.setAttribute('aria-label', 'Deck name');

    var nameLabel = document.createElement('span');
    nameLabel.id = 'azukiDeckNameLabel';
    nameLabel.textContent = currentName;
    nameLabel.title = currentName;

    var button = document.createElement('button');
    button.id = 'azukiDeckRenameButton';
    button.type = 'button';
    button.title = 'Rename deck';
    button.setAttribute('aria-label', 'Rename deck');
    button.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M11.73 1.27a1.75 1.75 0 0 1 2.47 0l.53.53a1.75 1.75 0 0 1 0 2.47L6.25 12.75 2 14l1.25-4.25 8.48-8.48ZM4.13 10.38l-.55 1.87 1.87-.55 7.97-7.96a.25.25 0 0 0 0-.36l-.8-.8a.25.25 0 0 0-.36 0l-7.97 7.97-.16-.17Z"/></svg>';
    button.addEventListener('click', function () {
      if (typeof window.StyledPrompt !== 'function') return;
      window.StyledPrompt('Enter a name for this deck.', {
        title: 'Deck Name',
        initial: currentName,
        confirmLabel: 'Save'
      }).then(function (newName) {
        if (!newName || newName === currentName) return;

        var params = new URLSearchParams({
          assetID: deckID,
          newName: newName,
          assetType: '1'
        });
        fetch('/TCGEngine/AccountFiles/RenameAsset.php?' + params.toString(), {
          credentials: 'same-origin'
        }).then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok || !data.success) {
              throw new Error(data.error || 'Failed to rename deck.');
            }
            currentName = newName;
            nameLabel.textContent = currentName;
            nameLabel.title = currentName;
            var historyTitle = document.getElementById('azukiDeckMatchHistoryTitle');
            if (historyTitle) historyTitle.textContent = currentName;
            if (typeof window.Toast === 'function') {
              window.Toast('Deck renamed successfully.', { type: 'success' });
            }
          });
        }).catch(function (error) {
          if (typeof window.Toast === 'function') {
            window.Toast(error.message || 'Failed to rename deck.', { type: 'danger' });
          }
        });
      });
    });

    control.appendChild(nameLabel);
    control.appendChild(button);
    var visibility = document.getElementById('AssetVisibility');
    toolbar.insertBefore(control, visibility || null);
  }

  function matchHistoryTotal() {
    return Number(matchHistory.wins || 0) + Number(matchHistory.losses || 0) + Number(matchHistory.draws || 0);
  }

  function formatMatchDate(value) {
    if (!value) return '';
    var date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined });
  }

  function createMatchHistoryRow(match) {
    var won = String(match.result || '').toUpperCase() === 'W';
    var row = document.createElement('div');
    row.className = 'azuki-deck-history-row ' + (won ? 'is-win' : 'is-loss');

    var result = document.createElement('span');
    result.className = 'azuki-deck-history-result';
    result.textContent = won ? 'W' : 'L';

    var opponent = document.createElement('div');
    opponent.className = 'azuki-deck-history-opponent';
    var opponentName = document.createElement('strong');
    opponentName.textContent = 'vs ' + String(match.opponentName || 'Guest');
    var mode = document.createElement('small');
    mode.textContent = String(match.gameMode || '') === 'rlbot' ? 'Training match' : 'Player match';
    opponent.appendChild(opponentName);
    opponent.appendChild(mode);

    var meta = document.createElement('div');
    meta.className = 'azuki-deck-history-meta';
    var order = document.createElement('span');
    order.textContent = Number(match.wentFirst || 0) === 1 ? 'Went first' : 'Went second';
    var turns = document.createElement('span');
    turns.textContent = String(Number(match.turnCount || 0)) + ' turns';
    meta.appendChild(order);
    meta.appendChild(turns);

    var completed = document.createElement('time');
    completed.className = 'azuki-deck-history-date';
    completed.textContent = formatMatchDate(match.completedAt);
    completed.dateTime = String(match.completedAt || '');

    row.appendChild(result);
    row.appendChild(opponent);
    row.appendChild(meta);
    row.appendChild(completed);
    return row;
  }

  function installMatchHistory() {
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if (!toolbar || document.getElementById('azukiDeckMatchesButton')) return;

    var total = matchHistoryTotal();
    var button = document.createElement('button');
    button.id = 'azukiDeckMatchesButton';
    button.type = 'button';
    button.setAttribute('aria-haspopup', 'dialog');
    button.innerHTML = '<span>Matches</span><span id="azukiDeckMatchesCount">' + String(total) + '</span>';

    var modal = document.createElement('div');
    modal.id = 'azukiDeckMatchHistoryModal';
    modal.hidden = true;
    modal.innerHTML =
      '<section class="azuki-deck-history-panel" role="dialog" aria-modal="true" aria-labelledby="azukiDeckMatchHistoryTitle">' +
        '<header class="azuki-deck-history-header">' +
          '<div><p class="azuki-deck-history-kicker">Deck record</p><h2 id="azukiDeckMatchHistoryTitle"></h2></div>' +
          '<button id="azukiDeckMatchHistoryClose" type="button" aria-label="Close match history">&times;</button>' +
        '</header>' +
        '<div class="azuki-deck-history-summary">' +
          '<div class="azuki-deck-history-stat"><small>Matches</small><strong data-history-stat="total"></strong></div>' +
          '<div class="azuki-deck-history-stat is-win"><small>Wins</small><strong data-history-stat="wins"></strong></div>' +
          '<div class="azuki-deck-history-stat is-loss"><small>Losses</small><strong data-history-stat="losses"></strong></div>' +
          '<div class="azuki-deck-history-stat"><small>Win rate</small><strong data-history-stat="rate"></strong></div>' +
        '</div>' +
        '<div class="azuki-deck-history-list"></div>' +
        '<footer class="azuki-deck-history-footer"><span>Results recorded while using this saved deck.</span><a href="/TCGEngine/SharedUI/Sites/AzukiSim/Matches.php">All matches</a></footer>' +
      '</section>';

    modal.querySelector('#azukiDeckMatchHistoryTitle').textContent = currentName;
    modal.querySelector('[data-history-stat="total"]').textContent = String(total);
    modal.querySelector('[data-history-stat="wins"]').textContent = String(Number(matchHistory.wins || 0));
    modal.querySelector('[data-history-stat="losses"]').textContent = String(Number(matchHistory.losses || 0));
    modal.querySelector('[data-history-stat="rate"]').textContent =
      (total > 0 ? Math.round((Number(matchHistory.wins || 0) / total) * 100) : 0) + '%';

    var list = modal.querySelector('.azuki-deck-history-list');
    var matches = Array.isArray(matchHistory.matches) ? matchHistory.matches : [];
    if (matches.length === 0) {
      var empty = document.createElement('div');
      empty.className = 'azuki-deck-history-empty';
      empty.textContent = 'No completed matches are associated with this deck yet.';
      list.appendChild(empty);
    } else {
      matches.forEach(function (match) {
        list.appendChild(createMatchHistoryRow(match));
      });
    }

    var previouslyFocused = null;
    function closeModal() {
      modal.hidden = true;
      document.body.style.removeProperty('overflow');
      button.setAttribute('aria-expanded', 'false');
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
    }
    function openModal() {
      previouslyFocused = document.activeElement;
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
      button.setAttribute('aria-expanded', 'true');
      modal.querySelector('#azukiDeckMatchHistoryClose').focus();
    }

    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', openModal);
    modal.querySelector('#azukiDeckMatchHistoryClose').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    var visibility = document.getElementById('AssetVisibility');
    toolbar.insertBefore(button, visibility || null);
    document.body.appendChild(modal);

    var mobilePanel = document.getElementById('swuMobileToolbarMenuPanel');
    if (mobilePanel) mobilePanel.appendChild(button);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      installRenameButton();
      installMatchHistory();
    });
  } else {
    installRenameButton();
    installMatchHistory();
  }
})();
</script>
HTML);
}

$azukiDeckGalleryDarkPath = '/TCGEngine/AzukiDeck/Custom/GalleryDark.css';
$azukiDeckGalleryDarkVersion = @filemtime(__DIR__ . '/GalleryDark.css');
$azukiDeckGalleryDarkHref = $azukiDeckGalleryDarkPath . ($azukiDeckGalleryDarkVersion ? '?v=' . $azukiDeckGalleryDarkVersion : '');
$azukiDeckGalleryDarkLink = '<link rel="stylesheet" href="' . htmlspecialchars($azukiDeckGalleryDarkHref, ENT_QUOTES) . '">';

// DeckStats.php reuses InitialLayout.php purely for the toolbar chrome (Home/Edit/Stats/â€¦),
// so it needs the shared Gallery Dark button styling emitted above â€” but NOT the deck-builder
// board. Rendering #swuDeckBoard (position:absolute; inset:0; z-index:11) would overlay the
// stats injected into #myStuff (z-index:10) and swallow every click + wheel/scroll event.
// Bail here: keep the button skin, skip the board (and the mobile board routing below).
if (!empty($suppressDeckBoard)) { echo $azukiDeckGalleryDarkLink; return; }

if (AzukiDeckIsMobileRequest()) {
  include __DIR__ . '/GameLayoutMobile.php';
  echo $azukiDeckGalleryDarkLink;
  return;
}
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
    --swu-identity-height: clamp(52px, 4.4vw, 72px);
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
  #swuDeckBoard #myGateSlot {
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
  #swuIdentityBanner #myGateWrapper,
  #swuIdentityBanner #myLeader,
  #swuIdentityBanner #myGate,
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
  #swuDeckBoard #myDeckSlot { display: none !important; }
  #swuDeckToolbar {
    position: static;
    z-index: 100200;
    display: flex;
    flex: 1 1 auto;
    min-width: 0;
    min-height: 30px;
    align-items: center;
    gap: 8px;
    margin-left: 14px;
    padding: 0;
    box-sizing: border-box;
    pointer-events: auto;
  }
  .swu-deck-toolbar-label {
    color: rgba(196,192,184,0.62);
    font: 700 10px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .swu-deck-view-control,
  .swu-deck-sort-control {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .swu-deck-view-segment {
    display: inline-flex;
    padding: 2px;
    border-radius: 7px;
    background: rgba(255,255,255,0.045);
  }
  #swuDeckToolbar .swu-deck-view-button {
    min-width: 48px !important;
    height: 26px !important;
    min-height: 26px !important;
    margin: 0 !important;
    padding: 0 8px !important;
    font-size: 10px !important;
  }
  #swuDeckToolbar .swu-deck-view-button.is-active {
    color: var(--text) !important;
  }
  #swuDeckToolbar .swu-deck-view-button.is-active::before { background: rgba(242,238,229,0.26) !important; }
  #swuDeckToolbar .swu-deck-view-button.is-active::after { background: rgba(242,238,229,0.10) !important; }
  #swuDesktopOverlayMenu {
    position: relative;
    margin-left: auto;
    z-index: 100200;
  }
  #swuDesktopOverlayButton {
    gap: 6px;
    width: auto !important;
    min-width: 82px !important;
    height: 28px !important;
    margin: 0 !important;
    padding: 4px 6px !important;
    color: rgba(190,216,232,0.82) !important;
  }
  #swuDesktopOverlayButton svg {
    display: block;
    width: 16px;
    height: 16px;
    margin: auto;
    fill: currentColor;
  }
  #swuDesktopOverlayButton span { font-family: inherit !important; }
  #swuDesktopOverlayMenu.has-active-overlay #swuDesktopOverlayButton,
  #swuDesktopOverlayMenu.is-open #swuDesktopOverlayButton {
    color: rgba(217,240,251,0.98) !important;
    filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.42)) !important;
  }
  #swuDesktopOverlayPanel {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    display: none;
    width: 230px;
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid rgba(var(--accent-rgb),0.28);
    border-radius: 7px;
    background: rgba(3,15,26,0.985);
    box-shadow: 0 10px 26px rgba(0,0,0,0.52), 0 0 9px rgba(var(--accent-rgb),0.08);
  }
  #swuDesktopOverlayMenu.is-open #swuDesktopOverlayPanel { display: block; }
  .swu-desktop-overlay-heading {
    padding: 1px 2px 7px;
    color: rgba(171,205,225,0.62);
    font: 700 9px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
  #swuDesktopOverlayPanel #myStatsSlot,
  #swuDesktopOverlayPanel #myStatsWrapper,
  #swuDesktopOverlayPanel #myStats,
  #swuDesktopOverlayPanel #myStats > div {
    position: static !important;
    width: 100% !important;
    min-width: 0 !important;
    overflow: visible !important;
    padding: 0 !important;
    box-sizing: border-box;
    background: transparent !important;
  }
  #swuDesktopOverlayPanel #myStats {
    font-size: 0 !important;
    line-height: 0 !important;
  }
  #swuDesktopOverlayPanel #myStats > span { display: none !important; }
  #swuDesktopOverlayPanel #myStats > div {
    display: flex !important;
    flex-direction: column;
    flex-wrap: nowrap !important;
    gap: 5px !important;
  }
  #swuDesktopOverlayPanel #myStats .widget-button,
  #swuDesktopOverlayPanel #myStats .widget-button-selected {
    width: 100% !important;
    min-width: 0 !important;
    height: 28px !important;
    margin: 0 !important;
    padding: 3px 7px !important;
    box-sizing: border-box;
    overflow: hidden;
    font-size: 10px !important;
    line-height: 20px !important;
    text-overflow: ellipsis;
    white-space: nowrap !important;
  }
  #swuDeckBoard #mySortSlot {
    position: relative !important;
    inset: auto !important;
    width: 150px !important;
    min-width: 0;
  }
  #swuDeckToolbar #mySortWrapper,
  #swuDeckToolbar #mySort {
    position: static !important;
    width: 100% !important;
    overflow: visible !important;
  }
  #swuDeckToolbar #mySort > span:first-child { display: none !important; }
  /* Main deck + Maybe share one normal-flow workspace. The Maybe section therefore follows
     the final main-deck row instead of being stranded against the bottom of the viewport. */
  #swuDeckBoard #swuDeckWorkspace {
    position: absolute;
    left: 26%;
    right: 10px;
    top: 8px;
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
  #swuDeckBoard .swu-deck-section-count {
    min-width: 18px;
    margin-left: 7px;
    padding: 2px 6px;
    border-radius: 999px;
    color: rgba(242,238,229,0.72);
    background: rgba(242,238,229,0.08);
    font-size: 10px;
    line-height: 14px;
    text-align: center;
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
    display: grid !important;
    grid-template-columns: repeat(8,minmax(0,1fr));
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
    justify-content: flex-start !important;
    align-content: flex-start;
    padding: 10px;
  }
  #swuDeckBoard.is-dense #myMainDeck,
  #swuDeckBoard.is-dense #mySideboard {
    grid-template-columns: repeat(10,minmax(0,1fr));
    gap: 6px;
  }
  #myMainDeck > span[data-mzid],
  #mySideboard > span[data-mzid] {
    position: relative !important;
    width: 100% !important;
    min-width: 0;
    margin: 0 !important;
  }
  #myMainDeck > span[data-mzid] > a,
  #mySideboard > span[data-mzid] > a {
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
  }
  #myMainDeck > span[data-mzid] > a > img:first-child,
  #mySideboard > span[data-mzid] > a > img:first-child {
    display: block !important;
    width: 100% !important;
    height: auto !important;
    aspect-ratio: 1;
    object-fit: cover;
  }
  #mySideboardWrapper {
    max-height: 250px;
    overflow-y: auto !important;
  }
  #myMainDeck .azuki-deck-group-heading {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    min-height: 24px;
    margin: 3px 0 -2px;
    color: rgba(242,238,229,0.66);
    font: 800 10px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  #myMainDeck .azuki-deck-group-heading::after {
    content: '';
    flex: 1;
    height: 1px;
    margin-left: 10px;
    background: rgba(242,238,229,0.08);
  }
  #myMainDeck > span[data-mzid]::after {
    content: '\00d7';
    position: absolute;
    top: 5px;
    right: 5px;
    z-index: 8;
    display: grid;
    width: 22px;
    height: 22px;
    place-items: center;
    border: 1px solid rgba(255,255,255,0.8);
    border-radius: 50%;
    color: #fff;
    background: rgba(190,31,47,0.94);
    box-shadow: 0 2px 7px rgba(0,0,0,0.62);
    font: 800 16px/1 Arial, Helvetica, sans-serif;
    opacity: 0;
    pointer-events: none;
    transform: scale(0.82);
    transition: opacity 120ms ease, transform 120ms ease;
  }
  #myMainDeck > span[data-mzid]:hover::after {
    opacity: 1;
    transform: scale(1);
  }
  #mySideboard > span:not([data-mzid]):not(.azuki-maybe-placeholder) { display: none !important; }

  /* Card actions are the primary hover affordance. Keep previews non-interactive and
     preserve the actions above them as a final safeguard on constrained viewports. */
  #cardDetail { pointer-events: none !important; }
  #swuDeckBoard span.draggable:hover .widget-buttons { z-index: 100100 !important; }
  #myMainDeck span.widget-buttons,
  #mySideboard span.widget-buttons {
    gap: 1px !important;
    max-width: calc(100% - 8px);
    font-size: 0 !important;
  }
  #myMainDeck span.widget-buttons .widget-button,
  #myMainDeck span.widget-buttons .widget-button-selected,
  #mySideboard span.widget-buttons .widget-button,
  #mySideboard span.widget-buttons .widget-button-selected {
    min-width: 24px !important;
    margin: 1px 0 !important;
    padding: 4px 5px !important;
    font-size: 12px !important;
  }
</style>
<div id="swuDeckBoard" style="position:absolute; left:0; top:0; right:0; bottom:0; z-index:11;">
  <!-- Slots carry only position; the generator's BindTo render sets each slot's .onclick and
       fills its inner `<zone>Wrapper` (the overflow/scroll container â€” CardPane's scroll
       position is saved/restored via ZoneScrollHandler on myCardPaneWrapper). -->
  <div id="swuIdentityBanner">
    <div id="myLeaderSlot"></div>
    <div id="myGateSlot"></div>
  </div>
  <div id="myCardPaneSlot"  style="position:absolute; left:10px; top:10px; bottom:10px; width:25%;"></div>
  <div id="myDeckSlot"      style="position:absolute; left:26%; top:16%;"></div>
  <div id="swuDeckWorkspace">
    <section class="swu-deck-section" aria-label="Main deck">
      <div class="swu-deck-section-title">
        <span>Main deck</span>
        <span id="azukiDeckHeaderSummary">
          <span id="azukiDeckHeaderCount" class="swu-deck-section-count"></span>
        </span>
        <div id="swuDeckToolbar" aria-label="Deck controls">
          <div class="swu-deck-view-control">
            <div class="swu-deck-view-segment" role="group" aria-label="Deck density">
              <button id="swuDeckGridView" class="widget-button swu-deck-view-button is-active" type="button" data-density="grid" aria-label="Comfortable grid view, larger cards with more spacing" title="Comfortable grid view — larger cards with more spacing" aria-pressed="true">
                <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/><rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/></svg>
              </button>
              <button id="swuDeckDenseView" class="widget-button swu-deck-view-button" type="button" data-density="dense" aria-label="Dense grid view, smaller cards with less spacing" title="Dense grid view — smaller cards with less spacing" aria-pressed="false">
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1 1h4v4H1V1Zm5 0h4v4H6V1Zm5 0h4v4h-4V1ZM1 6h4v4H1V6Zm5 0h4v4H6V6Zm5 0h4v4h-4V6ZM1 11h4v4H1v-4Zm5 0h4v4H6v-4Zm5 0h4v4h-4v-4Z"/></svg>
              </button>
            </div>
          </div>
          <div class="swu-deck-sort-control">
            <div id="mySortSlot"></div>
          </div>
          <div id="swuDesktopOverlayMenu">
            <button id="swuDesktopOverlayButton" class="widget-button" type="button" aria-label="Card overlays" aria-haspopup="true" aria-expanded="false">
              <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 13.5h12v1H1v-13h1v12Zm2-2.5h2V7H4v4Zm3.5 0h2V3h-2v8Zm3.5 0h2V5h-2v6Z"/></svg>
              <span>Overlays</span>
            </button>
            <div id="swuDesktopOverlayPanel">
              <div class="swu-desktop-overlay-heading">Card overlays</div>
              <div id="myStatsSlot"></div>
            </div>
          </div>
        </div>
      </div>
      <div id="myMainDeckSlot"></div>
    </section>
    <section class="swu-deck-section" aria-label="Maybe">
      <div class="swu-deck-section-title"><span>Maybe</span><span id="azukiMaybeCount" class="swu-deck-section-count">0</span></div>
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
    var imageFolder = String(window.assetImageFolder || './AzukiSim/concat');
    var cropFolder = imageFolder.replace(/\/concat\/?$/, '/crops');
    img.src = cropFolder + '/' + encodeURIComponent(cardID) + '_cropped.png';
  }
  function enhanceIdentityBanner(){
    useIdentityCrop('myLeaderSlot', true);
    useIdentityCrop('myGateSlot', false);
  }
  function compactPaneFilters(){
    var pane = document.getElementById('myCardPane');
    if(!pane) return;
    var tab = pane.querySelector('.panelTab');
    var tabsRow = tab && tab.parentElement;
    if(!tabsRow) return;
    tabsRow.classList.add('swu-pane-tabs-row');
    updatePaneTabState(tabsRow);

    // Leaders has no legality/aspect filters, but the control stays in place so switching
    // categories does not make the tab row jump horizontally.
    var legal = document.getElementById('legalFilterCheckbox');
    if(!legal) {
      if(!tabsRow.querySelector('.swu-pane-filter-menu')) {
        var emptyMenu = document.createElement('details');
        emptyMenu.className = 'swu-pane-filter-menu is-empty';

        var emptyTrigger = document.createElement('summary');
        emptyTrigger.className = 'widget-button swu-pane-filter-trigger';
        emptyTrigger.setAttribute('role', 'button');
        emptyTrigger.setAttribute('aria-label', 'Filters, none available for Leaders');
        emptyTrigger.setAttribute('aria-expanded', 'false');
        emptyTrigger.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1.5 3h13L10 8v4.1l-4 1.8V8L1.5 3Zm2.7 1.2L7.3 7.6v4.5l1.4-.6V7.6l3.1-3.4H4.2Z"/></svg>';

        var emptyPopover = document.createElement('div');
        emptyPopover.className = 'swu-pane-filter-popover swu-pane-filter-empty';
        emptyPopover.textContent = 'No filters available for Leaders';

        emptyMenu.appendChild(emptyTrigger);
        emptyMenu.appendChild(emptyPopover);
        tabsRow.appendChild(emptyMenu);
        emptyMenu.addEventListener('toggle', function(){
          emptyTrigger.setAttribute('aria-expanded', emptyMenu.open ? 'true' : 'false');
        });
      }
      updatePaneTabState(tabsRow);
      return;
    }
    var filterRow = legal.parentElement && legal.parentElement.parentElement;
    if(!filterRow) return;
    var existingMenu = filterRow.closest('.swu-pane-filter-menu');
    if(existingMenu) {
      updatePaneFilterSummary(existingMenu);
      updatePaneTabState(tabsRow);
      return;
    }
    filterRow.classList.add('swu-pane-filter-options');

    var menu = document.createElement('details');
    menu.className = 'swu-pane-filter-menu';
    menu.open = document.documentElement.dataset.swuPaneFiltersOpen === '1';

    var trigger = document.createElement('summary');
    trigger.className = 'widget-button swu-pane-filter-trigger';
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
    trigger.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1.5 3h13L10 8v4.1l-4 1.8V8L1.5 3Zm2.7 1.2L7.3 7.6v4.5l1.4-.6V7.6l3.1-3.4H4.2Z"/></svg><span class="swu-pane-filter-count"></span>';

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
    updatePaneTabState(tabsRow);
  }
  function updatePaneTabState(tabsRow){
    if(!tabsRow) return;
    var active = Number(window._my_CardPane_activePane || 0);
    tabsRow.setAttribute('role', 'tablist');
    Array.prototype.forEach.call(tabsRow.querySelectorAll('.panelTab'), function(tab, index){
      var selected = index === active;
      tab.classList.toggle('is-active', selected);
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
  }
  function updatePaneFilterSummary(menu){
    if(!menu) return;
    var boxes = menu.querySelectorAll('input[type="checkbox"]');
    var checked = menu.querySelectorAll('input[type="checkbox"]:checked').length;
    var count = menu.querySelector('.swu-pane-filter-count');
    var trigger = menu.querySelector('.swu-pane-filter-trigger');
    if(count) {
      count.textContent = String(checked);
      count.hidden = checked === 0;
    }
    if(trigger) trigger.setAttribute('aria-label', 'Filters, ' + checked + ' of ' + boxes.length + ' active');
  }
  function zoneCardCount(value){
    if(!value) return 0;
    return String(value).split('<|>').filter(function(entry){ return entry.trim() !== ''; }).length;
  }
  function updateDeckSummary(){
    var mainCount = zoneCardCount(window.myMainDeckData);
    var maybeCount = zoneCardCount(window.mySideboardData);
    var control = document.getElementById('azukiDeckNameControl');
    var count = document.getElementById('azukiDeckHeaderCount');
    if(control && !count) {
      var summary = document.createElement('span');
      summary.id = 'azukiDeckHeaderSummary';
      summary.innerHTML = '<span class="azuki-deck-summary-separator" aria-hidden="true">&middot;</span><span id="azukiDeckHeaderCount" class="swu-deck-section-count"></span>';
      control.insertBefore(summary, document.getElementById('azukiDeckRenameButton'));
      count = document.getElementById('azukiDeckHeaderCount');
    }
    if(count) {
      var valid = mainCount === 50;
      var validityMessage = valid
        ? 'Deck is valid: exactly 50 cards.'
        : (mainCount < 50
          ? 'Deck is illegal: add ' + (50 - mainCount) + ' card' + (50 - mainCount === 1 ? '' : 's') + ' to reach 50.'
          : 'Deck is illegal: remove ' + (mainCount - 50) + ' card' + (mainCount - 50 === 1 ? '' : 's') + ' to reach 50.');
      count.innerHTML = '<span class="azuki-deck-count-text">' + mainCount + '/50</span>';
      count.classList.toggle('is-valid', valid);
      count.classList.toggle('is-invalid', !valid);
      count.title = validityMessage;
      count.setAttribute('aria-label', mainCount + ' of 50 cards. ' + validityMessage);
    }
    var maybe = document.getElementById('azukiMaybeCount');
    if(maybe) maybe.textContent = String(maybeCount);
  }
  function setupToolbarChrome(){
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if(!toolbar) return;
    Array.prototype.forEach.call(toolbar.querySelectorAll(':scope > button'), function(button){
      var label = String(button.textContent || '').trim().toLowerCase();
      if(label === 'home' && !button.id) {
        button.id = 'azukiDeckBackButton';
        button.innerHTML = '<span aria-hidden="true">\u2190</span><span>Decks</span>';
        button.setAttribute('aria-label', 'Back to decks');
      } else if(label === 'edit' && !button.id) {
        button.id = 'azukiDeckLegacyEditButton';
      }
    });
    setupDeckSettingsMenu(toolbar);
    updateDeckSummary();
  }
  function setupDeckSettingsMenu(toolbar){
    if(!toolbar) return;
    if(window.TCGSettings && typeof window.TCGSettings.registerSchema === 'function') {
      window.TCGSettings.registerSchema('AzukiDeck', {
        EnableCardMotion: { type: 'boolean', defaultValue: true }
      });
    }

    var existing = document.getElementById('azukiDeckSettingsMenu');
    if(existing) {
      var existingToggle = existing.querySelector('#azukiDeckMotionSetting');
      if(existingToggle && window.TCGCardMotion) existingToggle.checked = window.TCGCardMotion.isEnabled('AzukiDeck');
      return;
    }

    var menu = document.createElement('details');
    menu.id = 'azukiDeckSettingsMenu';
    menu.innerHTML =
      '<summary id="azukiDeckSettingsButton" class="widget-button" aria-label="Deck settings" title="Deck settings">' +
        '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6.9 1h2.2l.35 1.55c.35.13.68.31.98.53l1.5-.48 1.1 1.9-1.15 1.08c.03.2.05.42.05.63s-.02.42-.05.63l1.15 1.08-1.1 1.9-1.5-.48c-.3.22-.63.4-.98.53L9.1 11.4H6.9l-.35-1.53a5.1 5.1 0 0 1-.98-.53l-1.5.48-1.1-1.9 1.15-1.08a4.1 4.1 0 0 1 0-1.26L2.97 4.5l1.1-1.9 1.5.48c.3-.22.63-.4.98-.53L6.9 1ZM8 4.35a1.86 1.86 0 1 0 0 3.72 1.86 1.86 0 0 0 0-3.72Z"/></svg>' +
      '</summary>' +
      '<div id="azukiDeckSettingsPanel" role="group" aria-label="Deck settings">' +
        '<div class="azuki-deck-settings-heading">Deck settings</div>' +
        '<label class="azuki-deck-settings-row" for="azukiDeckMotionSetting">' +
          '<span><strong>Card animations</strong><small>Animate cards moving between visible zones</small></span>' +
          '<input id="azukiDeckMotionSetting" type="checkbox" role="switch">' +
        '</label>' +
      '</div>';
    toolbar.appendChild(menu);

    var toggle = menu.querySelector('#azukiDeckMotionSetting');
    if(toggle) {
      toggle.checked = window.TCGCardMotion ? window.TCGCardMotion.isEnabled('AzukiDeck') : true;
      toggle.addEventListener('change', function(){
        if(window.TCGSettings && typeof window.TCGSettings.set === 'function') {
          window.TCGSettings.set('EnableCardMotion', !!toggle.checked, {
            rootName: 'AzukiDeck',
            type: 'boolean'
          });
        }
      });
    }
    document.addEventListener('click', function(event){
      if(menu.open && !menu.contains(event.target)) menu.open = false;
    });
  }
  function setDeckDensity(density){
    var dense = density === 'dense';
    var board = document.getElementById('swuDeckBoard');
    if(board) board.classList.toggle('is-dense', dense);
    Array.prototype.forEach.call(document.querySelectorAll('.swu-deck-view-button'), function(button){
      var selected = button.dataset.density === (dense ? 'dense' : 'grid');
      button.classList.toggle('is-active', selected);
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
    try { localStorage.setItem('azukiDeckDensity', dense ? 'dense' : 'grid'); } catch(error) {}
    updateMaybePlaceholders();
  }
  function setupDensityControl(){
    var buttons = document.querySelectorAll('.swu-deck-view-button');
    Array.prototype.forEach.call(buttons, function(button){
      if(button.dataset.densityBound === '1') return;
      button.dataset.densityBound = '1';
      button.addEventListener('click', function(){ setDeckDensity(button.dataset.density); });
    });
    var saved = 'grid';
    try { saved = localStorage.getItem('azukiDeckDensity') || 'grid'; } catch(error) {}
    setDeckDensity(saved === 'dense' ? 'dense' : 'grid');
  }
  function groupLabel(cardID, sortValue){
    var sort = String(sortValue || '').toLowerCase();
    if(sort === 'category' && typeof window.Cardcategory === 'function') {
      var category = String(window.Cardcategory(cardID) || 'Other');
      var labels = { unit: 'Units', entity: 'Units', action: 'Actions', spell: 'Actions', weapon: 'Regalia', regalia: 'Regalia', ikz: 'IKZ' };
      return labels[category.toLowerCase()] || category;
    }
    if(sort === 'ikzcost' && typeof window.CardikzCost === 'function') return 'Cost ' + String(window.CardikzCost(cardID));
    if(sort === 'element' && typeof window.Cardelement === 'function') return String(window.Cardelement(cardID) || 'Neutral');
    return '';
  }
  function updateDeckGroupHeadings(){
    var deck = document.getElementById('myMainDeck');
    if(!deck) return;
    var sortValue = String(window.mySortData || '').split(' ')[0];
    var cards = Array.prototype.slice.call(deck.querySelectorAll(':scope > span[data-mzid]'));
    var signature = sortValue + '|' + cards.map(function(card){ return cardIDFromImage(card.querySelector('img')); }).join('|');
    if(deck.dataset.azukiGroupingSignature === signature) return;
    deck.dataset.azukiGroupingSignature = signature;
    Array.prototype.forEach.call(deck.querySelectorAll('.azuki-deck-group-heading'), function(heading){ heading.remove(); });
    if(['category','ikzcost','element'].indexOf(sortValue.toLowerCase()) === -1) return;
    var previous = null;
    cards.forEach(function(card){
      var label = groupLabel(cardIDFromImage(card.querySelector('img')), sortValue);
      if(!label || label === previous) return;
      var heading = document.createElement('div');
      heading.className = 'azuki-deck-group-heading';
      heading.textContent = label;
      deck.insertBefore(heading, card);
      previous = label;
    });
  }
  function decorateDeckCards(){
    Array.prototype.forEach.call(document.querySelectorAll('#myMainDeck > span[data-mzid]'), function(card){
      card.title = 'Click to remove from deck';
    });
  }
  var workspaceUpdateQueued = false;
  function queueWorkspaceUpdate(){
    if(workspaceUpdateQueued) return;
    workspaceUpdateQueued = true;
    requestAnimationFrame(function(){
      workspaceUpdateQueued = false;
      updateDeckSummary();
      decorateDeckCards();
      updateDeckGroupHeadings();
      updateMaybePlaceholders();
    });
  }
  function updateMaybePlaceholders(){
    var maybeBoard = document.getElementById('mySideboard');
    if(!maybeBoard) return;
    var board = document.getElementById('swuDeckBoard');
    var columnCount = board && board.classList.contains('is-dense') ? 10 : 8;
    var cardCount = maybeBoard.querySelectorAll(':scope > span[data-mzid]').length;
    var desiredCount = Math.max(0, columnCount - Math.min(cardCount, columnCount));
    var placeholders = maybeBoard.querySelectorAll(':scope > .azuki-maybe-placeholder');
    while(placeholders.length > desiredCount) {
      placeholders[placeholders.length - 1].remove();
      placeholders = maybeBoard.querySelectorAll(':scope > .azuki-maybe-placeholder');
    }
    while(placeholders.length < desiredCount) {
      var placeholder = document.createElement('span');
      placeholder.className = 'azuki-maybe-placeholder';
      placeholder.setAttribute('aria-hidden', 'true');
      maybeBoard.appendChild(placeholder);
      placeholders = maybeBoard.querySelectorAll(':scope > .azuki-maybe-placeholder');
    }
  }
  function observeDeckWorkspace(){
    var workspace = document.getElementById('swuDeckWorkspace');
    if(!workspace || workspace.dataset.azukiObserved === '1') return;
    workspace.dataset.azukiObserved = '1';
    new MutationObserver(queueWorkspaceUpdate).observe(workspace, { childList: true, subtree: true });
    queueWorkspaceUpdate();
  }
  function observeToolbar(){
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if(!toolbar || toolbar.dataset.azukiObserved === '1') return;
    toolbar.dataset.azukiObserved = '1';
    new MutationObserver(function(){ requestAnimationFrame(setupToolbarChrome); }).observe(toolbar, { childList: true });
    setupToolbarChrome();
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
  function setupDesktopOverlayMenu(){
    var menu = document.getElementById('swuDesktopOverlayMenu');
    var button = document.getElementById('swuDesktopOverlayButton');
    var panel = document.getElementById('swuDesktopOverlayPanel');
    var stats = document.getElementById('myStatsSlot');
    if(!menu || !button || !panel || !stats || menu.dataset.ready === '1') return;
    menu.dataset.ready = '1';

    function setOpen(open){
      menu.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function updateActive(){
      var active = !!stats.querySelector('.widget-button-selected,.is-active');
      menu.classList.toggle('has-active-overlay', active);
    }

    button.addEventListener('click', function(event){
      event.preventDefault();
      event.stopPropagation();
      setOpen(!menu.classList.contains('is-open'));
    });
    panel.addEventListener('click', function(event){
      event.stopPropagation();
      if(event.target.closest('.widget-button,.widget-button-selected')) {
        window.setTimeout(function(){ setOpen(false); updateActive(); }, 0);
      }
    });
    document.addEventListener('click', function(event){
      if(!menu.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', function(event){
      if(event.key === 'Escape') {
        setOpen(false);
        button.focus();
      }
    });
    new MutationObserver(function(){ requestAnimationFrame(updateActive); })
      .observe(stats, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    updateActive();
  }
  function initializeLayoutEnhancements(){
    bindPaneFilterDismissal();
    observeCardPane();
    observeIdentityBanner();
    observeToolbar();
    observeDeckWorkspace();
    setupDensityControl();
    setupDesktopOverlayMenu();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeLayoutEnhancements);
  else initializeLayoutEnhancements();
})();
</script>
<?php echo $azukiDeckGalleryDarkLink; ?>
