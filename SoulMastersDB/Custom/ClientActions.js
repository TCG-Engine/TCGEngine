
function ClientWidgetActions(action) {
  switch(action) {
    case "Hand Draw":
      // Create overlay and modal elements
      var overlay = document.createElement('div');
      overlay.style.position = "fixed";
      overlay.style.top = "0";
      overlay.style.left = "0";
      overlay.style.width = "100%";
      overlay.style.height = "100%";
      overlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
      overlay.style.display = "flex";
      overlay.style.justifyContent = "center";
      overlay.style.alignItems = "center";
      overlay.style.zIndex = "2000";

      var modal = document.createElement('div');
      modal.style.backgroundColor = "#0D1B2A";
      modal.style.padding = "20px";
      modal.style.borderRadius = "8px";
      modal.style.position = "relative";
      modal.style.maxWidth = "90%";
      modal.style.maxHeight = "90%";
      modal.style.overflowY = "auto";
      modal.style.boxShadow = "0 0 15px 5px rgba(0, 123, 255, 0.7)";

      // Create close button
      var closeButton = document.createElement('button');
      closeButton.textContent = "X";
      closeButton.style.position = "absolute";
      closeButton.style.top = "5px";
      closeButton.style.right = "5px";
      closeButton.style.background = "transparent";
      closeButton.style.border = "none";
      closeButton.style.fontSize = "16px";
      closeButton.style.cursor = "pointer";
      closeButton.style.fontFamily = "'Orbitron', sans-serif";
      closeButton.style.color = "#FFFFFF";
      modal.appendChild(closeButton);
      // Display the first six elements of zoneData
      var content = document.createElement('div');
      content.id = "handDrawContent";
      content.innerHTML = HandDrawPopupContent();//contentHtml;
      modal.appendChild(content);

      // Append modal to overlay and overlay to body
      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      // Function to close the popup and clean up event listeners
      function closePopup() {
        document.body.removeChild(overlay);
        document.removeEventListener('keydown', handleKeydown);
      }

      // Close when close button is clicked
      closeButton.addEventListener('click', closePopup);

      // Close when clicking outside the modal
      overlay.addEventListener('click', function(event) {
        if (event.target === overlay) {
          closePopup();
        }
      });

      // Close when escape key is pressed
      function handleKeydown(event) {
        if (event.key === "Escape") {
          closePopup();
        }
      }
      document.addEventListener('keydown', handleKeydown);
      return true;
    default:
      return false;
  }
}

function HandDrawPopupContent() {
  var handSize = 5;
  var zoneData = GetZoneCards("myMainDeck");
  for(var i = 0; i < 3; i++) {
    zoneData = FisherYates(zoneData);
  }
  var contentHtml = "";
  for(var i = 0; i < handSize; i++) {
    contentHtml += "<img src='./SoulMastersDB/concat/" + zoneData[i] + ".webp' style='width: 120px; height: 120px; padding:2px;'>";
  }
  var zoneDataString = zoneData.join('!');
  contentHtml += "<div style='display:flex;justify-content:center;gap:10px;'>";
  contentHtml += "<button style='padding:10px 20px;font-size:16px;cursor:pointer;' onclick='document.getElementById(\"handDrawContent\").innerHTML = HandDrawPopupContentSubsequent(\"" + zoneDataString + "\", " + (handSize + 2) + ");'>Draw 2 more</button>";
  contentHtml += "<button style='padding:10px 20px;font-size:16px;cursor:pointer;' onclick='document.getElementById(\"handDrawContent\").innerHTML = HandDrawPopupContent();'>Redraw</button>";
  contentHtml += "</div>";
  return contentHtml;
}

function HandDrawPopupContentSubsequent(cards, amount) {
  var zoneData = cards.split('!');
  var contentHtml = "";
  for (var i = 0; i < amount; i++) {
    if(i >= zoneData.length) break;
    contentHtml += "<img src='./SoulMastersDB/concat/" + zoneData[i] + ".webp' style='width: 120px; height: 120px; padding:2px;'>";
  }
  contentHtml += "<div style='display:flex;justify-content:center;gap:10px;margin-top:10px;'>";
  contentHtml += "<button style='padding:10px 20px;font-size:16px;cursor:pointer;' onclick='document.getElementById(\"handDrawContent\").innerHTML = HandDrawPopupContentSubsequent(\"" + cards + "\", " + (amount + 2) + ");'>Draw 2 more</button>";
  contentHtml += "<button style='padding:10px 20px;font-size:16px;cursor:pointer;' onclick='document.getElementById(\"handDrawContent\").innerHTML = HandDrawPopupContent();'>Redraw</button>";
  contentHtml += "</div>";
  return contentHtml;
}

function FisherYates(array) {
  var currentIndex = array.length, temporaryValue, randomIndex;

  while(0 !== currentIndex) {
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex -= 1;

    temporaryValue = array[currentIndex];
    array[currentIndex] = array[randomIndex];
    array[randomIndex] = temporaryValue;
  }

  return array;
}

function CountsDisplay() {
  return TotalDisplay() + "&nbsp;&nbsp;&nbsp;&nbsp;" + NumUnitsDisplay() + "&nbsp;&nbsp;&nbsp;&nbsp;" + NumCoresDisplay() + "&nbsp;&nbsp;&nbsp;&nbsp;" + NumMercsDisplay() + "&nbsp;&nbsp;&nbsp;&nbsp;" + NumOtherDisplay();
}

function TotalDisplay() {
  var zoneData = GetZoneCards("myMainDeck");
  return "Deck: " + zoneData.length + "/50";
}

function NumUnitsDisplay() {
  var zoneData = GetZoneCards("myMainDeck");
  var unitCount = 0;
  for (var i = 0; i < zoneData.length; i++) {
    if(CardType(zoneData[i]) == "Unit") {
      unitCount++;
    }
  }
  var commanderData = GetZoneCards("myCommander");
  var maxMercs = "?";
  if(commanderData.length > 0) {
    maxMercs = CardMercenaryLimit(commanderData[0]);
  }
  return "Units: " + unitCount + "/25" + (maxMercs == "?" ? "" : " (Max: " + (25+maxMercs) + ")");
}

function NumCoresDisplay() {
  var zoneData = GetZoneCards("myMainDeck");
  var commanderData = GetZoneCards("myCommander");
  var maxCores = "?";
  if(commanderData.length > 0) {
    maxCores = CardCoreEnergy(commanderData[0]);
  }
  var coreCount = 0;
  for (var i = 0; i < zoneData.length; i++) {
    if(CardType(zoneData[i]) == "Core") {
      coreCount++;
    }
  }
  return "Cores: " + coreCount + "/" + maxCores;
}

function NumMercsDisplay() {
  var zoneData = GetZoneCards("myMainDeck");
  var commanderData = GetZoneCards("myCommander");
  var numHolidays = 0;
  var maxMercs = "?";
  if(commanderData.length > 0) {
    maxMercs = CardMercenaryLimit(commanderData[0]);
  }
  var mercCount = 0;
  for (var i = 0; i < zoneData.length; i++) {
    if(CardName(zoneData[i]).includes("Holiday")) {
      numHolidays++;
    }
    if(CardFaction(zoneData[i]).includes("Mercenary")) {
      mercCount++;
    }
  }
  if(numHolidays >= 3) --mercCount;
  if(mercCount > maxMercs) mercCount = maxMercs;
  return "Mercenaries: " + mercCount + "/" + maxMercs;
}

function NumOtherDisplay() {
  var zoneData = GetZoneCards("myMainDeck");
  var numSpells = 0;
  var numAbilities = 0;
  for(var i = 0; i < zoneData.length; i++) {
    if(CardType(zoneData[i]).includes("Spell")) {
      numSpells++;
    } else if(CardType(zoneData[i]).includes("Ability")) {
      numAbilities++;
    }
  }
  return "Spells: " + numSpells + "&nbsp;&nbsp;&nbsp;&nbsp;Abilities: " + numAbilities;
}

function ReserveDisplay() {
  var reserveCards = GetZoneCards("myReserveDeck");
  var numBattlefield = 0;
  var numWeapon = 0;
  var numArmor = 0;
  var numFeat = 0;
  for(var i = 0; i < reserveCards.length; i++) {
    var subtype = CardSubtype(reserveCards[i]);
    if(subtype == null) continue;
    if(subtype.includes("Battlefield")) {
      numBattlefield++;
    } else if(subtype.includes("Weapon")) {
      numWeapon++;
    } else if(subtype.includes("Armor")) {
      numArmor++;
    } else if(subtype.includes("Feat")) {
      numFeat++;
    }
  }
  return "Reserve Deck Count: " + reserveCards.length + "/8 (Battlefield: " + numBattlefield + "/2, Weapon: " + numWeapon + "/2, Armor: " + numArmor + "/2, Feat: " + numFeat + "/2)";
}