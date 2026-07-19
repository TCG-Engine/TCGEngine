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
