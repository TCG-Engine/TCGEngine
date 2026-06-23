class SetScreen {
    constructor(app) {
        this.app = app;
    }

    render() {
        if (!this.app.requireGame()) return;
        const sets = this.app.state.sets;
        const activeSet = this.app.activeSet();
        const canEdit = this.app.activeGame()?.can_edit;
        this.app.setContent(`
            <section class="workbench two-column">
                <div class="pane">
                    <div class="pane-head">
                        <h2>Sets</h2>
                        <button onclick="app.screens.sets.create()" ${canEdit ? '' : 'disabled'}>New Set</button>
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
        this.bindAutosave();
    }

    form(set) {
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        return `
            <form class="stack-form" id="setForm">
                <input type="hidden" name="id" value="${set ? set.id : ''}">
                <input type="hidden" name="gameId" value="${this.app.state.activeGameId || ''}">
                <input type="hidden" name="expectedUpdatedAt" value="${set ? PreviewRenderer.escape(set.updated_at || '') : ''}">
                <label>Name<input name="name" value="${set ? PreviewRenderer.escape(set.name) : ''}" required ${disabled}></label>
                <label>Slug<input name="slug" value="${set ? PreviewRenderer.escape(set.slug) : ''}" placeholder="auto-from-name" ${disabled}></label>
                <label>Description<textarea name="description" ${disabled}>${set ? PreviewRenderer.escape(set.description || '') : ''}</textarea></label>
            </form>
        `;
    }

    create() {
        this.app.state.activeSetId = null;
        this.render();
    }

    bindAutosave() {
        const form = document.getElementById('setForm');
        if (!form) return;
        this.app.autosave.bindForm(
            form,
            'set-form',
            () => {
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!payload.name.trim()) return null;
                return payload;
            },
            async payload => {
                const set = payload.id ? await ApiClient.updateSet(payload) : await ApiClient.createSet(payload);
                form.elements.id.value = set.id;
                form.elements.expectedUpdatedAt.value = set.updated_at || '';
                this.app.state.activeSetId = set.id;
                await this.app.refreshSets();
                if (!payload.id) this.render();
                return set;
            }
        );
    }
}
