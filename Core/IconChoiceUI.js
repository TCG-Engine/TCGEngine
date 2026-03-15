/**
 * IconChoiceUI.js — Directional compass-rose UI for Shifting Currents direction choices.
 *
 * Param format: "OPTION1&OPTION2|CURRENT_DIR|CARD_ID"
 *   - options: "&"-delimited list of selectable directions
 *   - current: the player's current Shifting Currents direction
 *   - cardID: mastery card ID (used to show card art)
 *
 * Returns the selected direction string (e.g. "NORTH") or "-" if skipped.
 */

function ShowIconChoiceUI(param, tooltip, decisionIndex, submitCallback) {
  // Remove any existing modal
  var existing = document.getElementById('iconchoice-modal');
  if (existing) existing.remove();

  // Parse param
  var parts = param.split('|');
  var options = parts[0].split('&').filter(Boolean);
  var current = parts.length > 1 ? parts[1] : '';
  var cardID = parts.length > 2 ? parts[2] : '';

  var allDirs = ['NORTH', 'EAST', 'SOUTH', 'WEST'];

  // Arrow symbols for each direction
  var arrowSymbols = {
    NORTH: '\u5317\u25B2', // ▲
    SOUTH: '\u5357\u25BC', // ▼
    EAST:  '\u4E1C\u25B6', // ▶
    WEST:  '\u25C0\u897F'  // ◀
  };

  var dirLabels = {
    NORTH: 'North',
    SOUTH: 'South',
    EAST:  'East',
    WEST:  'West'
  };

  // Direction colors
  var dirColors = {
    NORTH: '#4fc3f7', // light blue / wind
    SOUTH: '#ff7043', // coral / fire
    EAST:  '#66bb6a', // green / earth
    WEST:  '#ab47bc'  // purple / water
  };

  // Create overlay
  var overlay = document.createElement('div');
  overlay.id = 'iconchoice-modal';
  overlay.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.65);z-index:5000;display:flex;align-items:center;justify-content:center;';

  // Main container
  var container = document.createElement('div');
  container.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:12px;';

  // Tooltip / prompt
  var promptEl = document.createElement('div');
  promptEl.style.cssText = "font-family:'Orbitron',sans-serif;font-size:18px;color:#fff;text-align:center;margin-bottom:8px;text-shadow:0 0 8px rgba(100,200,255,0.5);";
  promptEl.textContent = tooltip || 'Choose a direction';
  container.appendChild(promptEl);

  // Compass grid — 3x3 grid with card in center and arrows at edges
  var grid = document.createElement('div');
  grid.style.cssText = 'display:grid;grid-template-columns:80px 180px 80px;grid-template-rows:80px 250px 80px;gap:4px;align-items:center;justify-items:center;';

  // Grid positions: [0,0]=empty [0,1]=NORTH [0,2]=empty
  //                 [1,0]=WEST  [1,1]=CARD  [1,2]=EAST
  //                 [2,0]=empty [2,1]=SOUTH [2,2]=empty
  var gridPositions = [
    null,    'NORTH', null,
    'WEST',  'CARD',  'EAST',
    null,    'SOUTH', null
  ];

  for (var gi = 0; gi < 9; gi++) {
    var cellContent = gridPositions[gi];
    var cell = document.createElement('div');
    cell.style.cssText = 'display:flex;align-items:center;justify-content:center;width:100%;height:100%;';

    if (cellContent === 'CARD') {
      // Center cell: card image
      var cardImg = document.createElement('div');
      cardImg.style.cssText = 'width:170px;height:240px;border-radius:8px;overflow:hidden;box-shadow:0 0 16px rgba(100,200,255,0.3);border:2px solid #334;position:relative;';

      if (cardID) {
        var rootPath = (typeof AssetReflectionPath === 'function') ? AssetReflectionPath() : '';
        var imgUrl = './' + rootPath + '/concat/' + cardID + '.webp';
        cardImg.style.backgroundImage = 'url(' + imgUrl + ')';
        cardImg.style.backgroundSize = 'cover';
        cardImg.style.backgroundPosition = 'center';
      } else {
        cardImg.style.background = 'linear-gradient(135deg, #1a2a3a, #0d1b2a)';
        var label = document.createElement('div');
        label.style.cssText = "color:#aaa;font-size:12px;font-family:'Orbitron',sans-serif;text-align:center;padding:10px;";
        label.textContent = 'Shifting Currents';
        cardImg.appendChild(label);
      }

      // Current direction indicator on card
      if (current && current !== 'NONE') {
        var indicator = document.createElement('div');
        indicator.style.cssText = 'position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.7);padding:3px 10px;border-radius:4px;font-size:11px;color:' + (dirColors[current] || '#fff') + ";font-family:'Orbitron',sans-serif;white-space:nowrap;";
        indicator.textContent = 'Current: ' + (dirLabels[current] || current);
        cardImg.appendChild(indicator);
      }

      cell.appendChild(cardImg);
    } else if (cellContent) {
      // Direction button
      var dir = cellContent;
      var isSelectable = options.indexOf(dir) !== -1;
      var isCurrent = (dir === current);

      var btn = document.createElement('button');
      btn.setAttribute('data-direction', dir);
      var baseStyles = "width:70px;height:70px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s ease;font-family:'Orbitron',sans-serif;border:2px solid;outline:none;";

      if (isCurrent) {
        // Current direction — highlighted but not selectable
        btn.style.cssText = baseStyles + 'background:rgba(255,255,255,0.12);border-color:' + (dirColors[dir] || '#888') + ';opacity:0.5;cursor:default;box-shadow:0 0 12px ' + (dirColors[dir] || '#888') + ';';
        btn.disabled = true;
      } else if (isSelectable) {
        // Available choice — bright and interactive
        btn.style.cssText = baseStyles + 'background:rgba(0,0,0,0.5);border-color:' + (dirColors[dir] || '#888') + ';color:' + (dirColors[dir] || '#fff') + ';box-shadow:0 0 8px ' + (dirColors[dir] || '#888') + '44;';
        (function(d, b) {
          b.onmouseenter = function() {
            b.style.background = (dirColors[d] || '#888') + '33';
            b.style.boxShadow = '0 0 20px ' + (dirColors[d] || '#888') + '88';
            b.style.transform = 'scale(1.12)';
          };
          b.onmouseleave = function() {
            b.style.background = 'rgba(0,0,0,0.5)';
            b.style.boxShadow = '0 0 8px ' + (dirColors[d] || '#888') + '44';
            b.style.transform = 'scale(1)';
          };
          b.onclick = function() {
            overlay.remove();
            submitCallback(d, decisionIndex);
          };
        })(dir, btn);
      } else {
        // Unavailable — dimmed
        btn.style.cssText = baseStyles + 'background:rgba(0,0,0,0.3);border-color:#333;color:#444;opacity:0.3;cursor:default;';
        btn.disabled = true;
      }

      // Arrow symbol
      var arrow = document.createElement('div');
      arrow.style.cssText = 'font-size:24px;line-height:1;';
      arrow.textContent = arrowSymbols[dir] || '?';
      btn.appendChild(arrow);

      // Direction label
      var dirLabel = document.createElement('div');
      dirLabel.style.cssText = 'font-size:9px;margin-top:2px;letter-spacing:1px;';
      dirLabel.textContent = dirLabels[dir] || dir;
      btn.appendChild(dirLabel);

      cell.appendChild(btn);
    }
    // Empty cells are just empty divs
    grid.appendChild(cell);
  }

  container.appendChild(grid);

  // Skip button
  var skipBtn = document.createElement('button');
  skipBtn.textContent = 'Skip';
  skipBtn.style.cssText = "margin-top:8px;padding:6px 28px;font-size:14px;font-family:'Orbitron',sans-serif;background:rgba(100,100,100,0.3);color:#999;border:1px solid #555;border-radius:5px;cursor:pointer;transition:all 0.2s ease;";
  skipBtn.onmouseenter = function() { skipBtn.style.background = 'rgba(150,150,150,0.3)'; skipBtn.style.color = '#ccc'; };
  skipBtn.onmouseleave = function() { skipBtn.style.background = 'rgba(100,100,100,0.3)'; skipBtn.style.color = '#999'; };
  skipBtn.onclick = function() {
    overlay.remove();
    submitCallback('-', decisionIndex);
  };
  container.appendChild(skipBtn);

  overlay.appendChild(container);
  document.body.appendChild(overlay);
}
