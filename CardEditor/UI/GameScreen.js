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

    tagsFromForm() {
        return [...document.querySelectorAll('#gameTagsEditor .tag-row')].map(row => ({
            id: row.dataset.tagId || '',
            name: row.querySelector('.tag-name-input')?.value || ''
        })).filter(tag => tag.name.trim());
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
                return payload;
            },
            async payload => {
                const game = payload.id ? await ApiClient.updateGame(payload) : await ApiClient.createGame(payload);
                form.elements.id.value = game.id;
                form.elements.expectedUpdatedAt.value = game.updated_at || '';
                this.app.state.activeGameId = game.id;
                await this.app.refreshAll();
                this.app.renderContext();
                if (!payload.id) this.render();
                return game;
            }
        );
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
