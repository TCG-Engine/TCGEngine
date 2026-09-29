(function () {
  'use strict';

  var dialog;
  var frame;
  var opener;
  var infoDialog;
  var button;
  var lastStatus = 'idle';
  var statusRequestID = 0;
  var appliedRequestID = 0;
  var noticeShown = false;
  var preferenceKey = 'swudeck-simulation-background-explained';
  var deckID = new URLSearchParams(window.location.search).get('gameName');
  if (!deckID || !/^\d+$/.test(deckID)) return;
  var statusURL = '/TCGEngine/SWUDeck/Simulation.php?gameName=' + encodeURIComponent(deckID) + '&action=status';
  var pendingKey = 'swudeck-simulation-pending-' + deckID;

  function close() {
    if (dialog && dialog.open) dialog.close();
  }

  function findButton() {
    button = document.querySelector('button[onclick*="/SWUDeck/Simulation.php?"]');
    if (button && !button.querySelector('.swu-sim-status')) {
      var indicator = document.createElement('span');
      indicator.className = 'swu-sim-status';
      indicator.setAttribute('aria-hidden', 'true');
      button.appendChild(indicator);
    }
    return button;
  }

  function updateButton(status) {
    if (!button || !button.isConnected) findButton();
    if (!button) return;
    button.classList.toggle('swu-sim-running', status === 'running');
    button.classList.toggle('swu-sim-done', status === 'done');
    button.classList.toggle('swu-sim-failed', status === 'failed');
    var descriptions = { running: 'Simulation running', done: 'Simulation ready', failed: 'Simulation failed' };
    button.title = descriptions[status] || 'Simulate this deck';
    button.setAttribute('aria-label', status === 'idle' ? 'Simulate this deck' : 'Simulate this deck. ' + descriptions[status]);
  }

  function showToast(status) {
    var existing = document.querySelector('.swu-simulation-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'swu-simulation-toast';
    toast.setAttribute('role', 'status');
    var message = document.createElement('span');
    message.textContent = status === 'done' ? 'Simulation ready. Open Simulate to see your results.' : 'Simulation stopped. Open Simulate for details.';
    var action = document.createElement('button');
    action.type = 'button';
    action.textContent = 'Open';
    action.addEventListener('click', function () { toast.remove(); if (findButton()) open(button); });
    toast.append(message, action);
    document.body.appendChild(toast);
    setTimeout(function () { if (toast.isConnected) toast.remove(); }, 12000);
  }

  function refreshStatus() {
    var requestID = ++statusRequestID;
    fetch(statusURL, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) { if (!response.ok) throw new Error('Status unavailable'); return response.json(); })
      .then(function (job) {
        if (requestID < appliedRequestID) return;
        appliedRequestID = requestID;
        var status = job.status || 'idle';
        updateButton(status);
        var pending = lastStatus === 'running';
        try {
          if (status === 'running') localStorage.setItem(pendingKey, '1');
          pending = pending || localStorage.getItem(pendingKey) === '1';
        } catch (error) { /* In-memory transition still works without storage. */ }
        if (pending && (status === 'done' || status === 'failed')) {
          showToast(status);
          try { localStorage.removeItem(pendingKey); } catch (error) { /* No persistent notice marker. */ }
        }
        lastStatus = status;
      })
      .catch(function () { /* Keep the last known state during a transient request failure. */ });
  }

  function showBackgroundInfo() {
    var explained = false;
    try { explained = localStorage.getItem(preferenceKey) === '1'; } catch (error) { explained = noticeShown; }
    if (explained || noticeShown) return;
    noticeShown = true;
    infoDialog = document.createElement('dialog');
    infoDialog.className = 'swu-simulation-info';
    infoDialog.innerHTML = '<div class="swu-simulation-info__icon" aria-hidden="true">↗</div><h2>Your simulation is running</h2><p>It will keep processing while you work on this deck. The icon beside <strong>Simulate</strong> shows its progress and changes when your results are ready.</p><button type="button">Got it!</button>';
    infoDialog.querySelector('button').addEventListener('click', function () {
      try { localStorage.setItem(preferenceKey, '1'); } catch (error) { /* This session still remembers dismissal. */ }
      infoDialog.close();
    });
    infoDialog.addEventListener('close', function () { infoDialog.remove(); });
    document.body.appendChild(infoDialog);
    infoDialog.showModal();
    infoDialog.querySelector('button').focus();
  }

  function open(source) {
    opener = source;
    if (!dialog) {
      dialog = document.createElement('dialog');
      dialog.className = 'swu-simulation-dialog';
      dialog.setAttribute('aria-label', 'Deck simulation');
      dialog.innerHTML = '<div class="swu-simulation-dialog__bar"><span>DECK SIMULATION</span><button type="button" aria-label="Close simulation">&times;</button></div><iframe title="Deck simulation" loading="eager"></iframe>';
      frame = dialog.querySelector('iframe');
      dialog.querySelector('button').addEventListener('click', close);
      dialog.addEventListener('click', function (event) { if (event.target === dialog) close(); });
      dialog.addEventListener('close', function () { if (opener && opener.isConnected) opener.focus(); });
      document.body.appendChild(dialog);
    }
    frame.src = '/TCGEngine/SWUDeck/Simulation.php?gameName=' + encodeURIComponent(deckID) + '&embedded=1';
    dialog.showModal();
    dialog.querySelector('button').focus();
  }

  document.addEventListener('click', function (event) {
    var target = event.target.closest('button[onclick*="/SWUDeck/Simulation.php?"]');
    if (!target) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    open(target);
  }, true);

  window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin || !frame || event.source !== frame.contentWindow) return;
    if (event.data === 'swu-simulation-close') close();
    if (event.data === 'swu-simulation-started') {
      lastStatus = 'running';
      try { localStorage.setItem(pendingKey, '1'); } catch (error) { /* This tab will still poll. */ }
      updateButton('running');
      close();
      showBackgroundInfo();
      refreshStatus();
    }
  });

  function initialize() {
    if (!findButton()) return;
    refreshStatus();
    setInterval(refreshStatus, 3000);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})();
