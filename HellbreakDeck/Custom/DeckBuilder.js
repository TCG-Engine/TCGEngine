(function () {
  'use strict';

  function countZoneData(value) {
    if (!value) return 0;
    return String(value).split('<|>').filter(function (entry) { return entry.trim() !== ''; }).length;
  }

  function cardIDFromImage(image) {
    if (!image) return '';
    var filename = String(image.getAttribute('src') || '').split('/').pop().split('?')[0];
    return filename.replace(/_back(?=\.(?:webp|png)$)/, '').replace(/\.(?:webp|png)$/, '');
  }

  function useIdentityCrops(slotID) {
    Array.prototype.forEach.call(document.querySelectorAll('#' + slotID + ' img'), function (image) {
      if (image.dataset.hbIdentityCrop === '1') return;
      var cardID = cardIDFromImage(image);
      if (!cardID) return;
      image.dataset.hbIdentityCrop = '1';
      image.src = '/TCGEngine/HellbreakSim/crops/' + encodeURIComponent(cardID) + '_cropped.png';
    });
  }

  function updateIdentity() {
    useIdentityCrops('myMonsterSlot');
    useIdentityCrops('myLocationSlot');
  }

  function updateCounts() {
    var main = document.getElementById('hbMainDeckCount');
    var sideboard = document.getElementById('hbSideboardCount');
    if (main) main.textContent = String(countZoneData(window.myMainDeckData));
    if (sideboard) sideboard.textContent = String(countZoneData(window.mySideboardData));
  }

  function decorateToolbar() {
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if (!toolbar || toolbar.dataset.hbDeckDecorated === '1') return;
    toolbar.dataset.hbDeckDecorated = '1';

    Array.prototype.forEach.call(toolbar.querySelectorAll(':scope > button'), function (button) {
      var label = String(button.textContent || '').trim().toLowerCase();
      if (label === 'home') {
        button.id = 'hbDeckBackButton';
        button.innerHTML = '<span aria-hidden="true">&larr;</span><span>Decks</span>';
        button.setAttribute('aria-label', 'Back to decks');
      } else if (label === 'edit') {
        button.id = 'hbDeckLegacyEditButton';
        button.hidden = true;
      }
    });

    var brand = document.createElement('div');
    brand.id = 'hbDeckBrand';
    brand.innerHTML = '<strong>northbeach.gg</strong><span>Deck operations / coastal district 09</span>';
    var back = document.getElementById('hbDeckBackButton');
    if (back && back.nextSibling) toolbar.insertBefore(brand, back.nextSibling);
    else toolbar.insertBefore(brand, toolbar.firstChild);

    var gameName = document.getElementById('gameName');
    if (gameName && gameName.value) {
      var asset = document.createElement('span');
      asset.id = 'hbDeckAssetID';
      asset.textContent = 'DECK ' + gameName.value;
      brand.appendChild(asset);
    }
  }

  function syncPaneControls() {
    var pane = document.getElementById('myCardPane');
    if (pane) {
      var firstTab = pane.querySelector('.panelTab');
      if (firstTab && firstTab.parentElement) {
        firstTab.parentElement.classList.add('hb-generated-pane-tabs');
        firstTab.parentElement.setAttribute('aria-hidden', 'true');
      }
    }
    var active = pendingPaneIndex === null
      ? Number(window._my_CardPane_activePane || 0)
      : pendingPaneIndex;
    Array.prototype.forEach.call(document.querySelectorAll('#hbPaneTabs button'), function (button) {
      var selected = Number(button.dataset.paneIndex) === active;
      button.classList.toggle('is-active', selected);
      button.classList.toggle('is-loading', selected && pendingPaneIndex !== null);
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');
      button.setAttribute('aria-busy', selected && pendingPaneIndex !== null ? 'true' : 'false');
    });
  }

  var pendingPaneIndex = null;
  var paneRetryTimer = 0;

  function paneDataReady(index) {
    return typeof window.RenderPane === 'function'
      && Array.isArray(window.myCardPanePanes)
      && typeof window.myCardPanePanes[index] === 'string';
  }

  function flushPendingPane() {
    if (pendingPaneIndex === null) return;
    if (!paneDataReady(pendingPaneIndex)) {
      if (!paneRetryTimer) {
        paneRetryTimer = window.setTimeout(function () {
          paneRetryTimer = 0;
          flushPendingPane();
        }, 100);
      }
      return;
    }

    var index = pendingPaneIndex;
    pendingPaneIndex = null;
    window._my_CardPane_activePane = index;
    window.RenderPane('my', 'CardPane', window.myCardPanePanes);
    queueUpdate();
  }

  function requestPane(index) {
    pendingPaneIndex = index;
    syncPaneControls();
    flushPendingPane();
  }

  function installPaneControls() {
    var controls = document.getElementById('hbPaneTabs');
    if (!controls || controls.dataset.hbTabsReady === '1') return;
    controls.dataset.hbTabsReady = '1';
    controls.addEventListener('click', function (event) {
      var button = event.target.closest && event.target.closest('button[data-pane-index]');
      if (!button || !controls.contains(button)) return;
      var index = Number(button.dataset.paneIndex);
      if (!Number.isInteger(index) || index < 0) return;
      requestPane(index);
    });
  }

  var updateQueued = false;
  function queueUpdate() {
    if (updateQueued) return;
    updateQueued = true;
    requestAnimationFrame(function () {
      updateQueued = false;
      updateIdentity();
      updateCounts();
      syncPaneControls();
      flushPendingPane();
    });
  }

  function initialize() {
    decorateToolbar();
    installPaneControls();
    var board = document.getElementById('hellbreakDeckBoard');
    if (board) new MutationObserver(queueUpdate).observe(board, { childList: true, subtree: true });
    queueUpdate();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})();
