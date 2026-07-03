class GameScreen {
    constructor(app) {
        this.app = app;
    }

    render() {
        const games = this.app.state.games;
        const canCreate = this.app.state.currentUser.loggedIn;
        this.app.setContent(`
            <section class="workbench two-column">
                <div class="pane">
                    <div class="pane-head">
                        <h2>Games</h2>
                        <button onclick="app.screens.games.create()" ${canCreate ? '' : 'disabled'}>New Game</button>
                    </div>
                    ${canCreate ? '' : '<div class="info-message">Log in to create or edit CardEditor games.</div>'}
                    <div class="list">
                        ${games.length ? games.map(game => `
                            <button class="list-row ${this.app.state.activeGameId === game.id ? 'active' : ''}" onclick="app.selectGame(${game.id})">
                                <strong>${PreviewRenderer.escape(game.name)}</strong>
                                <span>${PreviewRenderer.escape(game.slug)} / ${PreviewRenderer.escape(game.ownership?.visibility || 'private')}${game.can_edit ? '' : ' / read only'}</span>
                            </button>
                        `).join('') : '<div class="empty-state">No authored games yet.</div>'}
                    </div>
                </div>
                <div class="pane">
                    <div class="pane-head"><h2>${this.app.activeGame() ? 'Edit Game' : 'Game Details'}</h2></div>
                    ${this.form(this.app.activeGame())}
                </div>
            </section>
        `);
        this.bindAutosave();
    }

    form(game) {
        const canEdit = game ? game.can_edit : this.app.state.currentUser.loggedIn;
        const disabled = canEdit ? '' : 'disabled';
        const visibility = game?.ownership?.visibility || 'private';
        return `
            <form class="stack-form" id="gameForm">
                <input type="hidden" name="id" value="${game ? game.id : ''}">
                <input type="hidden" name="expectedUpdatedAt" value="${game ? PreviewRenderer.escape(game.updated_at || '') : ''}">
                <label>Name<input name="name" value="${game ? PreviewRenderer.escape(game.name) : ''}" required ${disabled}></label>
                <label>Slug<input name="slug" value="${game ? PreviewRenderer.escape(game.slug) : ''}" placeholder="auto-from-name" ${disabled}></label>
                <label>Visibility
                    <select name="visibility" ${disabled}>
                        ${['private', 'link only', 'public', 'team'].map(value => `<option value="${value}" ${visibility === value ? 'selected' : ''}>${value}</option>`).join('')}
                    </select>
                </label>
                <label>Description<textarea name="description" ${disabled}>${game ? PreviewRenderer.escape(game.description || '') : ''}</textarea></label>
                <div class="form-section">
                    <div class="section-head">
                        <strong>Tags</strong>
                        <button type="button" onclick="app.screens.games.addTag()" ${disabled}>Add Tag</button>
                    </div>
                    <div id="gameTagsEditor" class="tag-editor">
                        ${(game?.tags || []).map(tag => this.tagRow(tag, disabled)).join('') || '<div class="empty-state compact-empty">No tags yet.</div>'}
                    </div>
                </div>
                <div class="form-section">
                    <div class="section-head">
                        <strong>Icon Enums</strong>
                        <button type="button" onclick="app.screens.games.addEnum()" ${disabled}>Add Enum</button>
                    </div>
                    <div id="gameEnumsEditor" class="enum-editor">
                        ${(game?.enums || []).map(item => this.enumRow(item, disabled)).join('') || '<div class="empty-state compact-empty">No icon enums yet.</div>'}
                    </div>
                </div>
            </form>
        `;
    }

    tagRow(tag = {}, disabled = '') {
        return `
            <div class="tag-row" data-tag-id="${tag.id || ''}">
                <input class="tag-name-input" value="${PreviewRenderer.escape(tag.name || '')}" placeholder="Tag name" ${disabled}>
                <button type="button" class="link-button" onclick="app.screens.games.removeTag(this)" ${disabled}>Remove</button>
            </div>
        `;
    }

    enumRow(item = {}, disabled = '') {
        const options = item.options || [];
        return `
            <div class="enum-row" data-enum-id="${item.id || ''}">
                <div class="enum-row-head">
                    <input class="enum-name-input" value="${PreviewRenderer.escape(item.name || '')}" placeholder="Enum name, e.g. Element" ${disabled}>
                    <button type="button" onclick="app.screens.games.addEnumOption(this)" ${disabled}>Add Option</button>
                    <button type="button" class="link-button" onclick="app.screens.games.removeEnum(this)" ${disabled}>Remove</button>
                </div>
                <div class="enum-options">
                    ${options.map(option => this.enumOptionRow(option, disabled)).join('') || '<div class="empty-state compact-empty">No options yet.</div>'}
                </div>
            </div>
        `;
    }

    enumOptionRow(option = {}, disabled = '') {
        const assets = this.app.state.assets || [];
        return `
            <div class="enum-option-row" data-option-id="${option.id || ''}">
                <input class="enum-option-label" value="${PreviewRenderer.escape(option.label || '')}" placeholder="Label" ${disabled}>
                <input class="enum-option-value" value="${PreviewRenderer.escape(option.value || '')}" placeholder="stored value" ${disabled}>
                <select class="enum-option-asset" onchange="app.screens.games.updateEnumOptionAssetPreview(this)" ${disabled}>
                    <option value="">Icon asset</option>
                    ${assets.map(asset => `<option value="${asset.id}" ${Number(option.asset_id || option.assetId || 0) === Number(asset.id) ? 'selected' : ''}>${PreviewRenderer.escape(asset.original_filename)}</option>`).join('')}
                </select>
                ${this.enumOptionPreview(option)}
                <input type="file" accept="image/*" onchange="app.screens.games.uploadEnumOptionAsset(this)" ${disabled}>
                <button type="button" class="link-button" onclick="app.screens.games.removeEnumOption(this)" ${disabled}>Remove</button>
            </div>
        `;
    }

    enumOptionPreview(option = {}) {
        const assetId = Number(option.asset_id || option.assetId || 0);
        const asset = option.asset || (this.app.state.assets || []).find(item => Number(item.id) === assetId);
        return asset?.url ? `<img class="enum-icon-preview" src="${PreviewRenderer.escape(asset.url)}" alt="">` : '<span class="enum-icon-preview empty"></span>';
    }

    create() {
        this.app.state.activeGameId = null;
        this.render();
    }

    addTag() {
        const host = document.getElementById('gameTagsEditor');
        if (!host) return;
        if (host.querySelector('.compact-empty')) host.innerHTML = '';
        host.insertAdjacentHTML('beforeend', this.tagRow({}, ''));
        host.querySelector('.tag-row:last-child input')?.focus();
    }

    removeTag(button) {
        button.closest('.tag-row')?.remove();
        const host = document.getElementById('gameTagsEditor');
        if (host && !host.querySelector('.tag-row')) {
            host.innerHTML = '<div class="empty-state compact-empty">No tags yet.</div>';
        }
        this.saveGameFormNow();
    }

    addEnum() {
        const host = document.getElementById('gameEnumsEditor');
        if (!host) return;
        if (host.querySelector('.compact-empty')) host.innerHTML = '';
        host.insertAdjacentHTML('beforeend', this.enumRow({}, ''));
        host.querySelector('.enum-row:last-child .enum-name-input')?.focus();
    }

    removeEnum(button) {
        button.closest('.enum-row')?.remove();
        const host = document.getElementById('gameEnumsEditor');
        if (host && !host.querySelector('.enum-row')) {
            host.innerHTML = '<div class="empty-state compact-empty">No icon enums yet.</div>';
        }
        this.saveGameFormNow();
    }

    addEnumOption(button) {
        const optionsHost = button.closest('.enum-row')?.querySelector('.enum-options');
        if (!optionsHost) return;
        if (optionsHost.querySelector('.compact-empty')) optionsHost.innerHTML = '';
        optionsHost.insertAdjacentHTML('beforeend', this.enumOptionRow({}, ''));
        optionsHost.querySelector('.enum-option-row:last-child .enum-option-label')?.focus();
    }

    removeEnumOption(button) {
        const optionsHost = button.closest('.enum-options');
        button.closest('.enum-option-row')?.remove();
        if (optionsHost && !optionsHost.querySelector('.enum-option-row')) {
            optionsHost.innerHTML = '<div class="empty-state compact-empty">No options yet.</div>';
        }
        this.saveGameFormNow();
    }

    async uploadEnumOptionAsset(input) {
        try {
            const asset = await this.app.assetPicker.uploadFromInput(input);
            const row = input.closest('.enum-option-row');
            const select = row?.querySelector('.enum-option-asset');
            if (asset && select) {
                let option = [...select.options].find(item => Number(item.value) === Number(asset.id));
                if (!option) {
                    option = document.createElement('option');
                    option.value = asset.id;
                    option.textContent = asset.original_filename;
                    select.appendChild(option);
                }
                option.selected = true;
                this.updateEnumOptionAssetPreview(select);
                this.saveGameFormNow();
            }
        } catch (error) {
            this.app.toast(error.message, 'error');
        } finally {
            input.value = '';
        }
    }

    updateEnumOptionAssetPreview(select) {
        const row = select.closest('.enum-option-row');
        const asset = (this.app.state.assets || []).find(item => Number(item.id) === Number(select.value));
        const preview = row?.querySelector('.enum-icon-preview');
        if (!row || !preview) return;
        preview.outerHTML = asset?.url
            ? `<img class="enum-icon-preview" src="${PreviewRenderer.escape(asset.url)}" alt="">`
            : '<span class="enum-icon-preview empty"></span>';
    }

    tagsFromForm() {
        return [...document.querySelectorAll('#gameTagsEditor .tag-row')].map(row => ({
            id: row.dataset.tagId || '',
            name: row.querySelector('.tag-name-input')?.value || ''
        })).filter(tag => tag.name.trim());
    }

    enumsFromForm() {
        return [...document.querySelectorAll('#gameEnumsEditor .enum-row')].map(row => ({
            id: row.dataset.enumId || '',
            name: row.querySelector('.enum-name-input')?.value || '',
            options: [...row.querySelectorAll('.enum-option-row')].map((optionRow, index) => ({
                id: optionRow.dataset.optionId || '',
                label: optionRow.querySelector('.enum-option-label')?.value || '',
                value: optionRow.querySelector('.enum-option-value')?.value || '',
                assetId: optionRow.querySelector('.enum-option-asset')?.value || '',
                sortOrder: index
            })).filter(option => option.label.trim())
        })).filter(item => item.name.trim());
    }

    bindAutosave() {
        const form = document.getElementById('gameForm');
        if (!form) return;
        this.app.autosave.bindForm(
            form,
            'game-form',
            () => {
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!payload.name.trim()) return null;
                payload.tags = this.tagsFromForm();
                payload.enums = this.enumsFromForm();
                return payload;
            },
            async payload => {
                const game = payload.id ? await ApiClient.updateGame(payload) : await ApiClient.createGame(payload);
                form.elements.id.value = game.id;
                form.elements.expectedUpdatedAt.value = game.updated_at || '';
                this.app.state.activeGameId = game.id;
                await this.app.refreshAll();
                this.app.renderContext();
                if (!payload.id || this.payloadHasUnsavedMetadata(payload)) this.render();
                return game;
            }
        );
    }

    payloadHasUnsavedMetadata(payload) {
        const hasNewTag = (payload.tags || []).some(tag => !tag.id);
        const hasNewEnum = (payload.enums || []).some(item => !item.id || (item.options || []).some(option => !option.id));
        return hasNewTag || hasNewEnum;
    }

    saveGameFormNow() {
        this.app.autosave.saveNow(
            'game-form',
            () => {
                const form = document.getElementById('gameForm');
                if (!form) return null;
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!payload.name?.trim()) return null;
                payload.tags = this.tagsFromForm();
                payload.enums = this.enumsFromForm();
                return payload;
            },
            async payload => {
                const game = payload.id ? await ApiClient.updateGame(payload) : await ApiClient.createGame(payload);
                this.app.state.activeGameId = game.id;
                await this.app.refreshAll();
                this.app.renderContext();
                this.render();
                return game;
            }
        );
    }
}
