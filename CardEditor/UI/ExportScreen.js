class ExportScreen {
    constructor(app) {
        this.app = app;
    }

    render() {
        if (!this.app.requireGame()) return;
        const game = this.app.activeGame();
        this.app.setContent(`
            <section class="workbench two-column">
                <div class="pane">
                    <div class="pane-head"><h2>Export</h2></div>
                    <div class="summary-grid">
                        <div><strong>${this.app.state.templates.length}</strong><span>Templates</span></div>
                        <div><strong>${this.app.state.sets.length}</strong><span>Sets</span></div>
                        <div><strong>${this.app.state.assets.length}</strong><span>Assets</span></div>
                    </div>
                    <div class="button-row">
                        <button onclick="app.screens.export.exportGame()">Export Game JSON</button>
                        <select id="exportSetSelect">
                            ${this.app.state.sets.map(set => `<option value="${set.id}">${PreviewRenderer.escape(set.name)}</option>`).join('')}
                        </select>
                        <button onclick="app.screens.export.exportSet()">Export Set JSON</button>
                    </div>
                </div>
                <div class="pane">
                    <div class="pane-head"><h2>${PreviewRenderer.escape(game.name)} JSON</h2></div>
                    <textarea id="exportOutput" class="json-output" spellcheck="false"></textarea>
                </div>
            </section>
        `);
    }

    async exportGame() {
        try {
            const data = await ApiClient.exportGame(this.app.state.activeGameId);
            document.getElementById('exportOutput').value = JSON.stringify(data, null, 2);
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }

    async exportSet() {
        const setId = document.getElementById('exportSetSelect')?.value;
        if (!setId) return;
        try {
            const data = await ApiClient.exportSet(setId);
            document.getElementById('exportOutput').value = JSON.stringify(data, null, 2);
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
