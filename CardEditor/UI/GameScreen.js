class GameScreen {
    constructor(app) {
        this.app = app;
    }

    render() {
        const games = this.app.state.games;
        this.app.setContent(`
            <section class="workbench two-column">
                <div class="pane">
                    <div class="pane-head">
                        <h2>Games</h2>
                        <button onclick="app.screens.games.create()">New Game</button>
                    </div>
                    <div class="list">
                        ${games.length ? games.map(game => `
                            <button class="list-row ${this.app.state.activeGameId === game.id ? 'active' : ''}" onclick="app.selectGame(${game.id})">
                                <strong>${PreviewRenderer.escape(game.name)}</strong>
                                <span>${PreviewRenderer.escape(game.slug)}</span>
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
    }

    form(game) {
        return `
            <form class="stack-form" onsubmit="app.screens.games.save(event)">
                <input type="hidden" name="id" value="${game ? game.id : ''}">
                <label>Name<input name="name" value="${game ? PreviewRenderer.escape(game.name) : ''}" required></label>
                <label>Slug<input name="slug" value="${game ? PreviewRenderer.escape(game.slug) : ''}" placeholder="auto-from-name"></label>
                <label>Description<textarea name="description">${game ? PreviewRenderer.escape(game.description || '') : ''}</textarea></label>
                <button type="submit">${game ? 'Save Game' : 'Create Game'}</button>
            </form>
        `;
    }

    create() {
        this.app.state.activeGameId = null;
        this.render();
    }

    async save(event) {
        event.preventDefault();
        const form = new FormData(event.target);
        const payload = Object.fromEntries(form.entries());
        try {
            if (payload.id) {
                await ApiClient.updateGame(payload);
                this.app.toast('Game saved');
            } else {
                const game = await ApiClient.createGame(payload);
                this.app.state.activeGameId = game.id;
                this.app.toast('Game created');
            }
            await this.app.refreshAll();
            this.app.show('games');
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
