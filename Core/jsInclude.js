var _openPopup = null;

function OnLoadCallback(lastUpdate) {
  var log = document.getElementById("gamelog");
  if (log !== null) log.scrollTop = log.scrollHeight;
  reload();
}

var showDetailTimeout;

function ShowCardDetail(e, that) {
  clearTimeout(showDetailTimeout);//In case there was another card waiting to show detail
  showDetailTimeout = setTimeout(function() {
    if (e.target.hasAttribute("data-subcard-id")) {
      var subCardID = e.target.getAttribute("data-subcard-id");
      ShowDetail(e, `${window.location.origin}/SWUOnline/WebpImages/${subCardID}.webp`);
    } else {
      ShowDetail(e, that.getElementsByTagName("IMG")[0].src);
    }
  }, 1); // 1 milliseconds delay (hover delay)
}

function ShowDetail(e, imgSource) {
  imgSource = imgSource.replace("_cropped", "");
  imgSource = imgSource.replace("/crops/", "/WebpImages/");
  imgSource = imgSource.replace("_concat", "");
  imgSource = imgSource.replace("/concat/", "/WebpImages/");
  imgSource = imgSource.replace(".png", ".webp");
  var el = document.getElementById("cardDetail");
  var img = new Image();
  img.onload = function() {
    //Original dimension: height:523px; width:375px;
    var maxWidth = 400;
    var maxHeight = 400;
    var width = img.width;
    var height = img.height;

    if (width > height) {
      if (width > maxWidth) {
        height *= maxWidth / width;
        width = maxWidth;
      }
    } else {
      if (height > maxHeight) {
        width *= maxHeight / height;
        height = maxHeight;
      }
    }

    el.innerHTML = "<img style='height:" + height + "px; width:" + width + "px;' src='" + imgSource + "' />";
    el.style.display = "inline";
    el.style.opacity = 0;
    showDetailTimeout = setTimeout(function() {
      el.style.transition = "opacity 0.5s";
      el.style.opacity = 1;
    }, 100);
  };
  img.src = imgSource;
  el.style.left =
    (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  el.style.top =
    (e.clientY > window.innerHeight / 2 ? e.clientY - 523 - 20 : e.clientY + 30) + 'px';
  if (parseInt(el.style.top) + 523 >= window.innerHeight) {
    el.style.top = (window.innerHeight - 530) + 'px';
    el.style.left =
      (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  } else if (parseInt(el.style.top) <= 0) {
    el.style.top = '5px';
    el.style.left =
      (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  }
  el.style.zIndex = 100000;
  el.style.display = "none";
}

function HideCardDetail() {
  clearTimeout(showDetailTimeout);
  var el = document.getElementById("cardDetail");
  el.style.display = "none";
}

function ChatKey(event) {
  if (event.keyCode === 13) {
    event.preventDefault();
    SubmitChat();
  }
  event.stopPropagation();
}

function SubmitChat() {
  var chatBox = document.getElementById("chatText");
  if (chatBox.value == "") return;
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
    }
  };
  var ajaxLink =
    "SubmitChat.php?gameName=" + document.getElementById("gameName").value;
  ajaxLink +=
    "&playerID=" + document.getElementById("playerID").value +
    "&chatText=" + encodeURI(chatBox.value) +
    "&authKey=" + document.getElementById("authKey").value;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
  chatBox.value = "";
}

function AddCardToHand() {
  var card = document.getElementById("manualAddCardToHand").value;
  SubmitInput(10011, "&cardID=" + card);
}

function ZoneClickHandler(zone) {
  var zoneData = GetZoneData(zone);
  switch(zoneData.DisplayMode) {
    case "All":
      break;
    case "Tile":
      break;
    case "Panel":
      break;
    case "Pane":
      break;
    case "Value":
      break;
    case "Radio":
      break;
    case "Calculate":
      break;
    default:
      TogglePopup(zone);
      break;
  }
}

function SubmitInput(mode, params, fullRefresh = false) {
  mode = ModeAliasLookup(mode);
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      if (fullRefresh) location.reload();
      if(_openPopup != null) RefreshPopupContent(_openPopup);
    }
  };
  var ajaxLink =
    "ProcessInput.php?gameName=" + document.getElementById("gameName").value;
  ajaxLink += "&playerID=" + document.getElementById("playerID").value;
  ajaxLink += "&authKey=" + document.getElementById("authKey").value;
  ajaxLink += "&folderPath=" + document.getElementById("folderPath").value;
  ajaxLink += "&mode=" + mode;
  ajaxLink += params;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
}

function ModeAliasLookup(mode) {
  switch(mode) {
    case 'DECISION':
      return 100;
    default:
      return mode;
  }
}

function RefreshPopupContent(name) {
  var id = name + "Popup";
  fetchPopupContent(name, function(responseText) {
    var popup = createPopupHTML(name, responseText);
    document.getElementById("popupContainer").innerHTML = popup;
  });
}

function ClosePopup() {
  if (_openPopup != null) {
    document.getElementById(_openPopup + "Popup").style.display = "none";
    _openPopup = null;
  }
}

function ShowZonePopup(cardId) {
  // Extract zone name from card ID (format: "zoneName-index")
  // Handle cases where there's no "-" or the format is different
  var parts = cardId.split("-");
  var zoneName = parts.length > 1 ? parts[0] : cardId;
  if (zoneName) {
    TogglePopup(zoneName);
  }
}

function TogglePopup(name) {
  var id = name + "Popup";
  if (document.getElementById(id)?.style.display == "inline") {
    document.getElementById(id).style.display = "none";
    _openPopup = null;
  } else {
    fetchPopupContent(name, function(responseText) {
      var popup = createPopupHTML(name, responseText);
      document.getElementById("popupContainer").innerHTML = popup;
      _openPopup = name;
    });
  }
}

function fetchPopupContent(name, callback) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      callback(this.responseText);
    }
  };
  var ajaxLink =
    "./GetPopupContent.php?gameName=" +
    document.getElementById("gameName").value;
  ajaxLink += "&playerID=" + document.getElementById("playerID").value;
  ajaxLink += "&authKey=" + document.getElementById("authKey").value;
  ajaxLink += "&folderPath=" + document.getElementById("folderPath").value;
  ajaxLink += "&popupType=" + name;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
}

function createPopupHTML(name, responseText) {
  var id = name + "Popup";
  var folderPath = document.getElementById("folderPath").value;
  var popup = "<div id='" + id + "' style='overflow-y: auto; background-color:rgba(0, 0, 0, 0.6); backdrop-filter: blur(20px); border-radius: 10px; padding: 10px; font-weight: 500; scrollbar-color: #888888 rgba(0, 0, 0, 0); scrollbar-width: thin; z-index:1000; position: absolute; top:40%; left:calc(25% - 129px); width:50%; height:30%; display:inline;'>";
  popup += "<div style='display: flex; justify-content: center; align-items: center; padding-bottom: 10px;'>";
  popup += "<h2 style='text-align: center; color: white; margin: 0;'>" + name.split(/(?=[A-Z])/).join(" ").replace(/^./, str => str.toUpperCase()) + "</h2>";
  popup += "<button style='background-color: transparent; border: none; color: white; font-size: 24px; cursor: pointer; position: absolute; right: 10px;' onclick='ClosePopup()'>&times;</button>";
  popup += "</div>";
  var responseArr = responseText.split("</>");
  var macros = responseArr[0] == "" ? [] : responseArr[0].split(",");
  var cards = responseArr[1];
  popup += PopulateZone(name, cards, 96, "./" + folderPath + "/concat", 1, "All");
  macros.forEach(function(macro) {
    popup += "<button style='margin: 5px; padding: 5px 10px; background-color: #444; color: white; border: none; border-radius: 5px; cursor: pointer;' onclick='SubmitInput(10000, \"&buttonInput=" + macro + "&inputText=" + name + "\")'>" + macro + "</button>";
  });
  popup += "</div>";
  return popup;
}
