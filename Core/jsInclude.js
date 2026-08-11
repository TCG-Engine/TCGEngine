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
var cardDetailRequestToken = 0;
var CARD_DETAIL_LONG_PRESS_MS = 430;
var CARD_DETAIL_TOUCH_MOVE_TOLERANCE = 12;
var SWUDECK_CARD_DETAIL_HOVER_MS = 240;
var AZUKISIM_CARD_DETAIL_HOVER_MS = 400;
// Touch preview: fraction of the viewport the full card may occupy. Desktop hover keeps its own
// fixed 400px ceiling (see ShowDetail) — these apply only to the touch branch.
var CARD_DETAIL_TOUCH_VIEWPORT_W = 0.92;
var CARD_DETAIL_TOUCH_VIEWPORT_H = 0.90;
var cardDetailPersistent = false;

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
  var requestToken = ++cardDetailRequestToken;
  TrackCardDetailMouse(e);
  clearTimeout(showDetailTimeout);//In case there was another card waiting to show detail
  var folderPath = document.getElementById("folderPath").value;
  var timeOut = options.skipDelay ? 0 : (folderPath == "SWUDeck" || folderPath == "AzukiDeck" ? SWUDECK_CARD_DETAIL_HOVER_MS :
    (folderPath == "SWUSim" ? 850 :
    (folderPath == "AzukiSim" ? AZUKISIM_CARD_DETAIL_HOVER_MS :
    (folderPath == "GudnakSim" || folderPath == "GrandArchiveSim" ? 100 : 1))));
  showDetailTimeout = setTimeout(function() {
    if (requestToken !== cardDetailRequestToken) return;
    if (IsCardDetailSuppressed()) return;
    if (e.target.hasAttribute("data-subcard-id")) {
      var subCardID = e.target.getAttribute("data-subcard-id");
      var assetFolder = (typeof AssetReflectionPath === 'function' && AssetReflectionPath()) ? AssetReflectionPath() : folderPath;
      ShowDetail(e, `${window.location.origin}/TCGEngine/${assetFolder}/${subCardID}.png`, folderPath == "SWUDeck" || folderPath == "AzukiDeck" ? that : null, requestToken);
    } else {
      ShowDetail(e, that.getElementsByTagName("IMG")[0].src, folderPath == "SWUDeck" || folderPath == "AzukiDeck" ? that : null, requestToken);
    }
  }, timeOut); //(hover delay)
}

// Place the card-detail preview beside the cursor on desktop, but always keep it fully
// on-screen. On narrow viewports (phones) the preview is wider than half the screen, so
// beside-the-tap placement pushes it off an edge (the old `clientX - 400` bug) — center
// it horizontally there instead. cx/cy = pointer position; w/h = preview dimensions.
function PositionCardDetail(el, cx, cy, w, h, avoidEl) {
  var vw = window.innerWidth, vh = window.innerHeight;
  var left;
  if (w > vw * 0.6) {
    left = Math.max(5, Math.round((vw - w) / 2));
  } else if (avoidEl && typeof avoidEl.getBoundingClientRect === "function") {
    var avoidRect = avoidEl.getBoundingClientRect();
    var detailGap = 12;
    var roomRight = vw - avoidRect.right - detailGap;
    var roomLeft = avoidRect.left - detailGap;
    left = (roomRight >= w || roomRight >= roomLeft)
      ? avoidRect.right + detailGap
      : avoidRect.left - w - detailGap;
    left = Math.max(5, Math.min(left, vw - w - 5));
  } else {
    left = (cx < vw / 2) ? cx + 30 : cx - w - 10;
    left = Math.max(5, Math.min(left, vw - w - 5));
  }
  var top = (cy > vh / 2) ? cy - h - 20 : cy + 30;
  top = Math.max(5, Math.min(top, vh - h - 5));
  el.style.left = left + 'px';
  el.style.top = top + 'px';
}

// A touch preview is requested by the long-press path, which synthesizes an event of this type
// (see BeginCardDetailLongPress). Detecting it here keeps ShowDetail/ShowSubcardDetail's public
// signatures unchanged, so every existing caller — and the whole desktop hover path — is untouched.
function IsTouchPreviewEvent(e) {
  return !!(e && e.type === "touchlongpress");
}

// Desktop keeps the historical fixed 400px box. Touch fits the card to the viewport instead:
// on a 390px-wide phone the 400px ceiling renders a clipped, unreadable card. Never upscale past
// natural size — a 449x628 asset blown up reads blurry, and there is no detail to gain.
function ComputeCardDetailSize(naturalW, naturalH, touch) {
  if (!touch) {
    var maxWidth = 400;
    var maxHeight = 400;
    var width = naturalW;
    var height = naturalH;
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
    return { width: width, height: height };
  }

  var availW = window.innerWidth * CARD_DETAIL_TOUCH_VIEWPORT_W;
  var availH = window.innerHeight * CARD_DETAIL_TOUCH_VIEWPORT_H;
  var scale = Math.min(availW / naturalW, availH / naturalH, 1);
  return { width: Math.round(naturalW * scale), height: Math.round(naturalH * scale) };
}

function PlaceCardDetail(el, cx, cy, w, h, avoidEl, touch) {
  if (!touch) {
    PositionCardDetail(el, cx, cy, w, h, avoidEl);
    return;
  }
  // Centered, not finger-anchored: PositionCardDetail's geometry exists to dodge the cursor,
  // which is meaningless for a touch that has already lifted.
  el.style.left = Math.max(0, Math.round((window.innerWidth - w) / 2)) + 'px';
  el.style.top = Math.max(0, Math.round((window.innerHeight - h) / 2)) + 'px';
}

// Purely visual dim behind a persistent touch preview — it signals "tap to close" and lifts the
// card off a busy board. Deliberately pointer-events:none: dismissal is handled at the document
// level in BeginCardDetailLongPress, which sidesteps the `#cardDetail { pointer-events: none
// !important }` rules in SWUDeck/AzukiDeck GameLayout.php that a clickable scrim would fight.
function EnsureCardDetailScrim() {
  var scrim = document.getElementById("cardDetailScrim");
  if (scrim) return scrim;
  scrim = document.createElement("div");
  scrim.id = "cardDetailScrim";
  scrim.style.cssText = "position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; " +
    "display:none; pointer-events:none; opacity:0; transition:opacity 0.2s;";
  document.body.appendChild(scrim);
  return scrim;
}

function ShowCardDetailScrim() {
  var scrim = EnsureCardDetailScrim();
  scrim.style.display = "block";
  // next frame, so the opacity transition actually runs
  window.requestAnimationFrame(function() { scrim.style.opacity = 1; });
}

function HideCardDetailScrim() {
  var scrim = document.getElementById("cardDetailScrim");
  if (!scrim) return;
  scrim.style.opacity = 0;
  scrim.style.display = "none";
}

// Mock (preview) cards store their art as mock_<CardID>.webp — the CardID itself is never
// prefixed, so anywhere a filename is built from a CardID must resolve it through here.
// window.MockCardImageIDs is emitted by zzCardCodeGenerator into the client card dictionary;
// absent (real cards, or other games) means the CardID is returned unchanged.
// CardID -> art filename stem. Mirrors SWUCardImageID() in AppCore/SWU/CardImagePath.php.
//
// The shared corpus (AppCore/SWU/Images/) is SET_NNN-named, so a STORED FFG UID — deck files and
// stats rows keep theirs until the identity migration — must be normalised toward SET_NNN first.
// SWUNormalizeDictionaryKey is emitted for SWUDeck only; SWUSim's ids are already SET_NNN, so the
// guard makes this a no-op there.
function resolveCardImageID(cardID) {
  if (!cardID) return cardID;

  // A deployed leader renders its UNIT side as "<CardID>_back" (SWUArenaDisplayCardID), so the id
  // reaching here is not always a bare CardID. Split the suffix off before any lookup, then put it
  // back: "2579145458_back" -> "SOR_005_back", "HMW_004_back" -> "mock_HMW_004_back".
  var suffix = '';
  var id = String(cardID);
  var m = /^(.*)(_back)$/.exec(id);
  if (m) { id = m[1]; suffix = m[2]; }

  if (typeof SWUNormalizeDictionaryKey === 'function') id = SWUNormalizeDictionaryKey(id);

  var mocks = (typeof window !== 'undefined' && window.MockCardImageIDs) || null;
  if (mocks && mocks[id]) return 'mock_' + id + suffix;
  return id + suffix;
}

// ── Persistent touch-preview controls: close button + double-sided card flip ─────────────────
//
// Two gaps this closes on touch. (1) The preview is dismissed by "tap anywhere"
// (BeginCardDetailLongPress) — effective but undiscoverable, so give it an explicit X.
// (2) SWU leaders are DOUBLE-SIDED: the deployed Leader Unit side ships in the shared art corpus
// as "<CardID>_back" (160 of them), and on a phone there is otherwise no way to look at it.
//
// Face detection is by ASSET PROBE, not by a leader list: we ask for the opposite face and only
// offer the flip if it actually loads. That keeps this working for both apps without shipping a
// client-side leader table, and it flips in BOTH directions — SWUSim renders a deployed leader's
// tile as "_back" already, so there the button offers the leader side instead.
var cardDetailFaceProbeCache = {};

function CardDetailOppositeFace(src) {
  if (!src) return null;
  return /_back(\.[a-z0-9]+)(\?|$)/i.test(src)
    ? src.replace(/_back(\.[a-z0-9]+)/i, '$1')
    : src.replace(/(\.[a-z0-9]+)(\?|$)/i, '_back$1$2');
}

function ProbeCardDetailFace(src, cb) {
  if (Object.prototype.hasOwnProperty.call(cardDetailFaceProbeCache, src)) return cb(cardDetailFaceProbeCache[src]);
  var probe = new Image();
  probe.onload = function() { cardDetailFaceProbeCache[src] = true; cb(true); };
  probe.onerror = function() { cardDetailFaceProbeCache[src] = false; cb(false); };
  probe.src = src;
}

function CardDetailControlStyle(extra) {
  // pointer-events:auto is load-bearing: ShowDetail sets #cardDetail to pointer-events:none for
  // touch previews, so a control inherits "untappable" unless it opts back in.
  return "pointer-events:auto; -webkit-tap-highlight-color:transparent; cursor:pointer; " +
    "font-family:inherit; border:1px solid rgba(255,255,255,0.55); color:#fff; " +
    "background:rgba(0,0,0,0.72); box-shadow:0 2px 8px rgba(0,0,0,0.5); " + (extra || "");
}

// `place` re-centres the preview after a flip, since the two faces can differ in aspect ratio.
// `token` is the caller's cardDetailRequestToken: the asset probe is async, so without it a slow
// probe could append a flip button onto a preview the user has since replaced with another card.
function AddCardDetailControls(el, imgSource, place, token) {
  var current = function() { return token === cardDetailRequestToken && cardDetailPersistent; };
  var close = document.createElement("button");
  close.type = "button";
  close.setAttribute("data-card-detail-control", "close");
  close.setAttribute("aria-label", "Close card preview");
  close.textContent = "✕";
  close.style.cssText = CardDetailControlStyle(
    "position:absolute; top:-14px; right:-14px; width:34px; height:34px; border-radius:50%; " +
    "font-size:16px; line-height:1; display:flex; align-items:center; justify-content:center; z-index:2;");
  close.addEventListener("click", function(ev) { ev.stopPropagation(); HideCardDetail(true); });
  el.appendChild(close);

  var opposite = CardDetailOppositeFace(imgSource);
  if (!opposite || opposite === imgSource) return;

  ProbeCardDetailFace(opposite, function(exists) {
    // The preview may already have been dismissed while the probe was in flight.
    if (!exists || !current()) return;
    var showingBack = /_back\.[a-z0-9]+(\?|$)/i.test(imgSource);
    var faces = showingBack ? [imgSource, opposite] : [opposite, imgSource];  // [back, front]
    var onBack = showingBack;

    var flip = document.createElement("button");
    flip.type = "button";
    flip.setAttribute("data-card-detail-control", "flip");
    flip.style.cssText = CardDetailControlStyle(
      "position:absolute; left:50%; transform:translateX(-50%); bottom:-46px; white-space:nowrap; " +
      "padding:9px 16px; border-radius:999px; font-size:14px; font-weight:600; z-index:2;");
    var label = function() { flip.textContent = onBack ? "See Leader side" : "See Leader Unit side"; };
    label();

    flip.addEventListener("click", function(ev) {
      ev.stopPropagation();
      onBack = !onBack;
      label();
      var next = onBack ? faces[0] : faces[1];
      var img = el.querySelector("img");
      if (!img) return;
      // Size from the NEW face's natural dimensions — the two sides are not always the same shape.
      var loader = new Image();
      loader.onload = function() {
        if (!current()) return;
        var size = ComputeCardDetailSize(loader.width, loader.height, true);
        img.style.height = size.height + "px";
        img.style.width = size.width + "px";
        img.src = next;
        if (typeof place === "function") place(size.width, size.height);
      };
      loader.src = next;
    });
    el.appendChild(flip);
  });
}

function ShowDetail(e, imgSource, avoidEl, requestToken) {
  if (IsCardDetailSuppressed()) return;
  if (typeof requestToken !== "number") requestToken = ++cardDetailRequestToken;
  TrackCardDetailMouse(e);
  var originalSource = imgSource;   // tile art, kept as the onerror fallback
  imgSource = imgSource.replace("_cropped", "");
  imgSource = imgSource.replace("/crops/", "/WebpImages/");
  imgSource = imgSource.replace("_concat", "");
  imgSource = imgSource.replace("/concat/", "/WebpImages/");
  imgSource = imgSource.replace(".png", ".webp");
  var el = document.getElementById("cardDetail");
  var cx = e.clientX, cy = e.clientY; // capture: pointer may move before the image loads
  var touch = IsTouchPreviewEvent(e);
  // Claim persistence NOW, not in img.onload. EndCardDetailLongPress runs when the finger lifts and
  // hides any preview that is not yet persistent — so an image still in flight at that moment was
  // killed the instant it arrived. That is the normal case in the CARDS library, whose tiles are
  // /concat/ art while the preview loads a different /WebpImages/ file: uncached, the load lands
  // after touchend and the long-press appears to do nothing. (The Leaders pane hid this bug — it
  // already renders WebpImages, so the preview art was always cached and onload was synchronous.)
  if (touch) cardDetailPersistent = true;
  el.style.display = "none";
  el.style.zIndex = 100000;
  var img = new Image();
  img.onload = function() {
    // Ignore an image load that completed after another card was entered or the pointer left.
    if (requestToken !== cardDetailRequestToken) return;
    //Original dimension: height:523px; width:375px;
    var size = ComputeCardDetailSize(img.width, img.height, touch);
    var width = size.width;
    var height = size.height;

    el.innerHTML = "<img style='height:" + height + "px; width:" + width + "px;' src='" + imgSource + "' />";
    PlaceCardDetail(el, cx, cy, width, height, avoidEl, touch);
    if (touch) {
      cardDetailPersistent = true;
      // #cardDetail computes pointer-events:auto on the mobile layout — the
      // `pointer-events:none !important` rule in SWUDeck/Custom/GameLayout.php:663 does not reach
      // GameLayoutMobile.php. Left interactive, the centered preview sits under the pointer and
      // ping-pongs: it covers the card (mouseout -> hide), which uncovers the card (mouseover ->
      // show), forever. Set it here rather than in CSS so only the touch preview is affected.
      el.style.pointerEvents = "none";
      ShowCardDetailScrim();
      AddCardDetailControls(el, imgSource, function(w, h) {
        PlaceCardDetail(el, cx, cy, w, h, avoidEl, true);
      }, requestToken);
    }
    el.style.display = "inline";
    el.style.opacity = 0;
    showDetailTimeout = setTimeout(function() {
      if (requestToken !== cardDetailRequestToken) return;
      el.style.transition = "opacity 0.5s";
      el.style.opacity = 1;
    }, 100);
  };
  // The /concat/ -> /WebpImages/ rewrite above is string-based and never checks the asset exists.
  // Fall back to the tile art rather than showing an empty frame when a full card is missing.
  img.onerror = function() {
    if (requestToken !== cardDetailRequestToken) return;
    if (img.src === originalSource) return;   // already the fallback; give up
    img.src = originalSource;
  };
  img.src = imgSource;
}

function ShowSubcardDetail(e, imgEl, options) {
  options = options || {};
  if (ShouldIgnoreCardDetailEvent(e, options)) return;
  if (IsCardDetailSuppressed()) return;
  var requestToken = ++cardDetailRequestToken;
  TrackCardDetailMouse(e);
  clearTimeout(showDetailTimeout);
  showDetailTimeout = setTimeout(function() {
    if (requestToken !== cardDetailRequestToken) return;
    if (IsCardDetailSuppressed()) return;
    var src = imgEl.getAttribute('src') || '';
    // Transform concat URL to WebpImages for the popup
    src = src.replace('/concat/', '/WebpImages/');
    src = src.replace('.webp', '.webp'); // Keep as webp
    var el = document.getElementById('cardDetail');
    // Subcards have no natural dimensions to hand ComputeCardDetailSize (the image is not
    // preloaded here), so feed it the standard SWU portrait ratio: 0.71 wide per 1 tall.
    var touch = IsTouchPreviewEvent(e);
    var size = ComputeCardDetailSize(Math.round(400 * 0.71), 400, touch);
    var displayWidth = size.width;
    var displayHeight = size.height;
    el.innerHTML = "<img style='height:" + displayHeight + "px; width:" + displayWidth + "px;' src='" + src + "' />";
    if (touch) {
      cardDetailPersistent = true;
      ShowCardDetailScrim();
    }
    el.style.display = 'inline';
    el.style.opacity = 0;
    showDetailTimeout = setTimeout(function() {
      if (requestToken !== cardDetailRequestToken) return;
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = 1;
    }, 100);
    PlaceCardDetail(el, e.clientX, e.clientY, displayWidth, displayHeight, null, touch);
    el.style.zIndex = 100000;
  }, options.skipDelay ? 0 : 1);
}

function HideCardDetail(force) {
  if (!force && freezeCardDetailUntilMouseMove) return;
  // A persistent touch preview is dismissed only by an explicit force call — the tap handled in
  // BeginCardDetailLongPress, or a rotation. Every card carries an inline onmouseout=
  // 'HideCardDetail()' (UILibraries:297), and touch platforms fire a SYNTHETIC mouseout when the
  // finger moves between cards; without this guard that stray event closes a preview the user
  // just opened, so the second and later long-presses flash and vanish. ShowCardDetail already
  // guards the mirror case via suppressMouseCardDetailUntil.
  if (!force && cardDetailPersistent) return;
  cardDetailRequestToken++;
  clearTimeout(showDetailTimeout);
  var el = document.getElementById("cardDetail");
  el.style.display = "none";
  el.style.pointerEvents = "";   // release the touch-preview override; desktop keeps its own rules
  // Drop the touch controls rather than leaving them parked in a hidden #cardDetail. ShowDetail
  // rebuilds innerHTML and would clear them anyway, but any path that reveals the preview WITHOUT
  // rebuilding (a subcard preview, a re-show of the same card) would otherwise surface the previous
  // card's buttons — including a leader flip on a card that has no second face.
  var stale = el.querySelectorAll ? el.querySelectorAll("[data-card-detail-control]") : [];
  for (var i = 0; i < stale.length; i++) {
    if (stale[i].parentNode) stale[i].parentNode.removeChild(stale[i]);
  }
  cardDetailPersistent = false;
  HideCardDetailScrim();
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
  // Arm the synthetic-mouse suppression FIRST, before any early return. Touch platforms emit
  // mousemove/mouseover after a tap, and #cardDetail is pointer-events:none, so those land on the
  // card *underneath* the preview and re-fire its inline onmouseover='ShowCardDetail'. That
  // rebuilds #cardDetail's innerHTML — silently destroying the controls we just added. (Measured:
  // tapping flip bumped cardDetailRequestToken 9 -> 10 and left #cardDetail with only its <img>.)
  suppressMouseCardDetailUntil = Date.now() + 900;

  // The preview's own controls (X, leader flip) must be allowed to handle their tap. This handler
  // is registered at document level in CAPTURE phase, so without this exemption it would dismiss
  // the preview before the flip button's own click listener ever ran — the button would look dead.
  if (e && e.target && typeof e.target.closest === "function" &&
      e.target.closest("[data-card-detail-control]")) return;

  // A persistent touch preview is open: this tap dismisses it and does nothing else. Returning
  // early (rather than arming another long press) means the dismissing tap cannot immediately
  // reopen a preview on whatever card sits under the finger. The click suppression stops that
  // same tap from also adding or removing a card.
  if (cardDetailPersistent) {
    ClearCardDetailLongPress();
    HideCardDetail(true);
    suppressNextCardDetailClickUntil = Date.now() + 1200;
    return;
  }

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
  // A touch preview stays up after the finger lifts so the card can actually be read; it is
  // dismissed by the next tap (see BeginCardDetailLongPress). Desktop and any non-persistent
  // preview still hide here.
  if (!cardDetailPersistent) HideCardDetail(true);
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
  // The preview's own controls are exempt. This suppressor runs for up to 700ms after a long-press
  // (EndCardDetailLongPress), which is exactly when the player reaches for the X or the leader
  // flip — without this the button would be rendered, tappable, and silently dead on a real phone.
  if (e && e.target && typeof e.target.closest === "function" &&
      e.target.closest("[data-card-detail-control]")) return;
  if (Date.now() >= suppressNextCardDetailClickUntil) return;
  suppressNextCardDetailClickUntil = 0;
  if (e && typeof e.preventDefault === "function") e.preventDefault();
  if (e && typeof e.stopPropagation === "function") e.stopPropagation();
}, true);

// Long-press on a card belongs to our preview, not to the browser's image menu. iOS Safari is
// handled in CSS (-webkit-touch-callout, SharedUI/css/card-touch.css); Android Chrome ignores
// that property and opens its own "Open image in new tab / Download image" menu on long-press,
// so cancel contextmenu for cards here. Only cards: long-press on ordinary page text or links
// keeps native behavior, and desktop right-click on a card is suppressed as a harmless side
// effect (there is no card context menu in any app).
document.addEventListener("contextmenu", function(e) {
  if (!FindLongPressCardDetailTarget(e)) return;
  if (typeof e.preventDefault === "function") e.preventDefault();
}, true);

// Cards are marked draggable='true' for desktop drag-and-drop, and iOS starts an HTML5 drag from
// a long-press — the very gesture that is supposed to open a preview. Before the callout was
// suppressed this never surfaced, because the native image menu consumed the gesture first; now
// it fires dragStart() and paints the yellow dashed .droppable targets instead.
//
// CSS (-webkit-user-drag, SharedUI/css/card-touch.css) is only a hint that WebKit may ignore when
// draggable='true' is set explicitly, so enforce it here. Gated on the same coarse-pointer /
// no-hover query so mouse drag-and-drop on desktop is untouched.
function IsCoarsePointerDevice() {
  return !!(window.matchMedia && window.matchMedia("(hover: none) and (pointer: coarse)").matches);
}

document.addEventListener("dragstart", function(e) {
  if (!IsCoarsePointerDevice()) return;
  if (!FindLongPressCardDetailTarget(e)) return;
  if (typeof e.preventDefault === "function") e.preventDefault();
}, true);

// A persistent preview is sized and centered against the viewport it opened in, so a rotation
// leaves it mispositioned. Dismiss rather than resize: rotating mid-inspect is rare, and losing
// the preview is a cheaper outcome than a half-resized frame.
window.addEventListener("orientationchange", function() {
  if (cardDetailPersistent) HideCardDetail(true);
});
window.addEventListener("resize", function() {
  if (cardDetailPersistent) HideCardDetail(true);
});

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
var _chatToastBaselineID = 0;

function InitializeChatNotifications(baselineID) {
  var parsedBaseline = parseInt(baselineID || 0, 10);
  _chatToastBaselineID = Number.isNaN(parsedBaseline) ? 0 : parsedBaseline;
}

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
      var messageID = parseInt(m.id || 0, 10);
      var isNewSincePageLoad = !Number.isNaN(messageID) && messageID > _chatToastBaselineID;
      _AppendChatMessage(m, isNewSincePageLoad);
    }
    if (m.id > _lastChatID) _lastChatID = m.id;
  }
  if (version > _lastChatVersion) _lastChatVersion = version;
  // Neutral "chat disabled" state (e.g. a player was blocked). Never reveals why.
  var ci = document.getElementById("chatText");
  if (ci) {
    var sendButton = document.getElementById("chatSendBtn");
    if (payload.chatDisabled) {
      if (!ci.disabled) { ci.dataset.ph = ci.placeholder || ""; ci.placeholder = "Chat disabled"; }
      ci.disabled = true;
      if (sendButton) sendButton.disabled = true;
    } else if (ci.disabled) {
      ci.disabled = false;
      if (sendButton) sendButton.disabled = false;
      if (ci.dataset.ph !== undefined) ci.placeholder = ci.dataset.ph;
    }
  }
  return msgs.length > 0 || version > 0;
}

function _ChatPlayerLabel(msg) {
  var seatName = (window.SWU_SEAT_USERNAMES && (msg.playerID === 1 || msg.playerID === 2 || msg.playerID === "1" || msg.playerID === "2"))
    ? window.SWU_SEAT_USERNAMES[String(msg.playerID)] : null;
  return seatName ? seatName : (msg.playerLabel ? msg.playerLabel : ("P" + msg.playerID));
}

function _ChatHistoryIsOpen() {
  var expanded = document.getElementById("chatExpanded");
  return !!expanded && expanded.style.display === "flex";
}

function _ClearChatToasts() {
  var host = document.getElementById("chatToastHost");
  if (host) host.replaceChildren();
}

function _PositionChatToastHost(host) {
  var controls = document.getElementById("chatWidgetControls");
  if (!controls || controls.getClientRects().length === 0) return false;
  var rect = controls.getBoundingClientRect();
  var gap = 8;
  var edge = 8;
  var width = Math.min(300, Math.max(220, rect.width), window.innerWidth - edge * 2);
  host.style.width = width + "px";
  host.style.left = Math.max(edge, Math.min(rect.left, window.innerWidth - width - edge)) + "px";
  if (rect.top < window.innerHeight / 2) {
    host.style.top = (rect.bottom + gap) + "px";
    host.style.bottom = "auto";
  } else {
    host.style.top = "auto";
    host.style.bottom = (window.innerHeight - rect.top + gap) + "px";
  }
  return true;
}

function _ShowChatToast(msg) {
  if (_ChatHistoryIsOpen()) return;
  var host = document.getElementById("chatToastHost");
  if (!host || !_PositionChatToastHost(host)) return;

  var toast = document.createElement("div");
  toast.className = "chatToast";
  toast.setAttribute("role", "status");
  toast.title = "Open chat history";
  var label = document.createElement("span");
  label.className = "chatToastLabel";
  label.textContent = _ChatPlayerLabel(msg) + ":";
  var body = document.createElement("span");
  body.textContent = msg.text;
  toast.appendChild(label);
  toast.appendChild(body);
  toast.addEventListener("click", function() {
    if (!_ChatHistoryIsOpen() && typeof _ToggleChat === "function") _ToggleChat();
  });
  host.appendChild(toast);

  while (host.children.length > 3) host.removeChild(host.firstElementChild);
  window.setTimeout(function() {
    if (!toast.isConnected) return;
    toast.classList.add("is-leaving");
    window.setTimeout(function() { if (toast.isConnected) toast.remove(); }, 200);
  }, 5000);
}

function _AppendChatMessage(msg, notify) {
  var log = document.getElementById("chatLog");
  if (!log) return;
  var div = document.createElement("div");
  div.className = "chatMsg chatMsg-p" + msg.playerID;
  div.style.cssText = "padding:2px 4px; word-break:break-word; font-size:13px;";
  var label = document.createElement("span");
  label.style.cssText = "font-weight:700; margin-right:4px;";
  // Prefer the seat's username (SWUSim) so chat reads from real names; fall back to P#/label.
  label.textContent = _ChatPlayerLabel(msg) + ":";
  var body = document.createElement("span");
  body.textContent = msg.text;
  div.appendChild(label);
  div.appendChild(body);
  log.appendChild(div);
  log.scrollTop = log.scrollHeight;
  if (notify && !_ChatHistoryIsOpen()) {
    _ShowChatToast(msg);
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
  // A submitted answer ends the current prompt immediately. Reset the delayed
  // undo affordance here so a following identical prompt gets its own delay.
  if (String(mode).toUpperCase() === 'DECISION'
      && typeof ResetDelayedDecisionUndoAffordance === 'function') {
    ResetDelayedDecisionUndoAffordance();
  }
  SubmitEngineInput(mode, params, { fullRefresh: fullRefresh }).then(function() {
    if (!fullRefresh && typeof window.QueueGameUpdate === "function") window.QueueGameUpdate();
    if(_openPopup != null) RefreshPopupContent(_openPopup);
  }).catch(function(error) {
    if (window.console && console.error) console.error(error);
  });
}

function SetBotControllerState(state) {
  if (!state || typeof state !== "object") return;

  var players = Array.isArray(state.players) ? state.players.map(function(player) {
    return parseInt(player, 10);
  }).filter(function(player, index, allPlayers) {
    return (player === 1 || player === 2) && allPlayers.indexOf(player) === index;
  }) : [];
  var pendingPlayer = parseInt(state.pendingPlayer || 0, 10);
  if (players.indexOf(pendingPlayer) === -1) pendingPlayer = 0;

  window.BotController = {
    enabled: state.enabled === true && players.length > 0,
    mode: typeof state.mode === "string" ? state.mode : "",
    folderPath: typeof state.folderPath === "string" ? state.folderPath : "",
    players: players,
    pendingPlayer: pendingPlayer
  };

  if (!window.BotController.enabled || pendingPlayer === 0) {
    window.__botControllerRetryCount = 0;
    window.__botControllerRetryRequested = false;
    window.__botControllerRunRequested = false;
    if (window.__botControllerRetryTimer) {
      window.clearTimeout(window.__botControllerRetryTimer);
      window.__botControllerRetryTimer = null;
    }
  }
}

function ScheduleBotControllerRetry() {
  if (window.__botControllerRetryTimer) return;
  var controller = window.BotController || {};
  if (!controller.enabled || !controller.pendingPlayer) return;
  if (window.__botControllerStepInFlight) {
    window.__botControllerRetryRequested = true;
    return;
  }

  var retryCount = parseInt(window.__botControllerRetryCount || 0, 10);
  if (Number.isNaN(retryCount) || retryCount < 0) retryCount = 0;
  var delay = Math.min(5000, 250 * Math.pow(2, Math.min(retryCount, 5)));
  window.__botControllerRetryCount = retryCount + 1;
  window.__botControllerRetryTimer = window.setTimeout(function() {
    window.__botControllerRetryTimer = null;
    MaybeRunBotControllerStep();
  }, delay);
}

function MaybeRunBotControllerStep() {
  if (typeof window === "undefined") return;
  var controller = window.BotController || {};
  if (!controller.enabled) return;
  if (window.__botControllerRetryTimer) return;
  if (IsSpectatorClient()) return;

  var botPlayers = Array.isArray(controller.players) ? controller.players.map(function(player) {
    return parseInt(player, 10);
  }).filter(function(player) {
    return player === 1 || player === 2;
  }) : [];
  if (botPlayers.length === 0) return;

  var pendingPlayer = parseInt(controller.pendingPlayer || 0, 10);
  if (botPlayers.indexOf(pendingPlayer) === -1) return;
  if (window.__botControllerStepInFlight) {
    window.__botControllerRunRequested = true;
    return;
  }

  var folderPath = controller.folderPath || (typeof window.rootPath === "string" ? window.rootPath.replace(/^(\.\/|\/)/, "").replace(/\/.*$/, "") : "");
  if (folderPath === "") {
    if (window.console && console.warn) console.warn("Bot controller folder path is not available.");
    return;
  }

  window.__botControllerStepInFlight = true;
  window.__botControllerRunRequested = false;
  SubmitEngineInput(10017, "", {
    folderPath: folderPath,
    responseFormat: "json"
  }).then(function(response) {
    if (!response || response.success !== true) {
      if (response && response.botController) SetBotControllerState(response.botController);
      var message = response && response.message ? response.message : "Bot controller step failed.";
      if (window.console && console.warn) console.warn(message);
      if (!response || response.botStepRetryable !== false) ScheduleBotControllerRetry();
      return;
    }
    if (response.botStepApplied === true) {
      window.__botControllerRetryCount = 0;
      if (typeof window.QueueGameUpdate === "function") window.QueueGameUpdate();
    } else {
      if (response.botController) SetBotControllerState(response.botController);
      if ((window.BotController || {}).pendingPlayer) ScheduleBotControllerRetry();
    }
  }).catch(function(error) {
    if (window.console && console.error) console.error(error);
    ScheduleBotControllerRetry();
  }).finally(function() {
    window.__botControllerStepInFlight = false;
    if (window.__botControllerRetryRequested) {
      window.__botControllerRetryRequested = false;
      ScheduleBotControllerRetry();
    } else if (window.__botControllerRunRequested) {
      window.__botControllerRunRequested = false;
      MaybeRunBotControllerStep();
    }
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
