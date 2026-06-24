class TemplateScreen {
    constructor(app) {
        this.app = app;
        this.expandedFieldKeys = new Set();
    }

    async selectTemplate(id) {
        this.app.state.activeTemplateId = Number(id);
        this.app.state.activeTemplateDetail = await ApiClient.getTemplate(id);
        this.app.templateCanvas.selectedIndex = -1;
        this.expandedFieldKeys.clear();
        this.render();
    }

    async render() {
        if (!this.app.requireGame()) return;
        const templates = this.app.state.templates;
        const canEdit = this.app.activeGame()?.can_edit;
        if (this.app.state.activeTemplateId && !this.app.state.activeTemplateDetail) {
            this.app.state.activeTemplateDetail = await ApiClient.getTemplate(this.app.state.activeTemplateId);
        }
        const template = this.app.state.activeTemplateDetail;
        this.app.setContent(`
            <section class="template-workbench">
                <aside class="pane compact">
                    <div class="pane-head">
                        <h2>Templates</h2>
                        <button onclick="app.screens.templates.createTemplate()" ${canEdit ? '' : 'disabled'}>New</button>
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
                        ${template && canEdit ? '<button onclick="app.screens.templates.addField()">Add</button>' : ''}
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
        this.bindTemplateAutosave();
        this.bindFieldsAutosave();
        this.app.templateCanvas.render();
    }

    templateForm(template) {
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        return `
            <form class="stack-form mini" id="templateForm">
                <input type="hidden" name="id" value="${template ? template.id : ''}">
                <input type="hidden" name="gameId" value="${this.app.state.activeGameId || ''}">
                <input type="hidden" name="expectedUpdatedAt" value="${template ? PreviewRenderer.escape(template.updated_at || '') : ''}">
                <label>Name<input name="name" value="${template ? PreviewRenderer.escape(template.name) : ''}" required ${disabled}></label>
                <label>Slug<input name="slug" value="${template ? PreviewRenderer.escape(template.slug) : ''}" ${disabled}></label>
                <label>Width<input name="canvasWidth" type="number" value="${template ? template.canvas_width : 750}" ${disabled}></label>
                <label>Height<input name="canvasHeight" type="number" value="${template ? template.canvas_height : 1050}" ${disabled}></label>
                <label>Background<input name="canvasBackgroundColor" value="${template ? PreviewRenderer.escape(template.canvas_background_color || '#ffffff') : '#ffffff'}" ${disabled}></label>
                <label>Safe Padding<input name="safeAreaPadding" type="number" value="${template ? template.safe_area_padding : 40}" ${disabled}></label>
                <label>Description<textarea name="description" ${disabled}>${template ? PreviewRenderer.escape(template.description || '') : ''}</textarea></label>
            </form>
        `;
    }

    fieldsForm(template) {
        const fields = template.fields || [];
        return `
            <form class="fields-list" id="templateFieldsForm">
                ${fields.map((field, index) => this.fieldRow(field, index)).join('')}
                ${fields.length ? '' : '<div class="empty-state">Add fields to define this template.</div>'}
            </form>
        `;
    }

    fieldRow(field, index) {
        const settings = field.settings_json || {};
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        const expanded = this.isFieldExpanded(field, index);
        const summary = [
            field.field_key || 'field',
            field.field_type || 'text',
            field.default_value ? `default: ${field.default_value}` : ''
        ].filter(Boolean).join(' | ');
        return `
            <div class="field-row ${expanded ? 'expanded' : 'collapsed'}" data-index="${index}">
                <input type="hidden" name="id" value="${field.id || ''}">
                <div class="field-row-head">
                    <button type="button" class="field-toggle" onclick="app.screens.templates.toggleField(${index})" aria-expanded="${expanded ? 'true' : 'false'}">${expanded ? 'v' : '>'}</button>
                    <button type="button" class="field-summary" onclick="app.screens.templates.toggleField(${index})">
                        <strong>${PreviewRenderer.escape(field.label || 'Untitled Field')}</strong>
                        <span>${PreviewRenderer.escape(summary)}</span>
                    </button>
                    <button type="button" ${field.id && !disabled ? `onclick="app.templateCanvas.addField(${field.id})"` : 'disabled'}>Place</button>
                </div>
                <div class="field-row-body" ${expanded ? '' : 'hidden'}>
                    <label>Label<input name="label" value="${PreviewRenderer.escape(field.label || '')}" required ${disabled}></label>
                    <label>Key<input name="fieldKey" value="${PreviewRenderer.escape(field.field_key || '')}" required ${disabled}></label>
                    <label>Type
                        <select name="fieldType" ${disabled}>
                            ${['text', 'longtext', 'number', 'boolean', 'select', 'multiselect', 'image'].map(type => `<option value="${type}" ${field.field_type === type ? 'selected' : ''}>${type}</option>`).join('')}
                        </select>
                    </label>
                    <label>Default<input name="defaultValue" value="${PreviewRenderer.escape(field.default_value || '')}" ${disabled}></label>
                    <label>Options<input name="options" value="${PreviewRenderer.escape((settings.options || []).join(', '))}" placeholder="select options" ${disabled}></label>
                    <label>Help<input name="helpText" value="${PreviewRenderer.escape(field.help_text || '')}" ${disabled}></label>
                    <div class="field-actions">
                        <button type="button" class="danger" onclick="app.screens.templates.removeField(${index})" ${disabled}>Remove</button>
                    </div>
                </div>
            </div>
        `;
    }

    fieldExpansionKeys(field, index) {
        const keys = [];
        if (field?.id) keys.push(`id:${field.id}`);
        if (field?.field_key) keys.push(`key:${field.field_key}`);
        keys.push(`index:${index}`);
        return keys;
    }

    isFieldExpanded(field, index) {
        return this.fieldExpansionKeys(field, index).some(key => this.expandedFieldKeys.has(key));
    }

    primaryFieldExpansionKey(field, index) {
        return this.fieldExpansionKeys(field, index)[0];
    }

    toggleField(index) {
        const template = this.app.state.activeTemplateDetail;
        const field = template?.fields?.[index];
        if (!field) return;
        const key = this.primaryFieldExpansionKey(field, index);
        if (this.isFieldExpanded(field, index)) this.expandedFieldKeys.delete(key);
        else this.expandedFieldKeys.add(key);
        document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(template);
        this.bindFieldsAutosave();
    }

    createTemplate() {
        this.app.state.activeTemplateId = null;
        this.app.state.activeTemplateDetail = null;
        this.expandedFieldKeys.clear();
        this.render();
    }

    bindTemplateAutosave() {
        const form = document.getElementById('templateForm');
        if (!form) return;
        this.app.autosave.bindForm(
            form,
            'template-form',
            () => {
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!payload.name.trim()) return null;
                payload.canvasWidth = Number(payload.canvasWidth || 750);
                payload.canvasHeight = Number(payload.canvasHeight || 1050);
                payload.safeAreaPadding = Number(payload.safeAreaPadding || 40);
                return payload;
            },
            async payload => {
                const template = payload.id ? await ApiClient.updateTemplate(payload) : await ApiClient.createTemplate(payload);
                form.elements.id.value = template.id;
                form.elements.expectedUpdatedAt.value = template.updated_at || '';
                this.app.state.activeTemplateId = template.id;
                this.app.state.activeTemplateDetail = template;
                await this.app.refreshTemplates();
                await this.app.preloadTemplateDetails();
                if (!payload.id) this.render();
                else this.app.templateCanvas.render();
                return template;
            }
        );
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
        const field = {
            id: '',
            label: 'New Field',
            field_key: `field_${template.fields.length + 1}`,
            field_type: 'text',
            default_value: '',
            help_text: '',
            sort_order: template.fields.length,
            settings_json: {}
        };
        template.fields.push(field);
        this.expandedFieldKeys.add(this.primaryFieldExpansionKey(field, template.fields.length - 1));
        document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(template);
        this.bindFieldsAutosave();
        this.saveFieldsForm();
    }

    removeField(index) {
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        const field = template.fields[index];
        this.fieldExpansionKeys(field, index).forEach(key => this.expandedFieldKeys.delete(key));
        template.fields.splice(index, 1);
        template.layout = (template.layout || []).filter(element => Number(element.field_id) !== Number(field.id));
        document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(template);
        this.bindFieldsAutosave();
        this.app.templateCanvas.render();
        this.saveFieldsForm();
    }

    bindFieldsAutosave() {
        const form = document.getElementById('templateFieldsForm');
        if (!form) return;
        this.app.autosave.bindForm(
            form,
            `template-fields:${this.app.state.activeTemplateId || 'new'}`,
            () => this.collectFields(),
            async fields => {
                const template = this.app.state.activeTemplateDetail;
                if (!template?.id) return null;
                const needsRerender = fields.some(field => !field.id);
                this.app.state.activeTemplateDetail = await ApiClient.saveTemplateFields(template.id, fields);
                this.app.state.templateDetails[template.id] = this.app.state.activeTemplateDetail;
                await this.app.refreshTemplates();
                if (needsRerender) {
                    document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(this.app.state.activeTemplateDetail);
                    this.bindFieldsAutosave();
                    this.app.templateCanvas.render();
                }
                return this.app.state.activeTemplateDetail;
            }
        );
    }

    collectFields() {
        const template = this.app.state.activeTemplateDetail;
        if (!template?.id) return null;
        const form = document.getElementById('templateFieldsForm');
        if (!form) return null;
        const rows = [...form.querySelectorAll('.field-row')];
        return rows.map((row, index) => {
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
    }

    saveFieldsForm() {
        const template = this.app.state.activeTemplateDetail;
        if (!template?.id) return;
        this.app.autosave.saveNow(
            `template-fields:${template.id}`,
            () => this.collectFields(),
            async fields => {
                const needsRerender = fields.some(field => !field.id);
                this.app.state.activeTemplateDetail = await ApiClient.saveTemplateFields(template.id, fields);
                this.app.state.templateDetails[template.id] = this.app.state.activeTemplateDetail;
                await this.app.refreshTemplates();
                if (needsRerender) {
                    document.getElementById('templateFieldsHost').innerHTML = this.fieldsForm(this.app.state.activeTemplateDetail);
                    this.bindFieldsAutosave();
                    this.app.templateCanvas.render();
                }
                return this.app.state.activeTemplateDetail;
            }
        );
    }

    async saveFields(event) {
        event.preventDefault();
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        const normalized = this.collectFields();
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
