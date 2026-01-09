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
      background: linear-gradient(145deg, #1a2a3a, #0d1b2a);
      border: 2px solid #3a5a7a;
      border-radius: 16px;
      padding: 24px 32px;
      max-width: 95vw;
      max-height: 85vh;
      overflow: auto;
      box-shadow: 0 0 60px rgba(0, 100, 200, 0.3), inset 0 1px 0 rgba(255,255,255,0.1);
      animation: mzrearrange-slide-up 0.4s ease-out;
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
      font-size: 22px;
      color: #fff;
      text-align: center;
      margin-bottom: 20px;
      text-shadow: 0 0 20px rgba(100, 200, 255, 0.5);
      letter-spacing: 1px;
    }

    .mzrearrange-piles-container {
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      justify-content: center;
      margin-bottom: 24px;
    }

    .mzrearrange-pile {
      background: rgba(0, 20, 40, 0.6);
      border: 2px solid #2a4a6a;
      border-radius: 12px;
      padding: 16px;
      min-width: 180px;
      min-height: 200px;
      transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
      will-change: transform, box-shadow;
    }

    .mzrearrange-pile.drag-over {
      border-color: #00ccff;
      background: rgba(0, 100, 150, 0.3);
      box-shadow: 0 0 30px rgba(0, 200, 255, 0.4);
      transform: scale(1.02);
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
      background: rgba(30, 50, 70, 0.5);
      padding: 4px;
      will-change: transform;
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
      transform: rotate(5deg) scale(1.1);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
      will-change: transform, left, top;
      transition: none;
    }

    .mzrearrange-card-placeholder {
      border: 2px dashed #00ccff;
      border-radius: 8px;
      background: rgba(0, 200, 255, 0.1);
      animation: mzrearrange-pulse 1s ease-in-out infinite;
    }

    @keyframes mzrearrange-pulse {
      0%, 100% { opacity: 0.5; }
      50% { opacity: 1; }
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

    .mzrearrange-btn {
      padding: 12px 32px;
      font-size: 16px;
      font-family: 'Orbitron', sans-serif;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    .mzrearrange-btn-submit {
      background: linear-gradient(145deg, #00cc66, #009944);
      color: #fff;
      box-shadow: 0 4px 15px rgba(0, 200, 100, 0.4);
    }

    .mzrearrange-btn-submit:hover {
      background: linear-gradient(145deg, #00dd77, #00aa55);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 200, 100, 0.6);
    }

    .mzrearrange-btn-submit:active {
      transform: translateY(0);
    }

    .mzrearrange-btn-reset {
      background: linear-gradient(145deg, #555, #333);
      color: #ccc;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .mzrearrange-btn-reset:hover {
      background: linear-gradient(145deg, #666, #444);
      color: #fff;
      transform: translateY(-2px);
    }

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
      <div class="mzrearrange-card" data-card-id="${cardId}" draggable="true">
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
    
    // Title
    const title = document.createElement('div');
    title.className = 'mzrearrange-title';
    title.textContent = tooltip || 'Arrange Cards';
    modal.appendChild(title);
    
    // Instructions
    const instructions = document.createElement('div');
    instructions.className = 'mzrearrange-instructions';
    instructions.textContent = 'Drag cards to reorder within a pile or move between piles';
    modal.appendChild(instructions);
    
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
    
    modal.appendChild(pilesContainer);
    
    // Buttons
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'mzrearrange-buttons';
    
    const resetBtn = document.createElement('button');
    resetBtn.className = 'mzrearrange-btn mzrearrange-btn-reset';
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
    submitBtn.className = 'mzrearrange-btn mzrearrange-btn-submit';
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
    
    modal.appendChild(buttonsDiv);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
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
    
    /**
     * Setup listeners for cards in a container
     */
    function setupCardDragListeners(container) {
      const cards = container.querySelectorAll('.mzrearrange-card');
      cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
      });
    }
    
    // Make setupCardDragListeners available globally for reset
    window._mzrearrangeSetupCardDragListeners = setupCardDragListeners;
    
    /**
     * Smoothly update preview position using requestAnimationFrame
     */
    function updatePreviewPosition() {
      if (dragPreview && currentMouseX && currentMouseY) {
        dragPreview.style.left = (currentMouseX + 10) + 'px';
        dragPreview.style.top = (currentMouseY + 10) + 'px';
      }
      if (draggedCard) {
        animationFrameId = requestAnimationFrame(updatePreviewPosition);
      }
    }
    
    function handleDragStart(e) {
      draggedCard = e.target.closest('.mzrearrange-card');
      if (!draggedCard) return;
      
      draggedCard.classList.add('dragging');
      
      // Store initial mouse position
      currentMouseX = e.clientX;
      currentMouseY = e.clientY;
      
      // Create drag preview
      dragPreview = draggedCard.cloneNode(true);
      dragPreview.classList.add('drag-preview');
      dragPreview.classList.remove('dragging');
      dragPreview.style.left = (currentMouseX + 10) + 'px';
      dragPreview.style.top = (currentMouseY + 10) + 'px';
      document.body.appendChild(dragPreview);
      
      // Create placeholder
      placeholder = document.createElement('div');
      placeholder.className = 'mzrearrange-card-placeholder';
      placeholder.style.height = draggedCard.offsetHeight + 'px';
      placeholder.style.width = draggedCard.offsetWidth + 'px';
      
      // Set drag image to transparent (we use our own preview)
      const emptyImg = new Image();
      emptyImg.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
      e.dataTransfer.setDragImage(emptyImg, 0, 0);
      e.dataTransfer.effectAllowed = 'move';
      
      // Start smooth animation loop
      animationFrameId = requestAnimationFrame(updatePreviewPosition);
    }
    
    function handleDragEnd(e) {
      // Cancel animation frame
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
      
      // Remove drag-over class from all piles
      modal.querySelectorAll('.mzrearrange-pile').forEach(pile => {
        pile.classList.remove('drag-over');
      });
      
      // Update all order badges
      modal.querySelectorAll('.mzrearrange-cards-container').forEach(container => {
        updateOrderBadges(container);
        // Update empty message
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
      
      draggedCard = null;
      currentMouseX = 0;
      currentMouseY = 0;
    }
    
    // Track mouse position continuously for smooth preview movement
    document.addEventListener('dragover', (e) => {
      if (draggedCard && e.clientX && e.clientY) {
        currentMouseX = e.clientX;
        currentMouseY = e.clientY;
      }
    });
    
    // Setup pile drop zones
    const piles = modal.querySelectorAll('.mzrearrange-pile');
    piles.forEach(pile => {
      const cardsContainer = pile.querySelector('.mzrearrange-cards-container');
      
      pile.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        pile.classList.add('drag-over');
        
        if (!draggedCard) return;
        
        // Find insert position
        const cards = [...cardsContainer.querySelectorAll('.mzrearrange-card:not(.dragging)')];
        const afterElement = getDragAfterElement(cardsContainer, e.clientY);
        
        // Remove empty message if present
        const emptyMsg = cardsContainer.querySelector('.mzrearrange-empty-message');
        if (emptyMsg) emptyMsg.remove();
        
        // Position placeholder
        if (placeholder.parentNode !== cardsContainer) {
          if (placeholder.parentNode) placeholder.remove();
        }
        
        if (afterElement) {
          cardsContainer.insertBefore(placeholder, afterElement);
        } else {
          cardsContainer.appendChild(placeholder);
        }
      });
      
      pile.addEventListener('dragleave', (e) => {
        // Only remove if actually leaving the pile
        if (!pile.contains(e.relatedTarget)) {
          pile.classList.remove('drag-over');
        }
      });
      
      pile.addEventListener('drop', (e) => {
        e.preventDefault();
        pile.classList.remove('drag-over');
        
        if (!draggedCard || !placeholder) return;
        
        // Move the card to placeholder position
        if (placeholder.parentNode) {
          placeholder.parentNode.insertBefore(draggedCard, placeholder);
          placeholder.remove();
        }
        
        // Re-setup listeners for the moved card
        draggedCard.addEventListener('dragstart', handleDragStart);
        draggedCard.addEventListener('dragend', handleDragEnd);
      });
      
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
      existing.style.animation = 'mzrearrange-fade-in 0.2s ease-out reverse';
      setTimeout(() => existing.remove(), 150);
    }
    // Cleanup global helper
    delete window._mzrearrangeSetupCardDragListeners;
  }

  // Export functions to global scope
  window.ShowMZRearrangePopup = ShowMZRearrangePopup;
  window.HideMZRearrangePopup = HideMZRearrangePopup;
  window.parseRearrangeParam = parseRearrangeParam;
  window.serializePiles = serializePiles;

})();
