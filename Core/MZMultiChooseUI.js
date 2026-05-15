(function() {
  'use strict';

  const MULTI_CHOOSE_STYLES = `
    .mzmulti-overlay {
      position: fixed;
      inset: 0;
      z-index: 5000;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.78);
      backdrop-filter: blur(3px);
      animation: mzmulti-fade-in 0.18s ease-out;
    }

    @keyframes mzmulti-fade-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .mzmulti-panel {
      width: fit-content;
      min-width: min(360px, 94vw);
      max-width: min(1100px, 94vw);
      max-height: 86vh;
      overflow: auto;
      box-sizing: border-box;
      padding: 24px;
      border-radius: 18px;
      border: 1px solid rgba(124, 186, 255, 0.35);
      background:
        radial-gradient(circle at top, rgba(85, 170, 255, 0.15), transparent 38%),
        linear-gradient(180deg, #102236 0%, #0b1624 100%);
      box-shadow: 0 28px 90px rgba(0, 0, 0, 0.55), 0 0 30px rgba(80, 150, 220, 0.22);
      color: #eef6ff;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
    }

    .mzmulti-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 20px;
    }

    .mzmulti-title-wrap {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .mzmulti-title {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 0.3px;
    }

    .mzmulti-subtitle {
      font-size: 12px;
      letter-spacing: 0.7px;
      text-transform: uppercase;
      color: #93b5d8;
    }

    .mzmulti-counter {
      align-self: center;
      min-width: 148px;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(124, 186, 255, 0.18);
      text-align: center;
      font-size: 13px;
      color: #cfe6ff;
    }

    .mzmulti-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-start;
      width: fit-content;
      max-width: 100%;
      gap: 14px 12px;
      margin-left: auto;
      margin-right: auto;
      margin-bottom: 20px;
    }

    .mzmulti-card {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 116px;
      gap: 5px;
      padding: 0;
      border: none;
      background: transparent;
      cursor: pointer;
      transition: transform 0.14s ease, opacity 0.14s ease;
      user-select: none;
    }

    .mzmulti-card:hover {
      transform: translateY(-1px);
    }

    .mzmulti-card.mzmulti-disabled {
      opacity: 0.56;
    }

    .mzmulti-check {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 1px solid rgba(193, 222, 255, 0.22);
      background: rgba(8, 16, 24, 0.72);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      color: transparent;
      transition: background 0.16s ease, color 0.16s ease, border-color 0.16s ease;
      pointer-events: none;
    }

    .mzmulti-selected .mzmulti-check {
      background: #4ed08b;
      color: #05200f;
      border-color: #4ed08b;
    }

    .mzmulti-card-image {
      position: relative;
      min-height: auto;
      width: 112px;
      border-radius: 11px;
      border: 1px solid rgba(152, 181, 214, 0.18);
      background: rgba(255, 255, 255, 0.02);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      transition: border-color 0.14s ease, box-shadow 0.14s ease, background 0.14s ease;
    }

    .mzmulti-card-image img {
      width: 112px;
      height: auto;
      border-radius: 10px;
      display: block;
      box-shadow: none;
    }

    .mzmulti-card:hover .mzmulti-card-image {
      border-color: rgba(137, 188, 233, 0.42);
      background: rgba(255, 255, 255, 0.04);
      box-shadow: 0 10px 18px rgba(0, 0, 0, 0.18);
    }

    .mzmulti-card.mzmulti-selected .mzmulti-card-image {
      border-color: rgba(103, 224, 154, 0.98);
      background: rgba(48, 120, 82, 0.1);
      box-shadow: 0 0 0 2px rgba(103, 224, 154, 0.24), 0 8px 18px rgba(8, 24, 14, 0.18);
    }

    .mzmulti-card.mzmulti-selected:hover .mzmulti-card-image {
      border-color: rgba(118, 234, 167, 1);
      background: rgba(48, 120, 82, 0.14);
      box-shadow: 0 0 0 2px rgba(103, 224, 154, 0.28), 0 10px 18px rgba(8, 24, 14, 0.2);
    }

    .mzmulti-label {
      width: 112px;
      min-height: 0;
      text-align: center;
      font-size: 10px;
      line-height: 1.25;
      color: #a9bfd7;
    }

    .mzmulti-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .mzmulti-guidance {
      font-size: 13px;
      color: #9ab8d7;
    }

    .mzmulti-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .mzmulti-btn {
      border: none;
      border-radius: 10px;
      padding: 10px 18px;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      font-size: 13px;
      letter-spacing: 0.4px;
      cursor: pointer;
      transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
    }

    .mzmulti-btn:disabled {
      cursor: not-allowed;
      opacity: 0.42;
      box-shadow: none;
      transform: none;
    }

    .mzmulti-btn-secondary {
      background: rgba(255, 255, 255, 0.08);
      color: #d9ebff;
      border: 1px solid rgba(154, 184, 215, 0.2);
    }

    .mzmulti-btn-secondary:hover:not(:disabled) {
      background: rgba(255, 255, 255, 0.12);
    }

    .mzmulti-btn-primary {
      background: linear-gradient(135deg, #36c16b, #66df8e);
      color: #082012;
      box-shadow: 0 0 16px rgba(54, 193, 107, 0.3);
      font-weight: 700;
    }

    .mzmulti-btn-primary:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 0 22px rgba(54, 193, 107, 0.42);
    }

    @media (max-width: 720px) {
      .mzmulti-panel {
        padding: 18px;
      }

      .mzmulti-header,
      .mzmulti-footer {
        flex-direction: column;
        align-items: stretch;
      }

      .mzmulti-counter,
      .mzmulti-actions {
        width: 100%;
      }

      .mzmulti-actions {
        justify-content: stretch;
      }

      .mzmulti-btn {
        flex: 1;
      }
    }
  `;

  let stylesInjected = false;
  let multiState = null;

  function injectStyles() {
    if (stylesInjected) return;
    const styleEl = document.createElement('style');
    styleEl.id = 'mzmulti-styles';
    styleEl.textContent = MULTI_CHOOSE_STYLES;
    document.head.appendChild(styleEl);
    stylesInjected = true;
  }

  function parseParam(param) {
    const parts = String(param || '').split('|');
    if (parts.length < 3) return null;
    return {
      min: parseInt(parts[0], 10),
      max: parseInt(parts[1], 10),
      specs: parts.slice(2).join('|')
    };
  }

  function parseSpecs(specString) {
    return String(specString || '')
      .split('&')
      .map(s => s.trim())
      .filter(Boolean)
      .map(spec => {
        const parts = spec.split(':');
        const rawZoneOrCard = parts[0].trim();
        const encodedParts = rawZoneOrCard.split('@');
        const zoneOrCard = encodedParts[0].trim();
        const actionPayload = encodedParts.length > 1 ? encodedParts[1].trim() : '';
        const selectionLabel = encodedParts.length > 2 ? encodedParts.slice(2).join('@').trim() : '';
        const filters = [];

        if (parts.length > 1) {
          const filterString = parts.slice(1).join(':');
          filterString.split(',').map(f => f.trim()).filter(Boolean).forEach(clause => {
            const match = clause.match(/^(\w+)(==|!=|<=|>=|=|<|>)(.*)$/);
            if (match) {
              filters.push({ field: match[1], op: match[2], value: match[3] });
            } else {
              filters.push({ field: clause, op: '=', value: 'true' });
            }
          });
        }

        const cardMatch = zoneOrCard.match(/^(.+)-(\d+)$/);
        if (cardMatch) {
          return {
            zone: cardMatch[1],
            specificIndex: parseInt(cardMatch[2], 10),
            isSpecificCard: true,
            filters: filters,
            originalSpec: spec,
            actionPayload: actionPayload,
            selectionLabel: selectionLabel
          };
        }

        return {
          zone: zoneOrCard,
          specificIndex: null,
          isSpecificCard: false,
          filters: filters,
          originalSpec: spec,
          actionPayload: actionPayload,
          selectionLabel: selectionLabel
        };
      });
  }

  function parseCardJson(cardArr) {
    if (!cardArr || cardArr.length < 3 || !cardArr[2] || cardArr[2] === '-') return {};
    try {
      return JSON.parse(cardArr[2]);
    } catch (err) {
      return {};
    }
  }

  function cardMatchesFilters(index, cardArr, filters) {
    if (!filters || filters.length === 0) return true;

    const cardData = parseCardJson(cardArr);
    for (let i = 0; i < filters.length; i++) {
      const filter = filters[i];
      const field = filter.field;
      const op = filter.op;
      const target = filter.value;
      let actual = null;

      if (field.toLowerCase() === 'index' || field.toLowerCase() === 'i') {
        actual = Number(index);
      } else if (Object.prototype.hasOwnProperty.call(cardData, field)) {
        actual = cardData[field];
      }

      if (actual === null || actual === undefined) return false;

      const numActual = Number(actual);
      const numTarget = Number(target);
      const numericCompare = !isNaN(numActual) && !isNaN(numTarget);

      switch (op) {
        case '=':
        case '==':
          if (numericCompare ? numActual != numTarget : String(actual) !== String(target)) return false;
          break;
        case '!=':
          if (numericCompare ? numActual == numTarget : String(actual) === String(target)) return false;
          break;
        case '<':
          if (!numericCompare || !(numActual < numTarget)) return false;
          break;
        case '>':
          if (!numericCompare || !(numActual > numTarget)) return false;
          break;
        case '<=':
          if (!numericCompare || !(numActual <= numTarget)) return false;
          break;
        case '>=':
          if (!numericCompare || !(numActual >= numTarget)) return false;
          break;
        default:
          return false;
      }
    }

    return true;
  }

  function zoneLabelForSpec(spec) {
    if (spec.selectionLabel) return spec.selectionLabel.replace(/_/g, ' ');
    return String(spec.zone || '').replace(/^(my|their)/, '');
  }

  function buildCandidate(spec, index, cardArr) {
    const cardNumber = cardArr[0] || '';
    const counters = cardArr.length > 1 ? cardArr[1] : '0';
    const mzID = spec.zone + '-' + index;
    const submittedValue = (!spec.isSpecificCard && spec.actionPayload) ? mzID : (spec.actionPayload ? spec.originalSpec : mzID);

    return {
      key: submittedValue,
      mzID: mzID,
      submittedValue: submittedValue,
      cardNumber: cardNumber,
      counters: counters,
      label: zoneLabelForSpec(spec),
      originalSpec: spec.originalSpec
    };
  }

  function expandCandidates(specs) {
    const candidates = [];
    const seen = new Set();

    for (let specIndex = 0; specIndex < specs.length; specIndex++) {
      const spec = specs[specIndex];
      const zoneDataVar = spec.zone + 'Data';
      const zoneDataStr = window[zoneDataVar];
      if (!zoneDataStr || typeof zoneDataStr !== 'string') continue;

      const cards = zoneDataStr.split('<|>').filter(s => s.trim());
      const indices = spec.isSpecificCard ? [spec.specificIndex] : cards.map((_, idx) => idx);

      for (let i = 0; i < indices.length; i++) {
        const cardIndex = indices[i];
        if (cardIndex < 0 || cardIndex >= cards.length) continue;
        const cardArr = cards[cardIndex].split(' ');
        if (!cardMatchesFilters(cardIndex, cardArr, spec.filters)) continue;

        const candidate = buildCandidate(spec, cardIndex, cardArr);
        if (seen.has(candidate.key)) continue;
        seen.add(candidate.key);
        candidates.push(candidate);
      }
    }

    return candidates;
  }

  function instructionText(min, max, total) {
    if (total <= 0) return 'No cards available.';
    if (min === max) return 'Select exactly ' + min + (min === 1 ? ' card.' : ' cards.');
    if (min === 0) return 'Select up to ' + max + (max === 1 ? ' card.' : ' cards.');
    return 'Select between ' + min + ' and ' + max + ' cards.';
  }

  function refreshUI() {
    if (!multiState) return;
    const selectedCount = multiState.selected.size;
    const atMax = selectedCount >= multiState.max;

    const counter = document.getElementById('mzmulti-counter');
    if (counter) {
      counter.textContent = selectedCount + ' selected / ' + multiState.max + ' max';
    }

    const confirm = document.getElementById('mzmulti-confirm');
    if (confirm) {
      confirm.disabled = selectedCount < multiState.min || selectedCount > multiState.max;
    }

    const selectAll = document.getElementById('mzmulti-select-all');
    if (selectAll) {
      selectAll.disabled = selectedCount >= multiState.candidates.length;
    }

    for (let i = 0; i < multiState.candidates.length; i++) {
      const candidate = multiState.candidates[i];
      const cardEl = document.getElementById('mzmulti-card-' + i);
      if (!cardEl) continue;
      const isSelected = multiState.selected.has(candidate.key);
      cardEl.classList.toggle('mzmulti-selected', isSelected);
      cardEl.classList.toggle('mzmulti-disabled', atMax && !isSelected);
      const checkEl = cardEl.querySelector('.mzmulti-check');
      if (checkEl) checkEl.textContent = isSelected ? '\u2713' : '';
    }
  }

  function toggleSelection(candidate) {
    if (!multiState) return;
    if (multiState.selected.has(candidate.key)) {
      multiState.selected.delete(candidate.key);
      refreshUI();
      return;
    }

    if (multiState.selected.size >= multiState.max) return;
    multiState.selected.add(candidate.key);
    refreshUI();
  }

  function createCard(candidate, index) {
    const cardEl = document.createElement('div');
    cardEl.className = 'mzmulti-card';
    cardEl.id = 'mzmulti-card-' + index;

    const check = document.createElement('div');
    check.className = 'mzmulti-check';
    cardEl.appendChild(check);

    const imageWrap = document.createElement('div');
    imageWrap.className = 'mzmulti-card-image';
    const folder = (window.rootPath || '.') + '/concat';
    const renderCardFn = (typeof window !== 'undefined' && typeof window.RenderCardHTML === 'function') ? window.RenderCardHTML : Card;
    imageWrap.innerHTML = renderCardFn(candidate.cardNumber, folder, 112, 0, 0, 0, 0, candidate.counters);
    cardEl.appendChild(imageWrap);

    const label = document.createElement('div');
    label.className = 'mzmulti-label';
    label.textContent = candidate.label;
    cardEl.appendChild(label);

    cardEl.addEventListener('click', function() {
      toggleSelection(candidate);
    });

    cardEl.addEventListener('mouseenter', function(e) {
      if (typeof ShowCardDetail === 'function') ShowCardDetail(e, cardEl);
    });
    cardEl.addEventListener('mouseleave', function() {
      if (typeof HideCardDetail === 'function') HideCardDetail();
    });

    return cardEl;
  }

  function serializeResult() {
    if (!multiState) return '-';
    const selected = multiState.candidates
      .filter(candidate => multiState.selected.has(candidate.key))
      .map(candidate => candidate.submittedValue);
    return selected.length > 0 ? selected.join('&') : '-';
  }

  function ShowMZMultiChooseUI(param, tooltip, decisionIndex, submitCallback) {
    HideMZMultiChooseUI();
    injectStyles();

    const parsed = parseParam(param);
    if (!parsed) {
      if (submitCallback) submitCallback('-', decisionIndex);
      return;
    }

    const candidates = expandCandidates(parseSpecs(parsed.specs));
    if (candidates.length === 0) {
      if (submitCallback) submitCallback('-', decisionIndex);
      return;
    }

    const max = Math.max(0, Math.min(isNaN(parsed.max) ? candidates.length : parsed.max, candidates.length));
    const min = Math.max(0, Math.min(isNaN(parsed.min) ? 0 : parsed.min, max));

    multiState = {
      min: min,
      max: max,
      candidates: candidates,
      selected: new Set(),
      callback: submitCallback,
      decisionIndex: decisionIndex
    };

    const overlay = document.createElement('div');
    overlay.className = 'mzmulti-overlay';
    overlay.id = 'mzmulti-overlay';

    const panel = document.createElement('div');
    panel.className = 'mzmulti-panel';

    const header = document.createElement('div');
    header.className = 'mzmulti-header';

    const titleWrap = document.createElement('div');
    titleWrap.className = 'mzmulti-title-wrap';

    const title = document.createElement('div');
    title.className = 'mzmulti-title';
    title.textContent = tooltip || 'Choose cards';
    titleWrap.appendChild(title);

    const subtitle = document.createElement('div');
    subtitle.className = 'mzmulti-subtitle';
    subtitle.textContent = instructionText(min, max, candidates.length);
    titleWrap.appendChild(subtitle);
    header.appendChild(titleWrap);

    const counter = document.createElement('div');
    counter.className = 'mzmulti-counter';
    counter.id = 'mzmulti-counter';
    header.appendChild(counter);
    panel.appendChild(header);

    const grid = document.createElement('div');
    grid.className = 'mzmulti-grid';
    for (let i = 0; i < candidates.length; i++) {
      grid.appendChild(createCard(candidates[i], i));
    }
    panel.appendChild(grid);

    const footer = document.createElement('div');
    footer.className = 'mzmulti-footer';

    const guidance = document.createElement('div');
    guidance.className = 'mzmulti-guidance';
    guidance.textContent = min === 0 ? 'Confirm with nothing selected to skip.' : 'Selected cards are highlighted in green.';
    footer.appendChild(guidance);

    const actions = document.createElement('div');
    actions.className = 'mzmulti-actions';

    const clearBtn = document.createElement('button');
    clearBtn.className = 'mzmulti-btn mzmulti-btn-secondary';
    clearBtn.textContent = 'Clear';
    clearBtn.addEventListener('click', function() {
      if (!multiState) return;
      multiState.selected.clear();
      refreshUI();
    });
    actions.appendChild(clearBtn);

    if (max === candidates.length) {
      const selectAllBtn = document.createElement('button');
      selectAllBtn.className = 'mzmulti-btn mzmulti-btn-secondary';
      selectAllBtn.id = 'mzmulti-select-all';
      selectAllBtn.textContent = 'Select All';
      selectAllBtn.addEventListener('click', function() {
        if (!multiState) return;
        multiState.selected = new Set(multiState.candidates.map(candidate => candidate.key));
        refreshUI();
      });
      actions.appendChild(selectAllBtn);
    }

    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'mzmulti-btn mzmulti-btn-primary';
    confirmBtn.id = 'mzmulti-confirm';
    confirmBtn.textContent = 'Confirm';
    confirmBtn.addEventListener('click', function() {
      if (!multiState) return;
      const result = serializeResult();
      const callback = multiState.callback;
      const idx = multiState.decisionIndex;
      HideMZMultiChooseUI();
      if (callback) callback(result, idx);
    });
    actions.appendChild(confirmBtn);
    footer.appendChild(actions);

    panel.appendChild(footer);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);

    refreshUI();
  }

  function HideMZMultiChooseUI() {
    const overlay = document.getElementById('mzmulti-overlay');
    if (overlay) overlay.remove();
    if (typeof HideCardDetail === 'function') HideCardDetail();
    multiState = null;
  }

  window.ShowMZMultiChooseUI = ShowMZMultiChooseUI;
  window.HideMZMultiChooseUI = HideMZMultiChooseUI;
})();