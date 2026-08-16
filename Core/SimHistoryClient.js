(function(global) {
  'use strict';

  var UNDO_OFFER_DELAY_MS = 2500;
  var undoOffer = { token: '', timer: null };

  function decisionVariables() {
    var raw = global.DecisionQueueVariablesData;
    if (!raw || typeof raw !== 'string') return {};
    try {
      var parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
      return {};
    }
  }

  function isMobileLayout() {
    return typeof global.IsMobileGameLayoutActive === 'function'
      && global.IsMobileGameLayoutActive();
  }

  function viewerIsPlayer() {
    var input = document.getElementById('playerID');
    var value = input ? parseInt(input.value, 10) : NaN;
    return value === 1 || value === 2;
  }

  function submit(mode, button) {
    if (!button || button.disabled || typeof global.SubmitInput !== 'function') return;
    button.disabled = true;
    global.SubmitInput(mode, '');
  }

  function createDesktopControls() {
    if (isMobileLayout() || document.getElementById('sim-history-controls')) return;
    var controls = document.createElement('div');
    controls.id = 'sim-history-controls';
    controls.setAttribute('aria-label', 'Game history');
    controls.style.cssText = [
      'position:fixed', 'left:61px', 'bottom:18px', 'z-index:10010',
      'display:none', 'gap:7px', 'opacity:0', 'transform:translateY(5px)',
      'transition:opacity 180ms ease,transform 180ms ease',
      'font-family:var(--azuki-font-ui,Segoe UI,sans-serif)'
    ].join(';');

    [['undo', 10004, '\u21b6'], ['redo', 10020, '\u21b7']].forEach(function(spec) {
      var button = document.createElement('button');
      button.id = 'sim-history-' + spec[0];
      button.type = 'button';
      button.dataset.simHistoryAction = spec[0];
      button.innerHTML = '<span aria-hidden="true">' + spec[2] + '</span> <span data-sim-history-label>'
        + (spec[0] === 'undo' ? 'Undo' : 'Redo') + '</span>';
      button.style.cssText = [
        'display:inline-flex', 'align-items:center', 'gap:4px', 'min-width:86px',
        'padding:8px 11px', 'border:1px solid rgba(226,216,198,.25)',
        'border-radius:8px', 'color:#f4ede0', 'background:rgba(20,19,21,.92)',
        'box-shadow:0 7px 20px rgba(0,0,0,.3)', 'cursor:pointer', 'font:inherit',
        'font-size:12px', 'font-weight:700', 'transition:background 150ms ease'
      ].join(';');
      button.onclick = function() { submit(spec[1], button); };
      controls.appendChild(button);
    });
    document.body.appendChild(controls);
  }

  function updateButton(button, enabled, baseLabel, actionLabel) {
    if (!button) return;
    button.disabled = !enabled;
    button.style.opacity = enabled ? '1' : '0.42';
    button.style.cursor = enabled ? 'pointer' : 'default';
    var label = button.querySelector('[data-sim-history-label]');
    if (label) label.textContent = baseLabel;
    button.title = actionLabel ? baseLabel + ': ' + actionLabel : 'Nothing to ' + baseLabel.toLowerCase();
    button.setAttribute('aria-label', button.title);
  }

  function setHistoryVisible(visible) {
    var controls = document.getElementById('sim-history-controls');
    if (controls) {
      if (!visible) {
        controls.style.display = 'none';
        controls.style.opacity = '0';
        controls.style.transform = 'translateY(5px)';
      } else if (controls.style.display === 'none') {
        controls.style.display = 'flex';
        global.requestAnimationFrame(function() {
          if (!controls.isConnected || controls.style.display === 'none') return;
          controls.style.opacity = '1';
          controls.style.transform = 'translateY(0)';
        });
      }
    }
    document.querySelectorAll('[data-sim-history-action]').forEach(function(button) {
      if (!controls || !controls.contains(button)) button.style.display = visible ? '' : 'none';
    });
  }

  function showHistoryImmediately(token) {
    if (undoOffer.timer !== null) {
      global.clearTimeout(undoOffer.timer);
      undoOffer.timer = null;
    }
    undoOffer.token = token;
    setHistoryVisible(true);
  }

  function revealDelayedHistory(token) {
    global.requestAnimationFrame(function() {
      if (undoOffer.token !== token) return;
      setHistoryVisible(true);
    });
  }

  function resetUndoOffer() {
    if (undoOffer.timer !== null) {
      global.clearTimeout(undoOffer.timer);
      undoOffer.timer = null;
    }
    undoOffer.token = '';
    setHistoryVisible(false);
  }

  function updateUndoOffer(canUndo, token) {
    if (!canUndo) {
      resetUndoOffer();
      return;
    }
    if (token === '') token = 'available';
    if (undoOffer.token === token) return;

    resetUndoOffer();
    undoOffer.token = token;
    undoOffer.timer = global.setTimeout(function() {
      if (undoOffer.token !== token) return;
      undoOffer.timer = null;
      revealDelayedHistory(token);
    }, UNDO_OFFER_DELAY_MS);
  }

  global.UpdateSimHistoryUI = function() {
    createDesktopControls();
    var vars = decisionVariables();
    var player = viewerIsPlayer();
    var canUndo = player && String(vars.SIM_HISTORY_CAN_UNDO || '') === 'true';
    var canRedo = player && String(vars.SIM_HISTORY_CAN_REDO || '') === 'true';
    var undoLabel = String(vars.SIM_HISTORY_UNDO_LABEL || '');
    var redoLabel = String(vars.SIM_HISTORY_REDO_LABEL || '');
    var undoToken = String(vars.SIM_HISTORY_UNDO_TOKEN || undoLabel);
    var lastOperation = String(vars.SIM_HISTORY_LAST_OPERATION || 'action');

    document.querySelectorAll('[data-sim-history-action="undo"]').forEach(function(button) {
      updateButton(button, canUndo, 'Undo', undoLabel);
    });
    document.querySelectorAll('[data-sim-history-action="redo"]').forEach(function(button) {
      updateButton(button, canRedo, 'Redo', redoLabel);
    });
    if (player && (canRedo || lastOperation === 'undo' || lastOperation === 'redo')) {
      showHistoryImmediately(undoToken);
    } else {
      updateUndoOffer(canUndo, undoToken);
    }
  };

  document.addEventListener('keydown', function(event) {
    if (!(event.ctrlKey || event.metaKey) || String(event.key || '').toLowerCase() !== 'z') return;
    var active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) return;
    var action = event.shiftKey ? 'redo' : 'undo';
    var button = document.querySelector('[data-sim-history-action="' + action + '"]');
    if (!button || button.disabled) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    submit(action === 'redo' ? 10020 : 10004, button);
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', global.UpdateSimHistoryUI);
  } else {
    global.UpdateSimHistoryUI();
  }
})(window);
