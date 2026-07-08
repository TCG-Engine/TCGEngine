var _openPopup = null;

function OnLoadCallback(lastUpdate) {
  var log = document.getElementById("gamelog");
  if (log !== null) log.scrollTop = log.scrollHeight;
  reload();
}

var showDetailTimeout;
var freezeCardDetailUntilMouseMove = false;
var freezeCardDetailMouseX = null;
var freezeCardDetailMouseY = null;
var lastCardDetailMouseX = null;
var lastCardDetailMouseY = null;
var cardDetailLongPressTimeout = null;
var cardDetailLongPressTarget = null;
var cardDetailLongPressStartX = null;
var cardDetailLongPressStartY = null;
var cardDetailLongPressPreviewShown = false;
var suppressMouseCardDetailUntil = 0;
var suppressNextCardDetailClickUntil = 0;
var CARD_DETAIL_LONG_PRESS_MS = 430;
var CARD_DETAIL_TOUCH_MOVE_TOLERANCE = 12;

function TrackCardDetailMouse(e) {
  if (!e || typeof e.clientX !== "number" || typeof e.clientY !== "number") return;
  lastCardDetailMouseX = e.clientX;
  lastCardDetailMouseY = e.clientY;
}

function IsCardDetailSuppressed() {
  return !!window._suppressCardDetail || freezeCardDetailUntilMouseMove;
}

function IsCardDetailOpen() {
  var el = document.getElementById("cardDetail");
  return !!(el && el.style.display !== "none");
}

function FreezeCardDetailUntilMouseMove() {
  if (!IsCardDetailOpen()) return false;
  freezeCardDetailUntilMouseMove = true;
  freezeCardDetailMouseX = lastCardDetailMouseX;
  freezeCardDetailMouseY = lastCardDetailMouseY;
  return true;
}

function IsTouchCardDetailEvent(e) {
  return !!(e && typeof e.type === "string" && e.type.indexOf("touch") === 0);
}

function IsMouseCardDetailEvent(e) {
  return !!(e && typeof e.type === "string" && e.type.indexOf("mouse") === 0);
}

function ShouldIgnoreCardDetailEvent(e, options) {
  if (options && options.allowTouch) return false;
  if (IsTouchCardDetailEvent(e)) return true;
  return IsMouseCardDetailEvent(e) && Date.now() < suppressMouseCardDetailUntil;
}

function ShowCardDetail(e, that, options) {
  options = options || {};
  if (ShouldIgnoreCardDetailEvent(e, options)) return;
  if (IsCardDetailSuppressed()) return;
  TrackCardDetailMouse(e);
  clearTimeout(showDetailTimeout);//In case there was another card waiting to show detail
  var folderPath = document.getElementById("folderPath").value;
  var timeOut = options.skipDelay ? 0 : (folderPath == "SWUSim" ? 850 :
    (folderPath == "GudnakSim" || folderPath == "GrandArchiveSim" || folderPath == "AzukiSim" ? 100 : 1));
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

// Place the card-detail preview beside the cursor on desktop, but always keep it fully
// on-screen. On narrow viewports (phones) the preview is wider than half the screen, so
// beside-the-tap placement pushes it off an edge (the old `clientX - 400` bug) — center
// it horizontally there instead. cx/cy = pointer position; w/h = preview dimensions.
function PositionCardDetail(el, cx, cy, w, h) {
  var vw = window.innerWidth, vh = window.innerHeight;
  var left;
  if (w > vw * 0.6) {
    left = Math.max(5, Math.round((vw - w) / 2));
  } else {
    left = (cx < vw / 2) ? cx + 30 : cx - w - 10;
    left = Math.max(5, Math.min(left, vw - w - 5));
  }
  var top = (cy > vh / 2) ? cy - h - 20 : cy + 30;
  top = Math.max(5, Math.min(top, vh - h - 5));
  el.style.left = left + 'px';
  el.style.top = top + 'px';
}

function ShowDetail(e, imgSource) {
  if (IsCardDetailSuppressed()) return;
  TrackCardDetailMouse(e);
  imgSource = imgSource.replace("_cropped", "");
  imgSource = imgSource.replace("/crops/", "/WebpImages/");
  imgSource = imgSource.replace("_concat", "");
  imgSource = imgSource.replace("/concat/", "/WebpImages/");
  imgSource = imgSource.replace(".png", ".webp");
  var el = document.getElementById("cardDetail");
  var cx = e.clientX, cy = e.clientY; // capture: pointer may move before the image loads
  el.style.display = "none";
  el.style.zIndex = 100000;
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
    PositionCardDetail(el, cx, cy, width, height);
    el.style.display = "inline";
    el.style.opacity = 0;
    showDetailTimeout = setTimeout(function() {
      el.style.transition = "opacity 0.5s";
      el.style.opacity = 1;
    }, 100);
  };
  img.src = imgSource;
}

function ShowSubcardDetail(e, imgEl, options) {
  options = options || {};
  if (ShouldIgnoreCardDetailEvent(e, options)) return;
  if (IsCardDetailSuppressed()) return;
  TrackCardDetailMouse(e);
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
    PositionCardDetail(el, e.clientX, e.clientY, displayWidth, displayHeight);
    el.style.zIndex = 100000;
  }, options.skipDelay ? 0 : 1);
}

function HideCardDetail(force) {
  if (!force && freezeCardDetailUntilMouseMove) return;
  clearTimeout(showDetailTimeout);
  var el = document.getElementById("cardDetail");
  el.style.display = "none";
}

document.addEventListener("mousemove", function(e) {
  if (!freezeCardDetailUntilMouseMove) {
    TrackCardDetailMouse(e);
    return;
  }

  var moved = freezeCardDetailMouseX === null || freezeCardDetailMouseY === null ||
    e.clientX !== freezeCardDetailMouseX || e.clientY !== freezeCardDetailMouseY;
  TrackCardDetailMouse(e);
  if (!moved) return;

  freezeCardDetailUntilMouseMove = false;
  freezeCardDetailMouseX = null;
  freezeCardDetailMouseY = null;
  HideCardDetail(true);
}, true);

function FirstChangedTouch(e) {
  if (e && e.changedTouches && e.changedTouches.length > 0) return e.changedTouches[0];
  if (e && e.touches && e.touches.length > 0) return e.touches[0];
  return null;
}

function FindLongPressCardDetailTarget(e) {
  var target = e && e.target;
  if (!target || typeof target.closest !== "function") return null;

  var subcardEl = target.closest("[onmouseover*='ShowSubcardDetail'], [data-subcard-id]");
  if (subcardEl) {
    return { type: "subcard", element: subcardEl };
  }

  var cardEl = target.closest("a[onmouseover*='ShowCardDetail']");
  if (cardEl) {
    return { type: "card", element: cardEl };
  }

  return null;
}

function ClearCardDetailLongPress() {
  clearTimeout(cardDetailLongPressTimeout);
  cardDetailLongPressTimeout = null;
  cardDetailLongPressTarget = null;
  cardDetailLongPressStartX = null;
  cardDetailLongPressStartY = null;
  cardDetailLongPressPreviewShown = false;
}

function BeginCardDetailLongPress(e) {
  suppressMouseCardDetailUntil = Date.now() + 900;
  if (IsCardDetailOpen()) HideCardDetail(true);
  ClearCardDetailLongPress();

  var touch = FirstChangedTouch(e);
  var detailTarget = FindLongPressCardDetailTarget(e);
  if (!touch || !detailTarget) return;

  cardDetailLongPressTarget = detailTarget;
  cardDetailLongPressStartX = touch.clientX;
  cardDetailLongPressStartY = touch.clientY;
  var previewEvent = {
    type: "touchlongpress",
    target: e.target,
    clientX: touch.clientX,
    clientY: touch.clientY
  };

  cardDetailLongPressTimeout = setTimeout(function() {
    if (!cardDetailLongPressTarget) return;
    cardDetailLongPressPreviewShown = true;
    suppressNextCardDetailClickUntil = Date.now() + 1200;
    if (cardDetailLongPressTarget.type === "subcard") {
      ShowSubcardDetail(previewEvent, cardDetailLongPressTarget.element, { allowTouch: true, skipDelay: true });
    } else {
      ShowCardDetail(previewEvent, cardDetailLongPressTarget.element, { allowTouch: true, skipDelay: true });
    }
  }, CARD_DETAIL_LONG_PRESS_MS);
}

function MoveCardDetailLongPress(e) {
  if (!cardDetailLongPressTarget) return;
  var touch = FirstChangedTouch(e);
  if (!touch) return;
  var dx = Math.abs(touch.clientX - cardDetailLongPressStartX);
  var dy = Math.abs(touch.clientY - cardDetailLongPressStartY);
  if (dx > CARD_DETAIL_TOUCH_MOVE_TOLERANCE || dy > CARD_DETAIL_TOUCH_MOVE_TOLERANCE) {
    ClearCardDetailLongPress();
  }
}

function EndCardDetailLongPress(e) {
  var previewWasShown = cardDetailLongPressPreviewShown;
  ClearCardDetailLongPress();
  if (!previewWasShown) return;

  suppressNextCardDetailClickUntil = Date.now() + 700;
  HideCardDetail(true);
  if (e && typeof e.preventDefault === "function" && e.cancelable) e.preventDefault();
  if (e && typeof e.stopPropagation === "function") e.stopPropagation();
}

// Touch uses long-press for card inspection. Normal taps should remain available for
// board actions and zone opening, so suppress the synthetic mouseover that follows touch.
document.addEventListener("touchstart", BeginCardDetailLongPress, { passive: true, capture: true });
document.addEventListener("touchmove", MoveCardDetailLongPress, { passive: true, capture: true });
document.addEventListener("touchend", EndCardDetailLongPress, { passive: false, capture: true });
document.addEventListener("touchcancel", EndCardDetailLongPress, { passive: false, capture: true });
document.addEventListener("click", function(e) {
  if (Date.now() >= suppressNextCardDetailClickUntil) return;
  suppressNextCardDetailClickUntil = 0;
  if (e && typeof e.preventDefault === "function") e.preventDefault();
  if (e && typeof e.stopPropagation === "function") e.stopPropagation();
}, true);

function ChatKey(event) {
  if (event.keyCode === 13) {
    event.preventDefault();
    SubmitChat();
  }
  event.stopPropagation();
}

function IsSpectatorClient() {
  var playerInput = document.getElementById("playerID");
  if (!playerInput) return false;
  return String(playerInput.value || '').toUpperCase() === 'S';
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
var _lastChatVersion = 0;
var _chatSeenIds = {};

function StartChatPoll() {
  return;
}

function ApplyChatPayload(payload) {
  if (!payload || typeof payload !== "object") return false;
  var version = parseInt(payload.version || 0, 10);
  if (Number.isNaN(version)) version = 0;
  var msgs = Array.isArray(payload.messages) ? payload.messages : [];
  for (var i = 0; i < msgs.length; ++i) {
    var m = msgs[i];
    if (!_chatSeenIds[m.id]) {
      _chatSeenIds[m.id] = true;
      _AppendChatMessage(m);
    }
    if (m.id > _lastChatID) _lastChatID = m.id;
  }
  if (version > _lastChatVersion) _lastChatVersion = version;
  // Neutral "chat disabled" state (e.g. a player was blocked). Never reveals why.
  var ci = document.getElementById("chatText");
  if (ci) {
    if (payload.chatDisabled) {
      if (!ci.disabled) { ci.dataset.ph = ci.placeholder || ""; ci.placeholder = "Chat disabled"; }
      ci.disabled = true;
    } else if (ci.disabled) {
      ci.disabled = false;
      if (ci.dataset.ph !== undefined) ci.placeholder = ci.dataset.ph;
    }
  }
  return msgs.length > 0 || version > 0;
}

function _AppendChatMessage(msg) {
  var log = document.getElementById("chatLog");
  if (!log) return;
  var div = document.createElement("div");
  div.className = "chatMsg chatMsg-p" + msg.playerID;
  div.style.cssText = "padding:2px 4px; word-break:break-word; font-size:13px;";
  var label = document.createElement("span");
  label.style.cssText = "font-weight:700; margin-right:4px;";
  // Prefer the seat's username (SWUSim) so chat reads from real names; fall back to P#/label.
  var seatName = (window.SWU_SEAT_USERNAMES && (msg.playerID === 1 || msg.playerID === 2 || msg.playerID === "1" || msg.playerID === "2"))
    ? window.SWU_SEAT_USERNAMES[String(msg.playerID)] : null;
  label.textContent = (seatName ? seatName : (msg.playerLabel ? msg.playerLabel : ("P" + msg.playerID))) + ":";
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
  if (window.SelectionMode && window.SelectionMode.active && window.SelectionMode.mode === 'CHOOSEZONE') {
    var allowedZoneSpecs = window.SelectionMode.allowedDecisionZones || [];
    for (var zi = 0; zi < allowedZoneSpecs.length; ++zi) {
      var spec = allowedZoneSpecs[zi];
      if (!spec || !spec.zone) continue;
      if (spec.zone !== zone) continue;

      var submittedValue = spec.submittedValue || zone;
      if (typeof window.SelectionMode.callback === 'function') {
        window.SelectionMode.callback(zone, submittedValue, window.SelectionMode.decisionIndex);
      }
      if (typeof ClearSelectionMode === 'function') {
        ClearSelectionMode();
      }
      return;
    }
  }

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

function FormInputValue(id) {
  var input = document.getElementById(id);
  return input ? input.value : "";
}

function AppendSubmitInputParams(url, params) {
  if (!params) return url;
  params = String(params);
  if (params === "") return url;
  if (params.charAt(0) === "&") return url + params;
  if (params.charAt(0) === "?") return url + "&" + params.substring(1);
  return url + "&" + params;
}

function SubmitEngineInput(mode, params, options) {
  options = options || {};
  if (!options.allowSpectator && IsSpectatorClient()) {
    return Promise.resolve({ success: false, message: "Spectators are view-only." });
  }

  mode = ModeAliasLookup(mode);
  var playerID = options.playerID != null ? String(options.playerID) : FormInputValue("playerID");
  var authKey = options.authKey != null ? String(options.authKey) : FormInputValue("authKey");
  var folderPath = options.folderPath != null ? String(options.folderPath) : FormInputValue("folderPath");
  var gameName = options.gameName != null ? String(options.gameName) : FormInputValue("gameName");

  var ajaxLink = "ProcessInput.php?gameName=" + encodeURIComponent(gameName);
  ajaxLink += "&playerID=" + encodeURIComponent(playerID);
  ajaxLink += "&authKey=" + encodeURIComponent(authKey);
  ajaxLink += "&folderPath=" + encodeURIComponent(folderPath);
  ajaxLink += "&mode=" + encodeURIComponent(mode);
  if (options.responseFormat) ajaxLink += "&responseFormat=" + encodeURIComponent(options.responseFormat);
  if (options.versionName) ajaxLink += "&versionName=" + encodeURIComponent(options.versionName);
  ajaxLink = AppendSubmitInputParams(ajaxLink, params);

  return new Promise(function(resolve, reject) {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
      if (this.readyState != 4) return;
      if (this.status < 200 || this.status >= 300) {
        reject(new Error("Input request failed with status " + this.status + "."));
        return;
      }

      if (options.fullRefresh) {
        location.reload();
        resolve("");
        return;
      }

      var responseText = this.responseText || "";
      if (options.afterSubmitReload === true && typeof window.QueueGameUpdate === "function") {
        window.QueueGameUpdate();
      }

      if (options.responseFormat === "json") {
        try {
          resolve(JSON.parse(responseText));
        } catch (e) {
          var preview = responseText.trim().substring(0, 80);
          reject(new Error("Input request returned invalid JSON" + (preview ? ": " + preview : ".")));
        }
      } else {
        resolve(responseText);
      }
    };
    xmlhttp.open("GET", ajaxLink, true);
    xmlhttp.send();
  });
}

function SubmitInput(mode, params, fullRefresh = false) {
  SubmitEngineInput(mode, params, { fullRefresh: fullRefresh }).then(function() {
    if(_openPopup != null) RefreshPopupContent(_openPopup);
  }).catch(function(error) {
    if (window.console && console.error) console.error(error);
  });
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

function ZonePopupStyle() {
  return [
    "overflow: auto",
    "-webkit-overflow-scrolling: touch",
    "background-color: rgba(0, 0, 0, 0.6)",
    "backdrop-filter: blur(20px)",
    "-webkit-backdrop-filter: blur(20px)",
    "border-radius: 10px",
    "padding: 10px",
    "font-weight: 500",
    "scrollbar-color: #888888 rgba(0, 0, 0, 0)",
    "scrollbar-width: thin",
    "z-index: 5000",
    "position: fixed",
    "top: 50%",
    "left: 50%",
    "transform: translate(-50%, -50%)",
    "width: min(760px, calc(100vw - 24px))",
    "max-width: calc(100vw - 24px)",
    "height: min(420px, calc(100vh - 88px))",
    "height: min(56dvh, 420px, calc(100dvh - 88px))",
    "max-height: calc(100vh - 88px)",
    "max-height: calc(100dvh - 88px)",
    "box-sizing: border-box",
    "display: block"
  ].join("; ");
}

function ZonePopupContentStyle() {
  return [
    "#popupContainer .tcg-zone-popup-cards > span {",
    "  width: 100% !important;",
    "  height: auto !important;",
    "  min-width: 0 !important;",
    "  min-height: 0 !important;",
    "  max-width: 100% !important;",
    "  max-height: none !important;",
    "  display: grid !important;",
    "  grid-template-columns: repeat(auto-fit, minmax(74px, 96px)) !important;",
    "  justify-content: center !important;",
    "  align-items: start !important;",
    "  gap: 10px 8px !important;",
    "  overflow: visible !important;",
    "}",
    "#popupContainer .tcg-zone-popup-cards > span > span[id] {",
    "  width: 96px !important;",
    "  max-width: 100% !important;",
    "  height: auto !important;",
    "  margin: 0 !important;",
    "  display: flex !important;",
    "  justify-content: center !important;",
    "}",
    "#popupContainer .tcg-zone-popup-cards img:not(.counter-image-icon) {",
    "  height: 96px !important;",
    "  width: 96px !important;",
    "  max-width: 100% !important;",
    "  object-fit: contain !important;",
    "}",
    "@media (max-width: 430px) {",
    "  #popupContainer .tcg-zone-popup-cards > span {",
    "    grid-template-columns: repeat(auto-fit, minmax(68px, 86px)) !important;",
    "    gap: 9px 7px !important;",
    "  }",
    "  #popupContainer .tcg-zone-popup-cards > span > span[id] {",
    "    width: 86px !important;",
    "  }",
    "  #popupContainer .tcg-zone-popup-cards img:not(.counter-image-icon) {",
    "    height: 86px !important;",
    "    width: 86px !important;",
    "  }",
    "}"
  ].join("\n");
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
  var existing = document.getElementById(id);
  if (existing && existing.style.display !== "none") {
    existing.style.display = "none";
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
  var viewerPerspective = document.getElementById("viewerPerspective");
  if (viewerPerspective) ajaxLink += "&viewerPerspective=" + encodeURIComponent(viewerPerspective.value);
  ajaxLink += "&popupType=" + name;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
}

function createPopupHTML(name, responseText) {
  var id = name + "Popup";
  var folderPath = document.getElementById("folderPath").value;
  var popup = "<div id='" + id + "' class='tcg-zone-popup' style='" + ZonePopupStyle() + "'>";
  popup += "<style>" + ZonePopupContentStyle() + "</style>";
  popup += "<div style='display: flex; justify-content: center; align-items: center; padding-bottom: 10px; position: sticky; top: -10px; z-index: 1; background: rgba(0, 0, 0, 0.64); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);'>";
  popup += "<h2 style='text-align: center; color: white; margin: 0;'>" + name.split(/(?=[A-Z])/).join(" ").replace(/^./, str => str.toUpperCase()) + "</h2>";
  popup += "<button style='background-color: transparent; border: none; color: white; font-size: 24px; cursor: pointer; position: absolute; right: 10px;' onclick='ClosePopup()'>&times;</button>";
  popup += "</div>";
  var responseArr = responseText.split("</>");
  var macros = responseArr[0] == "" ? [] : responseArr[0].split(",");
  var cards = responseArr[1];
  popup += "<div class='tcg-zone-popup-cards'>";
  popup += PopulateZone(name, cards, 96, "./" + folderPath + "/concat", 1, "All");
  popup += "</div>";
  macros.forEach(function(macro) {
    popup += "<button style='margin: 5px; padding: 5px 10px; background-color: #444; color: white; border: none; border-radius: 5px; cursor: pointer;' onclick='SubmitInput(10000, \"&buttonInput=" + macro + "&inputText=" + name + "\")'>" + macro + "</button>";
  });
  popup += "</div>";
  return popup;
}
