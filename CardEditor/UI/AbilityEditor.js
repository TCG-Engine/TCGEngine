/**
 * AbilityEditor Component
 * Manages the UI for editing card abilities
 */
class AbilityEditor {
    constructor(rootName, cardId, macros, existingAbilities = [], assetPath = null) {
        this.rootName = rootName;
        this.cardId = cardId;
        this.macros = macros;
        this.assetPath = assetPath || rootName; // Default to current root if not specified
        this.cardImplemented = false; // Track if card is marked as implemented
        this.abilities = existingAbilities.map(a => ({
            id: a.id || null,
            macroName: a.macro_name,
            abilityCode: a.ability_code,
            abilityName: a.ability_name,
            isImplemented: a.is_implemented ? true : false
        }));
        // Check if card should be marked as implemented (if any ability is implemented)
        if (this.abilities.length > 0 && this.abilities.some(a => a.isImplemented)) {
            this.cardImplemented = true;
        }
        this.editorContainer = document.getElementById('editorArea');
        this.statusMessage = null;
    }
    
    render() {
        // Use the reflected asset path for images
        // Go up two levels (CardEditor/UI -> CardEditor -> root) to reach the actual root folder
        const cardImagePath = `../../${this.assetPath}/WebpImages/${this.cardId}.webp`;
        
        const html = `
            <div class="card-image-sidebar">
                <div class="card-image-container" id="cardImageContainer">
                    <img src="${cardImagePath}" alt="${this.cardId}" onerror="this.parentElement.classList.add('empty'); this.style.display='none'; this.parentElement.textContent='Image not found';" />
                </div>
            </div>
            
            <div class="editor-container">
                <div class="card-header">
                    <div class="card-info">
                        <h2>${this.cardId}</h2>
                        <p>Root: ${this.rootName}</p>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div class="inline-checkbox">
                            <input 
                                type="checkbox" 
                                id="cardImplemented"
                                ${this.cardImplemented ? 'checked' : ''}
                                onchange="window.abilityEditor.updateCardImplemented(this.checked)"
                            />
                            <label for="cardImplemented">Card Implemented</label>
                        </div>
                        <button class="save-button" onclick="window.abilityEditor.saveAbilities()">
                            Save Abilities
                        </button>
                    </div>
                </div>
                
                <div id="statusArea"></div>
                
                <div class="abilities-area" id="abilitiesArea">
                    ${this.abilities.length === 0 
                        ? '<div class="loading">No abilities yet. Click "Add Ability" to create one.</div>'
                        : this.abilities.map((ability, index) => this.renderAbility(ability, index)).join('')
                    }
                </div>
                
                <button class="add-ability-btn" onclick="window.abilityEditor.addAbility()">
                    + Add Ability
                </button>
                
                <div class="footer">
                    Root: ${this.rootName} | Card: ${this.cardId} | Abilities: ${this.abilities.length}
                </div>
            </div>
        `;
        
        this.editorContainer.innerHTML = html;
        window.abilityEditor = this;
    }
    
    renderAbility(ability, index) {
        const macroOptions = this.macros.map(m => 
            `<option value="${m}" ${m === ability.macroName ? 'selected' : ''}>${m}</option>`
        ).join('');
        
        const isImplementedChecked = ability.isImplemented ? 'checked' : '';
        
        return `
            <div class="ability-editor" data-index="${index}">
                <div class="ability-header">
                    <div class="form-group" style="flex: 0 0 40%;">
                        <label>Macro</label>
                        <select onchange="window.abilityEditor.updateAbility(${index}, 'macroName', this.value)">
                            <option value="">-- Select Macro --</option>
                            ${macroOptions}
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Ability Name (Optional)</label>
                        <input 
                            type="text" 
                            placeholder="e.g., 'Destroy on Exhaust'"
                            value="${ability.abilityName || ''}"
                            onchange="window.abilityEditor.updateAbility(${index}, 'abilityName', this.value)"
                        />
                    </div>
                    <div class="form-group" style="flex: 0 0 auto;">
                        <div class="inline-checkbox">
                            <input 
                                type="checkbox" 
                                id="implemented_${index}"
                                ${isImplementedChecked}
                                onchange="window.abilityEditor.updateAbility(${index}, 'isImplemented', this.checked)"
                            />
                            <label for="implemented_${index}">Implemented</label>
                        </div>
                    </div>
                    <button class="delete-btn" onclick="window.abilityEditor.deleteAbility(${index})">
                        Delete
                    </button>
                </div>
                
                <label style="font-size: 12px; color: #858585; text-transform: uppercase; font-weight: 600;">
                    Code / Function Body
                </label>
                <textarea 
                    class="code-editor"
                    placeholder="// Enter the function body for this ability&#10;// Available parameters will depend on the macro"
                    onchange="window.abilityEditor.updateAbility(${index}, 'abilityCode', this.value)"
                >${ability.abilityCode || ''}</textarea>
            </div>
        `;
    }
    
    updateAbility(index, field, value) {
        if (index >= 0 && index < this.abilities.length) {
            this.abilities[index][field] = value;
        }
    }
    
    updateCardImplemented(isImplemented) {
        this.cardImplemented = isImplemented;
    }
    
    addAbility() {
        this.abilities.push({
            id: null,
            macroName: '',
            abilityCode: '',
            abilityName: '',
            isImplemented: false
        });
        this.render();
    }
    
    deleteAbility(index) {
        if (confirm('Delete this ability?')) {
            this.abilities.splice(index, 1);
            this.render();
        }
    }
    
    async saveAbilities() {
        // Validate that any abilities that exist have both macro and code
        const validAbilities = this.abilities.filter(a => a.macroName && a.abilityCode);
        const incompleteAbilities = this.abilities.filter(a => (a.macroName && !a.abilityCode) || (!a.macroName && a.abilityCode));
        
        if (incompleteAbilities.length > 0) {
            this.showStatus('error', 'All abilities must have both a macro and code, or be removed');
            return;
        }
        
        // Allow saving if: (1) there are valid abilities, or (2) card is marked as implemented with no abilities
        if (validAbilities.length === 0 && !this.cardImplemented) {
            this.showStatus('info', 'Add abilities or mark the card as implemented to save');
            return;
        }
        
        try {
            const response = await fetch('../API/SaveAbilities.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    root: this.rootName,
                    card: this.cardId,
                    abilities: validAbilities,
                    cardImplemented: this.cardImplemented
                })
            });
            
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to save abilities');
            }
            
            // Reload abilities from database to get updated IDs
            const loadResponse = await fetch(`../API/LoadAbilities.php?root=${encodeURIComponent(this.rootName)}&card=${encodeURIComponent(this.cardId)}`);
            const loadData = await loadResponse.json();
            
            if (loadData.success) {
                this.abilities = loadData.abilities.map(a => ({
                    id: a.id,
                    macroName: a.macro_name,
                    abilityCode: a.ability_code,
                    abilityName: a.ability_name,
                    isImplemented: a.is_implemented ? true : false
                }));
            }
            
            const message = validAbilities.length > 0 
                ? `Saved ${validAbilities.length} ability(ies)` 
                : 'Card marked as implemented';
            this.showStatus('success', message + ' successfully');
            this.render();
            
        } catch (error) {
            this.showStatus('error', `Save failed: ${error.message}`);
        }
    }
    
    showStatus(type, message) {
        const statusArea = document.getElementById('statusArea');
        if (statusArea) {
            statusArea.innerHTML = `
                <div class="${type}-message">${message}</div>
            `;
            
            // Auto-clear success messages after 3 seconds
            if (type === 'success') {
                setTimeout(() => {
                    if (statusArea) {
                        statusArea.innerHTML = '';
                    }
                }, 3000);
            }
        }
    }
}
