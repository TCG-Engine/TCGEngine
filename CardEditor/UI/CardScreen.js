class CardScreen {
    constructor(app) {
        this.app = app;
    }

    async selectCard(id) {
        const listPaneScrollTop = document.querySelector('.card-workbench .pane.compact')?.scrollTop || 0;
        this.app.state.activeCardId = Number(id);
        this.app.state.activeCardDetail = await ApiClient.getCard(id);
        this.render();
        const listPane = document.querySelector('.card-workbench .pane.compact');
        if (listPane) listPane.scrollTop = listPaneScrollTop;
    }

    async render() {
        if (!this.app.requireGame()) return;
        const set = this.app.activeSet();
        const card = this.app.state.activeCardDetail;
        const canEdit = this.app.activeGame()?.can_edit;
        this.app.setContent(`
            <section class="card-workbench">
                <aside class="pane compact">
                    <div class="pane-head"><h2>Set</h2></div>
                    <select class="wide-select" onchange="app.screens.cards.changeSet(this.value)">
                        <option value="">Select set</option>
                        ${this.app.state.sets.map(item => `<option value="${item.id}" ${this.app.state.activeSetId === item.id ? 'selected' : ''}>${PreviewRenderer.escape(item.name)}</option>`).join('')}
                    </select>
                    <div class="pane-head spaced">
                        <h2>Cards</h2>
                        ${set ? `<button onclick="app.screens.cards.createCard()" ${canEdit ? '' : 'disabled'}>New</button>` : ''}
                    </div>
                    <div class="list tight">
                        ${this.app.state.cards.length ? this.app.state.cards.map(item => `
                            <button class="list-row ${this.app.state.activeCardId === item.id ? 'active' : ''}" onclick="app.screens.cards.selectCard(${item.id})">
                                <strong>${PreviewRenderer.escape(item.name)}</strong>
                                <span>${PreviewRenderer.escape(item.slug)}</span>
                            </button>
                        `).join('') : '<div class="empty-state">No cards in this set.</div>'}
                    </div>
                </aside>
                <main class="pane">
                    <div class="pane-head">
                        <h2>${card ? 'Edit Card' : 'Card Details'}</h2>
                        ${card && canEdit ? `<button type="button" class="danger" onclick="app.screens.cards.deleteCard(${card.id})">Delete</button>` : ''}
                    </div>
                    ${set ? this.cardForm(card) : '<div class="empty-state">Create or select a set first.</div>'}
                </main>
                <aside class="pane preview-pane">
                    <div class="pane-head"><h2>Preview</h2></div>
                    <div id="cardPreviewHost"></div>
                </aside>
            </section>
        `);
        this.renderPreview();
        this.bindAutosave();
    }

    async changeSet(value) {
        this.app.state.activeSetId = value ? Number(value) : null;
        this.app.state.activeCardId = null;
        this.app.state.activeCardDetail = null;
        await this.app.refreshCards();
        this.render();
    }

    createCard() {
        this.app.state.activeCardId = null;
        this.app.state.activeCardDetail = null;
        this.render();
    }

    cardForm(card) {
        const templates = this.app.state.templates;
        const selectedTemplateId = card ? card.template_id : (templates[0] ? templates[0].id : '');
        const template = card ? card.template : this.app.state.templateDetails[selectedTemplateId];
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        return `
            <form class="stack-form card-edit-form" id="cardForm" oninput="app.screens.cards.renderPreviewFromForm()">
                <input type="hidden" name="id" value="${card ? card.id : ''}">
                <input type="hidden" name="gameId" value="${this.app.state.activeGameId || ''}">
                <input type="hidden" name="setId" value="${this.app.state.activeSetId || ''}">
                <input type="hidden" name="expectedUpdatedAt" value="${card ? PreviewRenderer.escape(card.updated_at || '') : ''}">
                <label>Name<input name="name" value="${card ? PreviewRenderer.escape(card.name) : ''}" required ${disabled}></label>
                <label>Slug<input name="slug" value="${card ? PreviewRenderer.escape(card.slug) : ''}" ${disabled}></label>
                <label>Template
                    <select name="templateId" ${card || disabled ? 'disabled' : ''} onchange="app.screens.cards.loadTemplateForNewCard(this.value)">
                        ${templates.map(item => `<option value="${item.id}" ${Number(selectedTemplateId) === Number(item.id) ? 'selected' : ''}>${PreviewRenderer.escape(item.name)}</option>`).join('')}
                    </select>
                </label>
                <div id="cardFieldsHost">${template ? this.valueFields(template, card ? card.values : []) : '<div class="empty-state">Create a template first.</div>'}</div>
            </form>
        `;
    }

    valueFields(template, values) {
        const valuesByFieldId = {};
        (values || []).forEach(value => { valuesByFieldId[value.field_id] = value; });
        return (template.fields || []).map(field => {
            const value = valuesByFieldId[field.id] || {};
            const current = this.rawValue(field, value);
            const settings = field.settings_json || {};
            return `
                <label data-field-id="${field.id}" data-field-type="${field.field_type}">
                    ${PreviewRenderer.escape(field.label)}
                    ${this.inputForField(field, current, settings)}
                    ${field.help_text ? `<span>${PreviewRenderer.escape(field.help_text)}</span>` : ''}
                </label>
            `;
        }).join('');
    }

    inputForField(field, current, settings) {
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        if (field.field_type === 'longtext') {
            return `<textarea name="field_${field.id}" ${disabled}>${PreviewRenderer.escape(current)}</textarea>`;
        }
        if (field.field_type === 'number') {
            return `<input type="number" step="${PreviewRenderer.escape(settings.step || 'any')}" name="field_${field.id}" value="${PreviewRenderer.escape(current)}" ${disabled}>`;
        }
        if (field.field_type === 'boolean') {
            return `<input type="checkbox" name="field_${field.id}" ${current ? 'checked' : ''} ${disabled}>`;
        }
        if (field.field_type === 'select') {
            return `<select name="field_${field.id}" ${disabled}>${(settings.options || []).map(option => `<option value="${PreviewRenderer.escape(option)}" ${current === option ? 'selected' : ''}>${PreviewRenderer.escape(option)}</option>`).join('')}</select>`;
        }
        if (field.field_type === 'multiselect') {
            const currentValues = Array.isArray(current) ? current : [];
            return `<select name="field_${field.id}" multiple ${disabled}>${(settings.options || []).map(option => `<option value="${PreviewRenderer.escape(option)}" ${currentValues.includes(option) ? 'selected' : ''}>${PreviewRenderer.escape(option)}</option>`).join('')}</select>`;
        }
        if (field.field_type === 'image') {
            return `
                <div class="asset-line">
                    <input name="field_${field.id}" value="${PreviewRenderer.escape(current)}" placeholder="https://example.com/card-image.jpg" ${disabled}>
                    <select onchange="app.screens.cards.setImageFieldUrl('field_${field.id}', this.value)" ${disabled}>
                        <option value="">Use uploaded asset</option>
                        ${this.app.state.assets.map(asset => `<option value="${PreviewRenderer.escape(asset.url)}" ${current === asset.url ? 'selected' : ''}>${PreviewRenderer.escape(asset.original_filename)}</option>`).join('')}
                    </select>
                    <input type="file" accept="image/*" onchange="app.screens.cards.uploadAsset(this, 'field_${field.id}')" ${disabled}>
                </div>
            `;
        }
        return `<input name="field_${field.id}" value="${PreviewRenderer.escape(current)}" ${disabled}>`;
    }

    rawValue(field, value) {
        if (field.field_type === 'number') return value.value_number ?? field.default_value ?? '';
        if (field.field_type === 'boolean') return value.value_boolean ?? (field.default_value === 'true' || field.default_value === '1');
        if (field.field_type === 'multiselect') return value.value_json || [];
        return value.value_text ?? field.default_value ?? '';
    }

    async loadTemplateForNewCard(templateId) {
        if (!templateId) return;
        if (!this.app.state.templateDetails[templateId]) {
            this.app.state.templateDetails[templateId] = await ApiClient.getTemplate(templateId);
        }
        const host = document.getElementById('cardFieldsHost');
        host.innerHTML = this.valueFields(this.app.state.templateDetails[templateId], []);
        this.renderPreviewFromForm();
    }

    async uploadAsset(input, fieldName) {
        try {
            const asset = await this.app.assetPicker.uploadFromInput(input);
            const urlInput = document.querySelector(`[name="${fieldName}"]`);
            const select = input.closest('.asset-line')?.querySelector('select');
            if (asset && urlInput) {
                urlInput.value = asset.url;
                if (select) {
                    const option = document.createElement('option');
                    option.value = asset.url;
                    option.textContent = asset.original_filename;
                    option.selected = true;
                    select.appendChild(option);
                }
            }
            this.renderPreviewFromForm();
            this.saveCardFormNow();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }

    setImageFieldUrl(fieldName, value) {
        if (!value) return;
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (!input) return;
        input.value = value;
        this.renderPreviewFromForm();
        this.saveCardFormNow();
    }

    valuesFromForm(form, template) {
        return (template.fields || []).map(field => {
            const input = form.querySelector(`[name="field_${field.id}"]`);
            let value = null;
            if (!input) value = null;
            else if (field.field_type === 'boolean') value = input.checked;
            else if (field.field_type === 'multiselect') value = [...input.selectedOptions].map(option => option.value);
            else value = input.value;
            return { fieldId: field.id, value };
        });
    }

    valuesForPreview(form, template) {
        return this.valuesFromForm(form, template).map(item => {
            const field = template.fields.find(candidate => Number(candidate.id) === Number(item.fieldId));
            const row = { field_id: item.fieldId, value_text: null, value_number: null, value_boolean: null, value_json: null };
            if (!field) return row;
            if (field.field_type === 'number') row.value_number = item.value === '' ? null : Number(item.value);
            else if (field.field_type === 'boolean') row.value_boolean = item.value;
            else if (field.field_type === 'multiselect') row.value_json = item.value;
            else row.value_text = item.value;
            return row;
        });
    }

    renderPreview() {
        const card = this.app.state.activeCardDetail;
        const host = document.getElementById('cardPreviewHost');
        if (card) PreviewRenderer.render(host, card.template, card.values, { scale: 0.32 });
        else this.renderPreviewFromForm();
    }

    renderPreviewFromForm() {
        const form = document.querySelector('.card-edit-form');
        const host = document.getElementById('cardPreviewHost');
        if (!form || !host) return;
        const templateId = form.querySelector('[name="templateId"]')?.value;
        const template = this.app.state.templateDetails[templateId];
        if (!template) return;
        PreviewRenderer.render(host, template, this.valuesForPreview(form, template), { scale: 0.32 });
    }

    async saveCard(event) {
        event.preventDefault();
        const form = event.target;
        const payload = Object.fromEntries(new FormData(form).entries());
        if (form.querySelector('[name="templateId"]')?.disabled) {
            payload.templateId = this.app.state.activeCardDetail.template_id;
        }
        try {
            let card;
            if (payload.id) {
                card = await ApiClient.updateCard(payload);
            } else {
                card = await ApiClient.createCard(payload);
            }
            const template = card.template;
            card = await ApiClient.saveCardFieldValues(card.id, this.valuesFromForm(form, template));
            this.app.state.activeCardId = card.id;
            this.app.state.activeCardDetail = card;
            await this.app.refreshCards();
            this.app.toast(payload.id ? 'Card saved' : 'Card created');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }

    bindAutosave() {
        const form = document.getElementById('cardForm');
        if (!form) return;
        this.app.autosave.bindForm(
            form,
            'card-form',
            () => this.collectCardPayload(),
            payload => this.saveCardPayload(payload),
            { delay: 650 }
        );
    }

    collectCardPayload() {
        const form = document.getElementById('cardForm');
        if (!form) return null;
        const payload = Object.fromEntries(new FormData(form).entries());
        if (!payload.name?.trim()) return null;
        const templateId = form.querySelector('[name="templateId"]')?.value || this.app.state.activeCardDetail?.template_id;
        if (!templateId) return null;
        payload.templateId = templateId;
        const template = this.app.state.activeCardDetail?.template || this.app.state.templateDetails[templateId];
        if (!template) return null;
        payload.values = this.valuesFromForm(form, template);
        return payload;
    }

    async saveCardPayload(payload) {
        let card;
        if (payload.id) {
            card = await ApiClient.updateCard(payload);
        } else {
            card = await ApiClient.createCard(payload);
            document.getElementById('cardForm').elements.id.value = card.id;
            this.app.state.activeCardId = card.id;
        }
        card = await ApiClient.saveCardFieldValues(card.id, payload.values);
        const form = document.getElementById('cardForm');
        if (form) form.elements.expectedUpdatedAt.value = card.updated_at || '';
        this.app.state.activeCardDetail = card;
        await this.app.refreshCards();
        if (!payload.id) this.render();
        else this.renderPreview();
        return card;
    }

    saveCardFormNow() {
        this.app.autosave.saveNow('card-form', () => this.collectCardPayload(), payload => this.saveCardPayload(payload));
    }

    async deleteCard(id) {
        const card = this.app.state.activeCardDetail;
        if (!card || Number(card.id) !== Number(id)) return;
        if (!confirm(`Delete "${card.name}"? This cannot be undone.`)) return;
        try {
            await this.app.autosave.flushAll();
            await ApiClient.deleteCard(id);
            this.app.state.activeCardId = null;
            this.app.state.activeCardDetail = null;
            await this.app.refreshCards();
            this.app.toast('Card deleted');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
