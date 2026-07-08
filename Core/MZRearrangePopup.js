/**
 * MZRearrangePopup.js - Card Rearrangement UI for Decision Queue
 * 
 * Provides a modern drag-and-drop interface for rearranging cards between
 * multiple piles. Used by the MZREARRANGE decision queue interaction.
 * 
 * Parameter format: "PileName1=card1,card2;PileName2=card3,card4"
 */

(function() {
  'use strict';

  // CSS styles for the rearrange popup
  const REARRANGE_STYLES = `
    .mzrearrange-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(8px);
      z-index: 6000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: 'Orbitron', 'Segoe UI', sans-serif;
      animation: mzrearrange-fade-in 0.3s ease-out;
    }

    @keyframes mzrearrange-fade-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .mzrearrange-modal {
      position: relative;
      width: min(560px, calc(100vw - 16px));
      background: linear-gradient(145deg, #1a2a3a, #0d1b2a);
      border: 2px solid #3a5a7a;
      border-radius: 16px;
      max-width: 95vw;
      max-height: 85vh;
      overflow-x: hidden;
      overflow-y: auto;
      box-shadow: 0 0 60px rgba(0, 100, 200, 0.3), inset 0 1px 0 rgba(255,255,255,0.1);
      animation: mzrearrange-slide-up 0.4s ease-out;
      pointer-events: auto;
    }

    .mzrearrange-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 16px 10px 18px;
      cursor: grab;
      user-select: none;
      touch-action: none;
    }

    .mzrearrange-header-label {
      flex: 1;
      min-width: 0;
      color: rgba(255, 255, 255, 0.92);
      text-transform: uppercase;
      letter-spacing: 0.22em;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .mzrearrange-controls {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .mzrearrange-minimize-btn {
      width: 28px;
      height: 28px;
      padding: 0;
      border-radius: 999px;
      border: 1px solid rgba(244, 236, 219, 0.18);
      background: rgba(244, 236, 219, 0.08);
      color: #f4ecdb;
      font-size: 18px;
      line-height: 1;
      cursor: pointer;
      font-family: 'Orbitron', sans-serif;
    }

    .mzrearrange-header-divider {
      height: 1px;
      margin: 0 16px;
      background: linear-gradient(90deg, rgba(100, 200, 255, 0.32), rgba(255, 255, 255, 0.06));
    }

    .mzrearrange-body {
      padding: 10px clamp(12px, 4vw, 24px) 24px clamp(12px, 4vw, 24px);
    }

    @keyframes mzrearrange-slide-up {
      from { 
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to { 
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .mzrearrange-title {
      font-size: clamp(18px, 5vw, 22px);
      color: #fff;
      text-align: center;
      margin-bottom: 20px;
      text-shadow: 0 0 20px rgba(100, 200, 255, 0.5);
      letter-spacing: 1px;
    }

    .mzrearrange-piles-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: clamp(12px, 3vw, 18px);
      margin-bottom: 24px;
    }

    .mzrearrange-pile {
      box-sizing: border-box;
      background: rgba(0, 20, 40, 0.6);
      border: 2px solid #2a4a6a;
      border-radius: 12px;
      padding: clamp(10px, 3vw, 16px);
      min-width: 0;
      min-height: 200px;
      transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
      will-change: box-shadow;
    }

    .mzrearrange-pile.drag-over {
      border-color: #00ccff;
      background: rgba(0, 100, 150, 0.3);
      box-shadow: 0 0 30px rgba(0, 200, 255, 0.4);
    }

    .mzrearrange-pile-header {
      font-size: 16px;
      color: #8cf;
      text-align: center;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 1px solid #3a5a7a;
      text-transform: uppercase;
      letter-spacing: 2px;
    }

    .mzrearrange-cards-container {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-height: 150px;
    }

    .mzrearrange-card {
      position: relative;
      cursor: grab;
      border-radius: 8px;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
      user-select: none;
      touch-action: none;
      background: rgba(30, 50, 70, 0.5);
      padding: 4px;
      will-change: transform;
    }

    .mzrearrange-card img {
      pointer-events: none;
      user-select: none;
      -webkit-user-drag: none;
    }

    .mzrearrange-card:hover {
      transform: translateY(-2px) scale(1.02);
      box-shadow: 0 8px 20px rgba(0, 150, 255, 0.3);
      z-index: 10;
    }

    .mzrearrange-card.dragging {
      opacity: 0.4;
      transform: scale(0.95);
      cursor: grabbing;
      transition: none;
    }

    .mzrearrange-card.drag-preview {
      position: fixed;
      pointer-events: none;
      z-index: 10000;
      opacity: 0.9;
      transform: rotate(2deg) scale(1.03);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
      will-change: transform, left, top;
      transition: none;
    }

    .mzrearrange-card-placeholder {
      border: 2px dashed #00ccff;
      border-radius: 8px;
      background: rgba(0, 200, 255, 0.1);
      animation: mzrearrange-pulse 1s ease-in-out infinite;
      transition: all 0.2s ease;
      margin: 2px 0;
    }

    .mzrearrange-card-placeholder.active {
      transform: scaleY(1.15);
      background: rgba(0, 200, 255, 0.2);
      border-width: 3px;
    }

    @keyframes mzrearrange-pulse {
      0%, 100% { opacity: 0.5; }
      50% { opacity: 1; }
    }

    @keyframes mzrearrange-drop {
      from {
        transform: var(--drop-from-transform);
      }
      to {
        transform: translate(0, 0);
      }
    }

    .mzrearrange-card.dropping {
      animation: mzrearrange-drop 0.16s ease-out;
    }

    .mzrearrange-card-order {
      position: absolute;
      top: -8px;
      left: -8px;
      width: 24px;
      height: 24px;
      background: linear-gradient(145deg, #00ccff, #0088cc);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: bold;
      color: #fff;
      box-shadow: 0 2px 8px rgba(0, 150, 255, 0.5);
      z-index: 5;
    }

    .mzrearrange-buttons {
      display: flex;
      gap: 16px;
      justify-content: center;
      margin-top: 8px;
    }

    /* Skin from .btn + .btn-primary/.btn-secondary (button.css); layout only kept here. */
    .mzrearrange-btn { padding: 12px 32px; font-size: 16px; }

    .mzrearrange-instructions {
      text-align: center;
      color: #8aa;
      font-size: 13px;
      margin-bottom: 16px;
      opacity: 0.8;
    }

    .mzrearrange-empty-message {
      color: #557;
      font-style: italic;
      text-align: center;
      padding: 20px;
      font-size: 13px;
    }

    @media (max-width: 340px) {
      .mzrearrange-piles-container {
        grid-template-columns: 1fr;
      }
    }
  `;

  /**
   * Parse the MZREARRANGE parameter string into pile data
   * Format: "PileName1=card1,card2;PileName2=card3,card4"
   * @param {string} param - The parameter string
   * @returns {Array<{name: string, cards: string[]}>} Array of pile objects
   */
  function parseRearrangeParam(param) {
    if (!param || typeof param !== 'string') return [];
    
    const piles = [];
    const pileStrings = param.split(';');
    
    for (const pileStr of pileStrings) {
      const trimmed = pileStr.trim();
      if (!trimmed) continue;
      
      const eqIndex = trimmed.indexOf('=');
      if (eqIndex === -1) {
        // Pile with no cards
        piles.push({ name: trimmed, cards: [] });
      } else {
        const pileName = trimmed.substring(0, eqIndex).trim();
        const cardsStr = trimmed.substring(eqIndex + 1).trim();
        const cards = cardsStr ? cardsStr.split(',').map(c => c.trim()).filter(Boolean) : [];
        piles.push({ name: pileName, cards: cards });
      }
    }
    
    return piles;
  }

  /**
   * Serialize pile data back to parameter format
   * @param {Array<{name: string, cards: string[]}>} piles - Array of pile objects
   * @returns {string} Serialized parameter string
   */
  function serializePiles(piles) {
    return piles.map(pile => {
      if (pile.cards.length === 0) {
        return pile.name + '=';
      }
      return pile.name + '=' + pile.cards.join(',');
    }).join(';');
  }

  /**
   * Inject styles if not already present
   */
  function injectStyles() {
    if (document.getElementById('mzrearrange-styles')) return;
    
    const styleEl = document.createElement('style');
    styleEl.id = 'mzrearrange-styles';
    styleEl.textContent = REARRANGE_STYLES;
    document.head.appendChild(styleEl);
  }

  /**
   * Create card HTML for display in the rearrange popup
   * @param {string} cardId - The card identifier
   * @param {number} order - The order/position number (1-based)
   * @returns {string} HTML string for the card
   */
  function createRearrangeCardHTML(cardId, order) {
    // Try to get card size and folder from window
    const cardSize = window.cardSize || 80;
    const rootPath = window.rootPath || '.';
    const folder = rootPath + '/concat';
    
    // Create card image using similar approach to UILibraries
    const imgSrc = folder + '/' + cardId + '.webp';
    const height = cardSize;
    const width = cardSize; // concat folder uses 1:1 ratio
    
    return `
      <div class="mzrearrange-card" data-card-id="${cardId}" draggable="true" onmouseover="ShowCardDetail(event, this)" onmouseout="HideCardDetail()">
        <div class="mzrearrange-card-order">${order}</div>
        <img 
          src="${imgSrc}" 
          alt="${cardId}"
          style="height: ${height}px; width: ${width}px; border-radius: 6px; display: block;"
          loading="lazy"
          onerror="this.src='./Assets/Images/cardback.png'"
        />
      </div>
    `;
  }

  /**
   * Update order badges on all cards in a pile
   * @param {HTMLElement} cardsContainer - The cards container element
   */
  function updateOrderBadges(cardsContainer) {
    const cards = cardsContainer.querySelectorAll('.mzrearrange-card');
    cards.forEach((card, index) => {
      const badge = card.querySelector('.mzrearrange-card-order');
      if (badge) {
        badge.textContent = index + 1;
      }
    });
  }

  /**
   * Get current pile state from DOM
   * @param {HTMLElement} modal - The modal element
   * @returns {Array<{name: string, cards: string[]}>} Current pile state
   */
  function getCurrentPileState(modal) {
    const piles = [];
    const pileElements = modal.querySelectorAll('.mzrearrange-pile');
    
    pileElements.forEach(pileEl => {
      const name = pileEl.dataset.pileName;
      const cards = [];
      const cardElements = pileEl.querySelectorAll('.mzrearrange-card');
      cardElements.forEach(cardEl => {
        cards.push(cardEl.dataset.cardId);
      });
      piles.push({ name, cards });
    });
    
    return piles;
  }

  /**
   * Show the MZREARRANGE popup
   * @param {string} param - The parameter string (e.g., "Battlefield=card1,card2;Hand=card3")
   * @param {string} tooltip - Optional tooltip/instructions
   * @param {number} decisionIndex - The decision queue index
   * @param {function} submitCallback - Callback when user submits (receives serialized result)
   */
  function ShowMZRearrangePopup(param, tooltip, decisionIndex, submitCallback) {
    // Hide any existing popup
    HideMZRearrangePopup();
    
    // Inject styles
    injectStyles();
    
    // Parse the parameter
    const initialPiles = parseRearrangeParam(param);
    if (initialPiles.length === 0) {
      console.warn('MZRearrangePopup: No piles to display');
      return;
    }
    
    // Store initial state for reset
    const initialState = JSON.parse(JSON.stringify(initialPiles));
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.id = 'mzrearrange-popup';
    overlay.className = 'mzrearrange-overlay';
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'mzrearrange-modal';

    const popupTitle = 'Rearrange Cards';

    // Header with minimize support
    const header = document.createElement('div');
    header.className = 'mzrearrange-header';
    header.setAttribute('data-drag-handle', 'true');

    const headerLabel = document.createElement('div');
    headerLabel.className = 'mzrearrange-header-label';
    headerLabel.textContent = popupTitle;
    header.appendChild(headerLabel);

    const controls = document.createElement('div');
    controls.className = 'mzrearrange-controls';

    const minimizeBtn = document.createElement('button');
    minimizeBtn.type = 'button';
    minimizeBtn.className = 'mzrearrange-minimize-btn';
    minimizeBtn.textContent = '−';
    minimizeBtn.title = 'Minimize';
    minimizeBtn.setAttribute('aria-label', 'Minimize rearrange popup');
    controls.appendChild(minimizeBtn);

    header.appendChild(controls);
    modal.appendChild(header);

    const headerDivider = document.createElement('div');
    headerDivider.className = 'mzrearrange-header-divider';
    modal.appendChild(headerDivider);

    const body = document.createElement('div');
    body.className = 'mzrearrange-body';
    
    // Title
    const title = document.createElement('div');
    title.className = 'mzrearrange-title';
    title.textContent = tooltip || 'Arrange Cards';
    body.appendChild(title);
    
    // Instructions
    const instructions = document.createElement('div');
    instructions.className = 'mzrearrange-instructions';
    instructions.textContent = 'Drag cards to reorder within a pile or move between piles';
    body.appendChild(instructions);
    
    // Piles container
    const pilesContainer = document.createElement('div');
    pilesContainer.className = 'mzrearrange-piles-container';
    
    // Create pile elements
    initialPiles.forEach((pile, pileIndex) => {
      const pileEl = document.createElement('div');
      pileEl.className = 'mzrearrange-pile';
      pileEl.dataset.pileName = pile.name;
      pileEl.dataset.pileIndex = pileIndex;
      
      // Pile header
      const header = document.createElement('div');
      header.className = 'mzrearrange-pile-header';
      header.textContent = pile.name;
      pileEl.appendChild(header);
      
      // Cards container
      const cardsContainer = document.createElement('div');
      cardsContainer.className = 'mzrearrange-cards-container';
      
      // Add cards
      if (pile.cards.length === 0) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'mzrearrange-empty-message';
        emptyMsg.textContent = 'Empty';
        cardsContainer.appendChild(emptyMsg);
      } else {
        pile.cards.forEach((cardId, cardIndex) => {
          cardsContainer.insertAdjacentHTML('beforeend', createRearrangeCardHTML(cardId, cardIndex + 1));
        });
      }
      
      pileEl.appendChild(cardsContainer);
      pilesContainer.appendChild(pileEl);
    });
    
    body.appendChild(pilesContainer);
    
    // Buttons
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'mzrearrange-buttons';
    
    const resetBtn = document.createElement('button');
    resetBtn.className = 'mzrearrange-btn mzrearrange-btn-reset btn btn-secondary';
    resetBtn.textContent = 'Reset';
    resetBtn.onclick = () => {
      // Reset to initial state
      const pileElements = modal.querySelectorAll('.mzrearrange-pile');
      pileElements.forEach((pileEl, idx) => {
        const cardsContainer = pileEl.querySelector('.mzrearrange-cards-container');
        cardsContainer.innerHTML = '';
        
        const pile = initialState[idx];
        if (pile.cards.length === 0) {
          const emptyMsg = document.createElement('div');
          emptyMsg.className = 'mzrearrange-empty-message';
          emptyMsg.textContent = 'Empty';
          cardsContainer.appendChild(emptyMsg);
        } else {
          pile.cards.forEach((cardId, cardIndex) => {
            cardsContainer.insertAdjacentHTML('beforeend', createRearrangeCardHTML(cardId, cardIndex + 1));
          });
        }
        setupCardDragListeners(cardsContainer);
      });
    };
    buttonsDiv.appendChild(resetBtn);
    
    const submitBtn = document.createElement('button');
    submitBtn.className = 'mzrearrange-btn mzrearrange-btn-submit btn btn-primary';
    submitBtn.textContent = 'Confirm';
    submitBtn.onclick = () => {
      const currentState = getCurrentPileState(modal);
      const serialized = serializePiles(currentState);
      HideMZRearrangePopup();
      if (submitCallback) {
        submitCallback(serialized, decisionIndex);
      }
    };
    buttonsDiv.appendChild(submitBtn);
    
    body.appendChild(buttonsDiv);
    modal.appendChild(body);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    if (typeof EnableDraggableModal === 'function') {
      EnableDraggableModal(modal, header, 'mzrearrange-popup-position-v1');
    }

    let isMinimized = false;

    function setMinimized(nextValue) {
      isMinimized = !!nextValue;
      body.style.display = isMinimized ? 'none' : 'block';
      overlay.style.background = isMinimized ? 'transparent' : 'rgba(0, 0, 0, 0.85)';
      overlay.style.backdropFilter = isMinimized ? 'none' : 'blur(8px)';
      overlay.style.pointerEvents = isMinimized ? 'none' : 'auto';
      modal.style.pointerEvents = 'auto';
      modal.style.maxHeight = isMinimized ? 'none' : '85vh';
      modal.style.overflow = isMinimized ? 'visible' : 'auto';
      modal.style.width = isMinimized ? 'min(440px, calc(100vw - 24px))' : '';
      modal.style.borderRadius = isMinimized ? '999px' : '16px';
      headerDivider.style.display = isMinimized ? 'none' : 'block';
      minimizeBtn.textContent = isMinimized ? '+' : '−';
      minimizeBtn.title = isMinimized ? 'Expand' : 'Minimize';
      minimizeBtn.setAttribute('aria-label', isMinimized ? 'Expand rearrange popup' : 'Minimize rearrange popup');
    }

    minimizeBtn.onclick = function(ev) {
      ev.preventDefault();
      ev.stopPropagation();
      setMinimized(!isMinimized);
    };
    
    // Setup drag and drop
    setupDragAndDrop(modal);
  }

  /**
   * Setup drag and drop functionality
   * @param {HTMLElement} modal - The modal element
   */
  function setupDragAndDrop(modal) {
    let draggedCard = null;
    let dragPreview = null;
    let placeholder = null;
    let currentMouseX = 0;
    let currentMouseY = 0;
    let animationFrameId = null;
    let activePointerDrag = null;
    let activeTouchDrag = null;
    const DRAG_START_THRESHOLD = 6;
    const MAX_DROP_ANIMATION_DISTANCE = 72;
    const supportsPointerEvents = typeof window.PointerEvent !== 'undefined';

    if (modal.__mzrearrangeDndAbortController) {
      modal.__mzrearrangeDndAbortController.abort();
      modal.__mzrearrangeDndAbortController = null;
    }

    const listenerOptions = {};
    const passiveListenerOptions = { passive: false };
    if (typeof AbortController !== 'undefined') {
      modal.__mzrearrangeDndAbortController = new AbortController();
      listenerOptions.signal = modal.__mzrearrangeDndAbortController.signal;
      passiveListenerOptions.signal = modal.__mzrearrangeDndAbortController.signal;
    }

    function setCardDetailSuppressed(isSuppressed) {
      window._suppressCardDetail = isSuppressed;
      if (isSuppressed && typeof HideCardDetail === 'function') {
        HideCardDetail();
      }
    }

    function hasMovedEnough(startX, startY, clientX, clientY) {
      return Math.abs(clientX - startX) >= DRAG_START_THRESHOLD ||
        Math.abs(clientY - startY) >= DRAG_START_THRESHOLD;
    }

    function nextRealCardSibling(card) {
      let next = card ? card.nextElementSibling : null;
      while (next && !next.classList.contains('mzrearrange-card')) {
        next = next.nextElementSibling;
      }
      return next;
    }

    function clampDropDelta(deltaX, deltaY) {
      const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
      if (distance <= MAX_DROP_ANIMATION_DISTANCE || distance === 0) {
        return { x: deltaX, y: deltaY };
      }
      const ratio = MAX_DROP_ANIMATION_DISTANCE / distance;
      return { x: deltaX * ratio, y: deltaY * ratio };
    }

    function beginCardDrag(card, clientX, clientY) {
      if (!card || draggedCard) return false;

      draggedCard = card;
      currentMouseX = clientX;
      currentMouseY = clientY;
      setCardDetailSuppressed(true);

      draggedCard.classList.add('dragging');

      // Create drag preview
      dragPreview = draggedCard.cloneNode(true);
      dragPreview.classList.add('drag-preview');
      dragPreview.classList.remove('dragging');
      dragPreview.style.left = (currentMouseX + 10) + 'px';
      dragPreview.style.top = (currentMouseY + 10) + 'px';
      document.body.appendChild(dragPreview);

      // Create placeholder (smaller than actual card)
      placeholder = document.createElement('div');
      placeholder.className = 'mzrearrange-card-placeholder';
      const placeholderHeight = Math.max(20, draggedCard.offsetHeight * 0.3);
      placeholder.style.height = placeholderHeight + 'px';
      placeholder.style.width = draggedCard.offsetWidth + 'px';
      placeholder.dataset.originalHeight = placeholderHeight;
      placeholder.dataset.fullHeight = draggedCard.offsetHeight;

      // Start smooth animation loop
      animationFrameId = requestAnimationFrame(updatePreviewPosition);
      return true;
    }

    function updateDropTargetAtPoint(clientX, clientY) {
      if (!draggedCard || !placeholder) return;

      const target = document.elementFromPoint(clientX, clientY);
      const pile = target && target.closest ? target.closest('.mzrearrange-pile') : null;

      modal.querySelectorAll('.mzrearrange-pile').forEach(pileEl => {
        pileEl.classList.toggle('drag-over', pileEl === pile);
      });

      if (!pile || !modal.contains(pile)) {
        if (placeholder.parentNode) placeholder.remove();
        placeholder.classList.remove('active');
        return;
      }

      positionPlaceholderInPile(pile, clientY);
    }

    function positionPlaceholderInPile(pile, clientY) {
      if (!draggedCard || !placeholder) return;

      const cardsContainer = pile.querySelector('.mzrearrange-cards-container');
      if (!cardsContainer) return;

      const afterElement = getDragAfterElement(cardsContainer, clientY);

      // Remove empty message if present
      const emptyMsg = cardsContainer.querySelector('.mzrearrange-empty-message');
      if (emptyMsg) emptyMsg.remove();

      // Determine if placeholder would actually move the card
      const draggedCardParent = draggedCard.parentNode;
      let wouldMove = false;

      if (draggedCardParent !== cardsContainer) {
        // Moving to a different pile - always show placeholder
        wouldMove = true;
      } else if (afterElement === null) {
        // Dropping at end - no move if the dragged card is already the last real card
        wouldMove = nextRealCardSibling(draggedCard) !== null;
      } else {
        // Dropping before the dragged card's current next real card is the same position
        wouldMove = afterElement !== nextRealCardSibling(draggedCard);
      }

      if (!wouldMove) {
        if (placeholder.parentNode) placeholder.remove();
        placeholder.classList.remove('active');
        return;
      }

      // Position placeholder
      if (placeholder.parentNode !== cardsContainer) {
        if (placeholder.parentNode) placeholder.remove();
      }

      if (afterElement) {
        cardsContainer.insertBefore(placeholder, afterElement);
      } else {
        cardsContainer.appendChild(placeholder);
      }

      placeholder.classList.add('active');
    }

    function commitDraggedCardToPlaceholder() {
      if (!draggedCard || !placeholder || !placeholder.parentNode) return false;

      // Capture positions for FLIP animation
      const draggedRect = draggedCard.getBoundingClientRect();
      const dragPreviewRect = dragPreview ? dragPreview.getBoundingClientRect() : draggedRect;

      // Move the card to placeholder position
      placeholder.parentNode.insertBefore(draggedCard, placeholder);
      placeholder.remove();

      // Get final position
      const finalRect = draggedCard.getBoundingClientRect();

      // Calculate transform from drag position to final position
      const deltaX = dragPreviewRect.left - finalRect.left;
      const deltaY = dragPreviewRect.top - finalRect.top;
      const clampedDelta = clampDropDelta(deltaX, deltaY);

      // Only animate if there's actual movement
      if (Math.abs(clampedDelta.x) > 1 || Math.abs(clampedDelta.y) > 1) {
        draggedCard.style.setProperty('--drop-from-transform', `translate(${clampedDelta.x}px, ${clampedDelta.y}px)`);
        draggedCard.classList.add('dropping');

        setTimeout(() => {
          draggedCard.classList.remove('dropping');
          draggedCard.style.removeProperty('--drop-from-transform');
        }, 180);
      }

      return true;
    }

    function normalizePileContainers() {
      modal.querySelectorAll('.mzrearrange-pile').forEach(pile => {
        pile.classList.remove('drag-over');
      });

      modal.querySelectorAll('.mzrearrange-cards-container').forEach(container => {
        updateOrderBadges(container);
        const cards = container.querySelectorAll('.mzrearrange-card');
        const emptyMsg = container.querySelector('.mzrearrange-empty-message');
        if (cards.length === 0 && !emptyMsg) {
          const msg = document.createElement('div');
          msg.className = 'mzrearrange-empty-message';
          msg.textContent = 'Empty';
          container.appendChild(msg);
        } else if (cards.length > 0 && emptyMsg) {
          emptyMsg.remove();
        }
      });
    }

    function finishCardDrag() {
      if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
      }

      if (draggedCard) {
        draggedCard.classList.remove('dragging');
      }

      if (dragPreview) {
        dragPreview.remove();
        dragPreview = null;
      }

      if (placeholder && placeholder.parentNode) {
        placeholder.remove();
      }
      placeholder = null;

      normalizePileContainers();

      draggedCard = null;
      currentMouseX = 0;
      currentMouseY = 0;
      setCardDetailSuppressed(false);
    }

    function updateDirectDrag(state, clientX, clientY) {
      if (!state) return false;
      state.currentX = clientX;
      state.currentY = clientY;

      if (!state.hasStarted) {
        if (!hasMovedEnough(state.startX, state.startY, clientX, clientY)) return false;
        state.hasStarted = beginCardDrag(state.card, state.startX, state.startY);
      }

      if (!state.hasStarted) return false;
      currentMouseX = clientX;
      currentMouseY = clientY;
      updateDropTargetAtPoint(clientX, clientY);
      return true;
    }

    /**
     * Setup listeners for cards in a container
     */
    function setupCardDragListeners(container) {
      const cards = container.querySelectorAll('.mzrearrange-card');
      cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart, listenerOptions);
        card.addEventListener('dragend', handleDragEnd, listenerOptions);
        if (supportsPointerEvents) {
          card.addEventListener('pointerdown', handlePointerDown, passiveListenerOptions);
        } else {
          card.addEventListener('touchstart', handleTouchStart, passiveListenerOptions);
        }
      });
    }

    // Make setupCardDragListeners available globally for reset
    window._mzrearrangeSetupCardDragListeners = setupCardDragListeners;

    /**
     * Smoothly update preview position using requestAnimationFrame
     */
    function updatePreviewPosition() {
      if (dragPreview && Number.isFinite(currentMouseX) && Number.isFinite(currentMouseY)) {
        dragPreview.style.left = (currentMouseX + 10) + 'px';
        dragPreview.style.top = (currentMouseY + 10) + 'px';
      }
      if (draggedCard) {
        animationFrameId = requestAnimationFrame(updatePreviewPosition);
      }
    }
    
    function handleDragStart(e) {
      if (activePointerDrag || activeTouchDrag) {
        e.preventDefault();
        return;
      }

      const card = e.target.closest('.mzrearrange-card');
      if (!beginCardDrag(card, e.clientX, e.clientY)) {
        e.preventDefault();
        return;
      }

      // Set drag image to transparent (we use our own preview)
      if (e.dataTransfer) {
        const emptyImg = new Image();
        emptyImg.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        e.dataTransfer.setDragImage(emptyImg, 0, 0);
        e.dataTransfer.effectAllowed = 'move';
      }
    }

    function handleDragEnd(e) {
      finishCardDrag();
    }

    // Track mouse position continuously for smooth preview movement
    document.addEventListener('dragover', (e) => {
      if (draggedCard && Number.isFinite(e.clientX) && Number.isFinite(e.clientY)) {
        currentMouseX = e.clientX;
        currentMouseY = e.clientY;
      }
    }, listenerOptions);

    function handlePointerDown(e) {
      if (e.pointerType === 'mouse' || (typeof e.button === 'number' && e.button !== 0)) return;
      if (activePointerDrag || draggedCard) return;

      const card = e.target.closest('.mzrearrange-card');
      if (!card) return;

      activePointerDrag = {
        pointerId: e.pointerId,
        card: card,
        startX: e.clientX,
        startY: e.clientY,
        currentX: e.clientX,
        currentY: e.clientY,
        hasStarted: false
      };

      if (card.setPointerCapture) {
        try {
          card.setPointerCapture(e.pointerId);
        } catch (err) {
          // Some browsers reject capture if the pointer has already been released.
        }
      }

      e.preventDefault();
    }

    function handlePointerMove(e) {
      if (!activePointerDrag || e.pointerId !== activePointerDrag.pointerId) return;
      if (updateDirectDrag(activePointerDrag, e.clientX, e.clientY)) {
        e.preventDefault();
      }
    }

    function handlePointerUp(e) {
      if (!activePointerDrag || e.pointerId !== activePointerDrag.pointerId) return;
      const state = activePointerDrag;
      activePointerDrag = null;

      if (state.card && state.card.releasePointerCapture) {
        try {
          state.card.releasePointerCapture(e.pointerId);
        } catch (err) {
          // Ignore capture-release mismatches.
        }
      }

      if (state.hasStarted) {
        updateDirectDrag(state, e.clientX, e.clientY);
        commitDraggedCardToPlaceholder();
        finishCardDrag();
        e.preventDefault();
      }
    }

    function handlePointerCancel(e) {
      if (!activePointerDrag || e.pointerId !== activePointerDrag.pointerId) return;
      const state = activePointerDrag;
      activePointerDrag = null;

      if (state.card && state.card.releasePointerCapture) {
        try {
          state.card.releasePointerCapture(e.pointerId);
        } catch (err) {
          // Ignore capture-release mismatches.
        }
      }

      if (state.hasStarted) finishCardDrag();
    }

    function getTouchByIdentifier(touches, identifier) {
      if (!touches) return null;
      for (let i = 0; i < touches.length; ++i) {
        if (touches[i].identifier === identifier) return touches[i];
      }
      return null;
    }

    function handleTouchStart(e) {
      if (activeTouchDrag || draggedCard || !e.touches || e.touches.length !== 1) return;

      const card = e.target.closest('.mzrearrange-card');
      if (!card) return;

      const touch = e.touches[0];
      activeTouchDrag = {
        identifier: touch.identifier,
        card: card,
        startX: touch.clientX,
        startY: touch.clientY,
        currentX: touch.clientX,
        currentY: touch.clientY,
        hasStarted: false
      };

      e.preventDefault();
    }

    function handleTouchMove(e) {
      if (!activeTouchDrag) return;
      const touch = getTouchByIdentifier(e.touches, activeTouchDrag.identifier);
      if (!touch) return;

      if (updateDirectDrag(activeTouchDrag, touch.clientX, touch.clientY)) {
        e.preventDefault();
      }
    }

    function handleTouchEnd(e) {
      if (!activeTouchDrag) return;
      const touch = getTouchByIdentifier(e.changedTouches, activeTouchDrag.identifier);
      if (!touch) return;

      const state = activeTouchDrag;
      activeTouchDrag = null;
      if (state.hasStarted) {
        updateDirectDrag(state, touch.clientX, touch.clientY);
        commitDraggedCardToPlaceholder();
        finishCardDrag();
        e.preventDefault();
      }
    }

    function handleTouchCancel(e) {
      if (!activeTouchDrag) return;
      const touch = getTouchByIdentifier(e.changedTouches, activeTouchDrag.identifier);
      if (!touch) return;

      const state = activeTouchDrag;
      activeTouchDrag = null;
      if (state.hasStarted) finishCardDrag();
    }

    if (supportsPointerEvents) {
      window.addEventListener('pointermove', handlePointerMove, passiveListenerOptions);
      window.addEventListener('pointerup', handlePointerUp, listenerOptions);
      window.addEventListener('pointercancel', handlePointerCancel, listenerOptions);
    } else {
      window.addEventListener('touchmove', handleTouchMove, passiveListenerOptions);
      window.addEventListener('touchend', handleTouchEnd, passiveListenerOptions);
      window.addEventListener('touchcancel', handleTouchCancel, listenerOptions);
    }
    
    // Setup pile drop zones
    const piles = modal.querySelectorAll('.mzrearrange-pile');
    piles.forEach(pile => {
      const cardsContainer = pile.querySelector('.mzrearrange-cards-container');
      
      pile.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
        pile.classList.add('drag-over');
        
        if (!draggedCard) return;

        positionPlaceholderInPile(pile, e.clientY);
      }, listenerOptions);
      
      pile.addEventListener('dragleave', (e) => {
        // Only remove if actually leaving the pile
        if (!pile.contains(e.relatedTarget)) {
          pile.classList.remove('drag-over');
          if (placeholder) {
            placeholder.classList.remove('active');
          }
        }
      }, listenerOptions);
      
      pile.addEventListener('drop', (e) => {
        e.preventDefault();
        pile.classList.remove('drag-over');
        commitDraggedCardToPlaceholder();
      }, listenerOptions);
      
      // Setup listeners for initial cards
      setupCardDragListeners(cardsContainer);
    });
  }
  
  /**
   * Get the element to insert after based on mouse Y position
   */
  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.mzrearrange-card:not(.dragging):not(.mzrearrange-card-placeholder)')];
    
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }
  
  // Helper to make setupCardDragListeners accessible from reset button
  function setupCardDragListeners(container) {
    if (window._mzrearrangeSetupCardDragListeners) {
      window._mzrearrangeSetupCardDragListeners(container);
    }
  }

  /**
   * Hide/remove the MZREARRANGE popup
   */
  function HideMZRearrangePopup() {
    const existing = document.getElementById('mzrearrange-popup');
    if (existing) {
      const modal = existing.querySelector('.mzrearrange-modal');
      if (modal && modal.__mzrearrangeDndAbortController) {
        modal.__mzrearrangeDndAbortController.abort();
        modal.__mzrearrangeDndAbortController = null;
      }
      existing.style.animation = 'mzrearrange-fade-in 0.2s ease-out reverse';
      setTimeout(() => existing.remove(), 150);
    }
    // Cleanup global helper
    delete window._mzrearrangeSetupCardDragListeners;
    window._suppressCardDetail = false;
    // Hide any card detail that may still be showing
    if (typeof HideCardDetail === 'function') HideCardDetail();
  }

  // Export functions to global scope
  window.ShowMZRearrangePopup = ShowMZRearrangePopup;
  window.HideMZRearrangePopup = HideMZRearrangePopup;
  window.parseRearrangeParam = parseRearrangeParam;
  window.serializePiles = serializePiles;

})();
