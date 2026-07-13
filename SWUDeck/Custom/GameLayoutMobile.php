<?php
// GameLayoutMobile.php — SWUDeck phone layout.
//
// The phone editor is a two-page horizontal workspace: a full-height card library and a
// full-height deck workspace. Both pages keep the generated zone slot ids, so
// NextTurnRender.php continues to populate them without a mobile-only renderer.
?>
<style>
  /* Seven desktop toolbar controls cannot fit a phone width. Keep their compact HUD styling,
     but let the rail itself pan horizontally instead of clipping Refresh/version controls. */
  .flex-container > .flex-item:first-child {
    overflow-x: auto !important;
    overflow-y: hidden !important;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }
  .flex-container > .flex-item:first-child::-webkit-scrollbar { display: none; }
  .flex-container > .flex-item:first-child button { white-space: nowrap !important; }

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
  #swuDeckMobileIdentity #myBaseSlot {
    position: absolute !important;
    top: 0 !important;
    width: 58%;
    height: 100%;
    overflow: hidden;
  }
  #swuDeckMobileIdentity #myLeaderSlot {
    left: 0 !important;
    -webkit-mask-image: linear-gradient(to right,#000 0%,#000 68%,transparent 100%);
    mask-image: linear-gradient(to right,#000 0%,#000 68%,transparent 100%);
  }
  #swuDeckMobileIdentity #myBaseSlot {
    right: 0 !important;
    -webkit-mask-image: linear-gradient(to left,#000 0%,#000 68%,transparent 100%);
    mask-image: linear-gradient(to left,#000 0%,#000 68%,transparent 100%);
  }
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
  #swuDeckMobileIdentity #myLeaderSlot img { object-position: center top; }

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

  /* Library page: fixed controls inside CardPane, scrollable results, persistent recent tray. */
  #swuMobileSearchPage { display: flex; flex-direction: column; overflow: hidden; }
  #swuMobileSearchPage #myCardPaneSlot {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    padding: 2px 8px 0;
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
  #swuMobileSearchPage #myCardPane > div:first-child { flex: 0 0 auto; overflow: visible !important; }
  #swuMobileSearchPage #my_CardPane_content {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    margin-top: 5px !important;
    padding: 5px !important;
    box-sizing: border-box;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    border: 1px solid rgba(var(--accent-rgb),0.20) !important;
    background: rgba(1,13,25,0.12) !important;
    box-shadow: inset 0 0 12px rgba(var(--accent-rgb),0.06) !important;
  }

  #swuMobileRecent {
    flex: 0 0 92px;
    min-width: 0;
    padding: 5px 8px calc(6px + env(safe-area-inset-bottom));
    box-sizing: border-box;
    border-top: 1px solid rgba(var(--accent-rgb),0.24);
    background: linear-gradient(180deg,rgba(5,18,30,0.97),rgba(2,12,22,0.98));
    box-shadow: 0 -5px 16px rgba(0,0,0,0.28);
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
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }
  #swuMobileRecentList::-webkit-scrollbar { display: none; }
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

  /* Deck page: its controls stay available while the deck and sideboard share one scroll. */
  #swuMobileDeckPage {
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
  }
  .swu-mobile-deck-controls {
    position: sticky;
    top: 0;
    z-index: 20;
    padding: 4px 8px 5px;
    background: linear-gradient(180deg,rgba(4,17,29,0.98),rgba(4,17,29,0.93));
    border-bottom: 1px solid rgba(var(--accent-rgb),0.20);
    box-shadow: 0 5px 14px rgba(0,0,0,0.25);
  }
  .swu-dm-controls-row1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-width: 0;
  }
  .swu-dm-controls-row2 { display: flex; align-items: center; min-width: 0; margin-top: 2px; }
  #swuMobileDeckPage #myDeckWrapper,
  #swuMobileDeckPage #myStatsWrapper,
  #swuMobileDeckPage #mySortWrapper { overflow: visible !important; }
  #swuMobileDeckPage #myDeckWrapper #myDeck { justify-content: flex-start !important; }
  #swuMobileDeckPage #myDeckWrapper #myDeck > span { font-size: 0; }
  #swuMobileDeckPage #myDeckWrapper #myDeck .widget-button { font-size: 12px; }
  #swuMobileDeckPage #mySortSlot,
  #swuMobileDeckPage #mySortWrapper { margin-left: auto; }

  .swu-dm-title {
    position: sticky;
    top: 68px;
    z-index: 12;
    padding: 5px 10px;
    box-sizing: border-box;
    border-top: 1px solid rgba(var(--accent-rgb),0.12);
    border-bottom: 1px solid rgba(var(--accent-rgb),0.20);
    background: rgba(5,18,30,0.95);
    color: rgba(190,216,232,0.82);
    font: 700 11px/18px Arial, Helvetica, sans-serif;
    letter-spacing: 0.09em;
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
</style>

<div id="swuDeckMobileRoot" data-pane="search">
  <div id="swuDeckMobileIdentity" aria-label="Deck leader and base">
    <div id="myLeaderSlot" onclick="ZoneClickHandler('myLeader');"></div>
    <div id="myBaseSlot" onclick="ZoneClickHandler('myBase');"></div>
  </div>

  <div id="swuDeckMobileViewport">
    <div id="swuDeckMobileTrack">
      <section id="swuMobileSearchPage" class="swu-mobile-page" aria-label="Card library">
        <div class="swu-mobile-page-bar">
          <span>Card Library</span>
          <span class="swu-mobile-page-dots" aria-hidden="true"><i></i><i></i></span>
        </div>
        <div id="myCardPaneSlot" onclick="ZoneClickHandler('myCardPane');"></div>
        <aside id="swuMobileRecent" aria-label="Recently added cards">
          <div class="swu-mobile-recent-heading"><span>Recently added</span><small>Tap to undo</small></div>
          <div id="swuMobileRecentList"></div>
        </aside>
      </section>

      <section id="swuMobileDeckPage" class="swu-mobile-page" aria-label="Deck workspace" aria-hidden="true">
        <div class="swu-mobile-page-bar">
          <span>Your Deck</span>
          <span class="swu-mobile-page-dots" aria-hidden="true"><i></i><i></i></span>
        </div>
        <div class="swu-mobile-deck-controls">
          <div class="swu-dm-controls-row1">
            <div id="myDeckSlot" onclick="ZoneClickHandler('myDeck');"></div>
            <div id="mySortSlot" onclick="ZoneClickHandler('mySort');"></div>
          </div>
          <div class="swu-dm-controls-row2">
            <div id="myStatsSlot" onclick="ZoneClickHandler('myStats');"></div>
          </div>
        </div>
        <div class="swu-dm-title">Main Deck</div>
        <div id="myMainDeckSlot" onclick="ZoneClickHandler('myMainDeck');"></div>
        <div class="swu-dm-title">Sideboard</div>
        <div id="mySideboardSlot" onclick="ZoneClickHandler('mySideboard');"></div>
      </section>
    </div>

    <button type="button" id="swuMobileToDeck" class="swu-mobile-edge-nav" aria-label="Show deck workspace">Deck &#8250;</button>
    <button type="button" id="swuMobileToSearch" class="swu-mobile-edge-nav" aria-label="Show card library">&#8249; Cards</button>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('swuDeckMobileRoot');
  var searchPage = document.getElementById('swuMobileSearchPage');
  var deckPage = document.getElementById('swuMobileDeckPage');
  var recentList = document.getElementById('swuMobileRecentList');
  var recentAdds = [];
  var pendingAdds = [];
  var pendingTimer = 0;
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
    return filename.replace(/_back(?=\.(?:webp|png)$)/, '').replace(/\.(?:webp|png)$/, '');
  }
  function assetRoot(){
    return typeof window.rootPath === 'string' && window.rootPath ? window.rootPath : '/TCGEngine/SWUDeck';
  }
  function useIdentityCrop(slotID, useBack){
    var img = document.querySelector('#' + slotID + ' img');
    if(!img || img.dataset.swuIdentityCrop === '1') return;
    var cardID = cardIDFromImage(img);
    if(!cardID) return;
    img.dataset.swuIdentityCrop = '1';
    var cropRoot = assetRoot() + '/crops/' + encodeURIComponent(cardID);
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
  function enhanceIdentity(){
    useIdentityCrop('myLeaderSlot', true);
    useIdentityCrop('myBaseSlot', false);
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
      if(Array.isArray(stored)) recentAdds = stored.slice(0, 8);
    } catch(e) { recentAdds = []; }
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
    if(recentAdds.length === 0) {
      var empty = document.createElement('div');
      empty.className = 'swu-mobile-recent-empty';
      empty.textContent = 'Cards you add will appear here.';
      recentList.appendChild(empty);
      return;
    }
    recentAdds.forEach(function(entry){
      var title = cardTitle(entry.cardID);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'swu-mobile-recent-card';
      button.dataset.recentID = entry.id;
      button.setAttribute('aria-label', 'Undo adding ' + entry.amount + ' ' + title + ' to ' + (entry.destination === 'mySideboard' ? 'sideboard' : 'main deck'));
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
      if(entry.amount > 1) {
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
    recentAdds.unshift({
      id: pending.cardID + '-' + Date.now() + '-' + Math.random().toString(36).slice(2,6),
      cardID: pending.cardID,
      destination: pending.destination,
      amount: actualAmount
    });
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
  function undoRecent(recentID, button){
    var recentIndex = recentAdds.findIndex(function(entry){ return entry.id === recentID; });
    if(recentIndex < 0) return;
    var entry = recentAdds[recentIndex];
    var matches = zoneEntries(entry.destination).filter(function(zoneEntry){ return zoneEntry.cardID === entry.cardID; });
    var targets = matches.slice(Math.max(0, matches.length - entry.amount));
    if(button) button.classList.add('is-busy');
    var chain = Promise.resolve();
    targets.forEach(function(target){
      chain = chain.then(function(){
        return window.SubmitEngineInput(10002, '&cardID=' + encodeURIComponent(target.mzID + '!Remove!'));
      });
    });
    chain.then(function(){
      recentAdds.splice(recentIndex, 1);
      saveRecent();
      renderRecent();
      if(typeof window.QueueGameUpdate === 'function') window.QueueGameUpdate();
    }).catch(function(){
      if(button) button.classList.remove('is-busy');
    });
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

  function initialize(){
    var initialPane = 'search';
    try { initialPane = sessionStorage.getItem('swu_mobile_active_pane') || 'search'; } catch(e) {}
    setPane(initialPane, false);
    loadRecent();
    renderRecent();
    bindSwipe();
    installAddTracker();
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
