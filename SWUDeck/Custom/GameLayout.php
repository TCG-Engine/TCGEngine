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
    /* The banner is purely an identity readout (which leader(s) + base the deck runs). The engine
       still wires the myLeader/myBase slots' onclick to their zone Click actions, so a stray click
       here used to fire Remove(myLeader) and silently drop the leader. Leaders/bases are changed
       from the Leader1 / Leader2 / Bases browse tabs, never from this banner — so make the whole
       banner non-interactive. pointer-events:none cascades to the slots and images beneath. */
    pointer-events: none;
  }
  /* Widths below are the pre-JS defaults; updateIdentityBannerLayout() overrides them inline once
     the leader count is known. They mirror its formula (base = 1/3 accent + half the 36% overlap =
     51.33%; leader = 2/3 + half the overlap = 84.67%) so there's no flash of a different split
     before the script runs. */
  #swuDeckBoard #myLeaderSlot {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 84.67%;
    height: 100%;
    overflow: hidden;
  }
  /* Cascade a flex row down through the generic engine's wrapper/span markup (#myLeaderWrapper
     div > #myLeader span[flex,flex-wrap — already set inline by PopulateZone] > one <span
     id="myLeader-N"><img></span> per placed leader, per createCardHTML() in Core/UILibraries — NOT
     an <a>, confirmed by reading that function directly) so N leaders (1 normally, up to 2 for
     Twin Suns) lay out side by side sharing the slot's width equally, instead of each trying to
     size to 100% of the whole slot and overlapping. */
  /* #myLeader already gets `display:flex` inline from PopulateZone(), but the pre-existing rule
     `#swuIdentityBanner ... #myLeader ... { display:block !important; }` (below) overrides that
     inline style (!important beats inline) — must explicitly win it back here for the leader slot. */
  #swuDeckBoard #myLeaderSlot #myLeaderWrapper,
  #swuDeckBoard #myLeaderSlot #myLeader {
    display: flex !important;
  }
  #swuDeckBoard #myLeaderSlot #myLeader > span {
    flex: 1 1 0 !important;
    width: auto !important;
    min-width: 0;
    display: block !important;
    /* EXPLICIT height:100% (not just flex-stretch). Firefox treats a flex-stretched height as
       indefinite for percentage-height children, so without this the leader <img>'s own height:100%
       falls back to auto → its natural aspect (e.g. a 350x270 unit crop at ~195px wide = ~151px
       tall), overflowing the ~98px banner and rendering the leaders oversized/zoomed next to a
       correctly-sized base. An explicit height at every level of the chain keeps the percentage
       resolvable in every engine. */
    height: 100% !important;
  }
  /* Per-tile fade/overlap (solid vs. faded edges, negative margin between tiles) is computed and
     applied inline by updateIdentityBannerLayout() in the script below — it depends on tile count
     and position, which plain CSS can't express. */
  #swuDeckBoard #myBaseSlot {
    position: absolute !important;
    left: auto !important;
    right: 0 !important;
    top: 0 !important;
    width: 51.33%;
    height: 100%;
    overflow: hidden;
    /* Pre-JS default (updateIdentityBannerLayout recomputes the solid% to match the overlap): opaque
       from the right edge, fading to transparent at the left so the base cross-dissolves into the
       leader beneath. */
    -webkit-mask-image: linear-gradient(to left, #000 0%, #000 34%, transparent 100%);
    mask-image: linear-gradient(to left, #000 0%, #000 34%, transparent 100%);
  }
  /* No ::after tint overlay: an earlier version darkened the banner's horizontal center (~48-52%)
     to hide the leader/base seam, but that just traded the seam for a vertical dark stripe. The wide
     cross-dissolve (BASE_OVERLAP_PCT) blends the two arts directly, so no masking tint is needed. */
  /* Base tile parity with the leader tiles. The engine renders the base card as a bare INLINE span
     (span#myBase-N); an inline box carries baseline/line-height spacing and a fragile
     percentage-height chain, so in stricter layout engines (Safari/Firefox) the base art ends up
     laid out shorter than the banner and not flush at the bottom — the "base is smaller/inset" bug.
     The leader tiles never hit this because #myLeader is a flex row and each tile is a flex item
     stretched to full height. Give the base the exact same flex-stretch treatment so its art fills
     the banner top-to-bottom identically. */
  #swuDeckBoard #myBaseSlot #myBase {
    display: flex !important;
    align-items: stretch !important;
  }
  #swuDeckBoard #myBaseSlot #myBase > span {
    flex: 1 1 0 !important;
    min-width: 0;
    display: block !important;
    height: 100% !important;
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
    /* !important so no cached/injected rule (e.g. a stale menuStyles/overrides bundle) can flip the
       banner art to object-fit:contain, which would letterbox the very-wide base crop into a short
       strip with dark bands above/below — the "base is smaller / inset" symptom. cover always fills
       the full banner height and crops the overflow. */
    object-fit: cover !important;
    object-position: center;
    border: 0 !important;
  }
  /* left top (not center top): crop files put the character portrait on the left ~40% and
     ability-text art on the right ~60%. A narrow per-leader flex slot (Twin Suns splits this
     into N tiles) center-cropping would land right on that boundary, showing mostly the text
     box's light background instead of the character. */
  #swuIdentityBanner #myLeaderSlot img { object-position: left top; }
  /* Base crops (crops/*_cropped.png, "Base" branch in zzImageConverter.php's CheckImage()) now
     start past the name banner/border at the pixel level (y=130 of the 628x450 landscape source),
     so this is pure art already — no CSS zoom/pan needed, matching the plain object-fit:cover the
     Leader crop uses. (Earlier attempts tried to fake this at runtime with transform:scale/translate
     on the still-includes-the-banner crop; both were fragile — one exposed a background gap when
     the translated image's filled box slid off the container edge. Fixing the crop file itself
     avoids that class of bug entirely.) */
  #swuIdentityBanner #myBaseSlot img { object-position: center; }
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
  /* Live deck-legality badge (design §D). Sits just under the DECK COUNT readout. Hidden for Open
     decks (no legality rules) and until the first validation result arrives. Its issue list is a
     child of the board (not the overflow:hidden banner) so it can expand downward. */
  #swuDeckBoard #swuValidationBadge {
    position: absolute;
    top: 30px;
    left: calc(26% + 12px);
    z-index: 40;
    display: none;
    align-items: center;
    gap: 6px;
    height: 20px;
    padding: 0 10px;
    border-radius: 10px;
    font: 600 11px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.03em;
    cursor: pointer;
    user-select: none;
    border: 1px solid transparent;
    white-space: nowrap;
  }
  /* These state rules carry the #swuDeckBoard prefix so they out-specify the base
     `#swuDeckBoard #swuValidationBadge/#swuValidationIssues` rules (two IDs). Without it, the base
     `display:none` on the issues panel beats a bare `.is-open` (one ID + one class) and the panel
     never opens; the border-color likewise wouldn't win. */
  #swuDeckBoard #swuValidationBadge.is-legal {
    color: #bdf0cd;
    background: rgba(24, 92, 52, 0.55);
    border-color: rgba(120, 230, 160, 0.5);
  }
  #swuDeckBoard #swuValidationBadge.is-illegal {
    color: #f5c6c0;
    background: rgba(104, 34, 30, 0.6);
    border-color: rgba(245, 150, 140, 0.55);
  }
  #swuValidationBadge .swu-val-caret { font-size: 9px; opacity: 0.75; }
  #swuDeckBoard #swuValidationIssues {
    position: absolute;
    top: 52px;
    left: calc(26% + 12px);
    z-index: 39;
    display: none;
    max-width: 44%;
    max-height: 220px;
    overflow-y: auto;
    padding: 8px 12px;
    border-radius: 8px;
    background: rgba(6, 20, 32, 0.97);
    border: 1px solid rgba(245, 150, 140, 0.4);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
    color: rgba(232, 210, 206, 0.95);
    font: 12px/1.45 Arial, Helvetica, sans-serif;
  }
  #swuDeckBoard #swuValidationIssues.is-open { display: block; }
  #swuValidationIssues ul { margin: 0; padding-left: 16px; }
  #swuValidationIssues li { margin: 2px 0; }
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
  /* Leaders/Leader1/Leader2/Bases tiles source full card art from WebpImages/ (landscape 628x450).
     Size them by WIDTH to --swu-deck-card-size (the same viewport/13 the "Cards" tiles use) with
     height following the natural landscape aspect. Since the browse pane is a fixed 25% of the
     viewport and the card is viewport/13, that's always 0.25*13 ≈ 3.25 tiles wide → 3 per row,
     matching the Cards panel at every viewport (rather than the old fixed 60px height, which left
     them as small 4-per-row strips). */
  #myCardPane [id^="myLeaders"] img,
  #myCardPane [id^="myLeader1"] img,
  #myCardPane [id^="myLeader2"] img,
  #myCardPane [id^="myBases"] img {
    width: calc(var(--swu-deck-card-size) - 8px) !important;
    height: auto !important;
    max-width: none !important;
    object-fit: fill;
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
  function applyIdentityCrop(img, useBack){
    if(!img || img.dataset.swuIdentityCrop === '1') return;
    var cardID = cardIDFromImage(img);
    if(!cardID) return;
    img.dataset.swuIdentityCrop = '1';
    if(useBack) {
      // Leaders: window.SWU_LEADER_CROP_URLS (injected by InitialLayout.php) already resolves
      // each leader to its deployed Leader Unit side's own crop via LeaderUnitByUUID() —
      // server-side, no more client-side "_back_cropped.png" guessing/fallback needed. The Leader
      // Unit side is a portrait action-pose image, so a tight portrait crop still reads correctly.
      var resolvedUrl = window.SWU_LEADER_CROP_URLS && window.SWU_LEADER_CROP_URLS[cardID];
      img.src = resolvedUrl || ('./SWUDeck/crops/' + encodeURIComponent(cardID) + '_cropped.png');
    } else {
      // Bases: crops/*_cropped.png now has its own "Base" crop branch (zzImageConverter.php's
      // CheckImage()) tailored to the true landscape WebpImages source — banner + art, border and
      // cost pip trimmed off — the same close-in identity framing the Leader crop gives leaders.
      img.src = './SWUDeck/crops/' + encodeURIComponent(cardID) + '_cropped.png';
    }
  }
  // myLeaderSlot can hold more than one image (Twin Suns decks place up to 2 leaders in the
  // same zone) — crop every leader image found, not just the first.
  function useIdentityCrop(slotID, useBack){
    var imgs = document.querySelectorAll('#' + slotID + ' img');
    imgs.forEach(function(img){ applyIdentityCrop(img, useBack); });
  }
  // How far the base slot overlaps the leader slot, as % of banner width. The base's inner (left)
  // edge fades to transparent across this whole overlap so it cross-dissolves into the leader art
  // underneath instead of butting against it with a visible seam. A WIDE overlap lets a
  // tonally-different base (a bright desert temple against a dark leader scene) melt in rather than
  // read as a second panel — but that same wide overlap CROWDS a second leader (the base creeps
  // over leader 2's right while leader 1 is untouched, so leader 2 looks squeezed). So use a wide
  // overlap only for a solo leader (where there's just one big portrait + base), and a narrower one
  // for Twin Suns so the two leaders and the base read as even thirds. Kept SEPARATE from the
  // leader-to-leader overlap below so tuning the base blend never disturbs the inter-leader seam.
  var BASE_OVERLAP_SOLO = 8;
  var BASE_OVERLAP_MULTI = 4;
  var LEADER_INNER_OVERLAP_PCT = 24;
  // How opaque the base stays toward the banner's middle: the % of the base slot (measured from its
  // outer/right edge) that is fully opaque before the mask fades to transparent at its inner edge.
  // Higher = the base holds its opacity further in and fades over a narrower band right at the
  // leader (rather than dissolving across the whole overlap). Clamped up to the geometric no-muddy-
  // band minimum below, so raising it is always safe.
  var BASE_SOLID_TARGET = 90;
  function updateIdentityBannerLayout(){
    var leaderSlot = document.getElementById('myLeaderSlot');
    var baseSlot = document.getElementById('myBaseSlot');
    if(!leaderSlot || !baseSlot) return;
    var tiles = Array.from(leaderSlot.querySelectorAll('#myLeader > span'));
    var BASE_OVERLAP_PCT = tiles.length > 1 ? BASE_OVERLAP_MULTI : BASE_OVERLAP_SOLO;
    // The base is always a ~1/3-width accent; the leader(s) share the other ~2/3 equally. This
    // was `100/(leaderCount+1)` (an equal split among leaders + base), but that gives a single
    // leader's base a full 50% — a bright base then reads as a dominant second half, and the pair
    // looks like two images butted together. For 2 leaders `100/(N+1)` already equals 100/3, so
    // Twin Suns is unchanged; fixing the fraction only shifts the single-leader case to that same
    // leader-dominant balance, just filled by 1 vs 2 leader tiles.
    var baseFair = 100 / 2.35;
    var leaderFair = 100 - baseFair;
    var baseSlotW = baseFair + BASE_OVERLAP_PCT / 2;
    leaderSlot.style.width = (leaderFair + BASE_OVERLAP_PCT / 2) + '%';
    baseSlot.style.width = baseSlotW + '%';

    // Base mask: opaque from the outer (right) edge inward, fading to transparent at the inner
    // (left) edge. The opaque region MUST reach the leader slot's right edge (x = leaderFair +
    // overlap/2 in banner %), so the base is fully solid exactly where the leader ends — if it were
    // still fading past that point it would sit over the dark banner background with no leader
    // beneath and leave a muddy band. That crossover, expressed as a fraction of the base slot's
    // own width and measured from its right edge, is (baseFair - overlap/2) / baseSlotW; +4% is a
    // safety margin so the base goes fully opaque a hair BEFORE the leader's edge, never after.
    var solidMin = ((baseFair - BASE_OVERLAP_PCT / 2) / baseSlotW) * 100 + 4;
    var solidPct = Math.max(0, Math.min(100, Math.max(solidMin, BASE_SOLID_TARGET)));
    var baseMask = 'linear-gradient(to left, #000 0%, #000 ' + solidPct + '%, transparent 100%)';
    baseSlot.style.webkitMaskImage = baseMask;
    baseSlot.style.maskImage = baseMask;

    // Leader tiles: fade an edge ONLY where it faces another tile (an inner Twin Suns seam). Outer
    // edges stay fully opaque — the leftmost tile's left edge frames the banner, and the rightmost
    // tile's right (hard) edge is hidden under the now-opaque base, so fading it would only thin the
    // leader and let dark background show through the base's transparent zone. Each non-last tile
    // gets a negative right margin so its neighbor slides over it by LEADER_INNER_OVERLAP_PCT.
    var innerOverlapPct = tiles.length > 1 ? LEADER_INNER_OVERLAP_PCT : 0;
    tiles.forEach(function(tile, i){
      var isFirst = i === 0;
      var isLast = i === tiles.length - 1;
      tile.style.marginRight = isLast ? '0' : (-innerOverlapPct) + '%';
      var left = isFirst ? '#000 0%' : 'transparent 0%, #000 18%';
      var right = isLast ? '#000 100%' : '#000 82%, transparent 100%';
      var gradient = 'linear-gradient(to right, ' + left + ', ' + right + ')';
      tile.style.webkitMaskImage = gradient;
      tile.style.maskImage = gradient;
    });
  }
  function enhanceIdentityBanner(){
    useIdentityCrop('myLeaderSlot', true);
    useIdentityCrop('myBaseSlot', false);
    updateIdentityBannerLayout();
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
  // Twin Suns decks pick leaders from separate "Leader1"/"Leader2" tabs (so the player controls
  // which slot they're filling); every other format keeps the single "Leaders" tab. All three
  // tabs always exist (schema-driven, one per pane zone) — this just shows/hides by tab label,
  // since generated tab buttons carry no other zone identifier client-side.
  function updateLeaderTabVisibility(){
    var pane = document.getElementById('myCardPane');
    if(!pane) return;
    var isTwinSuns = window.SWU_DECK_FORMAT === 'twinsuns';
    var tabs = pane.querySelectorAll('.panelTab');
    tabs.forEach(function(tab){
      var label = tab.textContent.trim();
      if(label === 'Leaders') tab.style.display = isTwinSuns ? 'none' : '';
      else if(label === 'Leader1' || label === 'Leader2') tab.style.display = isTwinSuns ? '' : 'none';
    });
  }
  // ── Live deck-legality validation (design §D) ─────────────────────────────────────────────────
  // Poll SWUDeck/ValidateDeckState.php (shared SWUCheckFormat over the saved gamestate) whenever the
  // deck changes and reflect the result in a badge. Open-format decks report applicable:false, which
  // hides the badge. The server autosaves before it returns the re-render that triggers our observer,
  // so the debounced fetch reads the just-saved state.
  var _swuValTimer = null;
  function swuGameNameFromURL(){
    try { return new URLSearchParams(window.location.search).get('gameName') || ''; }
    catch(e){ return ''; }
  }
  function ensureValidationEls(){
    var board = document.getElementById('swuDeckBoard');
    if(!board) return null;
    var badge = document.getElementById('swuValidationBadge');
    if(!badge){
      badge = document.createElement('div');
      badge.id = 'swuValidationBadge';
      badge.setAttribute('role', 'button');
      badge.setAttribute('tabindex', '0');
      badge.setAttribute('aria-expanded', 'false');
      badge.innerHTML = "<span class='swu-val-label'></span><span class='swu-val-caret' aria-hidden='true'>&#9662;</span>";
      board.appendChild(badge);
      var issues = document.createElement('div');
      issues.id = 'swuValidationIssues';
      issues.setAttribute('role', 'region');
      issues.setAttribute('aria-label', 'Deck legality issues');
      board.appendChild(issues);
      var toggle = function(){
        var box = document.getElementById('swuValidationIssues');
        if(!box || !box.innerHTML) return;   // nothing to expand when legal
        var open = box.classList.toggle('is-open');
        badge.setAttribute('aria-expanded', open ? 'true' : 'false');
      };
      badge.addEventListener('click', toggle);
      badge.addEventListener('keydown', function(e){ if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); toggle(); } });
    }
    return badge;
  }
  function _swuEscape(s){
    return String(s).replace(/[<>&]/g, function(c){ return { '<':'&lt;', '>':'&gt;', '&':'&amp;' }[c]; });
  }
  function renderValidation(data){
    var badge = ensureValidationEls();
    if(!badge) return;
    var box = document.getElementById('swuValidationIssues');
    if(!data || !data.applicable){
      badge.style.display = 'none';
      if(box){ box.classList.remove('is-open'); box.innerHTML = ''; }
      return;
    }
    badge.style.display = 'inline-flex';
    badge.classList.toggle('is-legal', !!data.legal);
    badge.classList.toggle('is-illegal', !data.legal);
    var label = badge.querySelector('.swu-val-label');
    if(data.legal){
      label.textContent = '✓ ' + (data.formatName || 'Format') + ' legal';
      if(box){ box.classList.remove('is-open'); box.innerHTML = ''; }
      badge.setAttribute('aria-expanded', 'false');
    } else {
      var n = data.issueCount || (data.issues ? data.issues.length : 0);
      label.textContent = '✗ ' + n + ' issue' + (n === 1 ? '' : 's');
      if(box){
        box.innerHTML = '<ul>' + (data.issues || []).map(function(s){ return '<li>' + _swuEscape(s) + '</li>'; }).join('') + '</ul>';
      }
    }
  }
  function runValidation(){
    var g = swuGameNameFromURL();
    if(!g) return;
    fetch('./SWUDeck/ValidateDeckState.php?gameName=' + encodeURIComponent(g), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(renderValidation)
      .catch(function(){ /* transient failure — keep the last shown state */ });
  }
  function scheduleValidation(){
    if(_swuValTimer) clearTimeout(_swuValTimer);
    _swuValTimer = setTimeout(runValidation, 450);
  }
  function observeValidation(){
    var deck = document.getElementById('myDeckSlot');       // "DECK COUNT: N" — mutates on card add/remove
    var banner = document.getElementById('swuIdentityBanner'); // mutates on leader/base change
    if(deck) new MutationObserver(scheduleValidation).observe(deck, { childList: true, subtree: true, characterData: true });
    if(banner) new MutationObserver(scheduleValidation).observe(banner, { childList: true, subtree: true });
    runValidation();
  }

  function observeCardPane(){
    var slot = document.getElementById('myCardPaneSlot');
    if(!slot) return;
    new MutationObserver(function(){ requestAnimationFrame(function(){
      bindCardPaneScroll();
      compactPaneFilters();
      updateLeaderTabVisibility();
    }); })
      .observe(slot, { childList: true, subtree: true });
    bindCardPaneScroll();
    compactPaneFilters();
    updateLeaderTabVisibility();
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
    observeValidation();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeLayoutEnhancements);
  else initializeLayoutEnhancements();
})();
</script>
