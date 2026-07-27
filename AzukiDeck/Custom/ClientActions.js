function ClientWidgetActions() {
  return false;
}

function AzukiDeckCardElement(cardID) {
  if (typeof window.Cardelement !== 'function') return '';
  var element = window.Cardelement(cardID);
  return element == null ? '' : String(element).trim().toLowerCase();
}

function AzukiDeckLeaderCardID() {
  if (!window.myLeaderData) return '';
  return String(window.myLeaderData).split('<|>')[0].split(' ')[0];
}

function AzukiDeckGateCardID() {
  if (!window.myGateData) return '';
  return String(window.myGateData).split('<|>')[0].split(' ')[0];
}

// UILibraries treats true as "filter this card out". A card is legal when it is
// neutral or shares the selected leader's elemental identity. Until a leader is
// selected, leave the library unfiltered so the deck builder remains usable.
function InLegalFilter(cardID) {
  var leaderID = AzukiDeckLeaderCardID();
  if (!leaderID) return false;

  var cardElement = AzukiDeckCardElement(cardID);
  if (!cardElement || cardElement === 'neutral') return false;

  var leaderElement = AzukiDeckCardElement(leaderID);
  if (!leaderElement) return false;

  return cardElement !== leaderElement;
}

window.InLegalFilter = InLegalFilter;

function showDeleteAutoVersionConfirm(versionID, versionNumber) {
  var promptText = 'Delete Version ' + versionNumber + '? Its children will be reparented and its aggregate stats will be deleted.';
  StyledConfirm(promptText, {
    title: 'Delete version',
    danger: true,
    confirmLabel: 'Delete'
  }).then(function(confirmed) {
    if (!confirmed) return;
    var gameNameInput = document.getElementById('gameName');
    var deckID = gameNameInput ? gameNameInput.value : '';
    var url = '/TCGEngine/AzukiDeck/DeleteVersion.php?deckID='
      + encodeURIComponent(deckID)
      + '&versionID='
      + encodeURIComponent(versionID);
    fetch(url, { credentials: 'same-origin' })
      .then(function(response) {
        return response.json().catch(function() { return {}; }).then(function(payload) {
          if (!response.ok || !payload.success) {
            throw new Error(payload.error || 'The version could not be deleted.');
          }
        });
      })
      .then(function() { window.location.reload(); })
      .catch(function(error) {
        if (typeof showFlashMessage === 'function') showFlashMessage(error.message, 6000);
        else StyledAlert(error.message);
      });
  });
}

window.showDeleteAutoVersionConfirm = showDeleteAutoVersionConfirm;

function AzukiAutoVersionCardLabel(cardID) {
  if (typeof window.Cardname === 'function') {
    var name = window.Cardname(cardID);
    if (name) return String(name);
  }
  return String(cardID || '');
}

function AzukiAutoVersionDeltaText(version) {
  if (version.parentVersionID === null) return 'Root configuration';
  var labels = [];
  var delta = version.delta || {};
  var identities = delta.identities || {};
  Object.keys(identities).forEach(function(slot) {
    var change = identities[slot] || {};
    labels.push(
      slot.charAt(0).toUpperCase() + slot.slice(1)
      + ': ' + AzukiAutoVersionCardLabel(change.from)
      + ' \u2192 ' + AzukiAutoVersionCardLabel(change.to)
    );
  });
  var mainDeck = delta.zones && delta.zones.mainDeck ? delta.zones.mainDeck : {};
  Object.keys(mainDeck.added || {}).forEach(function(cardID) {
    labels.push('+' + mainDeck.added[cardID] + ' ' + AzukiAutoVersionCardLabel(cardID));
  });
  Object.keys(mainDeck.removed || {}).forEach(function(cardID) {
    labels.push('\u2212' + mainDeck.removed[cardID] + ' ' + AzukiAutoVersionCardLabel(cardID));
  });
  var editLabel = version.distance + ' edit' + (version.distance === 1 ? '' : 's');
  return editLabel + (labels.length ? ' \u00b7 ' + labels.join(', ') : '');
}

function RenderAzukiAutoVersions(versions) {
  var menu = document.getElementById('versionDropdownMenu');
  if (!menu) return;
  menu.innerHTML = '';

  var current = document.createElement('div');
  current.className = 'azuki-auto-version-current';
  current.textContent = 'Current Version';
  current.style.cssText = 'padding:7px 12px;cursor:pointer;font-size:13px;color:#fff;white-space:nowrap;';
  current.onmouseover = function() { this.style.background = '#3a3a3a'; };
  current.onmouseout = function() { this.style.background = ''; };
  current.onclick = function() { selectVersion('current', 'Current Version'); };
  menu.appendChild(current);

  if (!versions.length) {
    var empty = document.createElement('div');
    empty.textContent = 'The first version will be created when this deck records a completed game.';
    empty.style.cssText = 'padding:9px 12px;color:#aaa;font-size:12px;white-space:normal;';
    menu.appendChild(empty);
    return;
  }

  versions.forEach(function(version) {
    var label = version.versionName || ('Version ' + version.versionNumber);
    var depth = Math.max(0, Number(version.depth) || 0);
    var row = document.createElement('div');
    row.className = 'azuki-auto-version-row';
    row.setAttribute('data-version-id', String(version.versionID));
    row.style.cssText = 'padding:7px 12px 7px '
      + (12 + depth * 18)
      + 'px;cursor:default;font-size:13px;color:#fff;display:flex;justify-content:space-between;'
      + 'align-items:center;gap:12px;border-top:1px solid rgba(255,255,255,0.06);';
    row.onmouseover = function() { this.style.background = '#3a3a3a'; };
    row.onmouseout = function() { this.style.background = ''; };

    var copy = document.createElement('span');
    copy.style.cssText = 'flex:1;min-width:0;';
    var heading = document.createElement('span');
    heading.style.cssText = 'display:flex;justify-content:space-between;gap:10px;';
    var name = document.createElement('strong');
    name.className = 'azuki-auto-version-name';
    name.textContent = (depth > 0 ? '\u21b3 ' : '') + label;
    var record = document.createElement('span');
    record.className = 'azuki-auto-version-record';
    record.style.cssText = 'white-space:nowrap;';
    record.textContent = version.wins + ' W \u00b7 ' + version.losses + ' L';
    heading.appendChild(name);
    heading.appendChild(record);
    var delta = document.createElement('span');
    delta.className = 'azuki-auto-version-delta';
    delta.style.cssText = 'display:block;margin-top:3px;font-size:11px;white-space:normal;';
    delta.textContent = AzukiAutoVersionDeltaText(version);
    copy.appendChild(heading);
    copy.appendChild(delta);

    var actions = document.createElement('span');
    actions.className = 'azuki-auto-version-actions';
    actions.style.cssText = 'display:inline-flex;align-items:center;gap:12px;flex-shrink:0;';
    var load = document.createElement('span');
    load.textContent = 'Load';
    load.style.cssText = 'padding:1px 5px;border-radius:3px;background:#1a73e8;color:#fff;'
      + 'font-size:9px;cursor:pointer;line-height:14px;white-space:nowrap;';
    load.onclick = function(event) {
      event.stopPropagation();
      selectVersion('auto:' + version.versionID, label);
    };
    var remove = document.createElement('span');
    remove.textContent = '\u2715';
    remove.style.cssText = 'width:15px;height:15px;border-radius:50%;background:#c0392b;color:#fff;'
      + 'font-size:9px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;line-height:1;';
    remove.onclick = function(event) {
      event.stopPropagation();
      closeVersionDropdown();
      showDeleteAutoVersionConfirm(version.versionID, version.versionNumber);
    };
    actions.appendChild(load);
    actions.appendChild(remove);
    row.appendChild(copy);
    row.appendChild(actions);
    menu.appendChild(row);
  });
}

function InitializeAzukiAutoVersions() {
  var wrapper = document.getElementById('versionDropdownWrapper');
  var gameNameInput = document.getElementById('gameName');
  if (!wrapper || !gameNameInput) return;
  wrapper.setAttribute('data-auto-versioning', '1');

  var menu = document.getElementById('versionDropdownMenu');
  if (menu) {
    menu.innerHTML = '';
    var loading = document.createElement('div');
    loading.textContent = 'Loading version history\u2026';
    loading.style.cssText = 'padding:9px 12px;color:#aaa;font-size:12px;';
    menu.appendChild(loading);
  }

  fetch(
    '/TCGEngine/AzukiDeck/GetVersions.php?deckID=' + encodeURIComponent(gameNameInput.value),
    { credentials: 'same-origin' }
  )
    .then(function(response) {
      return response.json().catch(function() { return {}; }).then(function(payload) {
        if (!response.ok || !payload.success) throw new Error(payload.error || 'Version history is unavailable.');
        return payload.versions || [];
      });
    })
    .then(RenderAzukiAutoVersions)
    .catch(function(error) {
      if (!menu) return;
      menu.innerHTML = '';
      var message = document.createElement('div');
      message.textContent = error.message;
      message.style.cssText = 'padding:9px 12px;color:#aaa;font-size:12px;white-space:normal;';
      menu.appendChild(message);
    });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', InitializeAzukiAutoVersions);
} else {
  InitializeAzukiAutoVersions();
}

function AzukiCardPlayWinRateTurnGraph(cardID) {
  var row = window.AzukiDeckCardStats && window.AzukiDeckCardStats[cardID];
  if (!row || !row.playWinRateDeltaByTurn || !row.playsByTurn) return -1;

  var labels = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'];
  var hasData = false;
  var deltaSeries = labels.map(function(label) {
    var rawDelta = row.playWinRateDeltaByTurn[label];
    var sampleSize = Number(row.playsByTurn[label]) || 0;
    var value = typeof rawDelta === 'number' && rawDelta >= -1 && sampleSize > 0
      ? rawDelta
      : null;
    if (value !== null) hasData = true;
    return {
      label: label,
      value: value,
      sampleSize: sampleSize
    };
  });
  if (!hasData) return -1;

  return {
    value: typeof row.playWinRate === 'number' ? row.playWinRate : -1,
    deltaSeries: deltaSeries,
    graphTitle: ''
  };
}

window.AzukiCardPlayWinRateTurnGraph = AzukiCardPlayWinRateTurnGraph;

function AzukiDeckMainDeckCardIDs() {
  if (!window.myMainDeckData) return [];
  return String(window.myMainDeckData)
    .split('<|>')
    .map(function(entry) { return entry.split(' ')[0]; })
    .filter(function(cardID) { return cardID !== ''; });
}

function AzukiDeckHypergeometricAtLeast(minimumSuccesses, sampleSize, populationSuccesses, populationSize) {
  if (populationSize < 0 || populationSuccesses < 0 || populationSuccesses > populationSize) return -1;
  if (sampleSize < 0 || sampleSize > populationSize || minimumSuccesses < 0) return -1;

  function combination(n, k) {
    if (k < 0 || k > n) return 0;
    k = Math.min(k, n - k);
    var result = 1;
    for (var i = 1; i <= k; i++) result = result * (n - k + i) / i;
    return result;
  }

  var total = combination(populationSize, sampleSize);
  if (!total) return -1;

  var probability = 0;
  var maximumSuccesses = Math.min(sampleSize, populationSuccesses);
  for (var successes = minimumSuccesses; successes <= maximumSuccesses; successes++) {
    probability += combination(populationSuccesses, successes)
      * combination(populationSize - populationSuccesses, sampleSize - successes)
      / total;
  }
  return probability;
}

function AzukiDeckCardHasSubtype(cardID, subtype) {
  if (typeof window.Cardsubtypes !== 'function') return false;
  var subtypes = window.Cardsubtypes(cardID);
  if (Array.isArray(subtypes)) return subtypes.includes(subtype);
  return String(subtypes == null ? '' : subtypes).split(',').map(function(value) {
    return value.trim();
  }).includes(subtype);
}

function AzukiDeckCardIsElement(cardID, element) {
  return typeof window.Cardelement === 'function'
    && String(window.Cardelement(cardID)).toLowerCase() === element.toLowerCase();
}

function AzukiDeckCardIsCategory(cardID, category) {
  return typeof window.Cardcategory === 'function'
    && String(window.Cardcategory(cardID)).toLowerCase() === category.toLowerCase();
}

function AzukiDeckCardCostAtMost(cardID, maximumCost) {
  return typeof window.CardikzCost === 'function'
    && Number(window.CardikzCost(cardID)) <= maximumCost;
}

function AzukiDeckCardIsZeroCostSpell(cardID) {
  return AzukiDeckCardIsCategory(cardID, 'Spell')
    && typeof window.CardikzCost === 'function'
    && Number(window.CardikzCost(cardID)) === 0;
}

function AzukiDeckCardIsTwoCostSpell(cardID) {
  return AzukiDeckCardIsCategory(cardID, 'Spell')
    && typeof window.CardikzCost === 'function'
    && Number(window.CardikzCost(cardID)) === 2;
}

function AzukiDeckHypergeoResult(value, explanation, requiredCardID) {
  return {
    value: value,
    explanation: explanation,
    requiredCardID: requiredCardID || ''
  };
}

function AzukiDeckTopCardsHitRate(deck, sourceCardID, sampleSize, predicate) {
  var populationSize = deck.length - 1;
  var populationSuccesses = deck.reduce(function(total, deckCardID) {
    return total + (predicate(deckCardID) ? 1 : 0);
  }, 0);

  // These effects resolve after their source has left the deck. If the source itself
  // matches its search predicate, remove it from both the population and hit count.
  if (predicate(sourceCardID)) populationSuccesses--;
  return AzukiDeckHypergeometricAtLeast(1, sampleSize, populationSuccesses, populationSize);
}

function HyperGeo(cardID) {
  var deck = AzukiDeckMainDeckCardIDs();
  if (!deck.length) return -1;

  switch (cardID) {
    case 'S1-AZK01-068_Pip_E_C_die':
      if (AzukiDeckGateCardID() !== 'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die') return -1;
      return AzukiDeckHypergeoResult(
        AzukiDeckTopCardsHitRate(deck, cardID, 6, AzukiDeckCardIsZeroCostSpell),
        'Given that your opening hand contains Pip, this is the chance that at least one of the other 6 cards is a 0-cost spell.',
        'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die'
      );
    case 'S1-STT01-007_Alley-Guy_E_C_die':
      if (AzukiDeckGateCardID() !== 'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die') return -1;
      return AzukiDeckHypergeoResult(
        AzukiDeckTopCardsHitRate(deck, cardID, 7, AzukiDeckCardIsZeroCostSpell),
        'Given that you draw Alley Guy by turn 2, this is the chance that at least one of the other 6 cards in your opening hand or your turn-2 draw is a 0-cost spell.',
        'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die'
      );
    case 'S1-AZK01-026_Moonlit-Crane_E_C_die':
      if (AzukiDeckGateCardID() !== 'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die') return -1;
      return AzukiDeckHypergeoResult(
        AzukiDeckTopCardsHitRate(deck, cardID, 10, AzukiDeckCardIsTwoCostSpell),
        'Given that you draw Moonlit Crane, this is the chance that at least one of the other 6 cards in your opening hand or your next 4 draws by turn 5 is a 2-cost spell.',
        'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die'
      );
    case 'S1-AZK01-003_Black-Jade-Courier_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return candidateID !== cardID && AzukiDeckCardHasSubtype(candidateID, 'Black Jade');
      });
    case 'S1-AZK01-021_Mizuto_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Driftward');
      });
    case 'S1-AZK01-031_Tidal-Insight_S_UC_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 3, function(candidateID) {
        return AzukiDeckCardIsElement(candidateID, 'Water');
      });
    case 'S1-AZK01-033_Elder-Hoshin_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Steelborn');
      });
    case 'S1-AZK01-045_Treetop-Scout_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Obsidian');
      });
    case 'S1-AZK01-056_Glass-Blower-Hokuto_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Scorchweaver');
      });
    case 'S1-AZK01-069_Link_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Beanz');
      });
    case 'S1-AZK01-092_Lotus-of-Reflection_S_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardIsElement(candidateID, 'Water')
          && AzukiDeckCardCostAtMost(candidateID, 2);
      });
    case 'S1-AZK01-097_Black-Jade-Pawnbroker_E_C_die':
    case 'S1-STT01-004_Black-Jade-Recruit_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardIsCategory(candidateID, 'Weapon');
      });
    case 'S1-STT02-003_Hayabusa-Itto_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Watercrafting');
      });
    case 'S1-STT02-013_Mizuki_E_SR_die':
    case 'STT02-013A_Mizuki_E_SR_die':
    case 'STT02-013ASN_Mizuki_E_SR_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 3, function(candidateID) {
        return AzukiDeckCardIsElement(candidateID, 'Water')
          && AzukiDeckCardCostAtMost(candidateID, 2);
      });
    case 'S1-STT03-003_Koyama-Farm-Potter_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Verdant');
      });
    case 'S1-STT04-005_Ruby_E_C_die':
      return AzukiDeckTopCardsHitRate(deck, cardID, 5, function(candidateID) {
        return AzukiDeckCardHasSubtype(candidateID, 'Pyreskin');
      });
    default:
      return -1;
  }
}

window.HyperGeo = HyperGeo;

(function AzukiDeckPlaybookBootstrap() {
  function loadPlaybookClient() {
    if (!window.AzukiDeckPlaybookConfig || document.getElementById('azukiPlaybookClient')) return;
    var version = encodeURIComponent(window.AzukiDeckPlaybookConfig.assetVersion || '');
    var versionQuery = version ? '?v=' + version : '';

    if (!document.getElementById('azukiPlaybookStyles')) {
      var styles = document.createElement('link');
      styles.id = 'azukiPlaybookStyles';
      styles.rel = 'stylesheet';
      styles.href = '/TCGEngine/AzukiDeck/Custom/Playbook.css' + versionQuery;
      document.head.appendChild(styles);
    }

    var script = document.createElement('script');
    script.id = 'azukiPlaybookClient';
    script.src = '/TCGEngine/AzukiDeck/Custom/Playbook.js' + versionQuery;
    document.body.appendChild(script);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadPlaybookClient);
  } else {
    loadPlaybookClient();
  }
})();
