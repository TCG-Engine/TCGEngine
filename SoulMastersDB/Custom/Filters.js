
window.customFilter = true;

//Return true to filter
function InFactionFilter(cardID) {
  if(!window.myCommanderData) {
    return false;
  }
  var commanderArr = window.myCommanderData.split(" ");
  var commanderName = CardName(commanderArr[0]);
  var cardSpecialization = CardSpecialization(cardID);
  if(cardSpecialization != "" && !commanderName.includes(cardSpecialization)) return true;
  var cardType = CardType(cardID);
  if(cardType == "Resource") return true;
  var commanderFactions = CardFaction(commanderArr[0]).split(",");
  var cardFactions = CardFaction(cardID).split(",");
  if(cardFactions[0] == "") return false;
  for (var i = 0; i < cardFactions.length; i++) {
    if(cardFactions[i] == "All") return false;
    if(cardFactions[i] == "Mercenary") return false;
    if(commanderFactions.includes(cardFactions[i])) return false;
  }
  return true;
}