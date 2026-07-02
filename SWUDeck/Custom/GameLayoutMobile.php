<?php
// GameLayoutMobile.php — SWUDeck deck-builder phone layout (vertical stack).
//
// Emits the SAME <zone>Slot ids as the desktop GameLayout.php (NextTurnRender.php fills
// each slot's inner <zone>Wrapper the same way for both layouts). Only the arrangement
// differs: a scrollable TOP region (Leader+Base, controls, Main Deck, Sideboard) and a
// collapsible card-browser pane pinned to the BOTTOM — so the builder is usable one-handed
// in portrait and the player can hide the pool to see their whole deck.
//
// Replaces the legacy JS DOM-reflow (MobileDeckEditorLayout), which is disabled whenever
// window.SWUDeckSlotLayout is set (see GameLayout.php + UILibraries).
?>
<style>
  /* z-index:11 sits above the background .myStuffWrapper (z-index:10) — the generated
     InitialLayout includes this layout as a sibling of that wrapper, so without it the
     #myStuff background would overlay (and intercept clicks on) the mobile stack. */
  #swuDeckMobileRoot { position:absolute; left:0; top:0; right:0; bottom:0; z-index:11; display:flex; flex-direction:column; overflow:hidden; }

  /* Scrollable deck region */
  #swuDeckMobileTop { flex:1 1 auto; min-height:0; overflow-y:auto; overflow-x:hidden; -webkit-overflow-scrolling:touch; padding-bottom:12px; box-sizing:border-box; }
  .swu-dm-leaderbase { display:flex; flex-direction:row; justify-content:center; align-items:flex-start; gap:8px; padding:8px 4px; flex-wrap:wrap; }
  /* Row 1: Deck count + Hand Draw pinned left, Sort dropdown pushed to the far right.
     Row 2: the three Stats buttons. */
  .swu-dm-controls-row1 { display:flex; flex-direction:row; flex-wrap:nowrap; justify-content:space-between; align-items:center; gap:8px; padding:4px 8px 2px; }
  .swu-dm-controls-row2 { display:flex; flex-direction:row; flex-wrap:wrap; align-items:center; gap:4px; padding:0 8px 4px; }
  .swu-dm-title      { position:sticky; top:0; z-index:10; background:#1a1a2e; color:#ccc; font-size:0.75rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; padding:3px 8px; border-bottom:1px solid #444; box-sizing:border-box; }

  /* In the stack, the deck/sideboard grids grow with their content — the TOP region scrolls,
     not each grid. (Desktop gives these wrappers a fixed height + inner scroll; override here.) */
  #swuDeckMobileRoot #myMainDeckWrapper,
  #swuDeckMobileRoot #mySideboardWrapper { height:auto !important; overflow:visible !important; width:100%; box-sizing:border-box; }
  #swuDeckMobileRoot #myLeaderWrapper,
  #swuDeckMobileRoot #myBaseWrapper,
  #swuDeckMobileRoot #myDeckWrapper,
  #swuDeckMobileRoot #myStatsWrapper,
  #swuDeckMobileRoot #mySortWrapper { overflow:visible !important; }

  /* Row 1 left: drop the "Deck Count: N" label on mobile so the Hand Draw button sits
     flush at the far left. (Deck count stays on desktop.) */
  #swuDeckMobileRoot #myDeckWrapper #myDeck { justify-content:flex-start !important; }
  #swuDeckMobileRoot #myDeckWrapper #myDeck > span { font-size:0; }
  #swuDeckMobileRoot #myDeckWrapper #myDeck .widget-button { font-size:13px; }

  /* Bottom card-browser pane (collapsible) */
  #swuDeckMobileBrowser { flex:0 0 auto; display:flex; flex-direction:column; border-top:2px solid #3f3f5a; box-sizing:border-box; height:48vh; min-height:0; transition:height 0.2s ease; }
  #swuDeckMobileBrowser.is-collapsed { height:36px; }
  #swuDeckBrowserHandle { flex:0 0 auto; height:36px; display:flex; align-items:center; justify-content:space-between; padding:0 14px; background:rgba(18,18,30,0.96); color:#cfe0ff; font-size:13px; font-weight:600; letter-spacing:0.02em; cursor:pointer; user-select:none; }
  #swuDeckBrowserHandle .swu-dm-chevron { transition:transform 0.2s ease; font-size:11px; opacity:0.85; }
  #swuDeckMobileBrowser.is-collapsed #swuDeckBrowserHandle .swu-dm-chevron { transform:rotate(180deg); }
  #myCardPaneSlot { flex:1 1 auto; min-height:0; overflow:hidden; }
  #swuDeckMobileBrowser.is-collapsed #myCardPaneSlot { display:none; }
</style>
<div id="swuDeckMobileRoot">
  <div id="swuDeckMobileTop">
    <div class="swu-dm-leaderbase">
      <div id="myLeaderSlot" onclick="ZoneClickHandler('myLeader');"></div>
      <div id="myBaseSlot"   onclick="ZoneClickHandler('myBase');"></div>
    </div>
    <div class="swu-dm-controls-row1">
      <div id="myDeckSlot" onclick="ZoneClickHandler('myDeck');"></div>
      <div id="mySortSlot" onclick="ZoneClickHandler('mySort');"></div>
    </div>
    <div class="swu-dm-controls-row2">
      <div id="myStatsSlot" onclick="ZoneClickHandler('myStats');"></div>
    </div>
    <div class="swu-dm-title">Main Deck</div>
    <div id="myMainDeckSlot" onclick="ZoneClickHandler('myMainDeck');"></div>
    <div class="swu-dm-title">Sideboard</div>
    <div id="mySideboardSlot" onclick="ZoneClickHandler('mySideboard');"></div>
  </div>
  <div id="swuDeckMobileBrowser">
    <div id="swuDeckBrowserHandle" onclick="SWUDeckToggleBrowser()">
      <span>Card Browser</span>
      <span class="swu-dm-chevron">&#9662;</span>
    </div>
    <div id="myCardPaneSlot" onclick="ZoneClickHandler('myCardPane');"></div>
  </div>
</div>
<script>
(function(){
  var COOKIE = 'swu_mobile_card_browser_hidden';
  function getCookie(n){ var m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return m ? m.pop() : ''; }
  function setCookie(n,v){ document.cookie = n + '=' + v + '; max-age=' + (365*24*60*60) + '; path=/; SameSite=Lax'; }
  window.SWUDeckToggleBrowser = function(){
    var b = document.getElementById('swuDeckMobileBrowser');
    if(!b) return;
    var collapsed = b.classList.toggle('is-collapsed');
    setCookie(COOKIE, collapsed ? '1' : '0');
  };
  function applyInitial(){
    var b = document.getElementById('swuDeckMobileBrowser');
    if(b && getCookie(COOKIE) === '1') b.classList.add('is-collapsed');
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applyInitial);
  else applyInitial();
})();
</script>
