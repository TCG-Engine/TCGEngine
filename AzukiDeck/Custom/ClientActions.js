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
