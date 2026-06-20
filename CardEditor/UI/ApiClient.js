const ApiClient = {
    async request(path, options = {}) {
        const response = await fetch(`../API/${path}`, options);
        const data = await response.json().catch(() => ({ success: false, error: 'Invalid JSON response' }));
        if (!response.ok || data.success === false) {
            throw new Error(data.error || 'Request failed');
        }
        return data.data;
    },

    get(path, params = {}) {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') query.set(key, value);
        });
        const suffix = query.toString() ? `?${query.toString()}` : '';
        return this.request(`${path}${suffix}`);
    },

    post(path, body = {}) {
        return this.request(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
    },

    uploadAsset(gameId, file) {
        const form = new FormData();
        form.append('gameId', gameId);
        form.append('asset', file);
        return this.request('UploadAsset.php', {
            method: 'POST',
            body: form
        });
    },

    listGames() { return this.get('ListGames.php'); },
    createGame(payload) { return this.post('CreateGame.php', payload); },
    updateGame(payload) { return this.post('UpdateGame.php', payload); },
    listSets(gameId) { return this.get('ListSets.php', { gameId }); },
    createSet(payload) { return this.post('CreateSet.php', payload); },
    updateSet(payload) { return this.post('UpdateSet.php', payload); },
    listTemplates(gameId) { return this.get('ListTemplates.php', { gameId }); },
    getTemplate(id) { return this.get('GetTemplate.php', { id }); },
    createTemplate(payload) { return this.post('CreateTemplate.php', payload); },
    updateTemplate(payload) { return this.post('UpdateTemplate.php', payload); },
    saveTemplateFields(templateId, fields) { return this.post('SaveTemplateFields.php', { templateId, fields }); },
    saveTemplateLayout(templateId, layout) { return this.post('SaveTemplateLayout.php', { templateId, layout }); },
    listCards(setId) { return this.get('ListCards.php', { setId }); },
    getCard(id) { return this.get('GetCard.php', { id }); },
    createCard(payload) { return this.post('CreateCard.php', payload); },
    updateCard(payload) { return this.post('UpdateCard.php', payload); },
    saveCardFieldValues(cardId, values) { return this.post('SaveCardFieldValues.php', { cardId, values }); },
    listAssets(gameId) { return this.get('ListAssets.php', { gameId }); },
    exportGame(gameId) { return this.get('ExportGame.php', { gameId }); },
    exportSet(setId) { return this.get('ExportSet.php', { setId }); }
};
