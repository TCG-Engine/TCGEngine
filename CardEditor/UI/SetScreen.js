class SetScreen {
    constructor(app) {
        this.app = app;
    }

    render() {
        if (!this.app.requireGame()) return;
        const sets = this.app.state.sets;
        const activeSet = this.app.activeSet();
        this.app.setContent(`
            <section class="workbench two-column">
                <div class="pane">
                    <div class="pane-head">
                        <h2>Sets</h2>
                        <button onclick="app.screens.sets.create()">New Set</button>
                    </div>
                    <div class="list">
                        ${sets.length ? sets.map(set => `
                            <button class="list-row ${this.app.state.activeSetId === set.id ? 'active' : ''}" onclick="app.selectSet(${set.id})">
                                <strong>${PreviewRenderer.escape(set.name)}</strong>
                                <span>${PreviewRenderer.escape(set.slug)}</span>
                            </button>
                        `).join('') : '<div class="empty-state">No sets in this game yet.</div>'}
                    </div>
                </div>
                <div class="pane">
                    <div class="pane-head"><h2>${activeSet ? 'Edit Set' : 'Set Details'}</h2></div>
                    ${this.form(activeSet)}
                </div>
            </section>
        `);
    }

    form(set) {
        return `
            <form class="stack-form" onsubmit="app.screens.sets.save(event)">
                <input type="hidden" name="id" value="${set ? set.id : ''}">
                <input type="hidden" name="gameId" value="${this.app.state.activeGameId || ''}">
                <label>Name<input name="name" value="${set ? PreviewRenderer.escape(set.name) : ''}" required></label>
                <label>Slug<input name="slug" value="${set ? PreviewRenderer.escape(set.slug) : ''}" placeholder="auto-from-name"></label>
                <label>Description<textarea name="description">${set ? PreviewRenderer.escape(set.description || '') : ''}</textarea></label>
                <button type="submit">${set ? 'Save Set' : 'Create Set'}</button>
            </form>
        `;
    }

    create() {
        this.app.state.activeSetId = null;
        this.render();
    }

    async save(event) {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(event.target).entries());
        try {
            if (payload.id) {
                await ApiClient.updateSet(payload);
                this.app.toast('Set saved');
            } else {
                const set = await ApiClient.createSet(payload);
                this.app.state.activeSetId = set.id;
                this.app.toast('Set created');
            }
            await this.app.refreshSets();
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
