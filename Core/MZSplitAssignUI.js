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

    /* Circular steppers keep their bespoke shape but pull colours from tokens (danger/success). */
    .mzsplit-btn-minus {
      background: var(--danger);
      color: var(--on-danger);
      box-shadow: 0 0 6px var(--danger);
    }
    .mzsplit-btn-minus:hover:not(:disabled) {
      filter: brightness(1.15);
      box-shadow: 0 0 12px var(--danger);
    }
    .mzsplit-btn-minus:disabled {
      background: var(--surface-sunken);
      color: var(--text-muted);
      cursor: default;
      box-shadow: none;
    }

    .mzsplit-btn-plus {
      background: var(--success);
      color: var(--on-success);
      box-shadow: 0 0 6px var(--success);
    }
    .mzsplit-btn-plus:hover:not(:disabled) {
      filter: brightness(1.15);
      box-shadow: 0 0 12px var(--success);
    }
    .mzsplit-btn-plus:disabled {
      background: var(--surface-sunken);
      color: var(--text-muted);
      cursor: default;
      box-shadow: none;
    }

    .mzsplit-amount {
      min-width: 28px;
      text-align: center;
      font-family: 'Orbitron', 'Segoe UI', monospace;
      font-size: 18px;
      font-weight: bold;
      color: var(--text, #fff);
      text-shadow: 0 0 8px var(--glow, rgba(100,200,255,0.7));
      flex-shrink: 0;
    }

    /* ── Highlight border for split-assign-eligible cards ─────────── */
    .mzsplit-target-card {
      outline: 2px solid var(--accent, rgba(100,200,255,0.7));
      outline-offset: -2px;
      box-shadow: 0 0 12px var(--glow, rgba(100,200,255,0.4));
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
      background: var(--surface-raised, linear-gradient(145deg, #0D1B2A, #162d44));
      border: 1.5px solid var(--border, #3a5a7a);
      border-radius: var(--radius, 14px);
      box-shadow: 0 0 30px var(--glow, rgba(0,80,160,0.4));
      backdrop-filter: blur(10px) saturate(110%);
      -webkit-backdrop-filter: blur(10px) saturate(110%);
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      color: var(--text, #fff);
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
      color: var(--accent-strong, #4af);
      text-shadow: 0 0 10px var(--glow, rgba(68,170,255,0.6));
      min-width: 30px;
      text-align: center;
    }

    /* Skin from .btn.btn-primary (button.css); layout only kept here. */
    .mzsplit-submit-btn { padding: 8px 22px; font-size: 14px; }
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
  // Param: "amount|mzID1&mzID2&mzID3"                              (full assignment, cap = pool)
  //   or:  "amount|mzID1:cap1&mzID2:cap2|UPTO"                     (per-target caps; partial OK)
  // The optional trailing "|UPTO" segment lets the player submit with points unassigned
  // ("up to N" effects — SOR_052 Redemption). Per-target ":cap" limits each target's amount
  // (e.g. a heal can't exceed a target's current damage). Backward compatible: no ":cap" → cap is
  // the full pool, and no "|UPTO" → must assign all (the original damage-split behaviour).
  function parseSplitParam(param) {
    const segs = String(param).split('|');
    if (segs.length < 2) return null;
    const amount = parseInt(segs[0], 10);
    const mode = (segs[2] || '').trim().toUpperCase();
    // Three shapes, and the card's printed wording picks which:
    //   (default)   "distribute N"            → must assign ALL N
    //   UPTO        "distribute up to N"      → any total 0..N
    //   ALLORNONE   "you may distribute … equal to N"
    //               → 0 (decline the whole optional ability) or EXACTLY N, nothing between.
    // ⚠ ALLORNONE is NOT "UPTO with a nag": "You may" makes the ABILITY optional, while "equal to"
    // makes the AMOUNT fixed once you engage. Treating it as up-to lets a player bank 3 of 40
    // Advantage, which the card never offers (ASH_195 Helgait).
    const allowPartial = (mode === 'UPTO');
    const allOrNone    = (mode === 'ALLORNONE');
    const targets = segs[1].split('&').map(s => s.trim()).filter(Boolean).map(function (spec) {
      const c = spec.indexOf(':');
      if (c === -1) return { mzID: spec, cap: amount };
      return { mzID: spec.substring(0, c), cap: parseInt(spec.substring(c + 1), 10) };
    });
    return { amount, targets, allowPartial, allOrNone, mzIDs: targets.map(t => t.mzID) };
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
      // Plus is capped by both the remaining pool AND this target's own cap (e.g. its damage).
      if (plusBtn)  plusBtn.disabled  = (splitState.remaining <= 0) || (target.amount >= target.cap);
    }

    // Banner
    const poolEl = document.getElementById('mzsplit-pool-remaining');
    if (poolEl) poolEl.textContent = splitState.remaining;

    const submitBtn = document.getElementById('mzsplit-submit');
    // "Up to" effects may submit with points left over; otherwise the full pool must be assigned.
    // Confirm is live when the assignment is a legal one for this mode. ALLORNONE allows the two
    // endpoints only — untouched (decline) or complete.
    if (submitBtn) {
      const assignedNow = splitState.totalPool - splitState.remaining;
      submitBtn.disabled = splitState.allowPartial ? false
        : splitState.allOrNone ? !(assignedNow === 0 || splitState.remaining === 0)
        : (splitState.remaining !== 0);
    }
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
      if (splitState.remaining > 0 && target.amount < target.cap) {
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
    submit.className = 'mzsplit-submit-btn btn btn-primary';
    submit.id = 'mzsplit-submit';
    submit.textContent = 'Confirm';
    submit.disabled = true;
    submit.addEventListener('click', function() {
      if (!splitState) return;
      const assignedAtSubmit = splitState.totalPool - splitState.remaining;
      if (splitState.allOrNone) {
        if (assignedAtSubmit !== 0 && splitState.remaining !== 0) return;
      } else if (!splitState.allowPartial && splitState.remaining !== 0) return;
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
  // ⚠ A TARGET'S mzID IS NOT ALWAYS A DOM id. The engine addresses a target in its own coordinates,
  // and an app is free to render that target somewhere else — or on another screen entirely. SWUSim's
  // Twin Suns is the case that forced this: ZoneSearch hands back seat-tagged ids (`p3GroundArena-1`)
  // for every opponent once a game has more than two seats, but no renderer ever emits a `p{n}` DOM id
  // — only `my…`/`their…`, and only for the two seats the current view draws. A bare getElementById
  // therefore resolved the caster's OWN units and nothing else, so an opponent could not be given a
  // single point of a split (bug #1022, Ninth Sister ASH_148). The app supplies the mapping; the
  // engine keeps the plain id lookup as its default, so every other root is unaffected.
  function resolveTargetElement(mzID) {
    if (typeof window.MZSplitResolveTargetElement === 'function') {
      const mapped = window.MZSplitResolveTargetElement(mzID);
      if (mapped) return mapped;
    }
    return document.getElementById(mzID);
  }

  function injectCardOverlays() {
    if (!splitState) return;
    for (const target of splitState.targets) {
      // Skip if overlay already attached — and note that a DETACHED overlay (its host card was wiped
      // by a zone/tile rebuild) does not match, which is exactly what lets a re-inject heal it.
      if (document.getElementById('mzsplit-overlay-' + target.mzID)) continue;
      const cardSpan = resolveTargetElement(target.mzID);
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
    if (!parsed || parsed.amount <= 0 || parsed.targets.length === 0) {
      // Nothing to split — auto-submit empty
      if (submitCallback) submitCallback('', decisionIndex);
      return;
    }

    // Initialize state
    splitState = {
      totalPool: parsed.amount,
      remaining: parsed.amount,
      allowPartial: parsed.allowPartial,
      allOrNone: parsed.allOrNone,
      targets: parsed.targets.map(t => ({ mzID: t.mzID, cap: t.cap, amount: 0 })),
      callback: submitCallback,
      decisionIndex: decisionIndex
    };

    // Show banner immediately — document.body always exists
    document.body.appendChild(createBanner(tooltip, decisionIndex));
    refreshUI();

    // Attach the per-card +/- controls. Two callers, two DOM orderings:
    //   • Some callers mount the decision UI AFTER the new board DOM exists → the synchronous inject
    //     below attaches in the same paint as the cards (no flicker).
    //   • SWUSim's NextTurnRender.php calls CheckAndShowDecisionQueue BEFORE its PopulateZone() calls
    //     rebuild every zone's innerHTML — so a synchronous inject lands on card spans that are then
    //     WIPED by the zone repopulate (the banner survives on document.body, but the on-card controls
    //     vanish — "Assign N / Remaining:N shows but no +/- buttons"). Re-inject on a 0ms timeout, which
    //     runs after the current render stack (past PopulateZone), re-attaching to the fresh card spans.
    // The `mzsplit-overlay-<mzID>` guard in injectCardOverlays makes whichever call runs second a no-op,
    // so no duplicate overlays regardless of ordering.
    injectCardOverlays();
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
  // Re-attach the on-card controls after the HOST elements have been replaced. The setTimeout(0) in
  // ShowMZSplitAssignUI only covers the render stack that mounted the decision; anything that rebuilds
  // a target's container LATER (SWUSim rebuilds its preview tiles with innerHTML on every board update)
  // takes its overlays with it, and the assignment silently loses its buttons while splitState still
  // holds the amounts. Idempotent — the overlay-id guard makes a redundant call free.
  window.MZSplitReinjectOverlays = injectCardOverlays;

})();
