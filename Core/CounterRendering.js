// Counter Rendering Module
// Handles creation, styling, and display of badge counters with support for CardIDs mode

// ==================== CSS Styles ====================

// CardIDs Badge Popup Styles - for displaying card images when hovering over a badge with Mode=CardIDs
const cardIdsBadgeStyle = document.createElement('style');
cardIdsBadgeStyle.innerHTML = `
  /* Badge hover effect */
  .counter-badge-cardids:hover {
    transform: scale(1.15);
    box-shadow: 0 0 12px rgba(255,255,255,0.8), 0 0 20px currentColor !important;
  }

  /* Container for the CardIDs popup */
  .cardids-popup {
    position: fixed;
    z-index: 10000;
    background: linear-gradient(145deg, rgba(30, 30, 40, 0.98), rgba(20, 20, 30, 0.98));
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 12px;
    box-shadow: 
      0 10px 40px rgba(0, 0, 0, 0.5),
      0 0 20px rgba(100, 100, 255, 0.15),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    pointer-events: none;
    opacity: 0;
    transform: translateY(10px) scale(0.95);
    transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), 
                transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    max-width: 400px;
    min-width: 80px;
  }

  .cardids-popup.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

  /* Header for the popup */
  .cardids-popup-header {
    font-family: Orbitron, 'Segoe UI', sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .cardids-popup-header::before {
    content: '✦';
    color: rgba(180, 140, 255, 0.9);
    animation: sparkle 2s ease-in-out infinite;
  }

  @keyframes sparkle {
    0%, 100% { opacity: 0.6; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
  }

  /* Container for card images */
  .cardids-popup-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
  }

  /* Individual card image wrapper */
  .cardids-popup-card {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    transform: translateY(20px);
    opacity: 0;
    animation: cardPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }

  .cardids-popup-card img {
    display: block;
    height: 90px;
    width: auto;
    border-radius: 4px;
    transition: transform 0.2s ease;
  }

  .cardids-popup-card:hover img {
    transform: scale(1.05);
  }

  /* Subtle glow effect on each card */
  .cardids-popup-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 6px;
    box-shadow: inset 0 0 15px rgba(255, 255, 255, 0.1);
    pointer-events: none;
  }

  /* Staggered animation for each card */
  @keyframes cardPopIn {
    0% {
      opacity: 0;
      transform: translateY(20px) scale(0.8);
    }
    100% {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  /* Apply animation delay to each card */
  .cardids-popup-card:nth-child(1) { animation-delay: 0.05s; }
  .cardids-popup-card:nth-child(2) { animation-delay: 0.1s; }
  .cardids-popup-card:nth-child(3) { animation-delay: 0.15s; }
  .cardids-popup-card:nth-child(4) { animation-delay: 0.2s; }
  .cardids-popup-card:nth-child(5) { animation-delay: 0.25s; }
  .cardids-popup-card:nth-child(6) { animation-delay: 0.3s; }
  .cardids-popup-card:nth-child(7) { animation-delay: 0.35s; }
  .cardids-popup-card:nth-child(8) { animation-delay: 0.4s; }

  /* Shimmer effect on popup border */
  .cardids-popup::before {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 12px;
    background: linear-gradient(
      45deg,
      transparent 30%,
      rgba(180, 140, 255, 0.3) 50%,
      transparent 70%
    );
    background-size: 200% 200%;
    animation: shimmer 3s ease-in-out infinite;
    z-index: -1;
    opacity: 0.5;
  }

  @keyframes shimmer {
    0% { background-position: 200% 200%; }
    100% { background-position: -200% -200%; }
  }
`;
document.head.appendChild(cardIdsBadgeStyle);

// ==================== Global State ====================

var cardIdsBadgePopup = null;
var cardIdsBadgePopupTimeout = null;

// ==================== Helper Functions ====================

function getOrCreateCardIdsBadgePopup() {
  if (!cardIdsBadgePopup) {
    cardIdsBadgePopup = document.createElement('div');
    cardIdsBadgePopup.className = 'cardids-popup';
    cardIdsBadgePopup.id = 'cardids-badge-popup';
    document.body.appendChild(cardIdsBadgePopup);
  }
  return cardIdsBadgePopup;
}

function showCardIdsBadgePopup(badgeEl, event) {
  try {
    // Clear any pending hide timeout
    if (cardIdsBadgePopupTimeout) {
      clearTimeout(cardIdsBadgePopupTimeout);
      cardIdsBadgePopupTimeout = null;
    }

    var cardIdsAttr = badgeEl.getAttribute('data-card-ids');
    if (!cardIdsAttr) return;

    var cardIds = [];
    try {
      cardIds = JSON.parse(cardIdsAttr);
    } catch (e) {
      return;
    }

    if (!cardIds || cardIds.length === 0) return;

    var popup = getOrCreateCardIdsBadgePopup();
    
    // Build popup content
    var rootPath = window.rootPath || '.';
    var html = '<div class="cardids-popup-header">Active Effects</div>';
    html += '<div class="cardids-popup-cards">';
    
    for (var i = 0; i < cardIds.length; i++) {
      var cardId = cardIds[i];
      // Try concat folder first, fallback to WebpImages
      var imgPath = rootPath + '/concat/' + cardId + '.webp';
      html += '<div class="cardids-popup-card">';
      html += '<img src="' + imgPath + '" alt="' + cardId + '" onerror="this.src=\'' + rootPath + '/WebpImages/' + cardId + '.webp\'" loading="lazy" />';
      html += '</div>';
    }
    
    html += '</div>';
    popup.innerHTML = html;

    // Position the popup near the badge
    var rect = badgeEl.getBoundingClientRect();
    var popupWidth = 200; // Estimated initial width
    var popupHeight = 150; // Estimated initial height

    // Default: position above the badge
    var left = rect.left + rect.width / 2;
    var top = rect.top - 10;

    // Show popup first to get actual dimensions
    popup.style.left = '-9999px';
    popup.style.top = '-9999px';
    popup.classList.add('visible');
    
    // Get actual dimensions after content is rendered
    requestAnimationFrame(function() {
      var actualWidth = popup.offsetWidth;
      var actualHeight = popup.offsetHeight;

      // Recalculate position with actual dimensions
      left = rect.left + rect.width / 2 - actualWidth / 2;
      top = rect.top - actualHeight - 10;

      // Keep within viewport bounds
      var viewportWidth = window.innerWidth;
      var viewportHeight = window.innerHeight;

      // Horizontal bounds
      if (left < 10) left = 10;
      if (left + actualWidth > viewportWidth - 10) left = viewportWidth - actualWidth - 10;

      // If not enough space above, show below
      if (top < 10) {
        top = rect.bottom + 10;
      }

      // If still not enough space, show to the side
      if (top + actualHeight > viewportHeight - 10) {
        top = Math.max(10, viewportHeight - actualHeight - 10);
        left = rect.right + 10;
        if (left + actualWidth > viewportWidth - 10) {
          left = rect.left - actualWidth - 10;
        }
      }

      popup.style.left = left + 'px';
      popup.style.top = top + 'px';
    });

  } catch (e) {
    if (console && console.error) console.error('showCardIdsBadgePopup error', e);
  }
}

function hideCardIdsBadgePopup(badgeEl) {
  // Use a small delay to prevent flickering when moving between badge and popup
  cardIdsBadgePopupTimeout = setTimeout(function() {
    var popup = document.getElementById('cardids-badge-popup');
    if (popup) {
      popup.classList.remove('visible');
    }
  }, 100);
}

// ==================== Main Counter Rendering Function ====================

/**
 * Creates HTML for counters on a card based on CounterRules
 * @param {string} zoneName - The zone name
 * @param {array} cardArr - [cardID, quantity, cardJSON]
 * @param {string} id - The card element ID
 * @returns {string} HTML for counter elements
 */
function CreateCountersHTML(zoneName, cardArr, id) {
  try {
    // Prefer CounterRules constant (emitted by generator). Fall back to zone metadata (in case GeneratedUI wasn't included).
    var rules = null;
    if (typeof CounterRules !== 'undefined' && CounterRules[zoneName]) rules = CounterRules[zoneName];
    else {
      try {
        var zoneMeta = GetZoneData(zoneName);
        if (zoneMeta && zoneMeta.Counters) rules = zoneMeta.Counters;
      } catch (e) {
        rules = null;
      }
    }
    if (!rules || !Array.isArray(rules) || rules.length == 0) return "";
    var cardData = {};
    if (cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
      try { cardData = JSON.parse(cardArr[2]); } catch (e) { cardData = {}; }
    }
    var html = "";
    // container is positioned relative via the card wrapper
    for (var r = 0; r < rules.length; ++r) {
      var rule = rules[r];
      var field = rule.field;
      var type = rule.type || "Badge";
      var params = rule.params || {};
      var value = cardData.hasOwnProperty(field) ? cardData[field] : null;
      if (value === null || value === undefined) continue;
      
      // Check for Mode=CardIDs - special handling for comma-delimited card ID lists
      var mode = params.Mode ? params.Mode.toLowerCase() : 'default';
      var displayValue = value;
      var cardIds = [];
      
      if (mode === 'cardids') {
        // Value is a comma-delimited list of card IDs
        var valueStr = String(value).trim();
        if (valueStr === '') continue; // Skip if empty
        cardIds = valueStr.split(',').map(function(id) { return id.trim(); }).filter(function(id) { return id !== ''; });
        displayValue = cardIds.length; // Display the count
        if (displayValue === 0) continue; // Skip if no card IDs
      } else {
        // normalize numeric strings for default mode
        if (!isNaN(value)) displayValue = Number(value);
        // Handle ShowZero
        if (params.ShowZero !== undefined && String(params.ShowZero).toLowerCase() === 'false' && Number(displayValue) === 0) continue;
        if (params.ShowNegative !== undefined && String(params.ShowNegative).toLowerCase() === 'false' && Number(displayValue) < 0) continue;
      }
      
      // Determine visual style
      var sizePx = 22;
      var bg = params.Color ? params.Color : 'red';
      var textColor = '#fff';
      var posStyle = 'top:6px; right:6px;';
      var pos = params.Position ? params.Position.toLowerCase() : 'topright';
      switch(pos) {
        case 'topleft': posStyle = 'top:6px; left:6px;'; break;
        case 'topright': posStyle = 'top:6px; right:6px;'; break;
        case 'bottomleft': posStyle = 'bottom:6px; left:6px;'; break;
        case 'bottomright': posStyle = 'bottom:6px; right:6px;'; break;
        case 'center': posStyle = 'top:50%; left:50%; transform: translate(-50%, -50%);'; break;
        default: posStyle = 'top:6px; right:6px;';
      }
      
      if (type.toLowerCase() === 'badge') {
        if (mode === 'cardids' && cardIds.length > 0) {
          // Badge with hover popup for card IDs
          var uniqueId = id + '-counter-' + field + '-' + r;
          var cardIdsJson = JSON.stringify(cardIds).replace(/'/g, "\\'");
          html += "<div data-counter-field='" + field + "' data-counter-mode='cardids' data-card-ids='" + cardIdsJson + "' ";
          html += "class='counter-badge-cardids' ";
          html += "onmouseenter='showCardIdsBadgePopup(this, event)' onmouseleave='hideCardIdsBadgePopup(this)' ";
          html += "style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6); cursor:pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;'>" + displayValue + "</div>";
        } else {
          // Standard badge
          html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + displayValue + "</div>";
        }
      } else if (type.toLowerCase() === 'icon') {
        // If an icon name is provided as positional param, use it as img src fallback
        var icon = params.Icon || (params[0] ? params[0] : null);
        if (icon) {
          html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + ";'>";
          html += "<img src='./Images/" + icon + ".png' style='width:" + sizePx + "px; height:" + sizePx + "px; object-fit:contain; filter: drop-shadow(0 0 3px rgba(0,0,0,0.6));'/>";
          html += "<div style='position:absolute; font-size:11px; font-weight:700; color:" + textColor + "; margin-top:12px; text-shadow: 0 0 3px rgba(0,0,0,0.6);'>" + displayValue + "</div>";
          html += "</div>";
        } else {
          // fallback to badge
          html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + displayValue + "</div>";
        }
      } else {
        // unknown type -> render as badge
        html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + displayValue + "</div>";
      }
    }
    return html;
  } catch (e) {
    if (console && console.error) console.error('CreateCountersHTML error', e);
    return "";
  }
}
