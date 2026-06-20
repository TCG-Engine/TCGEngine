class TemplateScreen {
    constructor(app) {
        this.app = app;
    }

    async selectTemplate(id) {
        this.app.state.activeTemplateId = Number(id);
        this.app.state.activeTemplateDetail = await ApiClient.getTemplate(id);
        this.app.templateCanvas.selectedIndex = -1;
        this.render();
    }

    async render() {
        if (!this.app.requireGame()) return;
        const templates = this.app.state.templates;
        if (this.app.state.activeTemplateId && !this.app.state.activeTemplateDetail) {
            this.app.state.activeTemplateDetail = await ApiClient.getTemplate(this.app.state.activeTemplateId);
        }
        const template = this.app.state.activeTemplateDetail;
        this.app.setContent(`
            <section class="template-workbench">
                <aside class="pane compact">
                    <div class="pane-head">
                        <h2>Templates</h2>
                        <button onclick="app.screens.templates.createTemplate()">New</button>
                    </div>
                    <div class="list tight">
                        ${templates.length ? templates.map(item => `
                            <button class="list-row ${this.app.state.activeTemplateId === item.id ? 'active' : ''}" onclick="app.screens.templates.selectTemplate(${item.id})">
                                <strong>${PreviewRenderer.escape(item.name)}</strong>
                                <span>${PreviewRenderer.escape(item.slug)}</span>
                            </button>
                        `).join('') : '<div class="empty-state">No templates yet.</div>'}
                    </div>
                    ${this.templateForm(template)}
                </aside>
                <aside class="pane fields-pane">
                    <div class="pane-head">
                        <h2>Fields</h2>
                        ${template ? '<button onclick="app.screens.templates.addField()">Add</button>' : ''}
                    </div>
                    <div id="templateFieldsHost">${template ? this.fieldsForm(template) : '<div class="empty-state">Select a template.</div>'}</div>
                </aside>
                <main class="pane canvas-pane">
                    <div id="templateCanvasHost"></div>
                </main>
                <aside class="pane inspector-pane">
                    <div class="pane-head"><h2>Inspector</h2></div>
                    <div id="templateInspector"></div>
                </aside>
            </section>
        `);
        this.app.templateCanvas.render();
    }

    templateForm(template) {
        return `
            <form class="stack-form mini" onsubmit="app.screens.templates.saveTemplate(event)">
                <input type="hidden" name="id" value="${template ? template.id : ''}">
                <input type="hidden" name="gameId" value="${this.app.state.activeGameId || ''}">
                <label>Name<input name="name" value="${template ? PreviewRenderer.escape(template.name) : ''}" required></label>
                <label>Slug<input name="slug" value="${template ? PreviewRenderer.escape(template.slug) : ''}"></label>
                <label>Width<input name="canvasWidth" type="number" value="${template ? template.canvas_width : 750}"></label>
                <label>Height<input name="canvasHeight" type="number" value="${template ? template.canvas_height : 1050}"></label>
                <label>Background<input name="canvasBackgroundColor" value="${template ? PreviewRenderer.escape(template.canvas_background_color || '#ffffff') : '#ffffff'}"></label>
                <label>Safe Padding<input name="safeAreaPadding" type="number" value="${template ? template.safe_area_padding : 40}"></label>
                <label>Description<textarea name="description">${template ? PreviewRenderer.escape(template.description || '') : ''}</textarea></label>
                <button type="submit">${template ? 'Save Template' : 'Create Template'}</button>
            </form>
        `;
    }

    fieldsForm(template) {
        const fields = template.fields || [];
        return `
            <form class="fields-list" onsubmit="app.screens.templates.saveFields(event)">
                ${fields.map((field, index) => this.fieldRow(field, index)).join('')}
                ${fields.length ? '<button type="submit">Save Fields</button>' : '<div class="empty-state">Add fields to define this template.</div>'}
            </form>
        `;
    }

    fieldRow(field, index) {
        const settings = field.settings_json || {};
        return `
            <div class="field-row" data-index="${index}">
                <input type="hidden" name="id" value="${field.id || ''}">
                <label>Label<input name="label" value="${PreviewRenderer.escape(field.label || '')}" required></label>
                <label>Key<input name="fieldKey" value="${PreviewRenderer.escape(field.field_key || '')}" required></label>
                <label>Type
                    <select name="fieldType">
                        ${['text', 'longtext', 'number', 'boolean', 'select', 'multiselect', 'image'].map(type => `<option value="${type}" ${field.field_type === type ? 'selected' : ''}>${type}</option>`).join('')}
                    </select>
                </label>
                <label>Default<input name="defaultValue" value="${PreviewRenderer.escape(field.default_value || '')}"></label>
                <label>Options<input name="options" value="${PreviewRenderer.escape((settings.options || []).join(', '))}" placeholder="select options"></label>
                <label>Help<input name="helpText" value="${PreviewRenderer.escape(field.help_text || '')}"></label>
                <div class="field-actions">
                    <button type="button" ${field.id ? `onclick="app.templateCanvas.addField(${field.id})"` : 'disabled'}>Place</button>
                    <button type="button" class="danger" onclick="app.screens.templates.removeField(${index})">Remove</button>
                </div>
            </div>
        `;
    }

    createTemplate() {
        this.app.state.activeTemplateId = null;
        this.app.state.activeTemplateDetail = null;
        this.render();
    }

    async saveTemplate(event) {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(event.target).entries());
        payload.canvasWidth = Number(payload.canvasWidth || 750);
        payload.canvasHeight = Number(payload.canvasHeight || 1050);
        payload.safeAreaPadding = Number(payload.safeAreaPadding || 40);
        try {
            const template = payload.id ? await ApiClient.updateTemplate(payload) : await ApiClient.createTemplate(payload);
            this.app.state.activeTemplateId = template.id;
            this.app.state.activeTemplateDetail = template;
            await this.app.refreshTemplates();
            this.app.toast(payload.id ? 'Template saved' : 'Template created');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }

    addField() {
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        template.fields = template.fields || [];
        template.fields.push({
            id: '',
            label: 'New Field',
            field_key: `field_${template.fields.length + 1}`,
            field_type: 'text',
            default_value: '',
            help_text: '',
            sort_order: template.fields.length,
            settings_json: {}
        });
        document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(template);
    }

    removeField(index) {
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        const field = template.fields[index];
        template.fields.splice(index, 1);
        template.layout = (template.layout || []).filter(element => Number(element.field_id) !== Number(field.id));
        document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(template);
        this.app.templateCanvas.render();
    }

    async saveFields(event) {
        event.preventDefault();
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        const rows = [...event.target.querySelectorAll('.field-row')];
        const normalized = rows.map((row, index) => {
            const get = name => row.querySelector(`[name="${name}"]`)?.value || '';
            const options = get('options').split(',').map(item => item.trim()).filter(Boolean);
            return {
                id: get('id'),
                label: get('label'),
                fieldKey: get('fieldKey'),
                fieldType: get('fieldType'),
                defaultValue: get('defaultValue'),
                helpText: get('helpText'),
                sortOrder: index,
                settingsJson: options.length ? { options } : {}
            };
        });
        try {
            this.app.state.activeTemplateDetail = await ApiClient.saveTemplateFields(template.id, normalized);
            await this.app.refreshTemplates();
            this.app.toast('Fields saved');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }

    async saveLayout() {
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        try {
            this.app.state.activeTemplateDetail = await ApiClient.saveTemplateLayout(template.id, template.layout || []);
            this.app.toast('Layout saved');
            this.render();
        } catch (error) {
            this.app.toast(error.message, 'error');
        }
    }
}
