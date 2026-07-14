<?php
// GameLayoutMobile.php — SWUDeck phone layout.
//
// The phone editor is a two-page horizontal workspace: a full-height card library and a
// full-height deck workspace. Both pages keep the generated zone slot ids, so
// NextTurnRender.php continues to populate them without a mobile-only renderer.
?>
<style>
  :root { --swu-mobile-viewport-height: 100vh; }
  @supports (height: 100dvh) {
    :root { --swu-mobile-viewport-height: 100dvh; }
  }

  /* Own the complete phone viewport. NextTurn's shared fixed shell historically relies on
     browser defaults here, which can expose the document canvas as a thin strip along the
     right/bottom edge on mobile. The dynamic height also keeps iOS Safari's bottom URL bar
     from covering the deck workspace as its visible viewport expands and contracts. */
  html,
  body {
    width: 100%;
    height: var(--swu-mobile-viewport-height);
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    background: #020c16;
  }
  #mainDiv {
    inset: 0 0 auto 0 !important;
    width: auto !important;
    height: var(--swu-mobile-viewport-height) !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    box-sizing: border-box;
    overflow: hidden;
  }

  /* Keep the four primary destinations visible. Secondary deck controls are moved into the
     mobile overflow menu by setupToolbarMenu(), so the rail never clips or pans sideways. */
  .flex-container > .flex-item:first-child {
    overflow: visible !important;
    padding-right: 5px !important;
  }
  .flex-container > .flex-item:first-child button { white-space: nowrap !important; }
  .flex-container > .flex-item:first-child > #AssetVisibility,
  .flex-container > .flex-item:first-child > #Versions,
  .flex-container > .flex-item:first-child > button:nth-of-type(n+5) { display: none !important; }

  #swuMobileToolbarMenu {
    position: relative;
    flex: 0 0 auto;
    margin-left: auto;
    z-index: 130;
  }
  #swuMobileToolbarMenuButton {
    width: 34px !important;
    min-width: 34px !important;
    padding: 0 !important;
    gap: 3px;
    flex-direction: column !important;
  }
  #swuMobileToolbarMenuButton .swu-mobile-menu-line {
    display: block;
    width: 14px;
    height: 1px;
    border-radius: 2px;
    background: rgba(190,216,232,0.88);
    box-shadow: 0 0 4px rgba(var(--accent-rgb),0.18);
  }
  #swuMobileToolbarMenuPanel {
    position: absolute;
    top: calc(100% + 8px);
    right: 1px;
    display: none;
    width: min(250px,calc(100vw - 14px));
    max-height: calc(100dvh - 58px);
    padding: 8px;
    box-sizing: border-box;
    overflow-y: auto;
    border: 1px solid rgba(var(--accent-rgb),0.30);
    border-radius: 8px;
    background: rgba(3,15,26,0.985);
    box-shadow: 0 12px 30px rgba(0,0,0,0.56),0 0 10px rgba(var(--accent-rgb),0.08);
  }
  #swuMobileToolbarMenu.is-open #swuMobileToolbarMenuPanel {
    display: flex;
    flex-direction: column;
    gap: 7px;
  }
  .swu-mobile-toolbar-menu-heading {
    padding: 1px 3px 4px;
    color: rgba(171,205,225,0.62);
    font: 700 9px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  .swu-mobile-toolbar-setting {
    display: grid;
    grid-template-columns: 66px minmax(0,1fr);
    gap: 7px;
    align-items: center;
    min-width: 0;
    padding: 6px;
    border: 1px solid rgba(var(--accent-rgb),0.14);
    border-radius: 6px;
    background: rgba(7,24,38,0.72);
  }
  .swu-mobile-toolbar-setting > span {
    color: rgba(171,205,225,0.66);
    font: 700 9px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .swu-mobile-toolbar-setting > div { min-width: 0; }
  #swuMobileToolbarMenu #AssetVisibility,
  #swuMobileToolbarMenu #Versions {
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #swuMobileToolbarMenu #visibilityDropdownWrapper,
  #swuMobileToolbarMenu #versionDropdownWrapper { display: block !important; width: 100%; }
  #swuMobileToolbarMenu #visibilityDropdownTrigger,
  #swuMobileToolbarMenu #versionDropdownTrigger,
  #swuMobileToolbarRefreshSlot > button {
    width: 100% !important;
    min-width: 0 !important;
    margin: 0 !important;
    justify-content: space-between !important;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #swuMobileToolbarMenu #visibilityDropdownMenu,
  #swuMobileToolbarMenu #versionDropdownMenu {
    position: static !important;
    width: 100%;
    min-width: 0 !important;
    max-height: 210px;
    margin-top: 5px;
    overflow-y: auto !important;
    box-sizing: border-box;
  }
  #swuMobileToolbarMenu #myDeckWrapper,
  #swuMobileToolbarMenu #myDeck {
    width: 100%;
    min-width: 0;
    overflow: visible !important;
    background: transparent !important;
  }
  #swuMobileToolbarMenu #myDeck {
    justify-content: stretch !important;
    font-size: 0 !important;
  }
  #swuMobileToolbarMenu #myDeck > span {
    display: flex !important;
    width: 100%;
    margin: 0 !important;
  }
  #swuMobileToolbarMenu #myDeck .widget-button {
    width: 100% !important;
    min-width: 0 !important;
    margin: 0 !important;
    justify-content: center !important;
    font-size: 11px !important;
  }

  /* The shared engine shell gives .myStuff an inset gray frame. The desktop SWU layout
     already removes it; mobile needs the same edge-to-edge geometry while retaining the
     shell's starfield background. */
  #myStuff.myStuff {
    inset: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
  }

  #swuDeckMobileRoot {
    position: absolute;
    inset: 0;
    z-index: 11;
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
    touch-action: pan-y;
  }

  /* The same shallow leader-unit/base treatment used by the desktop editor. It remains
     visible while paging, acting as the deck's identity rather than belonging to either job. */
  #swuDeckMobileIdentity {
    position: relative;
    flex: 0 0 clamp(68px, 18vw, 84px);
    margin: 7px 9px 5px;
    overflow: hidden;
    border: 1px solid rgba(var(--accent-rgb),0.24);
    border-radius: 8px;
    background: rgba(1,10,20,0.76);
    box-shadow: inset 0 0 20px rgba(0,0,0,0.38);
  }
  #swuDeckMobileIdentity #myLeaderSlot,
  #swuDeckMobileIdentity #myBaseSlot,
  #swuDeckMobileIdentity .swu-mobile-identity-art {
    position: absolute !important;
    top: 0 !important;
    width: 58%;
    height: 100%;
    overflow: hidden;
  }
  #swuDeckMobileIdentity #myLeaderSlot,
  #swuMobileLeaderArt {
    left: 0 !important;
    -webkit-mask-image: linear-gradient(to right,#000 0%,#000 68%,transparent 100%);
    mask-image: linear-gradient(to right,#000 0%,#000 68%,transparent 100%);
  }
  #swuDeckMobileIdentity #myBaseSlot,
  #swuMobileBaseArt {
    right: 0 !important;
    -webkit-mask-image: linear-gradient(to left,#000 0%,#000 68%,transparent 100%);
    mask-image: linear-gradient(to left,#000 0%,#000 68%,transparent 100%);
  }
  /* The source crop is much taller than this banner. A slightly narrower visible base layer
     makes object-fit:cover scale it down, revealing more of the art without stretching it.
     Keep the larger slot above for the full click target and center blend. */
  #swuMobileBaseArt { width: 48%; }
  #swuDeckMobileIdentity .swu-mobile-identity-art {
    z-index: 1;
    pointer-events: none;
  }
  #swuDeckMobileIdentity #myLeaderSlot,
  #swuDeckMobileIdentity #myBaseSlot { z-index: 3; }
  #swuDeckMobileIdentity #myLeaderSlot > *,
  #swuDeckMobileIdentity #myBaseSlot > * { visibility: hidden !important; }
  #swuDeckMobileIdentity::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: linear-gradient(90deg,
      rgba(2,12,23,0.08) 0%, transparent 31%, rgba(3,18,32,0.36) 48%,
      rgba(3,18,32,0.28) 52%, transparent 69%, rgba(2,12,23,0.08) 100%);
  }
  #swuDeckMobileIdentity #myLeaderWrapper,
  #swuDeckMobileIdentity #myBaseWrapper,
  #swuDeckMobileIdentity #myLeader,
  #swuDeckMobileIdentity #myBase,
  #swuDeckMobileIdentity a,
  #swuDeckMobileIdentity img {
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
  }
  #swuDeckMobileIdentity img {
    object-fit: cover;
    object-position: center;
    border: 0 !important;
  }
  #swuDeckMobileIdentity #myLeaderSlot img,
  #swuMobileLeaderArt img { object-position: center top; }
  /* Base crops put most of their landmarks below the vertical midpoint. Bias the zoomed-out
     slice toward that lower focal area instead of filling it with the quieter sky band. */
  #swuMobileBaseArt img { object-position: center 68%; }

  #swuDeckMobileViewport {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
  }
  #swuDeckMobileTrack {
    display: flex;
    width: 200%;
    height: 100%;
    transform: translate3d(0,0,0);
    transition: transform 220ms cubic-bezier(.2,.72,.2,1);
    will-change: transform;
  }
  #swuDeckMobileRoot[data-pane="deck"] #swuDeckMobileTrack { transform: translate3d(-50%,0,0); }
  @media (prefers-reduced-motion: reduce) {
    #swuDeckMobileTrack { transition: none; }
  }
  .swu-mobile-page {
    position: relative;
    flex: 0 0 50%;
    width: 50%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    box-sizing: border-box;
  }
  .swu-mobile-page-bar {
    flex: 0 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 11px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(var(--accent-rgb),0.18);
    color: rgba(190,216,232,0.78);
    font: 700 11px/28px Arial, Helvetica, sans-serif;
    letter-spacing: 0.09em;
    text-transform: uppercase;
  }
  .swu-mobile-page-dots { display: inline-flex; gap: 5px; }
  .swu-mobile-library-bar {
    position: relative;
    z-index: 220;
    gap: 6px;
    overflow: visible;
  }
  .swu-mobile-library-bar > span:first-child { flex: 0 0 auto; }
  #swuMobileLibraryTabsSlot {
    flex: 1 1 auto;
    min-width: 0;
  }
  #swuMobileLibraryTabsSlot .swu-mobile-pane-tabs-row {
    justify-content: flex-end !important;
    width: 100%;
    margin: 0 !important;
  }
  #swuMobileLibraryTabsSlot .panelTab {
    min-height: 22px !important;
    margin: 1px !important;
    padding: 0 7px !important;
    font-size: 10px !important;
    line-height: 20px !important;
  }
  .swu-mobile-library-bar .swu-mobile-page-dots { flex: 0 0 auto; }
  .swu-mobile-page-title { display: inline-flex; align-items: center; min-width: 0; }
  #swuMobileDeckCount {
    margin-left: 7px;
    padding-left: 7px;
    border-left: 1px solid rgba(var(--accent-rgb),0.22);
    color: rgba(205,228,240,0.88);
    font: 700 9px/28px Arial, Helvetica, sans-serif;
    letter-spacing: 0.06em;
  }
  .swu-mobile-page-dots i {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: rgba(var(--accent-rgb),0.24);
  }
  #swuMobileSearchPage .swu-mobile-page-dots i:first-child,
  #swuMobileDeckPage .swu-mobile-page-dots i:last-child {
    background: var(--accent-strong);
    box-shadow: 0 0 5px rgba(var(--accent-rgb),0.36);
  }
  .swu-mobile-deck-bar {
    position: relative;
    z-index: 220;
    gap: 5px;
    overflow: visible;
  }
  .swu-mobile-deck-bar .swu-mobile-page-title {
    flex: 0 1 auto;
    white-space: nowrap;
  }
  .swu-mobile-deck-bar #swuMobileDeckCount {
    margin-left: 5px;
    padding-left: 5px;
  }
  .swu-mobile-deck-bar .swu-mobile-deck-title-tools { flex: 1 1 auto; }
  .swu-mobile-deck-bar #mySortSlot {
    width: min(132px,32vw);
    min-width: 78px;
  }
  .swu-mobile-deck-bar .swu-mobile-page-dots { flex: 0 0 auto; }

  /* Library page: fixed controls inside CardPane, scrollable results, persistent recent tray. */
  #swuMobileSearchPage { display: flex; flex-direction: column; overflow: hidden; }
  #swuMobileSearchPage #myCardPaneSlot {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    padding: 2px 0 0;
    box-sizing: border-box;
    overflow: hidden;
  }
  #swuMobileSearchPage #myCardPaneWrapper {
    width: 100%;
    height: 100%;
    overflow: hidden !important;
    box-sizing: border-box;
  }
  #swuMobileSearchPage #myCardPane {
    display: flex !important;
    flex-direction: column;
    flex-wrap: nowrap !important;
    align-items: stretch;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden !important;
  }
  #swuMobileSearchPage #myCardPane > div:first-child {
    flex: 0 0 auto;
    margin-inline: 8px;
    width: auto !important;
    overflow: visible !important;
  }
  #swuMobileSearchPage .swu-mobile-pane-tabs-row {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    gap: 0;
    position: relative;
    margin-inline: 8px;
    overflow: visible !important;
  }
  #swuMobileSearchPage .swu-mobile-filter-menu {
    position: relative;
    flex: 0 0 auto;
    margin-left: auto;
    z-index: 230;
  }
  #swuMobileSearchPage .swu-mobile-filter-trigger {
    position: relative !important;
    display: inline-flex !important;
    width: 28px !important;
    min-width: 28px !important;
    height: 25px !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 3px 5px !important;
    margin: 2px !important;
    list-style: none;
  }
  #swuMobileSearchPage .swu-mobile-filter-trigger::-webkit-details-marker { display: none; }
  #swuMobileSearchPage .swu-mobile-filter-trigger svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
  }
  #swuMobileSearchPage .swu-mobile-filter-count {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 14px;
    height: 14px;
    padding: 0 3px;
    box-sizing: border-box;
    border: 1px solid rgba(var(--accent-rgb),0.48);
    border-radius: 8px;
    background: rgba(3,15,25,0.98);
    color: rgba(215,238,249,0.96);
    font: 700 8px/12px Arial, Helvetica, sans-serif;
    text-align: center;
  }
  #swuMobileSearchPage .swu-mobile-filter-menu[open] .swu-mobile-filter-trigger {
    color: rgba(217,240,251,0.98) !important;
    filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.42)) !important;
  }
  #swuMobileSearchPage .swu-mobile-filter-popover {
    position: absolute;
    top: calc(100% + 4px);
    right: 1px;
    min-width: 172px;
    padding: 5px;
    box-sizing: border-box;
    border: 1px solid rgba(var(--accent-rgb),0.30);
    border-radius: 7px;
    background: rgba(3,15,26,0.985);
    box-shadow: 0 10px 25px rgba(0,0,0,0.54),0 0 8px rgba(var(--accent-rgb),0.08);
  }
  #swuMobileSearchPage .swu-mobile-filter-options {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    flex-wrap: nowrap !important;
    gap: 2px !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #swuMobileSearchPage .swu-mobile-filter-options > div {
    display: flex !important;
    min-height: 30px;
    align-items: center !important;
    padding: 3px 6px;
    box-sizing: border-box;
  }
  #swuMobileSearchPage .swu-mobile-filter-options > div:hover { background: rgba(var(--accent-rgb),0.08); }
  #swuMobileSearchPage .swu-mobile-filter-options label {
    margin-left: 0 !important;
    font-size: 11px !important;
    white-space: nowrap;
  }
  #swuMobileSearchPage #my_CardPane_content {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    margin-top: 4px !important;
    padding: 5px 7px 9px !important;
    box-sizing: border-box;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(var(--accent-rgb),0.34) transparent;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
  }
  #swuMobileSearchPage #my_CardPane_content > span {
    width: 100%;
    justify-content: flex-start !important;
    align-content: flex-start;
  }
  #swuMobileSearchPage #my_CardPane_content > span > span[data-mzid] {
    flex: 0 0 calc(25% - 4px);
    width: calc(25% - 4px);
    min-width: 0;
    margin: 2px !important;
    box-sizing: border-box;
  }
  #swuMobileSearchPage #my_CardPane_content > span > span[data-mzid] > a {
    display: block !important;
    width: 100%;
    margin: 0 !important;
  }
  #swuMobileSearchPage #my_CardPane_content > span > span[data-mzid] > a > img:first-child {
    display: block;
    width: 100% !important;
    height: auto !important;
    box-sizing: border-box;
  }
  #swuMobileSearchPage #my_CardPane_content::-webkit-scrollbar { width: 5px; }
  #swuMobileSearchPage #my_CardPane_content::-webkit-scrollbar-track { background: transparent; }
  #swuMobileSearchPage #my_CardPane_content::-webkit-scrollbar-thumb {
    border-radius: 4px;
    background: rgba(var(--accent-rgb),0.34);
  }
  /* Card actions use tap-specific mobile flows; never expose the desktop hover controls. */
  #swuDeckMobileRoot [data-mzid] .widget-buttons {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }

  #swuMobileRecent {
    position: relative;
    flex: 0 0 92px;
    min-width: 0;
    padding: 5px 8px calc(6px + env(safe-area-inset-bottom));
    box-sizing: border-box;
    border-top: 1px solid rgba(var(--accent-rgb),0.24);
    background: linear-gradient(180deg,rgba(5,18,30,0.97),rgba(2,12,22,0.98));
    box-shadow: 0 -5px 16px rgba(0,0,0,0.28);
    transition: flex-basis 160ms ease,padding 160ms ease;
  }
  #swuMobileRecent.is-empty {
    flex-basis: 34px;
    padding-top: 3px;
    padding-bottom: calc(3px + env(safe-area-inset-bottom));
    box-shadow: 0 -3px 10px rgba(0,0,0,0.20);
  }
  #swuMobileRecent.is-empty .swu-mobile-recent-heading { height: 28px; line-height: 28px; }
  #swuMobileRecent.is-empty .swu-mobile-recent-heading small { line-height: 28px; }
  #swuMobileRecent.is-empty #swuMobileRecentList { display: none; }
  #swuMobileRecent.is-empty #swuMobileRecentConfirm { display: none; }
  @media (prefers-reduced-motion: reduce) {
    #swuMobileRecent { transition: none; }
  }
  .swu-mobile-recent-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    height: 18px;
    color: rgba(190,216,232,0.82);
    font: 700 10px/18px Arial, Helvetica, sans-serif;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .swu-mobile-recent-heading small {
    color: rgba(160,195,225,0.48);
    font: 500 9px/18px Arial, Helvetica, sans-serif;
    letter-spacing: 0.04em;
  }
  #swuMobileRecentList {
    display: flex;
    gap: 6px;
    height: 62px;
    padding-right: 70px;
    box-sizing: border-box;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    -webkit-mask-image: linear-gradient(to right,#000 0,#000 calc(100% - 148px),rgba(0,0,0,0.68) calc(100% - 114px),rgba(0,0,0,0.20) calc(100% - 82px),transparent calc(100% - 60px));
    mask-image: linear-gradient(to right,#000 0,#000 calc(100% - 148px),rgba(0,0,0,0.68) calc(100% - 114px),rgba(0,0,0,0.20) calc(100% - 82px),transparent calc(100% - 60px));
  }
  #swuMobileRecentList::-webkit-scrollbar { display: none; }
  #swuMobileRecentConfirm {
    position: absolute;
    right: 8px;
    bottom: calc(8px + env(safe-area-inset-bottom));
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    padding: 0;
    border: 1px solid rgba(124,247,168,0.84);
    border-radius: 8px;
    background: linear-gradient(180deg,rgba(39,151,85,0.90),rgba(17,88,49,0.96));
    box-shadow: inset 0 0 13px rgba(172,255,203,0.16),0 0 13px rgba(75,255,145,0.34);
    color: rgba(218,255,231,0.98);
    cursor: pointer;
  }
  #swuMobileRecentConfirm:hover,
  #swuMobileRecentConfirm:focus-visible {
    border-color: rgba(194,255,216,1);
    background: linear-gradient(180deg,rgba(64,215,123,0.98),rgba(24,137,70,1));
    box-shadow: inset 0 0 17px rgba(202,255,220,0.28),0 0 20px rgba(82,255,151,0.68);
    color: #f0fff5;
    outline: none;
  }
  #swuMobileRecentConfirm:active { transform: translateY(1px); }
  .swu-mobile-confirm-check {
    position: absolute;
    top: 2px;
    left: 50%;
    width: 34px;
    height: 34px;
    transform: translateX(-50%);
  }
  .swu-mobile-confirm-check::after {
    content: '';
    position: absolute;
    left: 10px;
    top: 4px;
    width: 10px;
    height: 19px;
    border: solid rgba(194,255,216,0.98);
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
    filter: drop-shadow(0 0 2px rgba(88,255,153,0.72));
  }
  .swu-mobile-confirm-label {
    position: absolute;
    right: 2px;
    bottom: 4px;
    left: 2px;
    color: rgba(224,255,235,0.94);
    font: 700 8px/9px Arial, Helvetica, sans-serif;
    letter-spacing: 0.06em;
    text-align: center;
    text-transform: uppercase;
    text-shadow: 0 0 4px rgba(78,255,145,0.45);
  }
  .swu-mobile-recent-empty {
    display: flex;
    align-items: center;
    height: 100%;
    color: rgba(160,195,225,0.42);
    font: 500 11px/1.2 Arial, Helvetica, sans-serif;
  }
  .swu-mobile-recent-card {
    position: relative;
    flex: 0 0 112px;
    display: grid;
    grid-template-columns: 50px 1fr;
    gap: 6px;
    align-items: center;
    height: 58px;
    padding: 3px 5px 3px 3px;
    box-sizing: border-box;
    overflow: hidden;
    border: 1px solid rgba(var(--accent-rgb),0.25);
    border-radius: 6px;
    background: rgba(8,27,42,0.94);
    color: rgba(210,232,244,0.92);
    text-align: left;
    cursor: pointer;
  }
  .swu-mobile-recent-card:active { transform: translateY(1px); background: rgba(13,38,57,0.98); }
  .swu-mobile-recent-card.is-busy { opacity: 0.48; pointer-events: none; }
  .swu-mobile-recent-card img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
  .swu-mobile-recent-card-copy {
    min-width: 0;
    font: 600 10px/1.15 Arial, Helvetica, sans-serif;
  }
  .swu-mobile-recent-card-copy strong,
  .swu-mobile-recent-card-copy span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .swu-mobile-recent-card-copy strong { max-height: 24px; color: inherit; }
  .swu-mobile-recent-card-copy span { margin-top: 3px; color: rgba(160,195,225,0.55); font-size: 9px; white-space: nowrap; }
  .swu-mobile-recent-qty {
    position: absolute;
    left: 38px;
    bottom: 3px;
    min-width: 16px;
    height: 16px;
    border: 1px solid rgba(var(--accent-rgb),0.55);
    border-radius: 8px;
    background: rgba(3,15,25,0.96);
    color: #dff4ff;
    font: 700 9px/14px Arial, Helvetica, sans-serif;
    text-align: center;
  }

  /* Deck page: the page label and command dock are fixed. Only the card collections scroll. */
  #swuMobileDeckPage {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding-bottom: 0;
  }
  #swuMobileDeckScroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    box-sizing: border-box;
    scrollbar-width: thin;
    scrollbar-color: rgba(var(--accent-rgb),0.34) transparent;
  }
  #swuMobileDeckScroll::-webkit-scrollbar { width: 5px; }
  #swuMobileDeckScroll::-webkit-scrollbar-track { background: transparent; }
  #swuMobileDeckScroll::-webkit-scrollbar-thumb {
    border-radius: 4px;
    background: rgba(var(--accent-rgb),0.34);
  }
  #swuMobileDeckPage #myStatsWrapper,
  #swuMobileDeckPage #mySortWrapper,
  #swuMobileDeckPage #myStats,
  #swuMobileDeckPage #mySort {
    min-width: 0;
    overflow: visible !important;
    background: transparent !important;
  }
  #swuMobileDeckPage #mySort,
  #swuMobileDeckPage #myStats {
    width: 100%;
    font-size: 0 !important;
  }
  #swuMobileDeckPage #mySort > span,
  #swuMobileDeckPage #myStats > span { display: none !important; }
  #swuMobileDeckPage #mySort > div,
  #swuMobileDeckPage #myStats > div {
    width: 100%;
    min-width: 0;
    padding: 0 !important;
    box-sizing: border-box;
  }
  #swuMobileDeckPage #myStats > div {
    display: flex !important;
    flex-direction: column;
    flex-wrap: nowrap !important;
    gap: 5px !important;
    justify-content: stretch !important;
  }
  #swuMobileDeckPage #mySort .widget-dd-trigger {
    width: 100% !important;
    height: 24px !important;
    min-width: 0;
    margin: 0 !important;
    padding: 2px 8px !important;
    box-sizing: border-box;
    justify-content: space-between !important;
    font-size: 10px !important;
  }
  #swuMobileDeckPage #mySort .widget-dd-wrap { width: 100%; }
  #swuMobileDeckPage #myStats {
    display: flex !important;
    flex-direction: column;
    flex-wrap: nowrap !important;
    gap: 5px;
    align-items: stretch;
    justify-content: stretch !important;
  }
  #swuMobileDeckPage #myStats .widget-button,
  #swuMobileDeckPage #myStats .widget-button-selected {
    flex: 0 0 auto;
    width: 100%;
    min-width: 0;
    margin: 0 !important;
    height: 28px !important;
    padding: 3px !important;
    box-sizing: border-box;
    overflow: hidden;
    font-size: 10px !important;
    text-overflow: ellipsis;
    white-space: nowrap !important;
  }

  .swu-dm-title {
    position: sticky;
    top: 0;
    z-index: 12;
    display: flex;
    min-height: 34px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 4px 8px 4px 10px;
    box-sizing: border-box;
    border-top: 1px solid rgba(var(--accent-rgb),0.12);
    border-bottom: 1px solid rgba(var(--accent-rgb),0.20);
    background: rgba(5,18,30,0.95);
    color: rgba(190,216,232,0.82);
    font: 700 11px/18px Arial, Helvetica, sans-serif;
    letter-spacing: 0.09em;
    text-transform: uppercase;
  }
  .swu-dm-title #mySortSlot {
    width: min(142px,45vw);
    min-width: 0;
  }
  .swu-mobile-deck-title-tools {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 5px;
    margin-left: auto;
  }
  .swu-mobile-overlay-menu { position: relative; flex: 0 0 auto; }
  #swuMobileOverlayButton {
    width: 28px !important;
    min-width: 28px !important;
    height: 24px !important;
    margin: 0 !important;
    padding: 3px 5px !important;
    color: rgba(190,216,232,0.82) !important;
  }
  #swuMobileOverlayButton svg {
    display: block;
    width: 14px;
    height: 14px;
    margin: auto;
    fill: currentColor;
  }
  .swu-mobile-overlay-menu.has-active-overlay #swuMobileOverlayButton {
    color: rgba(217,240,251,0.98) !important;
    filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.42)) !important;
  }
  #swuMobileOverlayPanel {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    display: none;
    width: min(216px,calc(100vw - 20px));
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid rgba(var(--accent-rgb),0.28);
    border-radius: 7px;
    background: rgba(3,15,26,0.985);
    box-shadow: 0 10px 26px rgba(0,0,0,0.52),0 0 9px rgba(var(--accent-rgb),0.08);
  }
  .swu-mobile-overlay-menu.is-open #swuMobileOverlayPanel { display: block; }
  .swu-mobile-overlay-heading {
    padding: 1px 2px 7px;
    color: rgba(171,205,225,0.62);
    font: 700 9px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
  #swuMobileDeckPage #myMainDeckWrapper,
  #swuMobileDeckPage #mySideboardWrapper {
    width: 100%;
    height: auto !important;
    overflow: visible !important;
    box-sizing: border-box;
  }
  #swuMobileDeckPage #myMainDeck,
  #swuMobileDeckPage #mySideboard {
    width: 100%;
    padding: 5px 7px 9px;
    box-sizing: border-box;
    justify-content: flex-start !important;
    align-content: flex-start;
  }
  #swuMobileDeckPage #mySideboard > span:only-child:not([data-mzid]) { display: none !important; }
  #swuMobileDeckPage .counter-bubble {
    top: auto !important;
    right: 4px !important;
    bottom: 4px !important;
    left: auto !important;
    width: 22px !important;
    height: 22px !important;
    margin: 0 !important;
    transform: none !important;
    border: 1px solid rgba(var(--accent-rgb),0.50) !important;
    border-radius: 6px !important;
    background: rgba(5,17,27,0.95) !important;
    color: rgba(215,236,247,0.96) !important;
    font: 700 12px/20px Arial, Helvetica, sans-serif !important;
  }

  .swu-mobile-edge-nav {
    position: absolute;
    top: 46%;
    z-index: 80;
    display: inline-flex;
    align-items: center;
    min-height: 38px;
    padding: 6px 7px;
    border: 1px solid rgba(var(--accent-rgb),0.40);
    background: rgba(5,20,33,0.94);
    color: rgba(205,230,243,0.92);
    box-shadow: 0 4px 14px rgba(0,0,0,0.45),0 0 7px rgba(var(--accent-rgb),0.10);
    font: 700 10px/1 Arial, Helvetica, sans-serif;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
  }
  #swuMobileToDeck { right: 0; border-right: 0; border-radius: 8px 0 0 8px; writing-mode: vertical-rl; }
  #swuMobileToSearch { left: 0; border-left: 0; border-radius: 0 8px 8px 0; writing-mode: vertical-rl; transform: rotate(180deg); }
  #swuDeckMobileRoot[data-pane="search"] #swuMobileToSearch,
  #swuDeckMobileRoot[data-pane="deck"] #swuMobileToDeck { display: none; }

  /* RenderPanes always refreshes both player prefixes. Mobile only displays the player's
     library, but the hidden opponent panel must still exist for shared renderer writes and
     deferred scroll restoration. */
  #swuMobileRendererCompatibility { display: none !important; }
</style>

<div id="swuDeckMobileRoot" data-pane="search">
  <div id="swuMobileToolbarMenu" aria-label="More deck options">
    <button id="swuMobileToolbarMenuButton" type="button" aria-label="More deck options" aria-haspopup="true" aria-expanded="false">
      <span class="swu-mobile-menu-line"></span><span class="swu-mobile-menu-line"></span><span class="swu-mobile-menu-line"></span>
    </button>
    <div id="swuMobileToolbarMenuPanel">
      <div class="swu-mobile-toolbar-menu-heading">Deck settings</div>
      <div class="swu-mobile-toolbar-setting"><span>Visibility</span><div id="swuMobileToolbarVisibilitySlot"></div></div>
      <div class="swu-mobile-toolbar-setting"><span>Version</span><div id="swuMobileToolbarVersionSlot"></div></div>
      <div id="swuMobileToolbarRefreshRow" class="swu-mobile-toolbar-setting"><span>Source</span><div id="swuMobileToolbarRefreshSlot"></div></div>
      <div class="swu-mobile-toolbar-setting"><span>Opening</span><div id="myDeckSlot" onclick="ZoneClickHandler('myDeck');"></div></div>
    </div>
  </div>

  <div id="swuDeckMobileIdentity" aria-label="Deck leader and base">
    <div id="swuMobileLeaderArt" class="swu-mobile-identity-art" aria-hidden="true"><img alt=""></div>
    <div id="swuMobileBaseArt" class="swu-mobile-identity-art" aria-hidden="true"><img alt=""></div>
    <div id="myLeaderSlot" onclick="ZoneClickHandler('myLeader');"></div>
    <div id="myBaseSlot" onclick="ZoneClickHandler('myBase');"></div>
  </div>

  <div id="swuDeckMobileViewport">
    <div id="swuDeckMobileTrack">
      <section id="swuMobileSearchPage" class="swu-mobile-page" aria-label="Card library">
        <div class="swu-mobile-page-bar swu-mobile-library-bar">
          <span>Library</span>
          <div id="swuMobileLibraryTabsSlot"></div>
          <span class="swu-mobile-page-dots" aria-hidden="true"><i></i><i></i></span>
        </div>
        <div id="myCardPaneSlot" onclick="ZoneClickHandler('myCardPane');"></div>
        <aside id="swuMobileRecent" aria-label="Recently added cards">
          <div class="swu-mobile-recent-heading"><span>Recently added</span><small>Tap to card to remove</small></div>
          <div id="swuMobileRecentList"></div>
          <button id="swuMobileRecentConfirm" type="button" title="Confirm recent additions" aria-label="Confirm recent additions and clear this history">
            <span class="swu-mobile-confirm-check" aria-hidden="true"></span>
            <span class="swu-mobile-confirm-label" aria-hidden="true">Confirm</span>
          </button>
        </aside>
      </section>

      <section id="swuMobileDeckPage" class="swu-mobile-page" aria-label="Deck workspace" aria-hidden="true">
        <div class="swu-mobile-page-bar swu-mobile-deck-bar">
          <span class="swu-mobile-page-title"><span>Main Deck</span><b id="swuMobileDeckCount"></b></span>
          <div class="swu-mobile-deck-title-tools">
            <div id="mySortSlot" onclick="ZoneClickHandler('mySort');"></div>
            <div id="swuMobileOverlayMenu" class="swu-mobile-overlay-menu">
              <button id="swuMobileOverlayButton" class="widget-button" type="button" aria-label="Card overlays" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 13.5h12v1H1v-13h1v12Zm2-2.5h2V7H4v4Zm3.5 0h2V3h-2v8Zm3.5 0h2V5h-2v6Z"/></svg>
              </button>
              <div id="swuMobileOverlayPanel">
                <div class="swu-mobile-overlay-heading">Card overlays</div>
                <div id="myStatsSlot" onclick="ZoneClickHandler('myStats');"></div>
              </div>
            </div>
          </div>
          <span class="swu-mobile-page-dots" aria-hidden="true"><i></i><i></i></span>
        </div>
        <div id="swuMobileDeckScroll">
          <div id="myMainDeckSlot" onclick="ZoneClickHandler('myMainDeck');"></div>
          <div class="swu-dm-title"><span>Sideboard</span></div>
          <div id="mySideboardSlot" onclick="ZoneClickHandler('mySideboard');"></div>
        </div>
      </section>
    </div>

    <button type="button" id="swuMobileToDeck" class="swu-mobile-edge-nav" aria-label="Show deck workspace">Deck &#8250;</button>
    <button type="button" id="swuMobileToSearch" class="swu-mobile-edge-nav" aria-label="Show card library">&#8249; Cards</button>
  </div>

  <div id="swuMobileRendererCompatibility" aria-hidden="true">
    <div id="theirCardPaneSlot"></div>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('swuDeckMobileRoot');
  var searchPage = document.getElementById('swuMobileSearchPage');
  var deckPage = document.getElementById('swuMobileDeckPage');
  var recentPanel = document.getElementById('swuMobileRecent');
  var recentList = document.getElementById('swuMobileRecentList');
  var recentConfirm = document.getElementById('swuMobileRecentConfirm');
  var recentHint = recentPanel && recentPanel.querySelector('.swu-mobile-recent-heading small');
  var recentAdds = [];
  var pendingAdds = [];
  var pendingTimer = 0;
  var libraryScrollTop = 0;
  var touchStartX = 0;
  var touchStartY = 0;
  var touchActive = false;
  var suppressClickUntil = 0;
  var RECENT_KEY = 'swu_mobile_recent_adds';

  function setPane(pane, remember){
    pane = pane === 'deck' ? 'deck' : 'search';
    root.dataset.pane = pane;
    var showDeck = pane === 'deck';
    searchPage.setAttribute('aria-hidden', showDeck ? 'true' : 'false');
    deckPage.setAttribute('aria-hidden', showDeck ? 'false' : 'true');
    if('inert' in searchPage) searchPage.inert = showDeck;
    if('inert' in deckPage) deckPage.inert = !showDeck;
    var viewport = document.getElementById('swuDeckMobileViewport');
    if(viewport) {
      viewport.scrollLeft = 0;
      requestAnimationFrame(function(){ viewport.scrollLeft = 0; });
    }
    if(remember !== false) {
      try { sessionStorage.setItem('swu_mobile_active_pane', pane); } catch(e) {}
    }
  }
  window.SWUDeckMobileSetPane = setPane;

  function cardIDFromImage(img){
    if(!img) return '';
    var filename = String(img.getAttribute('src') || '').split('/').pop().split('?')[0];
    return filename
      .replace(/_back_cropped(?=\.(?:webp|png)$)/, '')
      .replace(/_cropped(?=\.(?:webp|png)$)/, '')
      .replace(/_back(?=\.(?:webp|png)$)/, '')
      .replace(/\.(?:webp|png)$/, '');
  }
  function assetRoot(){
    return typeof window.rootPath === 'string' && window.rootPath ? window.rootPath : '/TCGEngine/SWUDeck';
  }
  function useIdentityCrop(slotID, artID, useBack){
    var sourceImg = document.querySelector('#' + slotID + ' img');
    var artImg = document.querySelector('#' + artID + ' img');
    if(!sourceImg || !artImg) return;
    var cardID = cardIDFromImage(sourceImg);
    if(!cardID) return;
    if(artImg.dataset.cardID === cardID) return;
    artImg.dataset.cardID = cardID;
    var cropRoot = assetRoot() + '/crops/' + encodeURIComponent(cardID);
    if(useBack) {
      artImg.onerror = function(){
        if(artImg.dataset.cardID === cardID) {
          artImg.onerror = null;
          artImg.src = cropRoot + '_cropped.png';
        }
      };
      artImg.src = cropRoot + '_back_cropped.png';
    } else {
      artImg.onerror = null;
      artImg.src = cropRoot + '_cropped.png';
    }
  }
  function enhanceIdentity(){
    useIdentityCrop('myLeaderSlot', 'swuMobileLeaderArt', true);
    useIdentityCrop('myBaseSlot', 'swuMobileBaseArt', false);
  }

  function updateDeckCount(){
    var source = document.getElementById('myDeckSlot');
    var output = document.getElementById('swuMobileDeckCount');
    if(!source || !output) return;
    var match = String(source.textContent || '').match(/deck\s*count\s*:\s*(\d+)/i);
    output.textContent = match ? match[1] + ' cards' : '';
  }
  function observeDeckCount(){
    var source = document.getElementById('myDeckSlot');
    if(!source) return;
    new MutationObserver(function(){ requestAnimationFrame(updateDeckCount); })
      .observe(source,{childList:true,subtree:true,characterData:true});
    updateDeckCount();
  }

  function updateMobileFilterSummary(menu){
    if(!menu) return;
    var boxes = menu.querySelectorAll('input[type="checkbox"]');
    var checked = menu.querySelectorAll('input[type="checkbox"]:checked').length;
    var count = menu.querySelector('.swu-mobile-filter-count');
    var trigger = menu.querySelector('.swu-mobile-filter-trigger');
    if(count) count.textContent = String(checked);
    if(trigger) trigger.setAttribute('aria-label','Card filters, ' + checked + ' of ' + boxes.length + ' active');
  }
  function bindMobileLibraryScroll(){
    var content = document.getElementById('my_CardPane_content');
    if(!content || content.dataset.swuMobileScrollBound === '1') return;
    content.dataset.swuMobileScrollBound = '1';
    var restore = function(){
      content.scrollTop = Math.min(libraryScrollTop, Math.max(0, content.scrollHeight - content.clientHeight));
    };
    restore();
    requestAnimationFrame(restore);
    content.addEventListener('scroll',function(){
      libraryScrollTop = content.scrollTop;
    },{passive:true});
  }
  function compactMobilePaneFilters(){
    var pane = document.getElementById('myCardPane');
    var tabsSlot = document.getElementById('swuMobileLibraryTabsSlot');
    if(!pane || !tabsSlot) return;
    bindMobileLibraryScroll();
    var tab = pane.querySelector('.panelTab');
    if(!tab) {
      updateMobileFilterSummary(tabsSlot.querySelector('.swu-mobile-filter-menu'));
      return;
    }
    var legal = pane.querySelector('#legalFilterCheckbox');
    if(!legal) return;
    var filterRow = legal.parentElement && legal.parentElement.parentElement;
    var tabsRow = tab && tab.parentElement;
    if(!filterRow || !tabsRow) return;

    tabsSlot.replaceChildren();

    tabsRow.classList.add('swu-mobile-pane-tabs-row');
    filterRow.classList.add('swu-mobile-filter-options');
    var menu = document.createElement('details');
    menu.className = 'swu-mobile-filter-menu';
    menu.open = document.documentElement.dataset.swuMobileFiltersOpen === '1';
    var trigger = document.createElement('summary');
    trigger.className = 'widget-button swu-mobile-filter-trigger';
    trigger.setAttribute('role','button');
    trigger.setAttribute('aria-expanded',menu.open ? 'true' : 'false');
    trigger.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1.5 2h13L9.6 7.7v4.1l-3.2 2.1V7.7L1.5 2Zm2.2 1 3.7 4.3v4.7l1.2-.8V7.3L12.3 3H3.7Z"/></svg><span class="swu-mobile-filter-count"></span>';
    var popover = document.createElement('div');
    popover.className = 'swu-mobile-filter-popover';
    popover.setAttribute('role','group');
    popover.setAttribute('aria-label','Card filters');
    popover.appendChild(filterRow);
    menu.appendChild(trigger);
    menu.appendChild(popover);
    tabsRow.appendChild(menu);
    tabsSlot.appendChild(tabsRow);

    trigger.addEventListener('click',function(){
      document.documentElement.dataset.swuMobileFiltersOpen = menu.open ? '0' : '1';
    });
    menu.addEventListener('toggle',function(){
      document.documentElement.dataset.swuMobileFiltersOpen = menu.open ? '1' : '0';
      trigger.setAttribute('aria-expanded',menu.open ? 'true' : 'false');
    });
    filterRow.addEventListener('change',function(){
      document.documentElement.dataset.swuMobileFiltersOpen = '1';
      updateMobileFilterSummary(menu);
    },true);
    updateMobileFilterSummary(menu);
  }
  function observeMobilePaneFilters(){
    var slot = document.getElementById('myCardPaneSlot');
    if(!slot) return;
    new MutationObserver(function(){ requestAnimationFrame(compactMobilePaneFilters); })
      .observe(slot,{childList:true,subtree:true});
    compactMobilePaneFilters();
    document.addEventListener('click',function(event){
      var menu = document.querySelector('#swuMobileSearchPage .swu-mobile-filter-menu[open]');
      if(menu && !menu.contains(event.target)) {
        menu.open = false;
        document.documentElement.dataset.swuMobileFiltersOpen = '0';
      }
    });
    document.addEventListener('keydown',function(event){
      if(event.key !== 'Escape') return;
      var menu = document.querySelector('#swuMobileSearchPage .swu-mobile-filter-menu[open]');
      if(!menu) return;
      menu.open = false;
      document.documentElement.dataset.swuMobileFiltersOpen = '0';
      var trigger = menu.querySelector('.swu-mobile-filter-trigger');
      if(trigger) trigger.focus();
    });
  }

  function setupToolbarMenu(){
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    var menu = document.getElementById('swuMobileToolbarMenu');
    var button = document.getElementById('swuMobileToolbarMenuButton');
    var panel = document.getElementById('swuMobileToolbarMenuPanel');
    if(!toolbar || !menu || !button || !panel || menu.dataset.ready === '1') return;
    menu.dataset.ready = '1';

    var directButtons = Array.prototype.slice.call(toolbar.children).filter(function(child){
      return child.tagName === 'BUTTON';
    });
    var refresh = directButtons.find(function(child){
      return String(child.textContent || '').trim().toLowerCase() === 'refresh';
    });
    var visibility = document.getElementById('AssetVisibility');
    var versions = document.getElementById('Versions');
    var visibilitySlot = document.getElementById('swuMobileToolbarVisibilitySlot');
    var versionSlot = document.getElementById('swuMobileToolbarVersionSlot');
    var refreshSlot = document.getElementById('swuMobileToolbarRefreshSlot');
    var refreshRow = document.getElementById('swuMobileToolbarRefreshRow');

    toolbar.appendChild(menu);
    if(visibility && visibilitySlot) visibilitySlot.appendChild(visibility);
    if(versions && versionSlot) versionSlot.appendChild(versions);
    if(refresh && refreshSlot) refreshSlot.appendChild(refresh);
    else if(refreshRow) refreshRow.style.display = 'none';

    function setOpen(open){
      menu.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      if(!open) {
        if(typeof window.closeVisibilityDropdown === 'function') window.closeVisibilityDropdown();
        if(typeof window.closeVersionDropdown === 'function') window.closeVersionDropdown();
      }
    }

    button.addEventListener('click', function(event){
      event.preventDefault();
      event.stopPropagation();
      setOpen(!menu.classList.contains('is-open'));
    });
    panel.addEventListener('click', function(event){
      event.stopPropagation();
      if(refresh && (event.target === refresh || refresh.contains(event.target))) setOpen(false);
      else if(event.target.closest('#myDeckSlot')) setOpen(false);
      else if(event.target.closest('#visibilityDropdownMenu,#versionDropdownMenu')) {
        window.setTimeout(function(){ setOpen(false); },0);
      }
    });
    document.addEventListener('click', function(event){
      if(!menu.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', function(event){
      if(event.key === 'Escape') setOpen(false);
    });
  }

  function setupDeckOverlayMenu(){
    var menu = document.getElementById('swuMobileOverlayMenu');
    var button = document.getElementById('swuMobileOverlayButton');
    var panel = document.getElementById('swuMobileOverlayPanel');
    var stats = document.getElementById('myStatsSlot');
    if(!menu || !button || !panel || !stats) return;

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
        window.setTimeout(function(){ setOpen(false); updateActive(); },0);
      }
    });
    document.addEventListener('click', function(event){
      if(!menu.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', function(event){
      if(event.key === 'Escape') setOpen(false);
    });
    new MutationObserver(function(){ requestAnimationFrame(updateActive); })
      .observe(stats,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
    updateActive();
  }

  function zoneEntries(zoneName){
    var raw = window[zoneName + 'Data'];
    if(typeof raw !== 'string' || raw === '') return [];
    return raw.split('<|>').map(function(entry, index){
      var cardID = String(entry || '').split(' ')[0];
      return { cardID: cardID, mzID: zoneName + '-' + index };
    }).filter(function(entry){ return entry.cardID && entry.cardID !== '-'; });
  }
  function zoneCardCount(zoneName, cardID){
    return zoneEntries(zoneName).filter(function(entry){ return entry.cardID === cardID; }).length;
  }
  function cardIDForMZID(mzID){
    var match = String(mzID || '').match(/^(.*)-(\d+)$/);
    if(match) {
      var entries = zoneEntries(match[1]);
      var index = parseInt(match[2], 10);
      var found = entries.find(function(entry){ return entry.mzID === match[1] + '-' + index; });
      if(found) return found.cardID;
    }
    var cardNode = document.getElementById(String(mzID));
    return cardIDFromImage(cardNode && cardNode.querySelector('img'));
  }
  function parseCardPayload(params){
    var match = String(params || '').match(/(?:^|[?&])cardID=([^&]*)/);
    if(!match) return '';
    try { return decodeURIComponent(match[1].replace(/\+/g, '%20')); }
    catch(e) { return match[1]; }
  }
  function addIntent(mode, params){
    var payload = parseCardPayload(params);
    var parts = payload.split('!');
    var source = parts[0] || '';
    if(source.indexOf('myCards-') !== 0) return null;
    var destination = '';
    var amount = 0;
    if(String(mode) === '10002' && parts[1] === 'Add') {
      destination = parts[2] === 'mySideboard' ? 'mySideboard' : 'myMainDeck';
      amount = 1;
    } else if(String(mode) === '10001' && parts[1] === 'CustomInput') {
      if(parts[2] === '>') { destination = 'myMainDeck'; amount = 1; }
      else if(parts[2] === '>>>') { destination = 'myMainDeck'; amount = 3; }
      else if(parts[2] === 'V') { destination = 'mySideboard'; amount = 1; }
    }
    if(!destination || amount < 1) return null;
    var cardID = cardIDForMZID(source);
    if(!cardID) return null;
    var queuedForCard = pendingAdds.reduce(function(total, pending){
      return total + (pending.destination === destination && pending.cardID === cardID ? pending.amount : 0);
    }, 0);
    return {
      cardID: cardID,
      destination: destination,
      amount: amount,
      before: zoneCardCount(destination, cardID) + queuedForCard,
      expires: Date.now() + 4500
    };
  }

  function loadRecent(){
    try {
      var stored = JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]');
      if(Array.isArray(stored)) {
        recentAdds = normalizeRecent(stored);
        saveRecent();
      }
    } catch(e) { recentAdds = []; }
  }
  function normalizeRecent(entries){
    var grouped = [];
    (Array.isArray(entries) ? entries : []).forEach(function(entry){
      if(!entry || !entry.cardID) return;
      var destination = entry.destination === 'mySideboard' ? 'mySideboard' : 'myMainDeck';
      var amount = Math.max(1,parseInt(entry.amount,10) || 1);
      var existing = grouped.find(function(group){
        return group.cardID === String(entry.cardID) && group.destination === destination;
      });
      if(existing) {
        existing.amount += amount;
        return;
      }
      grouped.push({
        id: entry.id || (String(entry.cardID) + '-' + Date.now() + '-' + Math.random().toString(36).slice(2,6)),
        cardID: String(entry.cardID),
        destination: destination,
        amount: amount
      });
    });
    return grouped.slice(0,8);
  }
  function saveRecent(){
    try { sessionStorage.setItem(RECENT_KEY, JSON.stringify(recentAdds.slice(0, 8))); } catch(e) {}
  }
  function cardTitle(cardID){
    return window.titleData && window.titleData[cardID] ? String(window.titleData[cardID]) : 'Recently added card';
  }
  function renderRecent(){
    if(!recentList) return;
    recentList.replaceChildren();
    var isEmpty = recentAdds.length === 0;
    if(recentPanel) recentPanel.classList.toggle('is-empty',isEmpty);
    if(recentHint) recentHint.textContent = isEmpty ? 'No recent adds' : 'Tap card to remove';
    if(isEmpty) return;
    recentAdds.forEach(function(entry){
      var title = cardTitle(entry.cardID);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'swu-mobile-recent-card';
      button.dataset.recentID = entry.id;
      button.setAttribute('aria-label', 'Remove one ' + title + ' from ' + (entry.destination === 'mySideboard' ? 'sideboard' : 'main deck'));
      var img = document.createElement('img');
      img.src = assetRoot() + '/concat/' + encodeURIComponent(entry.cardID) + '.webp';
      img.alt = '';
      var copy = document.createElement('span');
      copy.className = 'swu-mobile-recent-card-copy';
      var strong = document.createElement('strong');
      strong.textContent = title;
      var destination = document.createElement('span');
      destination.textContent = entry.destination === 'mySideboard' ? 'Sideboard' : 'Main deck';
      copy.appendChild(strong);
      copy.appendChild(destination);
      button.appendChild(img);
      button.appendChild(copy);
      if(entry.amount > 0) {
        var qty = document.createElement('span');
        qty.className = 'swu-mobile-recent-qty';
        qty.textContent = '×' + entry.amount;
        button.appendChild(qty);
      }
      button.addEventListener('click', function(event){
        event.stopPropagation();
        undoRecent(entry.id, button);
      });
      recentList.appendChild(button);
    });
  }
  function recordRecent(pending, actualAmount){
    var existingIndex = recentAdds.findIndex(function(entry){
      return entry.cardID === pending.cardID && entry.destination === pending.destination;
    });
    var entry = existingIndex >= 0 ? recentAdds.splice(existingIndex,1)[0] : {
      id: pending.cardID + '-' + Date.now() + '-' + Math.random().toString(36).slice(2,6),
      cardID: pending.cardID,
      destination: pending.destination,
      amount: 0
    };
    entry.amount += Math.max(1,parseInt(actualAmount,10) || 1);
    recentAdds.unshift(entry);
    recentAdds = recentAdds.slice(0, 8);
    saveRecent();
    renderRecent();
  }
  function watchPending(){
    if(pendingTimer || pendingAdds.length === 0) return;
    pendingTimer = window.setInterval(function(){
      var now = Date.now();
      pendingAdds = pendingAdds.filter(function(pending){
        var increase = zoneCardCount(pending.destination, pending.cardID) - pending.before;
        if(increase > 0) {
          recordRecent(pending, Math.min(pending.amount, increase));
          return false;
        }
        return now < pending.expires;
      });
      if(pendingAdds.length === 0) {
        window.clearInterval(pendingTimer);
        pendingTimer = 0;
      }
    }, 100);
  }
  function installAddTracker(){
    if(typeof window.SubmitEngineInput !== 'function') {
      window.setTimeout(installAddTracker, 50);
      return;
    }
    if(window.SubmitEngineInput.__swuRecentTracker) return;
    var original = window.SubmitEngineInput;
    function trackedSubmit(mode, params, options){
      var pending = addIntent(mode, params);
      return original.apply(this, arguments).then(function(result){
        if(pending) {
          pendingAdds.push(pending);
          watchPending();
        }
        return result;
      });
    }
    trackedSubmit.__swuRecentTracker = true;
    trackedSubmit.__swuOriginal = original;
    window.SubmitEngineInput = trackedSubmit;
  }
  function incomingLibraryIsUnchanged(responseArr, wrapper){
    if(!Array.isArray(responseArr) || !wrapper) return false;
    var perspectiveField = document.getElementById('viewerPerspective');
    var perspective = parseInt(perspectiveField && perspectiveField.value || '1', 10);
    if(perspective !== 1 && perspective !== 2) return false;
    var offset = (perspective - 1) * 13;
    var comparisons = [
      ['myLeaderData', 1],
      ['myBaseData', 2],
      ['myCardPaneData', 4],
      ['myLeadersData', 5],
      ['myBasesData', 6],
      ['myCardsData', 7],
      ['myStatsData', 10],
      ['mySortData', 11],
      ['myCardNotesData', 12]
    ];
    if(wrapper.dataset.swuRenderedCardSize !== String(window.cardSize)) return false;
    return comparisons.every(function(comparison){
      return String(window[comparison[0]] || '') === String(responseArr[comparison[1] + offset] || '');
    });
  }
  function installStableLibraryRender(){
    if(typeof window.RenderUpdate !== 'function' || window.RenderUpdate.__swuStableMobileLibrary) return;
    var originalRenderUpdate = window.RenderUpdate;
    function stableMobileRender(responseArr){
      var slot = document.getElementById('myCardPaneSlot');
      var wrapper = document.getElementById('myCardPaneWrapper');
      var preserveLibrary = !!(slot && wrapper && incomingLibraryIsUnchanged(responseArr, wrapper));
      var result = originalRenderUpdate.apply(this, arguments);

      /* NextTurnRender rebuilds every bound zone. If the library inputs did not change,
         restore its existing subtree before the browser can paint; this retains decoded
         images, scroll state, filter controls, and listeners while deck zones stay fresh. */
      if(preserveLibrary && slot && wrapper) slot.replaceChildren(wrapper);
      var activeWrapper = document.getElementById('myCardPaneWrapper');
      if(activeWrapper) activeWrapper.dataset.swuRenderedCardSize = String(window.cardSize);
      return result;
    }
    stableMobileRender.__swuStableMobileLibrary = true;
    stableMobileRender.__swuOriginal = originalRenderUpdate;
    window.RenderUpdate = stableMobileRender;
  }
  function undoRecent(recentID, button){
    var recentIndex = recentAdds.findIndex(function(entry){ return entry.id === recentID; });
    if(recentIndex < 0) return;
    var entry = recentAdds[recentIndex];
    var matches = zoneEntries(entry.destination).filter(function(zoneEntry){ return zoneEntry.cardID === entry.cardID; });
    var target = matches.length > 0 ? matches[matches.length - 1] : null;
    if(button) button.classList.add('is-busy');
    var chain = target
      ? window.SubmitEngineInput(10002, '&cardID=' + encodeURIComponent(target.mzID + '!Remove!'))
      : Promise.resolve();
    chain.then(function(){
      if(entry.amount > 1 && target) entry.amount -= 1;
      else recentAdds.splice(recentIndex, 1);
      saveRecent();
      renderRecent();
      if(typeof window.QueueGameUpdate === 'function') window.QueueGameUpdate();
    }).catch(function(){
      if(button) button.classList.remove('is-busy');
    });
  }
  function confirmRecent(){
    recentAdds = [];
    pendingAdds = [];
    if(pendingTimer) {
      window.clearInterval(pendingTimer);
      pendingTimer = 0;
    }
    saveRecent();
    renderRecent();
  }

  function bindSwipe(){
    root.addEventListener('touchstart', function(event){
      if(event.touches.length !== 1 || event.target.closest('input,select,textarea,.widget-dd-menu')) {
        touchActive = false;
        return;
      }
      touchStartX = event.touches[0].clientX;
      touchStartY = event.touches[0].clientY;
      touchActive = true;
    }, { passive: true });
    root.addEventListener('touchend', function(event){
      if(!touchActive || event.changedTouches.length !== 1) return;
      touchActive = false;
      var dx = event.changedTouches[0].clientX - touchStartX;
      var dy = event.changedTouches[0].clientY - touchStartY;
      if(Math.abs(dx) < 52 || Math.abs(dx) < Math.abs(dy) * 1.25) return;
      if(dx < 0 && root.dataset.pane === 'search') setPane('deck');
      else if(dx > 0 && root.dataset.pane === 'deck') setPane('search');
      else return;
      suppressClickUntil = Date.now() + 350;
    }, { passive: true });
    root.addEventListener('click', function(event){
      if(Date.now() >= suppressClickUntil) return;
      event.preventDefault();
      event.stopImmediatePropagation();
    }, true);
  }

  var viewportSyncFrame = 0;
  function syncMobileViewportHeight(){
    var viewport = window.visualViewport;
    var visibleHeight = viewport && viewport.height ? viewport.height : window.innerHeight;
    if(!visibleHeight) return;
    document.documentElement.style.setProperty('--swu-mobile-viewport-height', Math.round(visibleHeight) + 'px');
  }
  function scheduleMobileViewportSync(){
    if(viewportSyncFrame) cancelAnimationFrame(viewportSyncFrame);
    viewportSyncFrame = requestAnimationFrame(function(){
      viewportSyncFrame = 0;
      syncMobileViewportHeight();
    });
  }

  function initialize(){
    syncMobileViewportHeight();
    window.addEventListener('resize', scheduleMobileViewportSync, { passive: true });
    window.addEventListener('orientationchange', scheduleMobileViewportSync, { passive: true });
    if(window.visualViewport) {
      window.visualViewport.addEventListener('resize', scheduleMobileViewportSync, { passive: true });
      window.visualViewport.addEventListener('scroll', scheduleMobileViewportSync, { passive: true });
    }
    installStableLibraryRender();
    var initialPane = 'search';
    try { initialPane = sessionStorage.getItem('swu_mobile_active_pane') || 'search'; } catch(e) {}
    setupToolbarMenu();
    setupDeckOverlayMenu();
    setPane(initialPane, false);
    loadRecent();
    renderRecent();
    bindSwipe();
    installAddTracker();
    observeDeckCount();
    observeMobilePaneFilters();
    if(recentConfirm) recentConfirm.addEventListener('click', confirmRecent);
    document.getElementById('swuMobileToDeck').addEventListener('click', function(){ this.blur(); setPane('deck'); });
    document.getElementById('swuMobileToSearch').addEventListener('click', function(){ this.blur(); setPane('search'); });
    var identity = document.getElementById('swuDeckMobileIdentity');
    if(identity) new MutationObserver(function(){ requestAnimationFrame(enhanceIdentity); }).observe(identity,{childList:true,subtree:true});
    enhanceIdentity();
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})();
</script>
