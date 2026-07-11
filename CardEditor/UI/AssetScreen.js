class AssetScreen {
    constructor(app) {
        this.app = app;
        this.query = '';
        this.usageFilter = 'all';
        this.kindFilter = 'all';
    }

    render() {
        if (!this.app.requireGame()) return;
        const canEdit = this.app.activeGame()?.can_edit;
        const assets = this.filteredAssets();
        const kinds = [...new Set((this.app.state.assets || []).map(asset => asset.asset_kind || 'asset'))].sort();
        this.app.setContent(`
            <section class="asset-workbench">
                <div class="pane asset-pane">
                    <div class="asset-toolbar">
                        <div>
                            <h2>Assets</h2>
                            <span>${(this.app.state.assets || []).length} in ${PreviewRenderer.escape(this.app.activeGame()?.name || 'game')}</span>
                        </div>
                        <div class="asset-upload">
                            <input id="assetUploadInput" type="file" accept="image/*" onchange="app.screens.assets.uploadAsset(this)" ${canEdit ? '' : 'disabled'}>
                        </div>
                    </div>
                    <div class="asset-filters">
                        <label>Search<input value="${PreviewRenderer.escape(this.query)}" placeholder="filename, kind, MIME" oninput="app.screens.assets.setQuery(this.value)"></label>
                        <label>Usage
                            <select onchange="app.screens.assets.setUsageFilter(this.value)">
                                ${['all', 'used', 'unused'].map(value => `<option value="${value}" ${this.usageFilter === value ? 'selected' : ''}>${value}</option>`).join('')}
                            </select>
                        </label>
                        <label>Kind
                            <select onchange="app.screens.assets.setKindFilter(this.value)">
                                <option value="all" ${this.kindFilter === 'all' ? 'selected' : ''}>all</option>
                                ${kinds.map(value => `<option value="${PreviewRenderer.escape(value)}" ${this.kindFilter === value ? 'selected' : ''}>${PreviewRenderer.escape(value)}</option>`).join('')}
                            </select>
                        </label>
                    </div>
                    <div class="asset-grid">
                        ${assets.length ? assets.map(asset => this.assetCard(asset, canEdit)).join('') : '<div class="empty-state page-empty">No assets match these filters.</div>'}
                    </div>
                </div>
            </section>
        `);
    }

    filteredAssets() {
        const q = this.query.trim().toLowerCase();
        return (this.app.state.assets || []).filter(asset => {
            const usageCount = Number(asset.usage_count || 0);
            if (this.usageFilter === 'used' && usageCount === 0) return false;
            if (this.usageFilter === 'unused' && usageCount > 0) return false;
            if (this.kindFilter !== 'all' && asset.asset_kind !== this.kindFilter) return false;
            if (!q) return true;
            return [
                asset.original_filename,
                asset.asset_kind,
                asset.mime_type,
                asset.extension
            ].some(value => String(value || '').toLowerCase().includes(q));
        });
    }

    assetCard(asset, canEdit) {
        const usage = asset.usage || [];
        const isImage = String(asset.mime_type || '').startsWith('image/');
        const usageLabel = usage.length ? `${usage.length} use${usage.length === 1 ? '' : 's'}` : 'unused';
        return `
            <article class="asset-card">
                <button type="button" class="asset-preview-button" onclick="app.screens.assets.previewAsset(${asset.id})" aria-label="Preview ${PreviewRenderer.escape(asset.original_filename)}">
                    ${isImage ? `<img src="${PreviewRenderer.escape(asset.url)}" alt="">` : `<span>${PreviewRenderer.escape((asset.extension || asset.asset_kind || 'file').toUpperCase())}</span>`}
                </button>
                <div class="asset-card-body">
                    <strong title="${PreviewRenderer.escape(asset.original_filename)}">${PreviewRenderer.escape(asset.original_filename)}</strong>
                    <span>${PreviewRenderer.escape(asset.asset_kind || 'asset')} / ${PreviewRenderer.escape(asset.mime_type || asset.extension || 'unknown')}</span>
                    <span>${this.dimensions(asset)}${asset.file_size ? ' / ' + this.formatBytes(asset.file_size) : ''}</span>
                    <span class="${usage.length ? 'asset-used' : 'asset-unused'}">${usageLabel}</span>
                </div>
                <div class="asset-usage-list">
                    ${usage.length ? usage.map((item, index) => `<button type="button" class="link-button" onclick="app.screens.assets.openUsage(${asset.id}, ${index})">${PreviewRenderer.escape(item.label)}</button>`).join('') : '<span>No references</span>'}
                </div>
                <div class="asset-actions">
                    <button type="button" onclick="app.screens.assets.copyAssetUrl(${asset.id})">Copy URL</button>
                    <button type="button" class="danger" onclick="app.screens.assets.deleteAsset(${asset.id})" ${canEdit && !usage.length ? '' : 'disabled'}>Delete</button>
                </div>
            </article>
        `;
    }

    dimensions(asset) {
        return asset.width && asset.height ? `${asset.width}x${asset.height}` : 'no dimensions';
    }

    formatBytes(value) {
        const size = Number(value || 0);
        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${Math.round(size / 102.4) / 10} KB`;
        return `${Math.round(size / 104857.6) / 10} MB`;
    }

    setQuery(value) {
        this.query = value || '';
        this.renderAssetGrid();
    }

    setUsageFilter(value) {
        this.usageFilter = value || 'all';
        this.renderAssetGrid();
    }

    setKindFilter(value) {
        this.kindFilter = value || 'all';
        this.renderAssetGrid();
    }

    renderAssetGrid() {
        const host = document.querySelector('.asset-grid');
        if (!host) return;
        const assets = this.filteredAssets();
        const canEdit = this.app.activeGame()?.can_edit;
        host.innerHTML = assets.length
            ? assets.map(asset => this.assetCard(asset, canEdit)).join('')
            : '<div class="empty-state page-empty">No assets match these filters.</div>';
    }

    async uploadAsset(input) {
        try {
            const asset = await this.app.assetPicker.uploadFromInput(input);
            if (!asset) return;
            await this.app.refreshAssets();
            this.app.toast('Asset uploaded');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        } finally {
            input.value = '';
        }
    }

    openUsage(assetId, usageIndex) {
        const asset = (this.app.state.assets || []).find(item => Number(item.id) === Number(assetId));
        const usage = asset?.usage?.[usageIndex];
        if (usage) this.app.openAssetUsage(usage);
    }

    previewAsset(id) {
        const asset = (this.app.state.assets || []).find(item => Number(item.id) === Number(id));
        if (!asset) return;
        const isImage = String(asset.mime_type || '').startsWith('image/');
        const host = document.getElementById('authModalHost');
        if (!host) return;
        host.innerHTML = `
            <div class="modal-backdrop" onclick="app.screens.assets.closePreview(event)">
                <section class="auth-modal asset-preview-modal" role="dialog" aria-modal="true">
                    <div class="modal-head">
                        <h2>${PreviewRenderer.escape(asset.original_filename)}</h2>
                        <button type="button" class="icon-button" onclick="app.screens.assets.closePreview()">x</button>
                    </div>
                    ${isImage ? `<img class="asset-preview-large" src="${PreviewRenderer.escape(asset.url)}" alt="">` : '<div class="empty-state">Preview unavailable for this asset type.</div>'}
                    <div class="asset-detail-grid">
                        <span>${PreviewRenderer.escape(asset.mime_type || '')}</span>
                        <span>${this.dimensions(asset)}</span>
                        <span>${this.formatBytes(asset.file_size)}</span>
                    </div>
                </section>
            </div>
        `;
    }

    closePreview(event = null) {
        if (event && !event.target.classList.contains('modal-backdrop')) return;
        const host = document.getElementById('authModalHost');
        if (host) host.innerHTML = '';
    }

    async copyAssetUrl(id) {
        const asset = (this.app.state.assets || []).find(item => Number(item.id) === Number(id));
        if (!asset) return;
        try {
            await navigator.clipboard.writeText(asset.url);
            this.app.toast('Asset URL copied');
        } catch (error) {
            this.app.toast(asset.url, 'info');
        }
    }

    async deleteAsset(id) {
        const asset = (this.app.state.assets || []).find(item => Number(item.id) === Number(id));
        if (!asset || Number(asset.usage_count || 0) > 0) return;
        const ok = await StyledConfirm(`Delete "${asset.original_filename}"? This cannot be undone.`, {
            danger: true,
            title: 'Delete asset',
            confirmLabel: 'Delete'
        });
        if (!ok) return;
        try {
            await ApiClient.deleteAsset(id);
            await this.app.refreshAssets();
            this.app.toast('Asset deleted');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
