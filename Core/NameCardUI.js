(function() {
  'use strict';

  var overlayEl = null;
  var cardNamesCache = null;
  var nameToCardIdCache = null;
  var activeSuggestionIndex = -1;

  function normalizeName(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getAllCardNames() {
    if (Array.isArray(cardNamesCache)) return cardNamesCache;
    var source = (typeof nameData !== 'undefined' && nameData) ? Object.values(nameData) : [];
    var seen = new Set();
    cardNamesCache = source
      .map(function(name) { return String(name || '').trim(); })
      .filter(function(name) {
        if (!name) return false;
        var key = normalizeName(name);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
      })
      .sort(function(a, b) { return a.localeCompare(b); });
    return cardNamesCache;
  }

  function getRepresentativeCardIdForName(cardName) {
    if (!nameToCardIdCache) {
      nameToCardIdCache = {};
      if (typeof nameData !== 'undefined' && nameData) {
        Object.keys(nameData).forEach(function(cardId) {
          var rawName = String(nameData[cardId] || '').trim();
          var key = normalizeName(rawName);
          if (!key || nameToCardIdCache[key]) return;
          nameToCardIdCache[key] = cardId;
        });
      }
    }
    return nameToCardIdCache[normalizeName(cardName)] || null;
  }

  function findMatchingCardNames(query, limit) {
    var normalizedQuery = normalizeName(query);
    if (!normalizedQuery) return [];
    var maxResults = typeof limit === 'number' && limit > 0 ? limit : 12;
    return getAllCardNames().filter(function(name) {
      return normalizeName(name).indexOf(normalizedQuery) !== -1;
    }).slice(0, maxResults);
  }

  function resolveCardIdFromInput(rawValue) {
    var value = String(rawValue || '').trim();
    if (!value) return null;
    if (typeof nameData !== 'undefined' && nameData && Object.prototype.hasOwnProperty.call(nameData, value)) {
      return value;
    }

    var exactMatch = getRepresentativeCardIdForName(value);
    if (exactMatch) return exactMatch;

    var matches = findMatchingCardNames(value, 2);
    if (matches.length === 1) {
      return getRepresentativeCardIdForName(matches[0]);
    }

    return null;
  }

  function getAssetFolder() {
    var folderPathEl = document.getElementById('folderPath');
    var folderPath = folderPathEl ? folderPathEl.value : '';
    if (typeof AssetReflectionPath === 'function') {
      var reflected = AssetReflectionPath();
      if (reflected) return reflected;
    }
    return folderPath || 'GrandArchiveSim';
  }

  function getCardImageUrl(cardId) {
    if (!cardId) return '';
    return './' + getAssetFolder() + '/WebpImages/' + encodeURIComponent(cardId) + '.webp';
  }

  function showHoverPreview(event, imageUrl) {
    if (!imageUrl || typeof ShowDetail !== 'function') return;
    ShowDetail(event, imageUrl);
  }

  function parsePreviewParam(previewParam) {
    if (!previewParam || previewParam === '-') return { label: 'Reference cards', specs: [] };
    var raw = String(previewParam);
    var label = 'Reference cards';
    var specsPart = raw;
    var separatorIndex = raw.indexOf('||');
    if (separatorIndex >= 0) {
      var customLabel = raw.slice(0, separatorIndex).trim();
      specsPart = raw.slice(separatorIndex + 2);
      if (customLabel) label = customLabel.replace(/_/g, ' ');
    }
    var specs = specsPart.split('&').map(function(part) { return part.trim(); }).filter(Boolean);
    return { label: label, specs: specs };
  }

  function buildPreviewCards(previewParam) {
    var parsed = parsePreviewParam(previewParam);
    var previewCards = [];
    for (var i = 0; i < parsed.specs.length; ++i) {
      var directCardId = parsed.specs[i];
      if (typeof Cardname === 'function' && Cardname(directCardId)) {
        previewCards.push({ cardId: directCardId, spec: parsed.specs[i], cardEntry: null });
        continue;
      }
      var match = /^(.+)-(\d+)$/.exec(parsed.specs[i]);
      if (!match) continue;
      var zoneName = match[1];
      var cardIndex = parseInt(match[2], 10);
      if (Number.isNaN(cardIndex)) continue;
      var zoneDataStr = window[zoneName + 'Data'];
      if (!zoneDataStr || typeof zoneDataStr !== 'string') continue;
      var zoneCards = zoneDataStr.split('<|>').filter(function(entry) { return entry.trim(); });
      if (cardIndex < 0 || cardIndex >= zoneCards.length) continue;
      previewCards.push({ zoneName: zoneName, cardIndex: cardIndex, spec: parsed.specs[i], cardEntry: zoneCards[cardIndex] });
    }
    return { label: parsed.label, cards: previewCards };
  }

  function applyPreviewCardSelection(inputEl, cardId) {
    if (!inputEl || !cardId || typeof Cardname !== 'function') return;
    var resolvedName = Cardname(cardId) || cardId;
    inputEl.value = resolvedName;
    hideSuggestions();
    inputEl.focus();
  }

  function renderPreviewCard(cardInfo, inputEl) {
    var cardId = cardInfo.cardId || '';
    if (!cardId) {
      var cardArr = String(cardInfo.cardEntry || '').split(' ');
      if (cardArr.length === 0) return null;
      var sharedCardData = {};
      if (cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
        try { sharedCardData = JSON.parse(cardArr[2]); } catch (e) {}
      }
      cardId = sharedCardData.CardID || cardArr[0] || '';
    }
    if (!cardId) return null;
    var wrapper = document.createElement('div');
    wrapper.style.flex = '0 0 auto';
    wrapper.style.display = 'flex';
    wrapper.style.flexDirection = 'column';
    wrapper.style.alignItems = 'center';
    wrapper.style.gap = '6px';
    wrapper.style.minWidth = '96px';

    var imageWrap = document.createElement('div');
    imageWrap.style.position = 'relative';
    imageWrap.style.cursor = 'zoom-in';
    imageWrap.style.transition = 'transform 140ms ease, box-shadow 180ms ease';
    imageWrap.style.borderRadius = '10px';
    imageWrap.style.overflow = 'hidden';
    imageWrap.style.boxShadow = '0 10px 24px rgba(0, 0, 0, 0.34)';
    var renderCardFn = (typeof window !== 'undefined' && typeof window.RenderCardHTML === 'function')
      ? window.RenderCardHTML
      : null;
    if (renderCardFn && cardId) {
      imageWrap.innerHTML = renderCardFn(cardId, './' + getAssetFolder() + '/concat', 134, 0, 0, 0, 0, 0);
      var img = imageWrap.querySelector('img');
      if (img) {
        img.alt = 'Reference card ' + cardId;
        img.loading = 'lazy';
      }
    } else {
      var img = document.createElement('img');
      img.src = getCardImageUrl(cardId);
      img.alt = cardId ? ('Reference card ' + cardId) : 'Reference card';
      img.loading = 'lazy';
      img.style.height = '134px';
      img.style.width = '95px';
      img.style.borderRadius = '10px';
      img.style.objectFit = 'cover';
      imageWrap.appendChild(img);
    }
    imageWrap.onmouseenter = function(event) {
      imageWrap.style.transform = 'translateY(-2px) scale(1.03)';
      imageWrap.style.boxShadow = '0 0 0 1px rgba(219, 188, 99, 0.3)';
      showHoverPreview(event, getCardImageUrl(cardId));
    };
    imageWrap.onmouseleave = function() {
      imageWrap.style.transform = 'none';
      imageWrap.style.boxShadow = 'none';
      if (typeof HideCardDetail === 'function') HideCardDetail();
    };
    var handlePreviewClick = function(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      applyPreviewCardSelection(inputEl, cardId);
    };
    imageWrap.addEventListener('click', handlePreviewClick, true);
    wrapper.appendChild(imageWrap);

    var label = document.createElement('div');
    label.textContent = cardId && typeof Cardname === 'function'
      ? (Cardname(cardId) || cardId)
      : '';
    label.style.maxWidth = '110px';
    label.style.fontSize = '11px';
    label.style.lineHeight = '1.3';
    label.style.textAlign = 'center';
    label.style.color = '#dce8ff';
    label.style.cursor = 'pointer';
    label.onclick = handlePreviewClick;
    wrapper.appendChild(label);

    wrapper.addEventListener('click', handlePreviewClick, true);

    return wrapper;
  }

  function hideSuggestions() {
    if (!overlayEl) return;
    var list = overlayEl.querySelector('#namecard-suggestions');
    if (list) list.innerHTML = '';
    activeSuggestionIndex = -1;
  }

  function updateSuggestions(inputEl) {
    if (!overlayEl || !inputEl) return [];
    var query = normalizeName(inputEl.value);
    var list = overlayEl.querySelector('#namecard-suggestions');
    if (!list) return [];
    list.innerHTML = '';
    activeSuggestionIndex = -1;
    if (!query) return [];

    var matches = findMatchingCardNames(query, 12);

    matches.forEach(function(name, index) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = name;
      btn.dataset.index = String(index);
      btn.style.display = 'block';
      btn.style.width = '100%';
      btn.style.padding = '10px 12px';
      btn.style.border = '1px solid rgba(150, 183, 235, 0.18)';
      btn.style.borderRadius = '10px';
      btn.style.background = 'rgba(11, 24, 44, 0.84)';
      btn.style.color = '#eef4ff';
      btn.style.textAlign = 'left';
      btn.style.cursor = 'pointer';
      btn.onmouseenter = function(event) {
        activeSuggestionIndex = index;
        refreshActiveSuggestion(list);
        var previewCardId = getRepresentativeCardIdForName(name);
        if (previewCardId) showHoverPreview(event, getCardImageUrl(previewCardId));
      };
      btn.onmouseleave = function() {
        if (typeof HideCardDetail === 'function') HideCardDetail();
      };
      btn.onclick = function() {
        inputEl.value = name;
        hideSuggestions();
        inputEl.focus();
      };
      list.appendChild(btn);
    });
    return matches;
  }

  function refreshActiveSuggestion(list) {
    if (!list) return;
    var children = Array.prototype.slice.call(list.children || []);
    children.forEach(function(child, index) {
      child.style.background = index === activeSuggestionIndex ? 'rgba(34, 62, 104, 0.98)' : 'rgba(11, 24, 44, 0.84)';
      child.style.borderColor = index === activeSuggestionIndex ? 'rgba(219, 188, 99, 0.62)' : 'rgba(150, 183, 235, 0.18)';
    });
  }

  function submitName(inputEl, submitCallback, decisionIndex) {
    var chosenName = String(inputEl && inputEl.value ? inputEl.value : '').trim();
    if (!chosenName) return;
    HideNameCardUI();
    submitCallback(chosenName, decisionIndex);
  }

  function injectStyles() {
    if (document.getElementById('namecard-ui-style')) return;
    var style = document.createElement('style');
    style.id = 'namecard-ui-style';
    style.textContent = [
      '#cardDetail { z-index: 100030 !important; }',
      '#namecard-overlay { position: fixed; inset: 0; z-index: 100020; display: flex; align-items: center; justify-content: center; background: rgba(4, 10, 24, 0.72); backdrop-filter: blur(10px); }',
      '#namecard-modal { width: min(92vw, 960px); max-height: min(88vh, 820px); overflow: hidden; border-radius: 22px; border: 1px solid rgba(201, 168, 76, 0.38); background: linear-gradient(180deg, rgba(12, 24, 43, 0.98) 0%, rgba(8, 16, 30, 0.98) 100%); box-shadow: 0 28px 70px rgba(0, 0, 0, 0.48); color: #eef4ff; display: flex; flex-direction: column; }',
      '#namecard-preview { display: flex; gap: 12px; overflow-x: auto; padding: 4px 2px 2px; }',
      '#namecard-preview::-webkit-scrollbar { height: 10px; }',
      '#namecard-preview::-webkit-scrollbar-thumb { background: rgba(201, 168, 76, 0.45); border-radius: 999px; }',
      '#namecard-suggestions { display: grid; gap: 8px; max-height: 220px; overflow-y: auto; padding-right: 2px; }',
      '@media (max-width: 700px) { #namecard-modal { width: 96vw; border-radius: 16px; } }'
    ].join('\n');
    document.head.appendChild(style);
  }

  function ShowNameCardUI(previewParam, tooltip, decisionIndex, submitCallback) {
    HideNameCardUI();
    injectStyles();

    overlayEl = document.createElement('div');
    overlayEl.id = 'namecard-overlay';

    var modal = document.createElement('div');
    modal.id = 'namecard-modal';

    var body = document.createElement('div');
    body.style.padding = '22px';
    body.style.display = 'flex';
    body.style.flexDirection = 'column';
    body.style.gap = '16px';
    body.style.overflowY = 'auto';

    var title = document.createElement('div');
    title.textContent = tooltip || 'Choose a card name';
    title.style.fontSize = '22px';
    title.style.fontWeight = '700';
    title.style.letterSpacing = '0.02em';
    body.appendChild(title);

    var subtitle = document.createElement('div');
    subtitle.textContent = 'Type any card name. Matching suggestions appear as you type.';
    subtitle.style.fontSize = '14px';
    subtitle.style.color = 'rgba(220, 232, 255, 0.78)';
    body.appendChild(subtitle);

    var input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Start typing a card name';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.style.width = '100%';
    input.style.boxSizing = 'border-box';
    input.style.padding = '14px 16px';
    input.style.borderRadius = '14px';
    input.style.border = '1px solid rgba(201, 168, 76, 0.36)';
    input.style.background = 'rgba(7, 16, 30, 0.96)';
    input.style.color = '#f8fbff';
    input.style.fontSize = '16px';
    input.style.outline = 'none';

    var previewData = buildPreviewCards(previewParam);
    if (previewData.cards.length > 0) {
      var previewLabel = document.createElement('div');
      previewLabel.textContent = previewData.label;
      previewLabel.style.fontSize = '13px';
      previewLabel.style.fontWeight = '600';
      previewLabel.style.letterSpacing = '0.08em';
      previewLabel.style.textTransform = 'uppercase';
      previewLabel.style.color = 'rgba(201, 168, 76, 0.92)';
      body.appendChild(previewLabel);

      var previewWrap = document.createElement('div');
      previewWrap.id = 'namecard-preview';
      previewData.cards.forEach(function(cardInfo) {
        var node = renderPreviewCard(cardInfo, input);
        if (node) previewWrap.appendChild(node);
      });
      body.appendChild(previewWrap);
    }

    body.appendChild(input);

    var suggestions = document.createElement('div');
    suggestions.id = 'namecard-suggestions';
    body.appendChild(suggestions);

    var footer = document.createElement('div');
    footer.style.display = 'flex';
    footer.style.justifyContent = 'flex-end';
    footer.style.gap = '10px';

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.style.padding = '10px 16px';
    cancelBtn.style.borderRadius = '12px';
    cancelBtn.style.border = '1px solid rgba(153, 175, 214, 0.25)';
    cancelBtn.style.background = 'rgba(14, 28, 52, 0.94)';
    cancelBtn.style.color = '#d8e5ff';
    cancelBtn.style.cursor = 'pointer';
    cancelBtn.onclick = function() { HideNameCardUI(); };
    footer.appendChild(cancelBtn);

    var confirmBtn = document.createElement('button');
    confirmBtn.type = 'button';
    confirmBtn.textContent = 'Confirm';
    confirmBtn.style.padding = '10px 18px';
    confirmBtn.style.borderRadius = '12px';
    confirmBtn.style.border = '1px solid rgba(219, 188, 99, 0.35)';
    confirmBtn.style.background = 'linear-gradient(135deg, rgba(205, 172, 83, 0.95), rgba(160, 126, 44, 0.95))';
    confirmBtn.style.color = '#0f1d33';
    confirmBtn.style.fontWeight = '700';
    confirmBtn.style.cursor = 'pointer';
    confirmBtn.onclick = function() { submitName(input, submitCallback, decisionIndex); };
    footer.appendChild(confirmBtn);

    body.appendChild(footer);
    modal.appendChild(body);
    overlayEl.appendChild(modal);
    document.body.appendChild(overlayEl);

    input.addEventListener('input', function() {
      updateSuggestions(input);
    });
    input.addEventListener('keydown', function(event) {
      var items = Array.prototype.slice.call((overlayEl.querySelector('#namecard-suggestions') || {}).children || []);
      if (event.key === 'ArrowDown' && items.length > 0) {
        event.preventDefault();
        activeSuggestionIndex = Math.min(activeSuggestionIndex + 1, items.length - 1);
        refreshActiveSuggestion(overlayEl.querySelector('#namecard-suggestions'));
        return;
      }
      if (event.key === 'ArrowUp' && items.length > 0) {
        event.preventDefault();
        activeSuggestionIndex = Math.max(activeSuggestionIndex - 1, 0);
        refreshActiveSuggestion(overlayEl.querySelector('#namecard-suggestions'));
        return;
      }
      if (event.key === 'Enter') {
        event.preventDefault();
        if (activeSuggestionIndex >= 0 && activeSuggestionIndex < items.length) {
          items[activeSuggestionIndex].click();
          return;
        }
        submitName(input, submitCallback, decisionIndex);
        return;
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        HideNameCardUI();
      }
    });

    overlayEl.addEventListener('click', function(event) {
      if (event.target === overlayEl) HideNameCardUI();
    });

    setTimeout(function() { input.focus(); }, 0);
  }

  function HideNameCardUI() {
    if (overlayEl) {
      overlayEl.remove();
      overlayEl = null;
    }
    activeSuggestionIndex = -1;
  }

  window.ShowNameCardUI = ShowNameCardUI;
  window.HideNameCardUI = HideNameCardUI;
  window.NameCardLookup = {
    normalizeName: normalizeName,
    getAllCardNames: getAllCardNames,
    getRepresentativeCardIdForName: getRepresentativeCardIdForName,
    findMatchingCardNames: findMatchingCardNames,
    resolveCardIdFromInput: resolveCardIdFromInput
  };
})();
