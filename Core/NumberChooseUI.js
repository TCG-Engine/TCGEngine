/**
 * NumberChooseUI.js - Numeric Slider/Stepper UI for Decision Queue
 *
 * Provides a centered popup with +/- buttons and a numeric display,
 * allowing the player to choose a number within a configurable range.
 *
 * Decision queue Param format: "min|max"
 *   e.g. "0|5" means choose a number from 0 to 5 inclusive
 *
 * Return value: the chosen number as a string (e.g. "3")
 *
 * Usage (called from CheckAndShowDecisionQueue in UILibraries.js):
 *   ShowNumberChooseUI(paramString, tooltip, decisionIndex, submitCallback)
 */

(function() {
  'use strict';

  const NUMBER_CHOOSE_STYLES = `
    .numchoose-banner {
      position: fixed;
      bottom: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 16px 32px;
      background: linear-gradient(145deg, #0D1B2A, #162d44);
      border: 1.5px solid rgba(180,100,255,0.45);
      border-radius: 14px;
      box-shadow: 0 0 24px rgba(180,100,255,0.25), 0 4px 24px rgba(0,0,0,0.5);
      font-family: 'Orbitron', 'Segoe UI', monospace;
      user-select: none;
    }

    .numchoose-label {
      color: #e0d0ff;
      font-size: 14px;
      max-width: 260px;
      text-align: center;
    }

    .numchoose-stepper {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .numchoose-btn {
      width: 36px;
      height: 36px;
      border: none;
      border-radius: 50%;
      font-family: 'Orbitron', 'Segoe UI', monospace;
      font-size: 20px;
      font-weight: bold;
      line-height: 36px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.10s ease, box-shadow 0.15s ease, background 0.15s ease;
      padding: 0;
    }

    .numchoose-btn:active { transform: scale(0.88); }

    .numchoose-btn-minus {
      background: #dc3545;
      color: #fff;
      box-shadow: 0 0 6px rgba(220,53,69,0.6);
    }
    .numchoose-btn-minus:hover:not(:disabled) {
      background: #ff4d5e;
      box-shadow: 0 0 12px rgba(255,77,94,0.8);
    }
    .numchoose-btn-minus:disabled {
      background: #555; color: #999; cursor: default; box-shadow: none;
    }

    .numchoose-btn-plus {
      background: #28a745;
      color: #fff;
      box-shadow: 0 0 6px rgba(40,167,69,0.6);
    }
    .numchoose-btn-plus:hover:not(:disabled) {
      background: #34d058;
      box-shadow: 0 0 12px rgba(52,208,88,0.8);
    }
    .numchoose-btn-plus:disabled {
      background: #555; color: #999; cursor: default; box-shadow: none;
    }

    .numchoose-value {
      min-width: 48px;
      text-align: center;
      font-size: 28px;
      font-weight: bold;
      color: #fff;
      text-shadow: 0 0 12px rgba(180,100,255,0.7);
    }

    .numchoose-confirm {
      padding: 8px 22px;
      border: none;
      border-radius: 8px;
      font-family: 'Orbitron', 'Segoe UI', monospace;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      background: linear-gradient(135deg, #7c3aed, #a855f7);
      color: #fff;
      box-shadow: 0 0 10px rgba(124,58,237,0.5);
      transition: background 0.15s ease, box-shadow 0.15s ease;
    }
    .numchoose-confirm:hover {
      background: linear-gradient(135deg, #9333ea, #c084fc);
      box-shadow: 0 0 18px rgba(147,51,234,0.7);
    }
  `;

  let styleEl = null;
  let bannerEl = null;
  let currentValue = 0;
  let minVal = 0;
  let maxVal = 0;

  function injectStyles() {
    if (styleEl) return;
    styleEl = document.createElement('style');
    styleEl.textContent = NUMBER_CHOOSE_STYLES;
    document.head.appendChild(styleEl);
  }

  function render(tooltip, decisionIndex, submitCallback) {
    if (bannerEl) bannerEl.remove();

    bannerEl = document.createElement('div');
    bannerEl.className = 'numchoose-banner';

    const label = document.createElement('div');
    label.className = 'numchoose-label';
    label.textContent = tooltip;

    const stepper = document.createElement('div');
    stepper.className = 'numchoose-stepper';

    const minusBtn = document.createElement('button');
    minusBtn.className = 'numchoose-btn numchoose-btn-minus';
    minusBtn.textContent = '\u2212';

    const valueDisplay = document.createElement('div');
    valueDisplay.className = 'numchoose-value';
    valueDisplay.textContent = String(currentValue);

    const plusBtn = document.createElement('button');
    plusBtn.className = 'numchoose-btn numchoose-btn-plus';
    plusBtn.textContent = '+';

    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'numchoose-confirm';
    confirmBtn.textContent = 'Confirm';

    function updateUI() {
      valueDisplay.textContent = String(currentValue);
      minusBtn.disabled = (currentValue <= minVal);
      plusBtn.disabled = (currentValue >= maxVal);
    }

    minusBtn.addEventListener('click', function() {
      if (currentValue > minVal) { currentValue--; updateUI(); }
    });
    plusBtn.addEventListener('click', function() {
      if (currentValue < maxVal) { currentValue++; updateUI(); }
    });
    confirmBtn.addEventListener('click', function() {
      submitCallback(String(currentValue), decisionIndex);
      HideNumberChooseUI();
    });

    stepper.appendChild(minusBtn);
    stepper.appendChild(valueDisplay);
    stepper.appendChild(plusBtn);

    bannerEl.appendChild(label);
    bannerEl.appendChild(stepper);
    bannerEl.appendChild(confirmBtn);

    updateUI();
    document.body.appendChild(bannerEl);
  }

  /**
   * Show the number chooser UI.
   * @param {string} paramString - "min|max" format
   * @param {string} tooltip - Human-readable prompt
   * @param {number} decisionIndex - Index in the decision queue
   * @param {function} submitCallback - Called with (resultString, decisionIndex)
   */
  window.ShowNumberChooseUI = function(paramString, tooltip, decisionIndex, submitCallback) {
    injectStyles();
    const parts = paramString.split('|');
    minVal = parseInt(parts[0], 10) || 0;
    maxVal = parseInt(parts[1], 10) || 0;
    currentValue = minVal;
    render(tooltip, decisionIndex, submitCallback);
  };

  window.HideNumberChooseUI = function() {
    if (bannerEl) { bannerEl.remove(); bannerEl = null; }
  };
})();
