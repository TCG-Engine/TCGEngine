<?php
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../SWUDeck/Custom/DeckFormats.php';
require_once __DIR__ . '/../../Render/Auth.php';

include_once __DIR__ . '/MobileViewport.php';
?>
<script src="/TCGEngine/SharedUI/js/mobile-touch.js"></script>
<script src="/TCGEngine/SharedUI/js/pull-to-refresh.js"></script>
<script src="/TCGEngine/SharedUI/js/orientation-handler.js"></script>
<script src="/TCGEngine/SharedUI/js/card-zoom.js"></script>
<style>
.sciFiScroll::-webkit-scrollbar {
  width: 12px;
}

/* Ensure the track itself has rounded corners */
.sciFiScroll::-webkit-scrollbar-track {
  background: #000022;
  box-shadow: inset 0 0 5px #000;
  border-radius: 8px; /* Ensure rounded edges */
  overflow: hidden; /* Prevents clipping */
}
/* Modify the scrollbar thumb */
.sciFiScroll::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #2a4b8d, #001f4d);
  border-radius: 12px; /* Increase for more rounded effect */
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.5);
}

/* Smooth animation for hover */
.sciFiScroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #2a4b8d, #3a5b9d);
}

/* Optional: Handle the scrollbar corners */
.sciFiScroll::-webkit-scrollbar-corner {
  background: transparent; /* Prevents awkward edges */
}

.news-section {
  /* ...existing styles if any... */
}
@media (max-width: 700px) {
  .news-section {
    display: none !important;
  }
}
.card-search-mobile {
  display: none;
  padding-left: 10px;
  padding-right: 10px;
}
@media (max-width: 700px) {
  .card-search-mobile {
    display: block !important;
  }
  .right-pane .search-container {
    display: none !important;
  }
}
.deck-actions-mobile {
  display: none;
}
@media (max-width: 700px) {
  .deck-actions-desktop {
    display: none !important;
  }
  .deck-actions-mobile {
    display: table-cell !important;
    width: 1%;
    text-align: right;
    vertical-align: middle;
  }
  .deck-more-btn {
    background: #2a4b8d;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 4px #001f4d;
  }
}

/* ===== SWU HUD skin — chamfered cyan (matches the deck builder / SWUSim board) ===== */
/* Buttons: closed chamfer drawn with two negative-z pseudos, so it works on the plain
   <button>s here without markup changes. */
.tab-button, #createDeckButton, #importDeckButton,
.deck-actions-desktop button, .deck-more-btn {
  position: relative !important; z-index: 0 !important; isolation: isolate !important;
  border: 0 !important; border-radius: 0 !important; background: transparent !important;
  box-shadow: none !important;
  color: rgba(205,238,255,0.96) !important;
  filter: drop-shadow(0 0 3px rgba(110,190,255,0.30));
  transition: filter 150ms, color 150ms, transform 110ms !important;
  cursor: pointer;
}
.tab-button::before, #createDeckButton::before, #importDeckButton::before,
.deck-actions-desktop button::before, .deck-more-btn::before {
  content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
  clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px);
  background: rgba(140,210,255,0.80) !important;
}
.tab-button::after, #createDeckButton::after, #importDeckButton::after,
.deck-actions-desktop button::after, .deck-more-btn::after {
  content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
  clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
  background: rgba(20,42,70,0.95) !important;
}
.tab-button:hover, #createDeckButton:hover, #importDeckButton:hover,
.deck-actions-desktop button:hover, .deck-more-btn:hover {
  color: #fff !important; filter: drop-shadow(0 0 8px rgba(125,205,255,0.6)); transform: translateY(-1px);
}
.tab-button:hover::before, #createDeckButton:hover::before, #importDeckButton:hover::before,
.deck-actions-desktop button:hover::before, .deck-more-btn:hover::before { background: rgba(180,228,255,1) !important; }
/* Active tab — bright rim + lit fill */
.tab-button.active { color: #fff !important; }
.tab-button.active::before { background: rgba(180,228,255,1) !important; }
.tab-button.active::after  { background: rgba(30,64,104,0.96) !important; }
/* Partner (Patreon) tabs: every image tab gets the SAME width so no creator's button
   reads as bigger. Kept narrower than the "My Decks" tab (~92px) so that stays the widest;
   the widest logo (KTOD) scales down via max-width to fit. "My Decks" is text-only, so
   :has(img) leaves it untouched. */
.tab-button:has(img) {
  width: 72px !important;
  box-sizing: border-box !important;
  padding-left: 4px !important; padding-right: 4px !important;
  display: inline-flex !important; align-items: center !important; justify-content: center !important;
}
.tab-button:has(img) img { max-width: 100% !important; }
/* Vertical separator between "My Decks" (the text tab) and the partner-button cluster —
   a thin cyan HUD tick that fades out top and bottom, with a soft glow. Doesn't stretch
   or wrap oddly (flex: 0 0 auto), stays vertically centered with the buttons. */
.tab-divider {
  /* inline-block so width/height apply when the row is a plain inline row (desktop);
     flex props kick in when .tab-buttons is a flex container (mobile). */
  display: inline-block !important; vertical-align: middle !important;
  flex: 0 0 2px !important;
  align-self: center !important;
  width: 2px !important; min-width: 2px !important; height: 24px !important; margin: 0 8px !important;
  background: linear-gradient(to bottom,
    rgba(140,210,255,0) 0%, rgba(140,210,255,0.75) 22%,
    rgba(140,210,255,0.75) 78%, rgba(140,210,255,0) 100%);
  box-shadow: 0 0 6px rgba(120,200,255,0.5);
  border-radius: 1px;
}
/* Mobile: don't let the partner tabs stretch to fill the row (mobile-responsive.css sets
   flex: 1 1 auto). Pin them to their fixed width and pack from the left so the trailing
   empty space reads as "more creators can be added here". My Decks (text tab) stays natural
   width and remains the widest. */
@media (max-width: 768px) {
  .tab-buttons { justify-content: flex-start !important; }
  .tab-button:has(img) { flex: 0 0 72px !important; width: 72px !important; }
  .tab-button:not(:has(img)) { flex: 0 0 auto !important; }
}
/* Mobile "more" button: square chamfer instead of the round pill */
.deck-more-btn { border-radius: 0 !important; width: 34px !important; height: 30px !important; box-shadow: none !important;
  line-height: 1 !important; padding: 0 0 5px 0 !important; /* nudge the ⋮ glyph up to visual center */ }

/* Deck names — HUD sans-serif, but normal case (not all-caps). */
.deck-name, .deck-name span { font-family: Arial, Helvetica, sans-serif !important; text-transform: none !important; }
.deck-format-chip {
  display: inline-block;
  font-size: 11px;
  font-weight: bold;
  padding: 2px 8px;
  border-radius: 10px;
  margin-left: 8px;
  color: #001833;
  vertical-align: middle;
  white-space: nowrap;
}

/* Deck list: leader(s) + base as a staggered stack (full card art, same source as the builder's
   browse panes — not the small identity crop) instead of side-by-side thumbnails. The base sits
   behind, anchored top-right, so its name banner + aspect icon (both near a base card's top edge)
   stay visible past the leader(s) anchored in front, bottom-left. Specificity/!important needed to
   beat swudeck-overrides.css's .swu-main-menu .swu-deck-art img rule (height:80px!important). */
.swu-main-menu .swu-deck-art.swu-deck-stack {
  width: 1% !important;
  white-space: nowrap;
}
.swu-main-menu .swu-deck-art .swu-deck-stack-frame {
  position: relative !important;
  width: 112px !important;
  height: 80px !important;
  cursor: pointer !important;
  display: inline-block !important;
}
.swu-main-menu .swu-deck-art .swu-deck-stack-frame img {
  position: absolute !important;
  height: 58px !important;
  width: auto !important;
  object-fit: cover !important;
  border: 1px solid rgba(134,203,242,0.16) !important;
  border-radius: 4px !important;
  margin: 0 !important;
}
.swu-deck-stack-base {
  top: 0 !important;
  right: 0 !important;
  width: 82px !important;
  z-index: 1;
  object-position: top !important;
  box-shadow: 0 3px 9px rgba(0,0,0,0.38);
}
.swu-deck-stack-leader {
  bottom: 0 !important;
  left: 0 !important;
  height: 64px !important;
  width: 64px !important;
  z-index: 2;
  object-position: top left !important;
  box-shadow: -2px 2px 8px rgba(0,0,0,0.55);
}
.swu-deck-stack-leader-twin-a, .swu-deck-stack-leader-twin-b {
  width: 40px !important;
  height: 58px !important;
}
.swu-deck-stack-leader-twin-a { left: 0 !important; }
.swu-deck-stack-leader-twin-b { left: 40px !important; }

/* Create / Import buttons in the search row — match the search input's height. */
#createDeckButton, #importDeckButton {
  height: 40px !important; box-sizing: border-box !important; padding: 0 12px !important;
  display: inline-flex !important; align-items: center !important; justify-content: center !important;
}

/* Space out the desktop deck-action buttons (stats / copy / refresh / favorite / delete). */
.deck-actions-desktop button + button { margin-left: 6px !important; }

/* Delete deck action → red-tinted HUD (destructive). */
.deck-actions-desktop button[title='Delete'] { color: #ffe4e4 !important; filter: drop-shadow(0 0 4px rgba(230,95,95,0.45)) !important; }
.deck-actions-desktop button[title='Delete']::before { background: rgba(235,120,120,0.85) !important; }
.deck-actions-desktop button[title='Delete']::after  { background: rgba(52,20,26,0.95) !important; }
.deck-actions-desktop button[title='Delete']:hover::before { background: rgba(255,150,150,1) !important; }

/* Panels: thin cyan frame + faint glow. */
.left-pane .login.container.bg-black,
.right-pane .login.container.bg-black {
  border: 1px solid rgba(140,210,255,0.40) !important;
  box-shadow: 0 0 12px rgba(120,200,255,0.15) !important;
}

/* Text inputs + selects → cyan HUD fields. */
.left-pane input[type="text"], .right-pane input[type="text"],
.left-pane select, .right-pane select {
  background: rgba(20,42,70,0.55) !important; color: rgba(222,240,255,0.95) !important;
  border: 1px solid rgba(140,210,255,0.45) !important; border-radius: 0 !important;
}
.left-pane input[type="text"]::placeholder, .right-pane input[type="text"]::placeholder { color: rgba(160,195,225,0.6) !important; }

/* Top nav — HUD skin (same as hud.css; MainMenu uses its own chrome so it's duplicated here). */
.nav-bar .NavBarItem { color: rgba(205,238,255,0.92) !important; text-shadow: 0 0 5px rgba(120,200,255,0.30) !important; transition: color 150ms, text-shadow 150ms !important; }
.nav-bar .NavBarItem:hover { color: #fff !important; text-decoration: none !important; text-shadow: 0 0 10px rgba(140,215,255,0.70) !important; }
.nav-bar-user, .nav-bar-links { border: 1px solid rgba(140,210,255,0.35) !important; box-shadow: 0 0 10px rgba(120,200,255,0.12) !important; }
.dropdown-arrow { color: rgba(150,215,255,0.90) !important; }
.dropdown-content { background: rgba(14,26,44,0.98) !important; border: 1px solid rgba(140,210,255,0.55) !important; border-radius: 0 !important; box-shadow: 0 6px 18px rgba(0,0,0,0.6), 0 0 8px rgba(120,200,255,0.22) !important; }
.dropdown-content a { color: rgba(205,238,255,0.92) !important; }
.dropdown-content a:hover { background: rgba(30,64,104,0.90) !important; color: #fff !important; }
</style>

<?php
function LoadDecks() {
  $folderPath = "SWUDeck";
  if(!IsUserLoggedIn()) {
    echo("Log in to view your decks");
    return;
  }
  $allowedSorts = ['alpha_asc','alpha_desc','updated_desc','updated_asc','id_asc','id_desc'];
  $sortBy = isset($_GET['deckSort']) && in_array($_GET['deckSort'], $allowedSorts) ? $_GET['deckSort'] : 'id_desc';
  $decks = GetDecksByUserID(LoggedInUser(), $sortBy);
  $deckCodesJs = "";
  foreach ($decks as $d) {
    if (!empty($d["friendlyCode"])) {
      $deckCodesJs .= '"' . $d["assetIdentifier"] . '":"' . $d["friendlyCode"] . '",';
    }
  }
  echo "<script>window.SWU_DECK_CODES = Object.assign(window.SWU_DECK_CODES || {}, {" . $deckCodesJs . "});</script>";
  echo("<div class='sciFiScroll swu-deck-list'>");
  echo("<table class='swu-deck-table'>");
  $favoriteDecks = "";
  $otherDecks = "";
  foreach ($decks as $deck) {
    if($deck["assetStatus"] == 1) { // Check if it's deleted
      $thisDeck = "";
      $title = $deck["assetName"] != "" ? $deck["assetName"] : "Deck #" . $deck["assetIdentifier"] . " (Click to rename)";
      $thisDeck .= "<tr class='swu-deck-row' onclick=\"window.location='/TCGEngine/NextTurn.php?gameName=" . $deck["assetIdentifier"] . "&playerID=1&folderPath=SWUDeck';\">";
      $id = "deck" . $deck["assetIdentifier"] . "Title";
      $_deckNav = "event.stopPropagation(); window.location='/TCGEngine/NextTurn.php?gameName=" . $deck["assetIdentifier"] . "&playerID=1&folderPath=SWUDeck'; return false;";
      $thisDeck .= "<td class='swu-deck-art swu-deck-stack' colspan='2'>";
      $thisDeck .= "<div class='swu-deck-stack-frame' onclick=\"$_deckNav\">";
      // Base sits behind, anchored top-right, so its name banner + aspect icon (both near the
      // top of a base card) stay visible past the leader(s) in front. Leader(s) anchor
      // bottom-left, the natural focal point.
      if (!empty($deck["keyIndicator2"])) {
        $thisDeck .= "<img class='swu-deck-stack-base' src='" . SWUDeckWebpUrl($deck["keyIndicator2"]) . "' title='" . CardTitle($deck["keyIndicator2"]) . "' draggable='false' />";
      }
      if (!empty($deck["keyIndicator1"]) && !empty($deck["keyIndicator3"])) {
        // Twin Suns: both leaders side by side in front of the base.
        $thisDeck .= "<img class='swu-deck-stack-leader swu-deck-stack-leader-twin-a' src='" . SWUDeckWebpUrl($deck["keyIndicator1"]) . "' title='" . CardTitle($deck["keyIndicator1"]) . "' draggable='false' />";
        $thisDeck .= "<img class='swu-deck-stack-leader swu-deck-stack-leader-twin-b' src='" . SWUDeckWebpUrl($deck["keyIndicator3"]) . "' title='" . CardTitle($deck["keyIndicator3"]) . "' draggable='false' />";
      } else if (!empty($deck["keyIndicator1"])) {
        $thisDeck .= "<img class='swu-deck-stack-leader' src='" . SWUDeckWebpUrl($deck["keyIndicator1"]) . "' title='" . CardTitle($deck["keyIndicator1"]) . "' draggable='false' />";
      } else if (empty($deck["keyIndicator2"])) {
        $thisDeck .= "No Leader / No Base";
      }
      $thisDeck .= "</div>";
      $thisDeck .= "</td>";
      $deckFormat = $deck["format"] ?? "premier";
      $chipColor = htmlspecialchars(SWUDeckFormatColor($deckFormat), ENT_QUOTES);
      $chipLabel = htmlspecialchars(SWUDeckFormatDisplayName($deckFormat), ENT_QUOTES);
      $thisDeck .= "<td class='deck-name'><span id='" . $id . "'><span onclick='event.stopPropagation(); DeckNameClick(\"" . $id . "\")'>" . $title . "</span></span><span class='deck-format-chip' style='background-color: $chipColor;'>$chipLabel</span></td>";
      // Desktop action buttons
      $thisDeck .= "<td class='deck-actions-desktop' style='padding: 3px;'>";
      $thisDeck .= "<button title='Stats' onclick='event.stopPropagation(); window.location.href=\"/TCGEngine/$folderPath/DeckStats.php?gameName=" . $deck["assetIdentifier"] . "\"'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-bar-chart' viewBox='0 0 16 16'>
    <path d='M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z'/>
    </svg>";
      $thisDeck .= "</button>";
      $thisDeck .= "<button title='Copy Link' onclick='event.stopPropagation(); showCopyOptions(\"" . $deck["assetIdentifier"] . "\", event)'>";
      $thisDeck .= "<img src='/TCGEngine/Assets/Icons/clipboard-check.svg' width='16' height='16' alt='Copy Link' style='filter:invert(100%);' />";
      $thisDeck .= "</button>";
      $thisDeck .= "<button title='Generate Image' onclick='event.stopPropagation(); GenerateDeckImage(\"" . $deck["assetIdentifier"] . "\", event)'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-image' viewBox='0 0 16 16'>
    <path d='M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0'/>
    <path d='M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z'/>
    </svg>";
      $thisDeck .= "</button>";
      // "Export Card Text JSON" button — commented out for now; may re-add later.
      /*
      if (CheckLoggedInUserMod() === '') {
        $thisDeck .= "<button title='Export Card Text JSON' onclick='event.stopPropagation(); ShowCardTextJSON(\"" . $deck["assetIdentifier"] . "\")'>";
        $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-braces' viewBox='0 0 16 16'>
    <path d='M2.114 8.063V7.9c1.005-.102 1.497-.615 1.497-1.6V4.503c0-1.094.39-1.538 1.354-1.538h.273V2h-.376C3.25 2 2.49 2.759 2.49 4.352v1.524c0 1.094-.376 1.456-1.49 1.456v1.299c1.114 0 1.49.362 1.49 1.456v1.524c0 1.593.759 2.352 2.372 2.352h.376v-.964h-.273c-.964 0-1.354-.444-1.354-1.538V9.663c0-.984-.492-1.497-1.497-1.6M13.886 7.9v.163c-1.005.103-1.497.616-1.497 1.6v1.798c0 1.094-.39 1.538-1.354 1.538h-.273v.964h.376c1.613 0 2.372-.759 2.372-2.352v-1.524c0-1.094.376-1.456 1.49-1.456V7.332c-1.114 0-1.49-.362-1.49-1.456V4.352C13.51 2.759 12.75 2 11.138 2h-.376v.964h.273c.964 0 1.354.444 1.354 1.538V6.3c0 .984.492 1.497 1.497 1.6'/>
    </svg>";
        $thisDeck .= "</button>";
      }
      */
      if (!is_null($deck["assetSource"]) && !is_null($deck["assetSourceID"])) {
      $thisDeck .= "<button title='Refresh' onclick='event.stopPropagation(); RefreshDeck(\"" . $deck["assetIdentifier"] . "\", " . $deck["assetSource"] . ", \"" . $deck["assetSourceID"] . "\", event)'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-arrow-clockwise' viewBox='0 0 16 16'>
      <path fill-rule='evenodd' d='M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z'/>
      <path d='M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466'/>
      </svg>";
      $thisDeck .= "</button>";
      } else {
        $thisDeck .= "<button title='Refresh' style='background-color: grey;' disabled onclick='event.stopPropagation();'>";
        $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-arrow-clockwise' viewBox='0 0 16 16'>
        <path fill-rule='evenodd' d='M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z'/>
        <path d='M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466'/>
        </svg>";
        $thisDeck .= "</button>";
      }
      if($deck["assetFolder"] == 0) {
        $thisDeck .= "<button title='Favorite' onclick='event.stopPropagation(); MoveDeck(\"" . $id . "\", 1)'>";
        $thisDeck .= "<img src='/TCGEngine/Assets/Icons/heart.svg' width='16' height='16' alt='Favorite' style='filter: invert(100%);' />";
        $thisDeck .= "</button>";
      } else if($deck["assetFolder"] == 1) {
        $thisDeck .= "<button title='Favorite' onclick='event.stopPropagation(); MoveDeck(\"" . $id . "\", 0)'>";
        $thisDeck .= "<img src='/TCGEngine/Assets/Icons/heart-fill.svg' width='16' height='16' alt='Favorite' style='filter: invert(100%);' />";
        $thisDeck .= "</button>";
      }
      $thisDeck .= "<button title='Change Format' onclick='event.stopPropagation(); showFormatPicker(\"" . $deck["assetIdentifier"] . "\", \"" . $deckFormat . "\", event)'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-tag' viewBox='-1.5 -1 20 20'>
    <path d='M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0'/>
    <path d='M2.5 1A1.5 1.5 0 0 0 1 2.5v5.628a2.5 2.5 0 0 0 .732 1.767l6.5 6.5a2.5 2.5 0 0 0 3.536 0l5.628-5.628a2.5 2.5 0 0 0 0-3.536l-6.5-6.5A2.5 2.5 0 0 0 8.128 1zM2 2.5A.5.5 0 0 1 2.5 2h5.628a1.5 1.5 0 0 1 1.06.44l6.5 6.5a1.5 1.5 0 0 1 0 2.12L10.06 16.56a1.5 1.5 0 0 1-2.12 0l-6.5-6.5A1.5 1.5 0 0 1 2 8.128z'/>
    </svg>";
      $thisDeck .= "</button>";
      $thisDeck .= "<button title='Delete' onclick='event.stopPropagation(); DeleteDeck(\"" . $id . "\")'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3' viewBox='0 0 16 16'>
    <path d='M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5'/>
    <path d='M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1Z'/>
    <path d='M12.958 3l-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5ZM2.565 4.5a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L2.095 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5'/>
    </svg>";
      $thisDeck .= "</button>";
      $thisDeck .= "</td>";
      // Mobile dropdown button. NOTE: assetSource/assetFolder are numeric args passed UNQUOTED
      // into the onclick — a null (e.g. imported decks with no external source) would render as
      // an empty token and break the JS ("showDeckDropdown(this, ..., , ...)"). Encode nulls as
      // valid JS values so the handler still runs (canRefresh already guards the refresh action).
      $_ddAssetSource = is_null($deck['assetSource']) ? 'null' : intval($deck['assetSource']);
      $_ddAssetFolder = is_null($deck['assetFolder']) ? 0 : intval($deck['assetFolder']);
      $_ddCanRefresh = (!is_null($deck['assetSource']) && !is_null($deck['assetSourceID'])) ? 'true' : 'false';
      $_ddFormat = htmlspecialchars($deckFormat, ENT_QUOTES);
      $thisDeck .= "<td class='deck-actions-mobile' style='display: none;'><button class='deck-more-btn' title='More' aria-label='More actions for " . htmlspecialchars($title, ENT_QUOTES) . "' onclick='event.stopPropagation(); showDeckDropdown(this, \"$id\", \"{$deck['assetIdentifier']}\", {$_ddAssetSource}, \"{$deck['assetSourceID']}\", {$_ddAssetFolder}, {$_ddCanRefresh}, \"{$_ddFormat}\")'>⋮</button></td>";
      $thisDeck .= "</tr>";
      if($deck["assetFolder"] == 0) $otherDecks .= $thisDeck;
      else $favoriteDecks .= $thisDeck;
    }
  }
  echo($favoriteDecks);
  echo($otherDecks);
  echo("</table>");
  echo("</div>");
}

  function LoadPatreonDecks($patreonID) {
    $folderPath = "SWUDeck";
    $decks = GetDecksByPatreon($patreonID);
    echo("<div class='sciFiScroll' style='overflow-y: auto; max-height: calc(100vh - 380px);'>");
    echo("<table style='width: 100%; border-collapse: collapse;'>");
    foreach ($decks as $deck) {
      if($deck["assetStatus"] == 1) { // Check if it's deleted
      $title = $deck["assetName"] != "" ? $deck["assetName"] : "Deck #" . $deck["assetIdentifier"];
      echo("<tr style='border-bottom: 1px solid #002249; padding: 3px;'>");
      $id = "deck" . $deck["assetIdentifier"] . "Title";
      echo("<td style='padding: 3px;'><span id='" . $id . "'>" . $title . "</span></td>");
      echo("<td title='View' style='padding: 3px;'><button onclick=\"window.location.href='/TCGEngine/NextTurn.php?gameName=" . $deck["assetIdentifier"] . "&playerID=1&folderPath=SWUDeck'\">");
      echo("<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-eye' viewBox='0 0 16 16'><path d='M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 3a5 5 0 0 1 0 10A5 5 0 0 1 8 3z'/><path d='M8 5a3 3 0 1 0 0 6A3 3 0 0 0 8 5z'/></svg>");
      echo("</button></td>");
      echo("<td title='Stats' style='padding: 3px;'><button onclick='window.location.href=\"/TCGEngine/$folderPath/DeckStats.php?gameName=" . $deck["assetIdentifier"] . "\"'>");
      echo("<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-bar-chart' viewBox='0 0 16 16'>
      <path d='M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z'/>
      </svg>");
      echo("</button></td>");
      echo("<td title='Copy Link' style='padding: 3px;'><button onclick='CopyDeckLink(\"" . $deck["assetIdentifier"] . "\", event)'>");
      echo("<img src='/TCGEngine/Assets/Icons/clipboard-check.svg' width='16' height='16' alt='Copy Link' style='filter:invert(100%);' />");
      echo("</button></td>");
      echo("</tr>");
      }
    }
    echo("</table>");
    echo("</div>");
  }

  function GetDecksByUserID($userID, $sortBy = 'id_desc') {
    $conn = GetLocalMySQLConnection();
    $sortOptions = [
      'alpha_asc'    => "ORDER BY COALESCE(NULLIF(o.assetName,''), CONCAT('Deck #', o.assetIdentifier)) ASC",
      'alpha_desc'   => "ORDER BY COALESCE(NULLIF(o.assetName,''), CONCAT('Deck #', o.assetIdentifier)) DESC",
      'updated_desc' => 'ORDER BY lastUpdated DESC',
      'updated_asc'  => 'ORDER BY lastUpdated ASC',
      'id_asc'       => 'ORDER BY o.assetIdentifier ASC',
      'id_desc'      => 'ORDER BY o.assetIdentifier DESC',
    ];
    $orderClause = isset($sortOptions[$sortBy]) ? $sortOptions[$sortBy] : $sortOptions['id_desc'];
    $sql = "SELECT o.*
            FROM ownership o
            WHERE o.assetType = 1
            AND (o.assetOwner = ?
                 OR o.assetVisibility = (1000 + COALESCE((SELECT teamID FROM users WHERE usersId = ?), 0)))
            $orderClause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $decks = [];
    while ($row = $result->fetch_assoc()) {
      $decks[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $decks;
  }

  function GetDecksByPatreon($patreonID) {
    $conn = GetLocalMySQLConnection();
    $sql = "SELECT * FROM ownership WHERE assetVisibility = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patreonID);
    $stmt->execute();
    $result = $stmt->get_result();
    $decks = [];
    while ($row = $result->fetch_assoc()) {
      $decks[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $decks;
  }

  ?>
  <script>
    function showFlashMessage(message, event) {
      var flashMessage = document.createElement("div");
      flashMessage.innerText = message;
      flashMessage.style.position = "fixed";
      flashMessage.style.background = "#003366";
      flashMessage.style.color = "#fff";
      flashMessage.style.padding = "10px 24px";
      flashMessage.style.borderRadius = "8px";
      flashMessage.style.boxShadow = "0 0 10px rgba(0,0,0,0.5)";
      flashMessage.style.zIndex = 3000;
      flashMessage.style.fontSize = "18px";
      flashMessage.style.opacity = "0.97";

      if (window.innerWidth <= 768) {
        // Centered for mobile
        flashMessage.style.top = "50%";
        flashMessage.style.left = "50%";
        flashMessage.style.transform = "translate(-50%, -50%)";
      } else if (event && event.target) {
        var rect = event.target.getBoundingClientRect();
        flashMessage.style.top = rect.top - 35 + window.scrollY + "px";
        flashMessage.style.left = rect.left + 30 + window.scrollX + "px";
      } else {
        flashMessage.style.top = "20px";
        flashMessage.style.left = "50%";
        flashMessage.style.transform = "translateX(-50%)";
      }

      document.body.appendChild(flashMessage);
      setTimeout(function() {
        if (flashMessage.parentNode) flashMessage.parentNode.removeChild(flashMessage);
      }, 900);
    }

    function RefreshDeck(deckID, assetSource, assetSourceID, event) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/SWUDeck/RefreshImport.php?deckID=" + deckID + "&source=" + assetSource + "&sourceID=" + assetSourceID + "&playerID=1", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          showFlashMessage("Deck refreshed successfully!", event);
        }
      };
      xhr.send();
    }

    function showCopyOptions(deckID, event) {
      var optionsMenu = document.createElement("div");
      optionsMenu.style.position = "absolute";
      optionsMenu.style.backgroundColor = "#002249";
      optionsMenu.style.color = "#fff";
      optionsMenu.style.padding = "5px 10px";
      optionsMenu.style.border = "none";
      optionsMenu.style.boxShadow = "0 0 10px 2px #001f4d";
      optionsMenu.style.borderRadius = "4px";
      optionsMenu.style.top = event.clientY + "px";
      optionsMenu.style.left = event.clientX + "px";
      optionsMenu.style.zIndex = "1000";

      var copyLinkBtn = document.createElement("button");
      copyLinkBtn.innerText = "Copy Link";
      copyLinkBtn.style.marginRight = "10px";
      copyLinkBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckLink(deckID, event);
        document.body.removeChild(optionsMenu);
      };

      var copyKarabastBtn = document.createElement("button");
      copyKarabastBtn.innerText = "Copy Karabast Import Link";
      copyKarabastBtn.style.marginRight = "10px";
      copyKarabastBtn.onclick = function(e) {
        e.stopPropagation();
        CopyKarabastLink(deckID, event);
        document.body.removeChild(optionsMenu);
      };

      var copyTextBtn = document.createElement("button");
      copyTextBtn.innerText = "Copy Text";
      copyTextBtn.style.marginRight = "10px";
      copyTextBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckText(deckID, event);
        document.body.removeChild(optionsMenu);
      };

      var copyJsonBtn = document.createElement("button");
      copyJsonBtn.innerText = "Copy JSON";
      copyJsonBtn.style.marginRight = "10px";
      copyJsonBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckJSON(deckID, event);
        document.body.removeChild(optionsMenu);
      };

      optionsMenu.appendChild(copyLinkBtn);
      optionsMenu.appendChild(copyTextBtn);
      optionsMenu.appendChild(copyJsonBtn);
      optionsMenu.appendChild(copyKarabastBtn);
      document.body.appendChild(optionsMenu);

      document.addEventListener("click", function removeMenu(e) {
        if (document.body.contains(optionsMenu)) {
          document.body.removeChild(optionsMenu);
        }
        document.removeEventListener("click", removeMenu);
      });

      document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
          if (document.body.contains(optionsMenu)) {
            document.body.removeChild(optionsMenu);
          }
        }
      }, { once: true });
    }

    function showFormatPicker(deckID, currentFormat, event) {
      var existing = document.getElementById("formatPickerMenu");
      if (existing) existing.remove();

      var menu = document.createElement("div");
      menu.id = "formatPickerMenu";
      menu.style.position = "fixed";
      menu.style.backgroundColor = "#002249";
      menu.style.color = "#fff";
      menu.style.padding = "10px";
      menu.style.boxShadow = "0 0 10px 2px #001f4d";
      menu.style.borderRadius = "4px";
      menu.style.zIndex = "2000";
      var rect = (event.target.closest("button") || event.target).getBoundingClientRect();
      menu.style.top = rect.bottom + "px";
      menu.style.left = rect.left + "px";

      var options = Object.keys(SWU_DECK_FORMATS).map(function(id) {
        var selected = id === currentFormat ? " selected" : "";
        return '<option value="' + id + '"' + selected + '>' + SWU_DECK_FORMATS[id].displayName + '</option>';
      }).join('');

      menu.innerHTML = '<select id="formatPickerSelect" style="padding: 6px;">' + options + '</select>' +
        '<button onclick="applyFormatChange(\'' + deckID + '\')" style="margin-left: 8px; padding: 6px 12px;">Save</button>';
      document.body.appendChild(menu);

      document.getElementById("formatPickerSelect").focus();
      setTimeout(function() {
        document.addEventListener("click", function removeMenu(e) {
          if (menu.parentNode && !menu.contains(e.target)) menu.parentNode.removeChild(menu);
          document.removeEventListener("click", removeMenu);
        });
      }, 10);
    }

    function applyFormatChange(deckID) {
      var format = document.getElementById("formatPickerSelect").value;
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/AccountFiles/UpdateAssetFormat.php?assetID=" + deckID + "&assetType=1&format=" + encodeURIComponent(format), true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var menu = document.getElementById("formatPickerMenu");
          if (menu) menu.remove();
          location.reload();
        }
      };
      xhr.send();
    }

    function CopyDeckJSON(deckID, event) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/APIs/LoadDeck.php?deckID=" + deckID + "&setId=true", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var deckJSON = JSON.parse(xhr.responseText);
          var tempInput = document.createElement("textarea");
          tempInput.value = JSON.stringify(deckJSON, null, 2);
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand("copy");
          document.body.removeChild(tempInput);
          showFlashMessage("Deck JSON copied!", event);
        }
      };
      xhr.send();
    }

    function CopyDeckText(deckID, event) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/APIs/LoadDeck.php?deckID=" + deckID + "&format=text", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var tempInput = document.createElement("textarea");
          tempInput.value = xhr.responseText;
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand("copy");
          document.body.removeChild(tempInput);
          showFlashMessage("Deck text copied!", event);
        }
      };
      xhr.send();
    }

    async function convertBlobToPNG(blob) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          canvas.toBlob((pngBlob) => {
            if (pngBlob) {
              resolve(pngBlob);
            } else {
              reject(new Error('Canvas conversion failed'));
            }
          }, 'image/png');
        };
        img.onerror = function(error) {
          reject(new Error('Image load error: ' + error));
        };
        img.src = URL.createObjectURL(blob);
      });
    }

    async function CopyDeckImage(deckID, event) {
      try {
        const response = await fetch(`/TCGEngine/SWUDeck/CreateImage.php?gameName=${deckID}`);
        if (!response.ok) {
          showFlashMessage("Failed to load image!", event);
          return;
        }
        const blob = await response.blob();

        // If the image is JPEG, convert it to PNG.
        let imageBlob = blob;
        if (blob.type === "image/jpeg") {
          imageBlob = await convertBlobToPNG(blob);
        }

        const clipboardItem = new ClipboardItem({ "image/png": imageBlob });
        await navigator.clipboard.write([clipboardItem]);
        showFlashMessage("Deck image copied!", event);
      } catch (error) {
        console.error("Error copying image:", error);
        showFlashMessage("Failed to copy image!", event);
      }
    }

    async function copyDeckImageBlob(blob, event) {
      try {
        let imageBlob = blob;
        if (blob.type === "image/jpeg") {
          imageBlob = await convertBlobToPNG(blob);
        }
        const clipboardItem = new ClipboardItem({ "image/png": imageBlob });
        await navigator.clipboard.write([clipboardItem]);
        showFlashMessage("Deck image copied!", event);
      } catch (error) {
        console.error("Error copying image:", error);
        showFlashMessage("Failed to copy image!", event);
      }
    }

    const DECK_IMAGE_SORTS = [["cost","Cost"],["setnum","Set Number"],["power","Power"],["aspect","Aspect"],["name","Name"]];

    async function fetchDeckImageBlob(deckID, sort) {
      const response = await fetch(`/TCGEngine/SWUDeck/CreateImage.php?gameName=${deckID}&sort=${encodeURIComponent(sort)}`);
      if (!response.ok) throw new Error("load failed");
      const blob = await response.blob();
      if (!blob.type.startsWith("image/")) throw new Error("not an image");
      return blob;
    }

    function openDeckImageModal(deckID, blob, sort) {
      const overlay = document.createElement("div");
      overlay.id = "deckImageModalOverlay";
      overlay.style.cssText =
        "position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:5000;" +
        "display:flex;align-items:center;justify-content:center;padding:20px;";

      const panel = document.createElement("div");
      panel.style.cssText =
        "background:#002249;border-radius:8px;box-shadow:0 0 20px 4px #001f4d;" +
        "padding:16px;max-width:min(96vw,1600px);max-height:92vh;display:flex;" +
        "flex-direction:column;align-items:center;gap:12px;";

      let currentUrl = URL.createObjectURL(blob);
      let currentBlob = blob;
      const img = document.createElement("img");
      img.src = currentUrl;
      img.alt = "Deck image";
      img.style.cssText = "max-width:100%;max-height:70vh;height:auto;object-fit:contain;border-radius:4px;";

      // Sort control + regenerate
      const controls = document.createElement("div");
      controls.style.cssText = "display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:center;";
      const sortLabel = document.createElement("span");
      sortLabel.innerText = "Sort:";
      sortLabel.style.cssText = "color:#fff;";
      const sortSelect = document.createElement("select");
      sortSelect.style.cssText = "padding:6px;";
      DECK_IMAGE_SORTS.forEach(function(pair) {
        const o = document.createElement("option");
        o.value = pair[0]; o.innerText = pair[1];
        if (pair[0] === sort) o.selected = true;
        sortSelect.appendChild(o);
      });
      const regenBtn = document.createElement("button");
      regenBtn.innerText = "Generate New Image";
      regenBtn.style.cssText = "padding:8px 18px;cursor:pointer;";
      regenBtn.onclick = async function(e) {
        e.stopPropagation();
        regenBtn.disabled = true;
        const prev = regenBtn.innerText;
        regenBtn.innerText = "Generating…";
        try {
          const newBlob = await fetchDeckImageBlob(deckID, sortSelect.value);
          URL.revokeObjectURL(currentUrl);
          currentUrl = URL.createObjectURL(newBlob);
          currentBlob = newBlob;
          img.src = currentUrl;
        } catch (err) {
          console.error("Error regenerating image:", err);
          showFlashMessage("Failed to load image!", e);
        }
        regenBtn.disabled = false;
        regenBtn.innerText = prev;
      };

      const btnRow = document.createElement("div");
      btnRow.style.cssText = "display:flex;gap:12px;";

      const copyBtn = document.createElement("button");
      copyBtn.innerText = "Copy Image";
      copyBtn.style.cssText = "padding:8px 18px;cursor:pointer;";
      copyBtn.onclick = function(e) { e.stopPropagation(); copyDeckImageBlob(currentBlob, e); };

      const closeBtn = document.createElement("button");
      closeBtn.innerText = "Close";
      closeBtn.style.cssText = "padding:8px 18px;cursor:pointer;";

      function close() {
        URL.revokeObjectURL(currentUrl);
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        document.removeEventListener("keydown", onEsc);
      }
      function onEsc(e) { if (e.key === "Escape") close(); }
      closeBtn.onclick = function(e) { e.stopPropagation(); close(); };
      overlay.onclick = function(e) { if (e.target === overlay) close(); };
      document.addEventListener("keydown", onEsc);

      controls.appendChild(sortLabel);
      controls.appendChild(sortSelect);
      controls.appendChild(regenBtn);
      btnRow.appendChild(copyBtn);
      btnRow.appendChild(closeBtn);
      panel.appendChild(img);
      panel.appendChild(controls);
      panel.appendChild(btnRow);
      overlay.appendChild(panel);
      document.body.appendChild(overlay);
    }

    // Full-screen spinner overlay, shown while the (slow, ~several second) deck-image render runs so
    // the button gives instant feedback instead of looking dead. Returns a handle with close().
    function showLoadingOverlay(message) {
      if (!document.getElementById("swuSpinKeyframes")) {
        const st = document.createElement("style");
        st.id = "swuSpinKeyframes";
        st.textContent = "@keyframes swuSpin{to{transform:rotate(360deg)}}";
        document.head.appendChild(st);
      }
      const overlay = document.createElement("div");
      overlay.id = "deckImageLoadingOverlay";
      overlay.style.cssText =
        "position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:5000;display:flex;" +
        "flex-direction:column;align-items:center;justify-content:center;gap:16px;";
      const spinner = document.createElement("div");
      spinner.style.cssText =
        "width:54px;height:54px;border:5px solid rgba(140,210,255,0.25);border-top-color:#8cd2ff;" +
        "border-radius:50%;animation:swuSpin 0.8s linear infinite;";
      const label = document.createElement("div");
      label.innerText = message || "Loading…";
      label.style.cssText = "color:#fff;font-size:16px;text-align:center;";
      overlay.appendChild(spinner);
      overlay.appendChild(label);
      document.body.appendChild(overlay);
      return { close: function() { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); } };
    }

    async function GenerateDeckImage(deckID, event) {
      if (window.__deckImageGenerating) return; // guard against double-trigger spawning two fetches
      window.__deckImageGenerating = true;
      const loader = showLoadingOverlay("Generating deck image…");
      try {
        const blob = await fetchDeckImageBlob(deckID, "cost");
        loader.close();
        openDeckImageModal(deckID, blob, "cost");
      } catch (error) {
        loader.close();
        console.error("Error generating image:", error);
        showFlashMessage("Failed to load image!", event);
      } finally {
        window.__deckImageGenerating = false;
      }
    }

    function copyTextToClipboard(text) {
      var tempInput = document.createElement("input");
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
    }

    function CopyDeckLink(deckID, event) {
      var code = (window.SWU_DECK_CODES || {})[deckID];
      var deckLink = code
        ? window.location.origin + "/deck/" + code
        : window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + deckID + "&playerID=1&folderPath=SWUDeck";
      copyTextToClipboard(deckLink);
      showFlashMessage("Link copied!", event);
    }

    function CopyKarabastLink(deckID, event) {
      var code = (window.SWU_DECK_CODES || {})[deckID];
      if (!code) { showFlashMessage("No import link for this deck yet.", event); return; }
      // ?gameName={code} is what Karabast's importer extracts; the /deck/{code} path still works in a browser.
      var link = window.location.origin + "/deck/" + code + "?gameName=" + code;
      copyTextToClipboard(link);
      showFlashMessage("Karabast import link copied!", event);
    }

  function DeckNameClick(id) {
    var currentName = document.getElementById(id).innerText;
    var el = document.getElementById(id);
    el.innerHTML = "<input type='text' id='deckNameInput' value='" + currentName + "' onblur='DeckNameSave(\"" + id + "\")' onkeypress='DeckNameKeypress(\"" + id + "\")' onclick='event.stopPropagation();' />";
    var input = document.getElementById("deckNameInput");
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);
  }

  function DeckNameKeypress(id) {
    if (event.key === 'Enter') {
      DeckNameSave(id);
    }
  }

  function DeckNameSave(id) {
    var newName = document.getElementById("deckNameInput").value;
    var el = document.getElementById(id);
    el.innerHTML = "<span onclick='DeckNameClick(\"" + id + "\")'>" + newName + "</span>";
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "/TCGEngine/AccountFiles/RenameAsset.php?assetID=" + id.replace("deck", "").replace("Title", "") + "&newName=" + encodeURIComponent(newName) + "&assetType=1", true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        console.log("Deck name updated successfully");
      }
    };
    xhr.send();
  }
  function DeleteDeck(id) {
    StyledConfirm("Are you sure you want to delete this deck?", {title: 'Delete deck', danger: true, confirmLabel: 'Delete'}).then(function(ok) {
      if (!ok) return;
      var deckID = id.replace("deck", "").replace("Title", "");
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/AccountFiles/DeleteAsset.php?assetID=" + deckID + "&assetType=1", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          console.log("Deck deleted successfully");
          location.reload();
        }
      };
      xhr.send();
    });
  }
  function MoveDeck(id, folderID) {
    var deckID = id.replace("deck", "").replace("Title", "");
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "/TCGEngine/AccountFiles/MoveAsset.php?assetID=" + deckID + "&assetType=1&folderID=" + folderID, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        console.log("Deck moved successfully");
        location.reload();
      }
    };
    xhr.send();
  }

  function applyDeckSort(value) {
    localStorage.setItem('deckSort', value);
    var url = new URL(window.location.href);
    url.searchParams.set('deckSort', value);
    window.location.href = url.toString();
  }

  document.addEventListener('DOMContentLoaded', function() {
    var urlParam = new URLSearchParams(window.location.search).get('deckSort');
    var stored = localStorage.getItem('deckSort');
    var allowed = ['id_desc','id_asc','updated_desc','updated_asc','alpha_asc','alpha_desc'];
    // URL param wins (PHP already sorted by it); otherwise restore a valid saved sort.
    if (!urlParam && stored && allowed.indexOf(stored) !== -1) applyDeckSort(stored);
  });

  function filterDecks() {
    var input = document.getElementById("deckSearchInput");
    var filter = input.value.toLowerCase();
    var table = document.querySelector(".sciFiScroll table");
    var tr = table.getElementsByTagName("tr");

    for (var i = 0; i < tr.length; i++) {
      // Match the name cell by class, not by index. The art cell carries
      // colspan='2', so the name sits at td[1] even though it is visually the
      // third column -- indexing td[2] read the (empty) actions cell and hid
      // every row on any non-empty query.
      var td = tr[i].querySelector("td.deck-name");
      if (td) {
        var txtValue = td.textContent || td.innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
          tr[i].style.display = "";
        } else {
          tr[i].style.display = "none";
        }
      }
    }
  }

  // Dropdown for mobile deck actions
  function showDeckDropdown(btn, id, deckID, assetSource, assetSourceID, assetFolder, canRefresh, currentFormat) {
    // Remove any existing dropdown
    var existing = document.getElementById('deckDropdownMenu');
    if (existing) existing.remove();
    var menu = document.createElement('div');
    menu.id = 'deckDropdownMenu';
    menu.style.position = 'fixed'; // Use fixed so it's always relative to viewport
    var rect = btn.getBoundingClientRect();
    // Place menu just below the button, relative to viewport
    var top = rect.bottom;
    var left = rect.left;
    // If menu would go off bottom, show above button
    var menuHeight = 240; // estimate, 5 items * 48px
    if (top + menuHeight > window.innerHeight) {
      top = rect.top - menuHeight;
    }
    // If menu would go off right, shift left
    var menuWidth = 180;
    if (left + menuWidth > window.innerWidth) {
      left = window.innerWidth - menuWidth - 8;
    }
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
    menu.style.background = '#002249';
    menu.style.color = '#fff';
    menu.style.borderRadius = '6px';
    menu.style.boxShadow = '0 2px 12px #001f4d';
    menu.style.padding = '6px 0';
    menu.style.zIndex = 2000;
    menu.style.minWidth = menuWidth + 'px';
    menu.style.fontSize = '16px';
    var icons = {
      stats: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-bar-chart' viewBox='0 0 16 16'><path d='M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z'/></svg>`,
      copy: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-clipboard2-check' viewBox='0 0 16 16'><path d='M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z'/><path d='M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z'/><path d='M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z'/></svg>`,
      image: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-image' viewBox='0 0 16 16'><path d='M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0'/><path d='M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z'/></svg>`,
      refresh: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-arrow-clockwise' viewBox='0 0 16 16'><path fill-rule='evenodd' d='M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z'/><path d='M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466'/></svg>`,
      favorite: assetFolder == 1
        ? `<img src='/TCGEngine/Assets/Icons/heart-fill.svg' width='18' height='18' style='vertical-align:middle;margin-right:10px;filter:invert(100%);' alt='Unfavorite' />`
        : `<img src='/TCGEngine/Assets/Icons/heart.svg' width='18' height='18' style='vertical-align:middle;margin-right:10px;filter:invert(100%);' alt='Favorite' />`,
      delete: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-trash3' viewBox='0 0 16 16'><path d='M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5'/><path d='M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1Z'/><path d='M12.958 3l-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5ZM2.565 4.5a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L2.095 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5'/></svg>`,
      tag: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='currentColor' style='vertical-align:middle;margin-right:10px;' class='bi bi-tag' viewBox='-1 -1 18 18'><path d='M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0'/><path d='M2.5 1A1.5 1.5 0 0 0 1 2.5v5.628a2.5 2.5 0 0 0 .732 1.767l6.5 6.5a2.5 2.5 0 0 0 3.536 0l5.628-5.628a2.5 2.5 0 0 0 0-3.536l-6.5-6.5A2.5 2.5 0 0 0 8.128 1zM2 2.5A.5.5 0 0 1 2.5 2h5.628a1.5 1.5 0 0 1 1.06.44l6.5 6.5a1.5 1.5 0 0 1 0 2.12L10.06 16.56a1.5 1.5 0 0 1-2.12 0l-6.5-6.5A1.5 1.5 0 0 1 2 8.128z'/></svg>`
    };
    menu.innerHTML = `
      <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); window.location.href="/TCGEngine/SWUDeck/DeckStats.php?gameName=${deckID}";'>${icons.stats}Stats</button>
      ${window.innerWidth <= 768 ? `
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); CopyDeckLink("${deckID}", event); showFlashMessage("Link copied!", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.copy}Copy Link</button>
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); CopyDeckText("${deckID}", event); showFlashMessage("Text copied!", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.copy}Copy Text</button>
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); CopyDeckJSON("${deckID}", event); showFlashMessage("Deck JSON copied!", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.copy}Copy JSON</button>
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); CopyKarabastLink("${deckID}", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.copy}Copy Karabast Import Link</button>
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); GenerateDeckImage("${deckID}", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.image}Generate Image</button>
      ` : `
        <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); showCopyOptions("${deckID}", event); setTimeout(()=>{if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();},200);'>${icons.copy}Copy Link/Export</button>
      `}
      <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' ${canRefresh ? '' : 'disabled style="color:#888;"'} onclick='event.stopPropagation(); if(${canRefresh}) RefreshDeck("${deckID}", ${assetSource}, "${assetSourceID}", event); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.refresh}Refresh</button>
      <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); MoveDeck("${id}", ${assetFolder == 1 ? 0 : 1}); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.favorite}${assetFolder == 1 ? 'Unfavorite' : 'Favorite'}</button>
      <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove(); showFormatPicker("${deckID}", "${currentFormat}", event);'>${icons.tag}Change Format</button>
      <button style='width:100%;background:none;border:none;color:#fff;padding:10px 16px;text-align:left;display:flex;align-items:center;' onclick='event.stopPropagation(); DeleteDeck("${id}"); if(document.getElementById("deckDropdownMenu"))document.getElementById("deckDropdownMenu").remove();'>${icons.delete}Delete</button>
    `;
    document.body.appendChild(menu);
    // Remove menu on click elsewhere
    setTimeout(function() {
      document.addEventListener('click', removeMenu, { once: true });
    }, 10);
    function removeMenu(e) {
      if (menu && menu.parentNode) menu.parentNode.removeChild(menu);
      window.removeEventListener('scroll', removeMenuOnScroll, true);
    }
    // Remove on escape
    document.addEventListener('keydown', function esc(e) {
      if (e.key === 'Escape') {
        if (menu && menu.parentNode) menu.parentNode.removeChild(menu);
        document.removeEventListener('keydown', esc);
        window.removeEventListener('scroll', removeMenuOnScroll, true);
      }
    });
    // Remove on scroll (anywhere in the window, including inside deck list)
    function removeMenuOnScroll() {
      if (menu && menu.parentNode) menu.parentNode.removeChild(menu);
      window.removeEventListener('scroll', removeMenuOnScroll, true);
    }
    window.addEventListener('scroll', removeMenuOnScroll, true);
  }

  async function ShowCardTextJSON(deckID) {
    // Show loading state
    var modal = document.createElement('div');
    modal.id = 'cardTextJsonModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:5000;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = '<div style="background:#001833;border:1px solid #2a4b8d;border-radius:8px;padding:20px;width:min(680px,92vw);max-height:80vh;display:flex;flex-direction:column;box-shadow:0 0 30px rgba(0,60,120,0.6);">' +
      '<div style="color:#aac8ff;font-size:16px;text-align:center;padding:20px;">Loading card text...</div>' +
      '</div>';
    document.body.appendChild(modal);

    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeCardTextJsonModal();
    });
    document.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape') { closeCardTextJsonModal(); document.removeEventListener('keydown', escHandler); }
    });

    try {
      var resp = await fetch('/TCGEngine/SWUDeck/GetCardTextJSON.php?deckID=' + encodeURIComponent(deckID));
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      var data = await resp.json();
      if (data.error) throw new Error(data.error);

      var jsonText = JSON.stringify(data, null, 2);

      var inner = document.querySelector('#cardTextJsonModal > div');
      inner.innerHTML =
        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">' +
          '<span style="color:#aac8ff;font-size:15px;font-weight:bold;">' + data.length + ' unique card(s)</span>' +
          '<div style="display:flex;gap:8px;">' +
            '<button onclick="copyCardTextJson()" style="background:#1a4a8a;color:#fff;border:none;padding:5px 12px;border-radius:5px;cursor:pointer;font-size:13px;" onmouseover="this.style.background=\'#2a5aaa\'" onmouseout="this.style.background=\'#1a4a8a\'">Copy</button>' +
            '<button onclick="downloadCardTextJson()" style="background:#1a4a8a;color:#fff;border:none;padding:5px 12px;border-radius:5px;cursor:pointer;font-size:13px;" onmouseover="this.style.background=\'#2a5aaa\'" onmouseout="this.style.background=\'#1a4a8a\'">Download</button>' +
            '<button onclick="closeCardTextJsonModal()" style="background:#5a1a1a;color:#fff;border:none;padding:5px 12px;border-radius:5px;cursor:pointer;font-size:13px;" onmouseover="this.style.background=\'#7a2a2a\'" onmouseout="this.style.background=\'#5a1a1a\'">Close</button>' +
          '</div>' +
        '</div>' +
        '<textarea id="cardTextJsonContent" readonly style="flex:1;width:100%;box-sizing:border-box;background:#000c1a;color:#cce0ff;border:1px solid #2a4b8d;border-radius:4px;padding:10px;font-family:monospace;font-size:12px;resize:none;min-height:400px;outline:none;">' +
          jsonText.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
        '</textarea>';
      inner.style.cssText = 'background:#001833;border:1px solid #2a4b8d;border-radius:8px;padding:20px;width:min(680px,92vw);max-height:80vh;display:flex;flex-direction:column;box-shadow:0 0 30px rgba(0,60,120,0.6);';

      // Store for copy/download
      window._cardTextJsonData = jsonText;
      window._cardTextJsonDeckID = deckID;
    } catch (err) {
      var inner = document.querySelector('#cardTextJsonModal > div');
      inner.innerHTML = '<div style="color:#ff8888;text-align:center;padding:20px;">Error: ' + err.message + '</div>' +
        '<div style="text-align:center;margin-top:10px;"><button onclick="closeCardTextJsonModal()" style="background:#5a1a1a;color:#fff;border:none;padding:5px 14px;border-radius:5px;cursor:pointer;">Close</button></div>';
    }
  }

  function closeCardTextJsonModal() {
    var m = document.getElementById('cardTextJsonModal');
    if (m) m.parentNode.removeChild(m);
  }

  function copyCardTextJson() {
    var ta = document.getElementById('cardTextJsonContent');
    if (!ta) return;
    navigator.clipboard.writeText(window._cardTextJsonData || ta.value).then(function() {
      showFlashMessage('Card text JSON copied!', null);
    }).catch(function() {
      ta.select();
      document.execCommand('copy');
      showFlashMessage('Card text JSON copied!', null);
    });
  }

  function downloadCardTextJson() {
    var text = window._cardTextJsonData || '';
    var blob = new Blob([text], { type: 'application/json' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'deck-' + (window._cardTextJsonDeckID || 'cards') + '-text.json';
    document.body.appendChild(a);
    a.click();
    setTimeout(function() { document.body.removeChild(a); URL.revokeObjectURL(a.href); }, 100);
  }
</script>

<div class="pageContainer swu-main-menu<?= IsUserLoggedIn() ? '' : ' swu-main-menu-logged-out' ?>">
<?php include_once __DIR__ . '/Header.php'; ?>
  <div class="core-wrapper">
    <!-- Left pane: Deck List -->
    <div class="left-pane">
      <div class="decks-section tabs" style="width: 100%; margin: 0 auto;">
        <!-- Card Search for mobile (above deck list) -->
        <div class="card-search-mobile swu-card-search-mobile">
          <input type="text" id="cardSearchInputMobile" placeholder="Search cards..."
                 style="width: 100%; padding: 10px; background-color: #002249; color: white;
                        border: 1px solid #2a4b8d; border-radius: 4px; cursor: pointer;"
                 readonly onclick="openCardSearch()">
        </div>
        <!-- ...tab buttons and deck list content... -->
        <div class="login container bg-black swu-deck-library-panel">
          <div class="swu-deck-tabs-heading">
            <div class="tab-buttons">
              <button class="tab-button active" onclick="switchTab('tab-decks', event)">My Decks</button>
              <?php
              $isKTODPatron = IsPatron("11987758");
              $isRebelResourcePatron = IsPatron("12716027");
              $isStubbHubbPatron = IsPatron("13088942");
              $isStarWarzDadPatron = IsPatron("12636483");
              ?>
              <div id="swuCreatorMenu" class="swu-creator-menu">
                <button id="swuCreatorTrigger" class="tab-button swu-creator-trigger" type="button" aria-haspopup="true" aria-expanded="false" onclick="toggleCreatorMenu(event)">
                  <span id="swuCreatorTriggerLabel">Creators</span><span class="swu-creator-chevron" aria-hidden="true"></span>
                </button>
                <div id="swuCreatorDropdown" class="swu-creator-dropdown" role="menu" aria-label="Content creator decks">
                  <button class="swu-creator-option" type="button" role="menuitem" data-creator-label="KTOD" onclick="switchTab('tab-ktod', event)"><img src="/TCGEngine/Assets/Images/logos/KTODLogo.webp" alt=""><span>KTOD</span></button>
                  <button class="swu-creator-option" type="button" role="menuitem" data-creator-label="Rebel Resource" onclick="switchTab('tab-rebel', event)"><img src="/TCGEngine/Assets/Images/logos/RebelResourceLogo.webp" alt=""><span>Rebel Resource</span></button>
                  <button class="swu-creator-option" type="button" role="menuitem" data-creator-label="L8 Night Gaming" onclick="switchTab('tab-L8Night', event)"><img src="/TCGEngine/Assets/Images/logos/L8NightBanner.webp" alt=""><span>L8 Night Gaming</span></button>
                  <button class="swu-creator-option" type="button" role="menuitem" data-creator-label="Stubbs Hub" onclick="switchTab('tab-StubbHub', event)"><img src="/TCGEngine/Assets/Images/logos/StubbHub.webp" alt=""><span>Stubbs Hub</span></button>
                  <button class="swu-creator-option" type="button" role="menuitem" data-creator-label="Force Fam" onclick="switchTab('tab-StarWarzDad', event)"><img src="/TCGEngine/Assets/Images/logos/StarWarzDad.webp" alt=""><span>Force Fam</span></button>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content-container">
            <div id="tab-decks" class="tab-content" style="display: block;">
              <?php if (IsUserLoggedIn()): ?>
              <?php
                $allowedSortKeys = ['id_desc','id_asc','updated_desc','updated_asc','alpha_asc','alpha_desc'];
                $activeSortKey = isset($_GET['deckSort']) && in_array($_GET['deckSort'], $allowedSortKeys) ? $_GET['deckSort'] : null;
                $sortLabels = ['id_desc'=>'Date Created (Newest)','id_asc'=>'Date Created (Oldest)','updated_desc'=>'Last Updated (Recent)','updated_asc'=>'Last Updated (Oldest)','alpha_asc'=>'Alphabetical (A&rarr;Z)','alpha_desc'=>'Alphabetical (Z&rarr;A)'];
              ?>
              <div class="swu-deck-toolbar">
                <input type="text" id="deckSearchInput" placeholder="Search your decks..."
                      style="flex: 1; min-width: 0; margin: 0; padding: 10px; background-color: #002249; color: white;
                            border: 1px solid #2a4b8d; border-radius: 4px;"
                      onkeyup="filterDecks()">
                <button id="createDeckButton" onclick="createDeck()" title="Create New Deck" style="font-size: 18px; padding: 0 12px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                  </svg>
                </button>
                <button id="importDeckButton" onclick="importDeck()" title="Import Deck" style="font-size: 18px; padding: 0 12px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                    <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                  </svg>
                </button>
                <div id="swuSortMenu" class="swu-sort-menu">
                  <button id="swuSortTrigger" class="swu-sort-icon-control" type="button" title="Sort decks" aria-label="Sort decks, current: <?= htmlspecialchars($sortLabels[$activeSortKey ?? 'id_desc']) ?>" aria-haspopup="true" aria-expanded="false" onclick="toggleSortMenu(event)">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h10M4 11h7M4 16h4M17 5v13m0 0-3-3m3 3 3-3"/></svg>
                  </button>
                  <div class="swu-sort-dropdown" role="menu" aria-label="Sort decks by">
                    <?php foreach($sortLabels as $val => $label): ?>
                    <button type="button" role="menuitem" class="swu-sort-option<?= (($activeSortKey ?? 'id_desc') === $val) ? ' active' : '' ?>" onclick="applyDeckSort('<?= $val ?>')"><?= $label ?></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <div><?php LoadDecks(); ?></div>
              <?php else: ?>
                <?php
                  $mainMenuPath = '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php';
                  echo RenderEmbeddedSignup(LoadSiteDef('SWUDeck'), $mainMenuPath);
                ?>
              <?php endif; ?>
            </div>
            <div id="tab-ktod" class="tab-content" style="display: none;">
              <div>
                <?php
                  if ($isKTODPatron) {
                    if (isset($_SESSION["isWokling"]) && $_SESSION["isWokling"]) {
                      echo("<h3>Wokling Tier</h3>");
                      echo "<a href='https://www.patreon.com/c/ktod/membership' target='_blank'>Upgrade your tier to Kashyyyk Operative or above to see KTOD decks as they are built!</a>";
                    } else {
                      LoadPatreonDecks("11987758");
                    }
                  } else {
                    echo("<p>Subscribe to the <a href='https://www.patreon.com/c/ktod/membership' target='_blank'>KTOD Kashyyyk+ Tier</a> on Patreon to unlock exclusive access to in-progress decklists!</p>");
                  }
                ?>
              </div>
            </div>
            <div id="tab-rebel" class="tab-content" style="display: none;">
              <div>
                <?php
                  if ($isRebelResourcePatron) {
                    LoadPatreonDecks("12716027");
                  } else {
                    echo("<p>Subscribe to <a href='https://www.patreon.com/RebelResource' target='_blank'>Rebel Resource</a> on Patreon to unlock exclusive access decklists!</p>");
                  }
                ?>
              </div>
            </div>
            <div id="tab-L8Night" class="tab-content" style="display: none;">
              <div>
                <?php
                  LoadPatreonDecks("99999999");
                ?>
              </div>
            </div>
            <div id="tab-StubbHub" class="tab-content" style="display: none;">
              <div>
                <?php
                  if ($isStubbHubbPatron) {
                    LoadPatreonDecks("13088942");
                  } else {
                    echo("<p>Subscribe to <a href='https://patreon.com/stubbshub' target='_blank'>Stubb Hub</a> on Patreon to unlock exclusive access decklists!</p>");
                  }
                ?>
              </div>
            </div>
            <div id="tab-StarWarzDad" class="tab-content" style="display: none;">
              <div>
                <?php
                  if ($isStarWarzDadPatron) {
                    LoadPatreonDecks("12636483");
                  } else {
                    echo("<p>Subscribe to <a href='https://www.patreon.com/STARWARSDAD' target='_blank'>Force Fam</a> on Patreon to unlock exclusive access decklists!</p>");
                  }
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>    <!-- Right pane: Card Search and News -->
    <div class="right-pane">
      <div class="login container bg-black" style="margin-bottom: 20px;">
        <div class="search-container">
          <h3 style="margin-top: 0;">Card Search</h3>
          <input type="text" id="cardSearchInput" placeholder="Search cards..."
                 style="width: 100%; padding: 10px; background-color: #002249; color: white;
                        border: 1px solid #2a4b8d; border-radius: 4px; cursor: pointer;"
                 readonly onclick="openCardSearch()">
        </div>
      </div>
      <div class="login container bg-black news-section">
        <h2>SWU Stats is open source!</h2>
        <p class="login-message">SWU Stats is now open source! This project has been ongoing for a while as I wanted to mature the engine and make it possible for others to use. You can find a link to the source code in the top right corner of the page. It's based on a generic TCG card engine I made from lessons learned from all my work on Karabast/Petranaki/Talishar/other card game simulators.</p>
        <p style="margin-top: 12px;">If you would like to support my contributions to open source software, I would greatly appreciate if you check out my <a href="https://www.patreon.com/c/OotTheMonk" target="_blank" rel="noopener noreferrer">Patreon page</a>!</p>
      </div>
    </div>
  </div> <!-- Close core-wrapper div -->
  <?php include_once __DIR__ . '/Disclaimer.php'; ?>
</div> <!-- Close pageContainer div -->

<script>
  function closeCreatorMenu() {
    var menu = document.getElementById('swuCreatorMenu');
    var trigger = document.getElementById('swuCreatorTrigger');
    if (menu) menu.classList.remove('is-open');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
  }

  function toggleCreatorMenu(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    var menu = document.getElementById('swuCreatorMenu');
    var trigger = document.getElementById('swuCreatorTrigger');
    if (!menu || !trigger) return;
    var open = !menu.classList.contains('is-open');
    menu.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeSortMenu() {
    var menu = document.getElementById('swuSortMenu');
    var trigger = document.getElementById('swuSortTrigger');
    if (menu) menu.classList.remove('is-open');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
  }

  function toggleSortMenu(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    var menu = document.getElementById('swuSortMenu');
    var trigger = document.getElementById('swuSortTrigger');
    if (!menu || !trigger) return;
    var open = !menu.classList.contains('is-open');
    closeCreatorMenu();
    menu.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function switchTab(tabId, event) {
    var tabs = document.getElementsByClassName('tab-content');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].style.display = 'none';
    }
    document.getElementById(tabId).style.display = 'block';

    var buttons = document.querySelectorAll('.tab-button,.swu-creator-option');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.remove('active');
    }
    var activeButton = event && event.currentTarget ? event.currentTarget : event.target;
    var creatorTrigger = document.getElementById('swuCreatorTrigger');
    var creatorLabel = document.getElementById('swuCreatorTriggerLabel');
    if (activeButton && activeButton.classList.contains('swu-creator-option')) {
      activeButton.classList.add('active');
      if (creatorTrigger) creatorTrigger.classList.add('active');
      if (creatorLabel) creatorLabel.textContent = activeButton.dataset.creatorLabel || 'Creators';
    } else {
      if (activeButton) activeButton.classList.add('active');
      if (creatorLabel) creatorLabel.textContent = 'Creators';
    }
    closeCreatorMenu();
  }

  document.addEventListener('click', function(event) {
    var creatorMenu = document.getElementById('swuCreatorMenu');
    var sortMenu = document.getElementById('swuSortMenu');
    if (creatorMenu && !creatorMenu.contains(event.target)) closeCreatorMenu();
    if (sortMenu && !sortMenu.contains(event.target)) closeSortMenu();
  });
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeCreatorMenu();
      closeSortMenu();
    }
  });
</script>

<script>
  var SWU_DECK_FORMATS = <?= json_encode(SWUDeckBuildableFormats()) ?>;
</script>

<script>
  function createDeck() {
    var popup = document.createElement("div");
    popup.id = "createDeckPopup";
    popup.style.position = "fixed";
    popup.style.top = "50%";
    popup.style.left = "50%";
    popup.style.transform = "translate(-50%, -50%)";
    popup.style.backgroundColor = "#002249";
    popup.style.color = "#fff";
    popup.style.padding = "20px";
    popup.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.5)";
    popup.style.zIndex = "1000";

    var createOptions = Object.keys(SWU_DECK_FORMATS).map(function(id) {
      return '<option value="' + id + '">' + SWU_DECK_FORMATS[id].displayName + '</option>';
    }).join('');

    popup.innerHTML = `
      <h3>Create Deck</h3>
      <select id="createDeckFormat" style="width: 100%; padding: 10px; margin-bottom: 10px;">${createOptions}</select>
      <button onclick="submitCreateDeck()" style="padding: 10px 20px; margin-right: 10px;">Create</button>
      <button onclick="closeCreateDeckPopup()" style="padding: 10px 20px;">Cancel</button>
    `;
    document.body.appendChild(popup);
  }

  function closeCreateDeckPopup() {
    var popup = document.getElementById("createDeckPopup");
    if (popup) document.body.removeChild(popup);
  }

  function submitCreateDeck() {
    var format = document.getElementById("createDeckFormat").value;
    window.location.href = "/TCGEngine/SWUDeck/CreateDeck.php?format=" + encodeURIComponent(format);
  }

  function importDeck() {
    var popup = document.createElement("div");
    popup.id = "importDeckPopup";
    popup.style.position = "fixed";
    popup.style.top = "50%";
    popup.style.left = "50%";
    popup.style.transform = "translate(-50%, -50%)";
    popup.style.backgroundColor = "#002249"; // Darker blue background color
    popup.style.color = "#fff"; // White text color for better contrast
    popup.style.padding = "20px";
    popup.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.5)";
    popup.style.zIndex = "1000";

    var importOptions = Object.keys(SWU_DECK_FORMATS).map(function(id) {
      return '<option value="' + id + '">' + SWU_DECK_FORMATS[id].displayName + '</option>';
    }).join('');

    popup.innerHTML = `
      <h3>Import Deck</h3>
      <input type="text" id="deckLinkInput" placeholder="Enter deck link" style="width: 100%; padding: 10px; margin-bottom: 10px;" />
      <select id="importDeckFormat" style="width: 100%; padding: 10px; margin-bottom: 10px;">${importOptions}</select>
      <button onclick="importDeckLink()" style="padding: 10px 20px; margin-right: 10px;">Import</button>
      <button onclick="closePopup()" style="padding: 10px 20px;">Cancel</button>
    `;
    document.body.appendChild(popup);
    document.getElementById("deckLinkInput").focus();
  }

  function closePopup() {
    var popup = document.getElementById("importDeckPopup");
    if (popup) {
      document.body.removeChild(popup);
    }
  }

  function importDeckLink() {
    var deckLink = document.getElementById("deckLinkInput").value;
    var format = document.getElementById("importDeckFormat").value;
    if (deckLink !== "") {
      window.location.href = "/TCGEngine/SWUDeck/CreateDeck.php?deckLink=" + encodeURIComponent(deckLink) + "&format=" + encodeURIComponent(format);
    } else {
      StyledAlert("Enter a deck link to import");
    }
  }
</script>

<!-- Card Search Popup -->
<div id="cardSearchPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
  <div id="cardSearchOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); opacity: 0; transition: opacity 0.3s ease-out;" onclick="closeCardSearch()"></div>
  <div id="cardSearchContent" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.5); width: 90%; height: 90%; background-color: #002249; box-shadow: 0 0 20px rgba(51, 204, 255, 0.4); border-radius: 8px; overflow: hidden; opacity: 0; transition: transform 0.2s ease-out, opacity 0.2s ease-out;">
    <div style="position: absolute; top: 10px; right: 10px; z-index: 1001;">
      <button onclick="closeCardSearch()" style="background-color: #2a4b8d; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">
        ✕
      </button>
    </div>
    <iframe id="cardSearchFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
  </div>
</div>

<script>
  function openCardSearch() {
    const popup = document.getElementById("cardSearchPopup");
    const overlay = document.getElementById("cardSearchOverlay");
    const content = document.getElementById("cardSearchContent");

    // Show the popup container first
    popup.style.display = "block";

    // Set iframe source (same-origin, so the parent page's theme/CSS can reach it and it
    // follows whatever host it's served from instead of always hitting production).
    document.getElementById("cardSearchFrame").src = "/TCGEngine/NextTurn.php?gameName=1&playerID=1&folderPath=SWUCardList";

    // Force a reflow to ensure transitions work
    void popup.offsetWidth;

    // Start the animations
    overlay.style.opacity = "1";
    content.style.opacity = "1";
    content.style.transform = "translate(-50%, -50%) scale(1)";

    // Prevent background scrolling
    document.body.style.overflow = "hidden";

    // Add escape key listener
    document.addEventListener('keydown', handleEscKey);

    // Add touch event listener for mobile
    content.addEventListener('touchend', function(e) {
      e.stopPropagation();
    }, false);
  }

  function closeCardSearch() {
    const overlay = document.getElementById("cardSearchOverlay");
    const content = document.getElementById("cardSearchContent");
    const popup = document.getElementById("cardSearchPopup");

    // Start the closing animations
    overlay.style.opacity = "0";
    content.style.opacity = "0";
    content.style.transform = "translate(-50%, -50%) scale(0.5)";

    // Wait for animations to complete before hiding
    setTimeout(() => {
      popup.style.display = "none";
      document.getElementById("cardSearchFrame").src = "";
      document.body.style.overflow = "auto"; // Restore scrolling
    }, 50); // Match the transition duration (50ms)

    // Remove escape key listener
    document.removeEventListener('keydown', handleEscKey);
  }

  function handleEscKey(e) {
    if (e.key === "Escape") {
      closeCardSearch();
    }
  }

  // Add additional touch event handler for the search input
  document.addEventListener('DOMContentLoaded', function() {
    const cardSearchInput = document.getElementById('cardSearchInput');
    cardSearchInput.addEventListener('touchend', function(e) {
      e.preventDefault();
      openCardSearch();
    }, false);  });
</script>
