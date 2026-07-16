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

function HyperGeo(cardID) {
  var deck = AzukiDeckMainDeckCardIDs();
  if (!deck.length || typeof window.Cardcategory !== 'function') return -1;

  switch (cardID) {
    case 'S1-STT01-004_Black-Jade-Recruit_E_C_die':
      var weaponCount = deck.reduce(function(total, deckCardID) {
        return total + (String(window.Cardcategory(deckCardID)).toLowerCase() === 'weapon' ? 1 : 0);
      }, 0);
      // The Recruit has been played before its On Play ability looks at the top five.
      return AzukiDeckHypergeometricAtLeast(1, 5, weaponCount, deck.length - 1);
    default:
      return -1;
  }
}

window.HyperGeo = HyperGeo;
