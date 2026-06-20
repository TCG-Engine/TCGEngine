class AssetPicker {
    constructor(app) {
        this.app = app;
    }

    async list() {
        if (!this.app.state.activeGameId) return [];
        return ApiClient.listAssets(this.app.state.activeGameId);
    }

    async uploadFromInput(input) {
        if (!this.app.state.activeGameId || !input.files || !input.files[0]) return null;
        const asset = await ApiClient.uploadAsset(this.app.state.activeGameId, input.files[0]);
        await this.app.refreshAssets();
        return asset;
    }
}
