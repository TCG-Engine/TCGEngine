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
            </form>
        `;
    }

    create() {
        this.app.state.activeGameId = null;
        this.render();
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
}
