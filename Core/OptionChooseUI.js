/**
 * OptionChooseUI.js - Labeled multi-option picker for the Decision Queue.
 *
 * Renders a centered banner with one button per option; clicking submits that
 * option's label verbatim. Used for "choose an arena" style decisions
 * (SOR_221 Outmaneuver: "Ground" / "Space").
 *
 * Decision queue Param format: "[@CardID&]Opt1&Opt2[&Opt3...]"
 *   e.g. "Ground&Space"
 *   A leading "@CardID" segment (e.g. "@SOR_157&Play&Discard&Leave") is rendered as the card
 *   being acted on (its image is shown above the options) and is NOT a selectable option.
 *
 * Return value: the chosen option label as a string (e.g. "Ground").
 *
 * Deprecated for new card-authoring. Prefer MZMODAL / await $player.Modal(...)
 * for new finite labeled choices; keep this file for existing queued paths.
 *
 * Usage (called from the decision dispatcher in UILibraries.js):
 *   ShowOptionChooseUI(paramString, tooltip, decisionIndex, submitCallback)
 */

(function() {
  'use strict';

  const OPTION_CHOOSE_STYLES = `
    .optchoose-banner {
      position: fixed;
      bottom: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 16px 32px;
      max-width: 80vw;
      box-sizing: border-box;
      background: linear-gradient(145deg, #0D1B2A, #162d44);
      border: 1.5px solid rgba(95,208,255,0.45);
      border-radius: 14px;
      box-shadow: 0 0 24px rgba(95,208,255,0.25), 0 4px 24px rgba(0,0,0,0.5);
      font-family: 'Orbitron', 'Segoe UI', monospace;
      user-select: none;
    }
    .optchoose-label {
      color: #d0ecff;
      font-size: 14px;
      max-width: 260px;
      text-align: center;
      flex-shrink: 0;
    }
    /* Card strip scrolls horizontally so a large searched zone (e.g. a 30+ card deck)
       stays within the 80vw banner while the prompt and OK button remain visible. */
    .optchoose-cards {
      display: flex;
      gap: 8px;
      align-items: center;
      flex: 1 1 auto;
      min-width: 0;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: thin;
      padding-bottom: 6px;
    }
    .optchoose-cards::-webkit-scrollbar { height: 8px; }
    .optchoose-cards::-webkit-scrollbar-thumb {
      background: rgba(95,208,255,0.4);
      border-radius: 4px;
    }
    .optchoose-card {
      height: 132px;
      border-radius: 6px;
      border: 1px solid rgba(95,208,255,0.5);
      box-shadow: 0 0 10px rgba(95,208,255,0.3);
      display: block;
      flex: 0 0 auto;
    }
    .optchoose-options { display: flex; gap: 10px; flex-shrink: 0; }
    /* Skin from .btn (button.css); layout only kept here. */
    .optchoose-btn { padding: 10px 24px; font-size: 15px; }
  `;

  let styleEl = null;
  let bannerEl = null;

  function injectStyles() {
    if (styleEl) return;
    styleEl = document.createElement('style');
    styleEl.textContent = OPTION_CHOOSE_STYLES;
    document.head.appendChild(styleEl);
  }

  function render(options, tooltip, decisionIndex, submitCallback, cardIDs) {
    if (bannerEl) bannerEl.remove();

    bannerEl = document.createElement('div');
    bannerEl.className = 'optchoose-banner';

    // Optional: the card(s) being acted on, shown to the left of the prompt.
    if (cardIDs && cardIDs.length) {
      const cardsWrap = document.createElement('div');
      cardsWrap.className = 'optchoose-cards';
      const imgBase = (window.rootPath || '.') + '/concat/';
      cardIDs.forEach(function(cid) {
        const img = document.createElement('img');
        img.className = 'optchoose-card';
        img.src = imgBase + cid + '.webp';
        img.alt = cid;
        cardsWrap.appendChild(img);
      });
      bannerEl.appendChild(cardsWrap);
    }

    const label = document.createElement('div');
    label.className = 'optchoose-label';
    label.textContent = tooltip;

    const optionsWrap = document.createElement('div');
    optionsWrap.className = 'optchoose-options';

    options.forEach(function(opt) {
      const btn = document.createElement('button');
      btn.className = 'optchoose-btn btn';
      btn.textContent = opt;
      btn.addEventListener('click', function() {
        submitCallback(opt, decisionIndex);
        HideOptionChooseUI();
      });
      optionsWrap.appendChild(btn);
    });

    bannerEl.appendChild(label);
    bannerEl.appendChild(optionsWrap);
    document.body.appendChild(bannerEl);
  }

  /**
   * @param {string} paramString - "Opt1&Opt2[&...]"
   * @param {string} tooltip - Human-readable prompt
   * @param {number} decisionIndex - Index in the decision queue
   * @param {function} submitCallback - Called with (optionLabel, decisionIndex)
   */
  window.ShowOptionChooseUI = function(paramString, tooltip, decisionIndex, submitCallback) {
    injectStyles();
    const segs = String(paramString || '').split('&').filter(function(o) { return o !== ''; });
    // Segments prefixed with "@" are card images (the card being acted on), not options.
    const cardIDs = [];
    const options = [];
    segs.forEach(function(s) {
      if (s.charAt(0) === '@') cardIDs.push(s.slice(1));
      else options.push(s);
    });
    render(options, tooltip, decisionIndex, submitCallback, cardIDs);
  };

  window.HideOptionChooseUI = function() {
    if (bannerEl) { bannerEl.remove(); bannerEl = null; }
  };
})();
