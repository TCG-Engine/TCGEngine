
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
  var baseData = GetZoneCards("myBase");
  var handSize = 6;
  if(baseData.length > 0) {
    switch(baseData[0]) {
      case "9586661707"://Nabat Village
        handSize = 9;
        break;
      case "1029978899":
        handSize = 5;
        break;
      default: break;
    }
  }
  var zoneData = GetZoneCards("myMainDeck");
  for(var i = 0; i < 3; i++) {
    zoneData = FisherYates(zoneData);
  }
  var contentHtml = "";
  for(var i = 0; i < handSize; i++) {
    contentHtml += "<img src='./SWUDeck/concat/" + zoneData[i] + ".webp' style='width: 120px; height: 120px; padding:2px;'>";
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
    contentHtml += "<img src='./SWUDeck/concat/" + zoneData[i] + ".webp' style='width: 120px; height: 120px; padding:2px;'>";
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