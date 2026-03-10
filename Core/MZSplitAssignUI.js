/**
 * MZSplitAssignUI.js - Split/Assign Pool UI for Decision Queue
 *
 * Provides an inline overlay UI for splitting a numeric pool across multiple
 * target cards on the board. Each target gets +/- arrows and an assignment
 * counter overlaid on its card element. A bottom banner shows the remaining
 * pool and a submit button (enabled only when the entire pool is assigned).
 *
 * Decision queue Param format: "amount|mzID1&mzID2&mzID3"
 * Return format: "mzID1:amt1,mzID2:amt2" (comma-separated, non-zero only)
 *
 * Usage (called from CheckAndShowDecisionQueue in UILibraries.js):
 *   ShowMZSplitAssignUI(paramString, tooltip, decisionIndex, submitCallback)
 */

(function() {
  'use strict';

  // ── CSS ──────────────────────────────────────────────────────────────
  const SPLIT_ASSIGN_STYLES = `
    /* ── Per-card overlay ────────────────────────────────────────────── */
    .mzsplit-card-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2px;
      padding: 4px 2px;
      background: linear-gradient(0deg, rgba(0,0,0,0.88) 0%, rgba(0,0,0,0.55) 80%, transparent 100%);
      border-radius: 0 0 8px 8px;
      z-index: 1100;
      pointer-events: auto;
      user-select: none;
    }

    .mzsplit-btn {
      width: 26px;
      height: 26px;
      border: none;
      border-radius: 50%;
      font-family: 'Orbitron', 'Segoe UI', monospace;
      font-size: 16px;
      font-weight: bold;
      line-height: 26px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.10s ease, box-shadow 0.15s ease, background 0.15s ease;
      padding: 0;
      flex-shrink: 0;
    }

    .mzsplit-btn:active {
      transform: scale(0.88);
    }

    .mzsplit-btn-minus {
      background: #dc3545;
      color: #fff;
      box-shadow: 0 0 6px rgba(220,53,69,0.6);
    }
    .mzsplit-btn-minus:hover:not(:disabled) {
      background: #ff4d5e;
      box-shadow: 0 0 12px rgba(255,77,94,0.8);
    }
    .mzsplit-btn-minus:disabled {
      background: #555;
      color: #999;
      cursor: default;
      box-shadow: none;
    }

    .mzsplit-btn-plus {
      background: #28a745;
      color: #fff;
      box-shadow: 0 0 6px rgba(40,167,69,0.6);
    }
    .mzsplit-btn-plus:hover:not(:disabled) {
      background: #34d058;
      box-shadow: 0 0 12px rgba(52,208,88,0.8);
    }
    .mzsplit-btn-plus:disabled {
      background: #555;
      color: #999;
      cursor: default;
      box-shadow: none;
    }

    .mzsplit-amount {
      min-width: 28px;
      text-align: center;
      font-family: 'Orbitron', 'Segoe UI', monospace;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
      text-shadow: 0 0 8px rgba(100,200,255,0.7);
      flex-shrink: 0;
    }

    /* ── Highlight border for split-assign-eligible cards ─────────── */
    .mzsplit-target-card {
      outline: 2px solid rgba(100,200,255,0.7);
      outline-offset: -2px;
      box-shadow: 0 0 12px rgba(100,200,255,0.4);
    }

    /* ── Bottom banner ───────────────────────────────────────────── */
    .mzsplit-banner {
      position: fixed;
      bottom: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 12px 28px;
      background: linear-gradient(145deg, #0D1B2A, #162d44);
      border: 1.5px solid #3a5a7a;
      border-radius: 14px;
      box-shadow: 0 0 30px rgba(0,80,160,0.4);
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      color: #fff;
      animation: mzsplit-banner-in 0.35s ease-out;
    }

    @keyframes mzsplit-banner-in {
      from { opacity: 0; transform: translateX(-50%) translateY(20px); }
      to   { opacity: 1; transform: translateX(-50%) translateY(0); }
    }

    .mzsplit-banner-label {
      font-size: 15px;
      letter-spacing: 0.5px;
    }

    .mzsplit-banner-pool {
      font-size: 22px;
      font-weight: bold;
      color: #4af;
      text-shadow: 0 0 10px rgba(68,170,255,0.6);
      min-width: 30px;
      text-align: center;
    }

    .mzsplit-submit-btn {
      padding: 8px 22px;
      border: none;
      border-radius: 8px;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 0.5px;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
    }

    .mzsplit-submit-btn:not(:disabled) {
      background: #28a745;
      color: #fff;
      box-shadow: 0 0 12px rgba(40,167,69,0.5);
    }
    .mzsplit-submit-btn:not(:disabled):hover {
      background: #34d058;
      box-shadow: 0 0 20px rgba(52,208,88,0.7);
      transform: scale(1.04);
    }

    .mzsplit-submit-btn:disabled {
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
    el.id = 'mzsplit-assign-styles';
    el.textContent = SPLIT_ASSIGN_STYLES;
    document.head.appendChild(el);
    stylesInjected = true;
  }

  // ── State ────────────────────────────────────────────────────────────
  let splitState = null; // { totalPool, remaining, targets: [{mzID, amount}], callback, decisionIndex }

  // ── Parse param string ───────────────────────────────────────────────
  // Param: "amount|mzID1&mzID2&mzID3"
  function parseSplitParam(param) {
    const pipeIdx = param.indexOf('|');
    if (pipeIdx === -1) return null;
    const amount = parseInt(param.substring(0, pipeIdx), 10);
    const targetsStr = param.substring(pipeIdx + 1);
    const mzIDs = targetsStr.split('&').map(s => s.trim()).filter(Boolean);
    return { amount, mzIDs };
  }

  // ── Serialize result ─────────────────────────────────────────────────
  // Returns "mzID1:amt1,mzID2:amt2" (non-zero only)
  function serializeAssignments() {
    if (!splitState) return '';
    return splitState.targets
      .filter(t => t.amount > 0)
      .map(t => t.mzID + ':' + t.amount)
      .join(',');
  }

  // ── UI: Update all overlays + banner ─────────────────────────────────
  function refreshUI() {
    if (!splitState) return;

    // Recalculate remaining
    const assigned = splitState.targets.reduce((s, t) => s + t.amount, 0);
    splitState.remaining = splitState.totalPool - assigned;

    // Per-card overlays
    for (const target of splitState.targets) {
      const amountEl = document.getElementById('mzsplit-amount-' + target.mzID);
      const minusBtn = document.getElementById('mzsplit-minus-' + target.mzID);
      const plusBtn  = document.getElementById('mzsplit-plus-'  + target.mzID);
      if (amountEl) amountEl.textContent = target.amount;
      if (minusBtn) minusBtn.disabled = target.amount <= 0;
      if (plusBtn)  plusBtn.disabled  = splitState.remaining <= 0;
    }

    // Banner
    const poolEl = document.getElementById('mzsplit-pool-remaining');
    if (poolEl) poolEl.textContent = splitState.remaining;

    const submitBtn = document.getElementById('mzsplit-submit');
    if (submitBtn) submitBtn.disabled = splitState.remaining !== 0;
  }

  // ── Build per-card overlay ───────────────────────────────────────────
  function createCardOverlay(target) {
    const overlay = document.createElement('div');
    overlay.className = 'mzsplit-card-overlay';
    overlay.id = 'mzsplit-overlay-' + target.mzID;

    // Minus button
    const minus = document.createElement('button');
    minus.className = 'mzsplit-btn mzsplit-btn-minus';
    minus.id = 'mzsplit-minus-' + target.mzID;
    minus.textContent = '\u2212'; // −
    minus.disabled = true;
    minus.addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      if (target.amount > 0) {
        target.amount--;
        refreshUI();
      }
    });

    // Amount display
    const amountSpan = document.createElement('span');
    amountSpan.className = 'mzsplit-amount';
    amountSpan.id = 'mzsplit-amount-' + target.mzID;
    amountSpan.textContent = '0';

    // Plus button
    const plus = document.createElement('button');
    plus.className = 'mzsplit-btn mzsplit-btn-plus';
    plus.id = 'mzsplit-plus-' + target.mzID;
    plus.textContent = '+';
    plus.addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      if (splitState.remaining > 0) {
        target.amount++;
        refreshUI();
      }
    });

    overlay.appendChild(minus);
    overlay.appendChild(amountSpan);
    overlay.appendChild(plus);
    return overlay;
  }

  // ── Build bottom banner ──────────────────────────────────────────────
  function createBanner(tooltip, decisionIndex) {
    const banner = document.createElement('div');
    banner.className = 'mzsplit-banner';
    banner.id = 'mzsplit-banner';

    // Label text
    const label = document.createElement('span');
    label.className = 'mzsplit-banner-label';
    label.textContent = tooltip || 'Assign points';
    banner.appendChild(label);

    // Remaining pool badge
    const poolLabel = document.createElement('span');
    poolLabel.className = 'mzsplit-banner-label';
    poolLabel.textContent = 'Remaining:';
    banner.appendChild(poolLabel);

    const poolVal = document.createElement('span');
    poolVal.className = 'mzsplit-banner-pool';
    poolVal.id = 'mzsplit-pool-remaining';
    poolVal.textContent = splitState ? splitState.remaining : '0';
    banner.appendChild(poolVal);

    // Submit button
    const submit = document.createElement('button');
    submit.className = 'mzsplit-submit-btn';
    submit.id = 'mzsplit-submit';
    submit.textContent = 'Confirm';
    submit.disabled = true;
    submit.addEventListener('click', function() {
      if (!splitState || splitState.remaining !== 0) return;
      const result = serializeAssignments();
      const cb = splitState.callback;
      const di = splitState.decisionIndex;
      HideMZSplitAssignUI();
      if (cb) cb(result, di);
    });
    banner.appendChild(submit);

    return banner;
  }

  // ── Inject overlays onto card DOM elements ───────────────────────────
  // Called deferred (via setTimeout) so the zone render has completed first.
  function injectCardOverlays() {
    if (!splitState) return;
    for (const target of splitState.targets) {
      // Skip if overlay already attached
      if (document.getElementById('mzsplit-overlay-' + target.mzID)) continue;
      const cardSpan = document.getElementById(target.mzID);
      if (!cardSpan) {
        console.warn('MZSplitAssign: could not find card element for', target.mzID);
        continue;
      }
      // Ensure the card span has relative positioning (it should already)
      const pos = window.getComputedStyle(cardSpan).position;
      if (pos === 'static') cardSpan.style.position = 'relative';
      // Add highlight outline
      cardSpan.classList.add('mzsplit-target-card');
      // Attach overlay
      cardSpan.appendChild(createCardOverlay(target));
    }
    refreshUI();
  }

  // ── Main entry point ─────────────────────────────────────────────────
  /**
   * @param {string} param - Decision Param string: "amount|mzID1&mzID2&..."
   * @param {string} tooltip - Prompt text for the banner
   * @param {number} decisionIndex - Index in decision queue
   * @param {function(string, number)} submitCallback - Called with (serializedResult, decisionIndex)
   */
  function ShowMZSplitAssignUI(param, tooltip, decisionIndex, submitCallback) {
    // Clean up any previous instance
    HideMZSplitAssignUI();
    injectStyles();

    const parsed = parseSplitParam(param);
    if (!parsed || parsed.amount <= 0 || parsed.mzIDs.length === 0) {
      // Nothing to split — auto-submit empty
      if (submitCallback) submitCallback('', decisionIndex);
      return;
    }

    // Initialize state
    splitState = {
      totalPool: parsed.amount,
      remaining: parsed.amount,
      targets: parsed.mzIDs.map(id => ({ mzID: id, amount: 0 })),
      callback: submitCallback,
      decisionIndex: decisionIndex
    };

    // Show banner immediately — document.body always exists
    document.body.appendChild(createBanner(tooltip, decisionIndex));
    refreshUI();

    // Defer card overlay injection: CheckAndShowDecisionQueue is called BEFORE
    // AppendStaticZones populates the board DOM, so the card spans don't exist yet.
    // setTimeout(fn, 0) queues the callback after the current JS call stack finishes,
    // by which time AppendStaticZones has already inserted the card elements.
    setTimeout(injectCardOverlays, 0);
  }

  // ── Cleanup ──────────────────────────────────────────────────────────
  function HideMZSplitAssignUI() {
    // Remove all overlays
    document.querySelectorAll('.mzsplit-card-overlay').forEach(el => el.remove());
    document.querySelectorAll('.mzsplit-target-card').forEach(el => el.classList.remove('mzsplit-target-card'));

    // Remove banner
    const banner = document.getElementById('mzsplit-banner');
    if (banner) banner.remove();

    splitState = null;
  }

  // ── Export to global scope ───────────────────────────────────────────
  window.ShowMZSplitAssignUI  = ShowMZSplitAssignUI;
  window.HideMZSplitAssignUI  = HideMZSplitAssignUI;
  window.parseSplitParam      = parseSplitParam;

})();
