/**
 * MZModalUI.js - Modal Choice UI for Decision Queue
 *
 * Provides a popup UI for choosing N of M labeled options.
 * Used for multi-modal cards like "Choose one. If imbued, choose two."
 *
 * Decision queue Param format: "min|max|label1&label2&label3"
 *   min = minimum selections required
 *   max = maximum selections allowed
 *   labels = ampersand-delimited option labels (underscores become spaces)
 *
 * Return format: comma-separated 0-based indices of chosen options, e.g. "0,2"
 *   Returns "-" if no options were chosen (only when min=0).
 *
 * Usage (called from CheckAndShowDecisionQueue in UILibraries.js):
 *   ShowMZModalUI(paramString, tooltip, decisionIndex, submitCallback)
 */

(function() {
  'use strict';

  const MODAL_STYLES = `
    .mzmodal-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.6);
      z-index: 5000;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: mzmodal-fade-in 0.2s ease-out;
    }
    @keyframes mzmodal-fade-in {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    .mzmodal-panel {
      background: linear-gradient(145deg, #0D1B2A, #162d44);
      border: 1.5px solid #3a5a7a;
      border-radius: 14px;
      box-shadow: 0 0 30px rgba(0,80,160,0.5);
      padding: 28px 32px;
      min-width: 340px;
      max-width: 520px;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      color: #fff;
      animation: mzmodal-slide-in 0.25s ease-out;
    }
    @keyframes mzmodal-slide-in {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .mzmodal-title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 20px;
      letter-spacing: 0.5px;
      text-align: center;
      color: #cde;
    }

    .mzmodal-option {
      display: flex;
      align-items: center;
      padding: 10px 16px;
      margin-bottom: 8px;
      border-radius: 8px;
      border: 1.5px solid #3a5a7a;
      background: rgba(255,255,255,0.04);
      cursor: pointer;
      transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
      user-select: none;
    }
    .mzmodal-option:hover:not(.mzmodal-option-disabled) {
      background: rgba(68,170,255,0.10);
      border-color: #4af;
    }
    .mzmodal-option.mzmodal-option-selected {
      background: rgba(40,167,69,0.18);
      border-color: #28a745;
      box-shadow: 0 0 10px rgba(40,167,69,0.35);
    }
    .mzmodal-option.mzmodal-option-disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }

    .mzmodal-check {
      width: 22px; height: 22px;
      border: 2px solid #5a7a9a;
      border-radius: 4px;
      margin-right: 14px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      transition: background 0.15s, border-color 0.15s;
    }
    .mzmodal-option-selected .mzmodal-check {
      background: #28a745;
      border-color: #28a745;
    }

    .mzmodal-label {
      font-size: 15px;
      letter-spacing: 0.3px;
    }

    .mzmodal-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 20px;
    }

    .mzmodal-counter {
      font-size: 14px;
      color: #8ab;
    }

    .mzmodal-submit-btn {
      padding: 8px 26px;
      border: none;
      border-radius: 8px;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 0.5px;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
    }
    .mzmodal-submit-btn:not(:disabled) {
      background: #28a745;
      color: #fff;
      box-shadow: 0 0 12px rgba(40,167,69,0.5);
    }
    .mzmodal-submit-btn:not(:disabled):hover {
      background: #34d058;
      box-shadow: 0 0 20px rgba(52,208,88,0.7);
      transform: scale(1.04);
    }
    .mzmodal-submit-btn:disabled {
      background: #444;
      color: #888;
      cursor: not-allowed;
      box-shadow: none;
    }
  `;

  let stylesInjected = false;
  function injectStyles() {
    if (stylesInjected) return;
    const el = document.createElement('style');
    el.id = 'mzmodal-styles';
    el.textContent = MODAL_STYLES;
    document.head.appendChild(el);
    stylesInjected = true;
  }

  // ── State ────────────────────────────────────────────────────────────
  let modalState = null;

  // ── Parse param string ───────────────────────────────────────────────
  // Param: "min|max|label1&label2&label3"
  function parseModalParam(param) {
    const parts = param.split('|');
    if (parts.length < 3) return null;
    const min = parseInt(parts[0], 10);
    const max = parseInt(parts[1], 10);
    const labels = parts.slice(2).join('|').split('&').map(s => s.trim().replace(/_/g, ' ')).filter(Boolean);
    return { min, max, labels };
  }

  function refreshModal() {
    if (!modalState) return;
    const selectedCount = modalState.selected.filter(Boolean).length;

    for (let i = 0; i < modalState.labels.length; i++) {
      const optEl = document.getElementById('mzmodal-opt-' + i);
      if (!optEl) continue;
      const isSelected = modalState.selected[i];
      optEl.classList.toggle('mzmodal-option-selected', isSelected);
      // Disable if at max and not selected
      const atMax = selectedCount >= modalState.max;
      optEl.classList.toggle('mzmodal-option-disabled', atMax && !isSelected);
      // Check mark
      const checkEl = optEl.querySelector('.mzmodal-check');
      if (checkEl) checkEl.textContent = isSelected ? '\u2713' : '';
    }

    // Counter
    const counterEl = document.getElementById('mzmodal-counter');
    if (counterEl) {
      counterEl.textContent = 'Selected: ' + selectedCount + ' / ' + modalState.max;
    }

    // Submit button: enabled when selectedCount >= min
    const submitBtn = document.getElementById('mzmodal-submit');
    if (submitBtn) submitBtn.disabled = selectedCount < modalState.min;
  }

  function ShowMZModalUI(param, tooltip, decisionIndex, submitCallback) {
    HideMZModalUI();
    injectStyles();

    const parsed = parseModalParam(param);
    if (!parsed || parsed.labels.length === 0) {
      if (submitCallback) submitCallback('-', decisionIndex);
      return;
    }

    modalState = {
      min: parsed.min,
      max: parsed.max,
      labels: parsed.labels,
      selected: new Array(parsed.labels.length).fill(false),
      callback: submitCallback,
      decisionIndex: decisionIndex
    };

    // Build DOM
    const overlay = document.createElement('div');
    overlay.className = 'mzmodal-overlay';
    overlay.id = 'mzmodal-overlay';

    const panel = document.createElement('div');
    panel.className = 'mzmodal-panel';

    // Title
    const title = document.createElement('div');
    title.className = 'mzmodal-title';
    title.textContent = tooltip || ('Choose ' + parsed.min + (parsed.min !== parsed.max ? ' to ' + parsed.max : ''));
    panel.appendChild(title);

    // Options
    for (let i = 0; i < parsed.labels.length; i++) {
      const opt = document.createElement('div');
      opt.className = 'mzmodal-option';
      opt.id = 'mzmodal-opt-' + i;

      const check = document.createElement('div');
      check.className = 'mzmodal-check';
      opt.appendChild(check);

      const label = document.createElement('span');
      label.className = 'mzmodal-label';
      label.textContent = parsed.labels[i];
      opt.appendChild(label);

      opt.addEventListener('click', (function(idx) {
        return function() {
          if (!modalState) return;
          const selectedCount = modalState.selected.filter(Boolean).length;
          if (modalState.selected[idx]) {
            // Deselect
            modalState.selected[idx] = false;
          } else if (selectedCount < modalState.max) {
            // Select
            modalState.selected[idx] = true;
          }
          refreshModal();
        };
      })(i));

      panel.appendChild(opt);
    }

    // Footer
    const footer = document.createElement('div');
    footer.className = 'mzmodal-footer';

    const counter = document.createElement('span');
    counter.className = 'mzmodal-counter';
    counter.id = 'mzmodal-counter';
    footer.appendChild(counter);

    const submitBtn = document.createElement('button');
    submitBtn.className = 'mzmodal-submit-btn';
    submitBtn.id = 'mzmodal-submit';
    submitBtn.textContent = 'Confirm';
    submitBtn.disabled = true;
    submitBtn.addEventListener('click', function() {
      if (!modalState) return;
      const selectedCount = modalState.selected.filter(Boolean).length;
      if (selectedCount < modalState.min) return;
      const indices = [];
      for (let i = 0; i < modalState.selected.length; i++) {
        if (modalState.selected[i]) indices.push(i);
      }
      const result = indices.length > 0 ? indices.join(',') : '-';
      const cb = modalState.callback;
      const di = modalState.decisionIndex;
      HideMZModalUI();
      if (cb) cb(result, di);
    });
    footer.appendChild(submitBtn);

    panel.appendChild(footer);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);

    refreshModal();
  }

  function HideMZModalUI() {
    const overlay = document.getElementById('mzmodal-overlay');
    if (overlay) overlay.remove();
    modalState = null;
  }

  // Expose globally
  window.ShowMZModalUI = ShowMZModalUI;
  window.HideMZModalUI = HideMZModalUI;
})();
