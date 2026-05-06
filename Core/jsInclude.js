var _openPopup = null;

function OnLoadCallback(lastUpdate) {
  var log = document.getElementById("gamelog");
  if (log !== null) log.scrollTop = log.scrollHeight;
  reload();
}

var showDetailTimeout;

function IsCardDetailSuppressed() {
  return !!window._suppressCardDetail;
}

function ShowCardDetail(e, that) {
  if (IsCardDetailSuppressed()) return;
  clearTimeout(showDetailTimeout);//In case there was another card waiting to show detail
  var folderPath = document.getElementById("folderPath").value;
  var timeOut = folderPath == "GudnakSim" || folderPath == "GrandArchiveSim" ? 350 : 1;
  showDetailTimeout = setTimeout(function() {
    if (IsCardDetailSuppressed()) return;
    if (e.target.hasAttribute("data-subcard-id")) {
      var subCardID = e.target.getAttribute("data-subcard-id");
      var assetFolder = (typeof AssetReflectionPath === 'function' && AssetReflectionPath()) ? AssetReflectionPath() : folderPath;
      ShowDetail(e, `${window.location.origin}/TCGEngine/${assetFolder}/${subCardID}.png`);
    } else {
      ShowDetail(e, that.getElementsByTagName("IMG")[0].src);
    }
  }, timeOut); //(hover delay)
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

function ShowSubcardDetail(e, imgEl) {
  if (IsCardDetailSuppressed()) return;
  clearTimeout(showDetailTimeout);
  showDetailTimeout = setTimeout(function() {
    if (IsCardDetailSuppressed()) return;
    var src = imgEl.getAttribute('src') || '';
    // Transform concat URL to WebpImages for the popup
    src = src.replace('/concat/', '/WebpImages/');
    src = src.replace('.webp', '.webp'); // Keep as webp
    var el = document.getElementById('cardDetail');
    var displayHeight = 400;
    var displayWidth = Math.round(400 * 0.71);
    el.innerHTML = "<img style='height:" + displayHeight + "px; width:" + displayWidth + "px;' src='" + src + "' />";
    el.style.display = 'inline';
    el.style.opacity = 0;
    showDetailTimeout = setTimeout(function() {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = 1;
    }, 100);
    el.style.left = (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
    el.style.top = Math.max(5, Math.min(e.clientY - 200, window.innerHeight - 530)) + 'px';
    el.style.zIndex = 100000;
  }, 1);
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
  var text = chatBox.value.trim();
  if (text === "") return;
  chatBox.value = "";
  var xmlhttp = new XMLHttpRequest();
  var ajaxLink = "SubmitChat.php?gameName=" + encodeURIComponent(document.getElementById("gameName").value);
  ajaxLink += "&playerID=" + encodeURIComponent(document.getElementById("playerID").value);
  ajaxLink += "&authKey="  + encodeURIComponent(document.getElementById("authKey").value);
  ajaxLink += "&folderPath=" + encodeURIComponent(document.getElementById("folderPath").value);
  ajaxLink += "&chatText="  + encodeURIComponent(text);
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
}

var _lastChatID = 0;
var _chatPollTimer = null;
var _chatSeenIds = {};

function StartChatPoll() {
  if (_chatPollTimer !== null) return;
  _chatPollTimer = setInterval(function() {
    var gameName = document.getElementById("gameName");
    if (!gameName) return;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        try {
          var msgs = JSON.parse(this.responseText);
          if (Array.isArray(msgs) && msgs.length > 0) {
            for (var i = 0; i < msgs.length; ++i) {
              var m = msgs[i];
              if (!_chatSeenIds[m.id]) {
                _chatSeenIds[m.id] = true;
                _AppendChatMessage(m);
              }
              if (m.id > _lastChatID) _lastChatID = m.id;
            }
          }
        } catch(e) {}
      }
    };
    xmlhttp.open("GET", "GetChat.php?gameName=" + encodeURIComponent(gameName.value) + "&lastChatID=" + _lastChatID, true);
    xmlhttp.send();
  }, 1500);
}

function _AppendChatMessage(msg) {
  var log = document.getElementById("chatLog");
  if (!log) return;
  var div = document.createElement("div");
  div.className = "chatMsg chatMsg-p" + msg.playerID;
  div.style.cssText = "padding:2px 4px; word-break:break-word; font-size:13px;";
  var label = document.createElement("span");
  label.style.cssText = "font-weight:700; margin-right:4px;";
  label.textContent = "P" + msg.playerID + ":";
  var body = document.createElement("span");
  body.textContent = msg.text;
  div.appendChild(label);
  div.appendChild(body);
  log.appendChild(div);
  log.scrollTop = log.scrollHeight;
  // Show unread dot on toggle button when panel is collapsed
  var expanded = document.getElementById("chatExpanded");
  var btn = document.getElementById("chatToggleBtn");
  if (btn && expanded && expanded.style.display !== 'flex') {
    btn.textContent = '';
    btn.innerHTML = '&#128172; Chat <span style="background:#e33;color:white;border-radius:50%;width:8px;height:8px;display:inline-block;vertical-align:middle;margin-left:4px;"></span>';
  }
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
      //TogglePopup(zone);
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
    var zoneData = GetZoneData(zoneName);
    if (zoneData) {
      var isOpponentZone = zoneName.indexOf("their") === 0;
      var visibility = zoneData.Visibility || "Public";
      var displayMode = zoneData.DisplayMode || "";
      var hiddenFromViewer =
        visibility === "Private" ||
        (visibility === "Self" && isOpponentZone);
      if (displayMode === "Single" && hiddenFromViewer) return;
    }
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
