/**
 * TwoSidedSliderUI.js - Two-option slider chooser for Decision Queue
 *
 * Presents a numeric slider between two sides. The chosen number is interpreted
 * as the left-side count; the right-side count is max - chosen.
 *
 * Decision queue Param format: "min|max|leftSpec|rightSpec"
 * Side spec formats:
 *   label~Caption_text
 *   card~CARDID
 *   cardlabel~CARDID~Caption_text
 *
 * Return value: selected left-side count as a string (e.g. "2")
 */

(function() {
  'use strict';

  const STYLES = `
    .twosided-slider-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 8, 18, 0.38);
      z-index: 5000;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      animation: twosided-slider-fade 0.16s ease-out;
      pointer-events: none;
    }

    @keyframes twosided-slider-fade {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .twosided-slider-panel {
      width: min(92vw, 920px);
      margin: 0 0 16px;
      background: rgba(10, 21, 33, 0.88);
      border: 1px solid rgba(157, 211, 255, 0.22);
      border-radius: 16px;
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.32);
      padding: 14px 16px 12px;
      color: #f3fbff;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      pointer-events: auto;
      backdrop-filter: blur(5px);
    }

    .twosided-slider-title {
      text-align: center;
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 10px;
      color: #d7f2ff;
    }

    .twosided-slider-main {
      display: grid;
      grid-template-columns: auto auto minmax(240px, 1fr) auto auto;
      align-items: center;
      gap: 12px;
    }

    .twosided-slider-side {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      min-width: 0;
      text-align: center;
    }

    .twosided-slider-side img {
      width: clamp(68px, 9vw, 108px);
      aspect-ratio: 1 / 1;
      object-fit: contain;
      background: rgba(255, 255, 255, 0.04);
      border-radius: 10px;
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.28);
    }

    .twosided-slider-side-label {
      font-size: 12px;
      line-height: 1.2;
      color: #eef9ff;
      word-break: break-word;
      max-width: 110px;
    }

    .twosided-slider-side-count {
      font-size: 32px;
      font-weight: 700;
      color: #ffffff;
      text-shadow: 0 0 10px rgba(117, 213, 255, 0.38);
      min-width: 34px;
      text-align: center;
    }

    .twosided-slider-range-wrap {
      padding: 0 6px;
    }

    .twosided-slider-range {
      width: 100%;
      accent-color: #7bd6ff;
      cursor: pointer;
    }

    .twosided-slider-scale {
      display: flex;
      justify-content: space-between;
      margin-top: 4px;
      color: #8eb7cf;
      font-size: 11px;
    }

    .twosided-slider-footer {
      display: flex;
      justify-content: center;
      margin-top: 10px;
    }

    /* Skin from .btn.btn-primary (button.css); layout only kept here. */
    .twosided-slider-confirm { padding: 8px 22px; font-size: 13px; }

    @media (max-width: 640px) {
      .twosided-slider-panel {
        width: min(96vw, 520px);
        padding: 12px;
      }

      .twosided-slider-main {
        grid-template-columns: 1fr 1fr;
        gap: 10px 8px;
      }

      .twosided-slider-side-count {
        order: -1;
      }

      .twosided-slider-range-wrap {
        grid-column: 1 / -1;
      }
    }
  `;

  let styleEl = null;
  let overlayEl = null;
  let state = null;

  function injectStyles() {
    if (styleEl) return;
    styleEl = document.createElement('style');
    styleEl.textContent = STYLES;
    document.head.appendChild(styleEl);
  }

  function buildCardImageUrl(cardId) {
    let assetFolder = '';
    const folderPathInput = document.getElementById('folderPath');
    if (typeof AssetReflectionPath === 'function') {
      const reflected = AssetReflectionPath();
      if (reflected) assetFolder = reflected;
    }
    if (!assetFolder && folderPathInput) {
      assetFolder = folderPathInput.value || '';
    }
    return './' + assetFolder + '/concat/' + encodeURIComponent(cardId) + '.webp';
  }

  function parseSideSpec(spec) {
    const parts = String(spec || '').split('~');
    const kind = String(parts[0] || 'label').toLowerCase();
    if (kind === 'card') {
      return { kind: 'card', cardId: parts[1] || '', caption: '' };
    }
    if (kind === 'cardlabel') {
      return { kind: 'cardlabel', cardId: parts[1] || '', caption: (parts[2] || '').replace(/_/g, ' ') };
    }
    return { kind: 'label', cardId: '', caption: (parts[1] || parts[0] || '').replace(/_/g, ' ') };
  }

  function parseParam(paramString) {
    const parts = String(paramString || '').split('|');
    if (parts.length < 4) return null;
    let min = parseInt(parts[0], 10);
    let max = parseInt(parts[1], 10);
    if (Number.isNaN(min)) min = 0;
    if (Number.isNaN(max)) max = min;
    if (max < min) {
      const tmp = min;
      min = max;
      max = tmp;
    }
    return {
      min,
      max,
      left: parseSideSpec(parts[2]),
      right: parseSideSpec(parts[3])
    };
  }

  function renderSide(sideState, spec, count) {
    while (sideState.container.firstChild) {
      sideState.container.removeChild(sideState.container.firstChild);
    }
    if (sideState.countEl) {
      sideState.countEl.textContent = String(count);
    }

    if ((spec.kind === 'card' || spec.kind === 'cardlabel') && spec.cardId) {
      const img = document.createElement('img');
      img.src = buildCardImageUrl(spec.cardId);
      img.alt = spec.caption || spec.cardId;
      img.onerror = function() {
        if (this.dataset.fallbackStage === 'concat') {
          this.dataset.fallbackStage = 'webp';
          this.src = img.src.replace('/concat/', '/WebpImages/');
          return;
        }
        this.onerror = null;
        this.src = './Assets/Images/cardback.png';
      };
      img.dataset.fallbackStage = 'concat';
      sideState.container.appendChild(img);
    }

    if (spec.caption) {
      const label = document.createElement('div');
      label.className = 'twosided-slider-side-label';
      label.textContent = spec.caption;
      sideState.container.appendChild(label);
    }
  }

  function refresh() {
    if (!state) return;
    const leftCount = state.currentValue;
    const rightCount = state.max - state.currentValue;
    state.range.value = String(state.currentValue);
    renderSide(state.leftState, state.left, leftCount);
    renderSide(state.rightState, state.right, rightCount);
  }

  function hide() {
    if (overlayEl) {
      overlayEl.remove();
      overlayEl = null;
    }
    state = null;
  }

  function show(paramString, tooltip, decisionIndex, submitCallback) {
    injectStyles();
    hide();

    const parsed = parseParam(paramString);
    if (!parsed) {
      if (typeof submitCallback === 'function') submitCallback('0', decisionIndex);
      return;
    }

    overlayEl = document.createElement('div');
    overlayEl.className = 'twosided-slider-overlay';

    const panel = document.createElement('div');
    panel.className = 'twosided-slider-panel';

    const title = document.createElement('div');
    title.className = 'twosided-slider-title';
    title.textContent = tooltip || 'Choose a split';
    panel.appendChild(title);

    const main = document.createElement('div');
    main.className = 'twosided-slider-main';

    const leftCount = document.createElement('div');
    leftCount.className = 'twosided-slider-side-count';
    const leftSide = document.createElement('div');
    leftSide.className = 'twosided-slider-side';
    const rightCount = document.createElement('div');
    rightCount.className = 'twosided-slider-side-count';
    const rightSide = document.createElement('div');
    rightSide.className = 'twosided-slider-side';

    const rangeWrap = document.createElement('div');
    rangeWrap.className = 'twosided-slider-range-wrap';
    const range = document.createElement('input');
    range.className = 'twosided-slider-range';
    range.type = 'range';
    range.min = String(parsed.min);
    range.max = String(parsed.max);
    range.step = '1';
    range.value = String(parsed.min);
    rangeWrap.appendChild(range);

    const scale = document.createElement('div');
    scale.className = 'twosided-slider-scale';
    scale.innerHTML = `<span>${parsed.min}</span><span>${parsed.max}</span>`;
    rangeWrap.appendChild(scale);

    main.appendChild(leftCount);
    main.appendChild(leftSide);
    main.appendChild(rangeWrap);
    main.appendChild(rightSide);
    main.appendChild(rightCount);
    panel.appendChild(main);

    const footer = document.createElement('div');
    footer.className = 'twosided-slider-footer';
    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'twosided-slider-confirm btn btn-primary';
    confirmBtn.textContent = 'Confirm';
    footer.appendChild(confirmBtn);
    panel.appendChild(footer);

    overlayEl.appendChild(panel);
    document.body.appendChild(overlayEl);

    state = {
      min: parsed.min,
      max: parsed.max,
      currentValue: parsed.min,
      left: parsed.left,
      right: parsed.right,
      range,
      leftState: { container: leftSide, countEl: leftCount },
      rightState: { container: rightSide, countEl: rightCount },
      decisionIndex,
      submitCallback
    };

    range.addEventListener('input', function() {
      if (!state) return;
      state.currentValue = parseInt(range.value, 10) || 0;
      refresh();
    });

    confirmBtn.addEventListener('click', function() {
      if (!state || typeof state.submitCallback !== 'function') {
        hide();
        return;
      }
      const selected = String(state.currentValue);
      const callback = state.submitCallback;
      const selectedDecisionIndex = state.decisionIndex;
      hide();
      callback(selected, selectedDecisionIndex);
    });

    refresh();
  }

  window.ShowTwoSidedSliderUI = show;
  window.HideTwoSidedSliderUI = hide;
})();
