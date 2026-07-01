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

  /* Duration chip shown under each effect's source card (SWU). */
  .cardids-popup-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
  }
  .cardids-popup-chip {
    font-family: Orbitron, 'Segoe UI', sans-serif;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 1px 5px;
    border-radius: 6px;
    color: #fff;
    white-space: nowrap;
    line-height: 1.4;
  }
  .cardids-popup-chip.dur-attack { background: rgba(220, 70, 70, 0.9); }
  .cardids-popup-chip.dur-phase  { background: rgba(70, 120, 220, 0.9); }
  .cardids-popup-chip.dur-perm   { background: rgba(210, 170, 60, 0.95); color: #1a1a1a; }
`;
document.head.appendChild(cardIdsBadgeStyle);

// ==================== Global State ====================

var cardIdsBadgePopup = null;
var cardIdsBadgePopupTimeout = null;

// ==================== Helper Functions ====================

// Resolve the source-card image ID from an effect (CardIDs) token.
// SWU turn-effect tokens are a leading CardID (SET_NNN, e.g. SOR_092 / TWI_106 /
// TS26_046) optionally followed by params and/or a duration. Params may be
// '#'-separated ("SOR_076#2_2") OR '-'-separated ("SOR_092-2-2@phase"), and the
// duration is "@attack|@phase|@perm". The CardID is always the leading SET_NNN, so
// match that first. GA tokens use a non-SET_NNN base id with a trailing "-suffix"
// ("4hbA9FT56L-2"); those don't match SET_NNN and fall back to the legacy strip
// (drop from '#'/'@', then the trailing '-suffix').
function resolveEffectSourceCardId(token) {
  var s = String(token);
  // Explicit "^SOURCECARDID" provenance suffix (server-emitted for synthetic-base keyword grants,
  // e.g. "SENTINEL@phase^LOF_003"): the source card is everything after the caret.
  var caret = s.indexOf('^');
  if (caret >= 0) return s.slice(caret + 1);
  var swu = s.match(/^[A-Z0-9]{2,5}_\d{3}/);
  if (swu) return swu[0];
  return s.replace(/[#@].*$/, '').replace(/-[^-]+$/, '');
}

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
    
    var seenDisplayIds = {};
    for (var i = 0; i < cardIds.length; i++) {
      var cardId = cardIds[i];
      // Resolve the source-card image ID from the effect token (see resolveEffectSourceCardId).
      var displayCardId = resolveEffectSourceCardId(cardId);
      // Deduplicate: only show each source card's image once even if it contributes multiple effects
      if (seenDisplayIds[displayCardId]) continue;
      seenDisplayIds[displayCardId] = true;
      // Duration chip (SWU): the token carries an explicit "@attack|@phase|@perm" suffix. Tokens
      // without one (global-effect CardIDs, GA tokens) get no chip.
      var durHtml = '';
      var durMatch = cardId.match(/@([a-z]+)/);
      if (durMatch) {
        var durKey = durMatch[1];
        var durLabel = durKey === 'attack' ? 'Attack'
                     : durKey === 'phase'  ? 'Phase'
                     : durKey === 'perm'   ? 'Permanent' : '';
        if (durLabel) durHtml = '<div class="cardids-popup-chip dur-' + durKey + '">' + durLabel + '</div>';
      }
      // Try concat folder first, fallback to WebpImages, then hide to avoid infinite 404 spam
      var imgPath = rootPath + '/concat/' + displayCardId + '.webp';
      var imgFallback = rootPath + '/WebpImages/' + displayCardId + '.webp';
      html += '<div class="cardids-popup-card">';
      html += '<img src="' + imgPath + '" alt="' + displayCardId + '" loading="lazy"'
           + ' onerror="if(this.dataset.tried){this.onerror=null;this.style.display=\'none\';}else{this.dataset.tried=\'1\';this.src=\'' + imgFallback + '\';}" />';
      html += durHtml;
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

var NAMED_COLORS_RGB = {
  black: { r: 0, g: 0, b: 0 },
  white: { r: 255, g: 255, b: 255 },
  red: { r: 255, g: 0, b: 0 },
  green: { r: 0, g: 128, b: 0 },
  blue: { r: 0, g: 0, b: 255 },
  orange: { r: 255, g: 165, b: 0 },
  purple: { r: 128, g: 0, b: 128 },
  gold: { r: 255, g: 215, b: 0 },
  cyan: { r: 0, g: 255, b: 255 },
  silver: { r: 192, g: 192, b: 192 },
  brown: { r: 165, g: 42, b: 42 },
  magenta: { r: 255, g: 0, b: 255 },
  darkgreen: { r: 0, g: 100, b: 0 },
  teal: { r: 0, g: 128, b: 128 },
  gray: { r: 128, g: 128, b: 128 },
  grey: { r: 128, g: 128, b: 128 },
  yellow: { r: 255, g: 255, b: 0 },
  pink: { r: 255, g: 192, b: 203 },
  lime: { r: 0, g: 255, b: 0 },
  navy: { r: 0, g: 0, b: 128 }
};

function parseCssColorToRgb(color) {
  if (!color || typeof color !== 'string') return null;
  var s = color.trim().toLowerCase();

  if (NAMED_COLORS_RGB[s]) {
    return NAMED_COLORS_RGB[s];
  }

  // Hex: #rgb or #rrggbb
  var hexMatch = s.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hexMatch) {
    var hex = hexMatch[1];
    if (hex.length === 3) {
      return {
        r: parseInt(hex[0] + hex[0], 16),
        g: parseInt(hex[1] + hex[1], 16),
        b: parseInt(hex[2] + hex[2], 16)
      };
    }
    return {
      r: parseInt(hex.slice(0, 2), 16),
      g: parseInt(hex.slice(2, 4), 16),
      b: parseInt(hex.slice(4, 6), 16)
    };
  }

  // rgb()/rgba()
  var rgbMatch = s.match(/^rgba?\(([^)]+)\)$/i);
  if (rgbMatch) {
    var parts = rgbMatch[1].split(',').map(function(p) { return p.trim(); });
    if (parts.length >= 3) {
      var r = parseFloat(parts[0]);
      var g = parseFloat(parts[1]);
      var b = parseFloat(parts[2]);
      if (!isNaN(r) && !isNaN(g) && !isNaN(b)) {
        return { r: Math.max(0, Math.min(255, r)), g: Math.max(0, Math.min(255, g)), b: Math.max(0, Math.min(255, b)) };
      }
    }
  }

  // Unsupported/unknown format
  return null;
}

function getReadableTextColor(backgroundColor) {
  var rgb = parseCssColorToRgb(backgroundColor);
  if (!rgb) return '#fff';

  // Perceived luminance heuristic for contrast-aware text color.
  var luminance = (0.299 * rgb.r) + (0.587 * rgb.g) + (0.114 * rgb.b);
  return luminance >= 186 ? '#000' : '#fff';
}

function getCounterClusterOffset(slotIndex, total, spacingPx) {
  // Formation is centered as a group around (0,0).
  if (total <= 1) return { x: 0, y: 0 };
  if (total === 2) {
    return slotIndex === 0 ? { x: -spacingPx * 0.5, y: 0 } : { x: spacingPx * 0.5, y: 0 };
  }
  if (total === 3) {
    if (slotIndex === 0) return { x: -spacingPx * 0.5, y: -spacingPx * 0.4 };
    if (slotIndex === 1) return { x: spacingPx * 0.5, y: -spacingPx * 0.4 };
    return { x: 0, y: spacingPx * 0.5 };
  }
  if (total === 4) {
    if (slotIndex === 0) return { x: -spacingPx * 0.5, y: -spacingPx * 0.5 };
    if (slotIndex === 1) return { x: spacingPx * 0.5, y: -spacingPx * 0.5 };
    if (slotIndex === 2) return { x: -spacingPx * 0.5, y: spacingPx * 0.5 };
    return { x: spacingPx * 0.5, y: spacingPx * 0.5 };
  }
  if (total === 5) {
    if (slotIndex === 0) return { x: -spacingPx * 0.55, y: -spacingPx * 0.55 };
    if (slotIndex === 1) return { x: spacingPx * 0.55, y: -spacingPx * 0.55 };
    if (slotIndex === 2) return { x: -spacingPx * 0.55, y: spacingPx * 0.55 };
    if (slotIndex === 3) return { x: spacingPx * 0.55, y: spacingPx * 0.55 };
    return { x: 0, y: 0 };
  }

  // 6+ fallback: centered ring expansion.
  var angle = (Math.PI * 2 * slotIndex) / total;
  var radius = spacingPx * (0.6 + Math.floor((total - 1) / 8) * 0.3);
  return { x: Math.cos(angle) * radius, y: Math.sin(angle) * radius };
}

function shouldRenderSchemaVisualBySetting(ruleOrParams) {
  var params = (ruleOrParams && ruleOrParams.params) ? ruleOrParams.params : ruleOrParams;
  if (!params || typeof params !== 'object') return true;
  var settingsKey = params.SettingsKey || params.settingsKey;
  if (!settingsKey || typeof window.TCGSettings === 'undefined') return true;
  var expectedRaw = params.SettingsValue;
  if (typeof expectedRaw === 'undefined') expectedRaw = params.settingsValue;
  if (typeof expectedRaw === 'undefined') expectedRaw = true;
  var defaultRaw = params.SettingsDefault;
  if (typeof defaultRaw === 'undefined') defaultRaw = params.settingsDefault;
  if (typeof defaultRaw === 'undefined') defaultRaw = false;
  var expectedValue = String(expectedRaw).toLowerCase() === 'true';
  var defaultValue = String(defaultRaw).toLowerCase() === 'true';
  var currentValue = !!window.TCGSettings.get(String(settingsKey), { type: 'boolean', defaultValue: defaultValue });
  return currentValue === expectedValue;
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
    var renderItems = [];
    // Pass 1: collect visible counters with normalized rendering metadata.
    for (var r = 0; r < rules.length; ++r) {
      var rule = rules[r];
      if (!shouldRenderSchemaVisualBySetting(rule)) continue;
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
        var rawIds = valueStr.split(',').map(function(id) { return id.trim(); }).filter(function(id) { return id !== ''; });
        // Strip trailing variant suffix (e.g. "cardID-2" -> "cardID", "cardID-debuff" -> "cardID") and deduplicate
        // so multi-effect cards (using the cardID-suffix convention) count and display as one source card.
        var seenBase = {};
        cardIds = [];
        rawIds.forEach(function(id) {
          // Dedup by source CardID (see resolveEffectSourceCardId), so multiple
          // effects from one card count and display as a single source.
          var baseId = resolveEffectSourceCardId(id);
          if (!seenBase[baseId]) {
            seenBase[baseId] = true;
            cardIds.push(id); // Keep original ID for now; popup rendering will strip suffix
          }
        });
        displayValue = cardIds.length; // Display the count of unique source cards
        if (displayValue === 0) continue; // Skip if no card IDs
      } else {
        // normalize numeric strings for default mode
        if (!isNaN(value)) displayValue = Number(value);
        // Many schemas use -1 as an internal sentinel default; hide it unless negatives are explicitly requested.
        if (displayValue === -1 && params.ShowNegative === undefined) continue;
        // Handle ShowZero
        if (params.ShowZero !== undefined && String(params.ShowZero).toLowerCase() === 'false' && Number(displayValue) === 0) continue;
        if (params.ShowNegative !== undefined && String(params.ShowNegative).toLowerCase() === 'false' && Number(displayValue) < 0) continue;
      }
      
      // Determine visual style
      var sizePx = 22;
      var bg = params.Color ? params.Color : 'red';
      var textColor = params.TextColor ? params.TextColor : getReadableTextColor(bg);
      var pos = params.Position ? params.Position.toLowerCase() : 'topright';
      renderItems.push({
        r: r,
        field: field,
        type: type,
        params: params,
        mode: mode,
        displayValue: displayValue,
        cardIds: cardIds,
        sizePx: sizePx,
        bg: bg,
        textColor: textColor,
        pos: pos
      });
    }

    // Pass 2: assign centered formation slots per position, then render.
    var groupedByPos = {};
    for (var i = 0; i < renderItems.length; ++i) {
      var itemPos = renderItems[i].pos;
      if (!groupedByPos[itemPos]) groupedByPos[itemPos] = [];
      groupedByPos[itemPos].push(renderItems[i]);
    }

    for (var i2 = 0; i2 < renderItems.length; ++i2) {
      var item = renderItems[i2];
      var sizePx = item.sizePx;
      var bg = item.bg;
      var textColor = item.textColor;
      var field = item.field;
      var displayValue = item.displayValue;
      var type = item.type;
      var mode = item.mode;
      var cardIds = item.cardIds;
      var params = item.params;
      var pos = item.pos;
      var positionGroup = groupedByPos[pos] || [];
      var positionIndex = positionGroup.indexOf(item);
      var totalInPosition = positionGroup.length;
      var spacingPx = sizePx + 4;
      var cornerInsetPx = 3;
      var anchorInsetPx = cornerInsetPx + (sizePx * 0.1);
      var bottomAnchorInsetPx = anchorInsetPx + 3; // Align bottom badges over printed card stats.
      var bottomSideInsetPx = anchorInsetPx - 4;   // Push power/HP badges toward outer edges.
      var clusterOffset = getCounterClusterOffset(positionIndex, totalInPosition, spacingPx);

      // Optional fine-positioning nudge from schema params (OffsetX / OffsetY), in px.
      // Applied in absolute screen space below: +X = right, +Y = down, for any anchor.
      var offsetX = (params.OffsetX !== undefined && params.OffsetX !== '') ? (parseFloat(params.OffsetX) || 0) : 0;
      var offsetY = (params.OffsetY !== undefined && params.OffsetY !== '') ? (parseFloat(params.OffsetY) || 0) : 0;

      var posStyle = 'top:' + cornerInsetPx + 'px; right:' + cornerInsetPx + 'px;';
      switch (pos) {
        case 'topleft':
          posStyle = 'top:calc(' + anchorInsetPx + 'px + ' + clusterOffset.y + 'px); left:calc(' + anchorInsetPx + 'px + ' + clusterOffset.x + 'px);';
          break;
        case 'topright':
          posStyle = 'top:calc(' + anchorInsetPx + 'px + ' + clusterOffset.y + 'px); right:calc(' + anchorInsetPx + 'px - ' + clusterOffset.x + 'px);';
          break;
        case 'top':
        case 'topcenter':
          posStyle = 'top:calc(' + anchorInsetPx + 'px + ' + clusterOffset.y + 'px); left:50%; transform: translateX(calc(-50% + ' + clusterOffset.x + 'px));';
          break;
        case 'bottomleft':
          posStyle = 'bottom:calc(' + bottomAnchorInsetPx + 'px - ' + clusterOffset.y + 'px); left:calc(' + bottomSideInsetPx + 'px + ' + clusterOffset.x + 'px);';
          break;
        case 'bottomright':
          posStyle = 'bottom:calc(' + bottomAnchorInsetPx + 'px - ' + clusterOffset.y + 'px); right:calc(' + bottomSideInsetPx + 'px - ' + clusterOffset.x + 'px);';
          break;
        case 'bottom':
        case 'bottomcenter':
          posStyle = 'bottom:calc(' + bottomAnchorInsetPx + 'px - ' + clusterOffset.y + 'px); left:50%; transform: translateX(calc(-50% + ' + clusterOffset.x + 'px));';
          break;
        case 'center':
          posStyle = 'top:50%; left:50%; transform: translate(calc(-50% + ' + clusterOffset.x + 'px), calc(-50% + ' + clusterOffset.y + 'px));';
          break;
        default:
          posStyle = 'top:calc(' + anchorInsetPx + 'px + ' + clusterOffset.y + 'px); right:calc(' + anchorInsetPx + 'px - ' + clusterOffset.x + 'px);';
          break;
      }

      // Apply OffsetX/OffsetY as a translate, composed onto any centering transform
      // the position already uses (so e.g. Bottom keeps its -50% horizontal centering).
      if (offsetX !== 0 || offsetY !== 0) {
        var _nudge = ' translate(' + offsetX + 'px, ' + offsetY + 'px)';
        if (/transform\s*:/.test(posStyle)) {
          posStyle = posStyle.replace(/transform\s*:\s*([^;]+);?/, function (_m, t) { return 'transform:' + t.trim() + _nudge + ';'; });
        } else {
          posStyle += ' transform:' + _nudge + ';';
        }
      }

      var opacityStyle = (params.Opacity !== undefined && params.Opacity !== '') ? '; opacity:' + parseFloat(params.Opacity) + ';' : '';
      if (type.toLowerCase() === 'badge') {
        if (mode === 'cardids' && cardIds.length > 0) {
          // Badge with hover popup for card IDs
          var uniqueId = id + '-counter-' + field + '-' + item.r;
          var cardIdsJson = JSON.stringify(cardIds).replace(/'/g, "\\'");
          html += "<div data-counter-field='" + field + "' data-counter-mode='cardids' data-card-ids='" + cardIdsJson + "' ";
          html += "class='counter-badge-cardids' ";
          html += "onmouseenter='showCardIdsBadgePopup(this, event)' onmouseleave='hideCardIdsBadgePopup(this)' ";
          html += "style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6); cursor:pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;" + opacityStyle + "'>" + displayValue + "</div>";
        } else {
          // Standard badge
          html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);" + opacityStyle + "'>" + displayValue + "</div>";
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
      } else if (type.toLowerCase() === 'image') {
        var imagePath = params.Path || params.Image || (params[0] ? params[0] : null);
        var imageSize = params.Size && !isNaN(params.Size) ? Number(params.Size) : sizePx;
        if (imagePath) {
          html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + imageSize + "px; height:" + imageSize + "px; pointer-events:none;'>";
          html += "<img class='counter-image-icon' src='./" + imagePath + "' style='width:" + imageSize + "px; height:" + imageSize + "px; object-fit:contain; filter: drop-shadow(0 0 3px rgba(0,0,0,0.75));'/>";
          if (params.TextColor) {
            html += "<div style='position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; text-shadow: 0 0 3px rgba(0,0,0,0.95), 0 0 6px rgba(0,0,0,0.95);'>" + displayValue + "</div>";
          }
          html += "</div>";
        } else {
          // fallback to badge when no path is provided
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
