class TemplateCanvas {
    constructor(app) {
        this.app = app;
        this.zoom = 0.5;
        this.selectedIndex = -1;
        this.drag = null;
    }

    setZoom(value) {
        this.zoom = Number(value);
        this.render();
    }

    addField(fieldId) {
        const template = this.app.state.activeTemplateDetail;
        if (!template) return;
        const exists = (template.layout || []).some(element => Number(element.field_id) === Number(fieldId));
        if (exists) {
            this.app.toast('Field is already on the canvas', 'info');
            return;
        }
        template.layout = template.layout || [];
        template.layout.push({
            id: `local-${Date.now()}`,
            element_type: 'field',
            field_id: Number(fieldId),
            x: 40,
            y: 40 + template.layout.length * 28,
            width: 260,
            height: 80,
            z_index: template.layout.length,
            rotation: 0,
            is_visible: true,
            style_json: {}
        });
        this.selectedIndex = template.layout.length - 1;
        this.render();
        this.scheduleLayoutSave();
    }

    addImage(assetId) {
        const template = this.app.state.activeTemplateDetail;
        if (!template || !assetId) return;
        const canvasWidth = Number(template.canvas_width || 750);
        const canvasHeight = Number(template.canvas_height || 1050);
        template.layout = template.layout || [];
        template.layout.push({
            id: `local-image-${Date.now()}`,
            element_type: 'image',
            asset_id: Number(assetId),
            field_id: null,
            x: 0,
            y: 0,
            width: canvasWidth,
            height: canvasHeight,
            z_index: template.layout.length,
            rotation: 0,
            is_visible: true,
            style_json: { fitMode: 'cover' }
        });
        this.selectedIndex = template.layout.length - 1;
        this.render();
        this.scheduleLayoutSave();
    }

    selectedElement() {
        const template = this.app.state.activeTemplateDetail;
        if (!template || this.selectedIndex < 0) return null;
        return template.layout[this.selectedIndex] || null;
    }

    effectiveZIndex(element) {
        const layer = Number(element.z_index || 0);
        if (element.element_type === 'image') return 100000 + layer;
        return 200000 + layer;
    }

    isBehindTemplate(element) {
        const value = element?.style_json?.behindTemplate;
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    ensureStyleObject(element) {
        if (!element.style_json || Array.isArray(element.style_json) || typeof element.style_json !== 'object') {
            element.style_json = {};
        }
        return element.style_json;
    }

    render() {
        const host = document.getElementById('templateCanvasHost');
        if (!host) return;
        const template = this.app.state.activeTemplateDetail;
        if (!template) {
            host.innerHTML = '<div class="empty-state">Select or create a template.</div>';
            return;
        }

        const fieldsById = {};
        (template.fields || []).forEach(field => { fieldsById[field.id] = field; });
        const assetsById = {};
        (template.assets || this.app.state.assets || []).forEach(asset => { assetsById[asset.id] = asset; });
        const canEdit = this.app.activeGame()?.can_edit;
        const width = Number(template.canvas_width || 750);
        const height = Number(template.canvas_height || 1050);
        const elements = (template.layout || []).map((element, index) => {
            const field = fieldsById[element.field_id];
            const asset = assetsById[element.asset_id];
            if (element.element_type === 'field' && !field) return '';
            if (element.element_type === 'image' && !asset) return '';
            const isImage = element.element_type === 'image';
            const fit = element.style_json?.fitMode || 'cover';
            return `
                <div class="canvas-el ${isImage ? 'image-layer' : ''} ${index === this.selectedIndex ? 'selected' : ''}" data-index="${index}"
                    style="left:${element.x}px;top:${element.y}px;width:${element.width}px;height:${element.height}px;z-index:${this.effectiveZIndex(element)};transform:rotate(${element.rotation || 0}deg)"
                    onmousedown="${canEdit ? `app.templateCanvas.startDrag(event, ${index}, 'move')` : ''}">
                    ${isImage ? `<img src="${PreviewRenderer.escape(asset.url)}" style="object-fit:${PreviewRenderer.escapeCss(fit)}" alt="">` : `<span>${PreviewRenderer.escape(field.label)}</span>`}
                    ${canEdit ? `<i onmousedown="app.templateCanvas.startDrag(event, ${index}, 'resize')"></i>` : ''}
                </div>
            `;
        }).join('');
        const assetOptions = (template.assets || this.app.state.assets || []).map(asset => `<option value="${asset.id}">${PreviewRenderer.escape(asset.original_filename)}</option>`).join('');

        host.innerHTML = `
            <div class="canvas-toolbar">
                <select id="canvasAssetSelect">
                    <option value="">Add image layer</option>
                    ${assetOptions}
                </select>
                <button onclick="app.templateCanvas.addImage(document.getElementById('canvasAssetSelect').value)" ${canEdit ? '' : 'disabled'}>Add Image</button>
                <input type="file" accept="image/*" onchange="app.templateCanvas.uploadAndAddImage(this)" ${canEdit ? '' : 'disabled'}>
                <select onchange="app.templateCanvas.setZoom(this.value)">
                    ${[0.5, 0.75, 1, 1.5].map(z => `<option value="${z}" ${z === this.zoom ? 'selected' : ''}>${Math.round(z * 100)}%</option>`).join('')}
                </select>
            </div>
            <div class="canvas-scroll">
                <div class="canvas-scale" style="width:${width * this.zoom}px;height:${height * this.zoom}px;">
                    <div class="template-canvas" style="width:${width}px;height:${height}px;background:${PreviewRenderer.escapeCss(template.canvas_background_color || '#ffffff')};transform:scale(${this.zoom});transform-origin:top left;">
                        ${elements}
                    </div>
                </div>
            </div>
        `;
        this.renderInspector();
    }

    renderInspector() {
        const host = document.getElementById('templateInspector');
        if (!host) return;
        const template = this.app.state.activeTemplateDetail;
        const element = this.selectedElement();
        if (!template || !element) {
            host.innerHTML = '<div class="empty-state">Select a placed field.</div>';
            return;
        }
        const field = (template.fields || []).find(item => Number(item.id) === Number(element.field_id));
        const asset = (template.assets || this.app.state.assets || []).find(item => Number(item.id) === Number(element.asset_id));
        const style = element.style_json || {};
        const isImage = element.element_type === 'image';
        const isIconField = element.element_type === 'field' && field?.field_type === 'icon_enum';
        const disabled = this.app.activeGame()?.can_edit ? '' : 'disabled';
        host.innerHTML = `
            <div class="inspector">
                <h3>${PreviewRenderer.escape(isImage ? (asset?.original_filename || 'Image') : (field ? field.label : 'Field'))}</h3>
                ${this.numberControl('x', 'X', element.x, disabled)}
                ${this.numberControl('y', 'Y', element.y, disabled)}
                ${this.numberControl('width', 'Width', element.width, disabled)}
                ${this.numberControl('height', 'Height', element.height, disabled)}
                ${this.numberControl('z_index', 'Layer', element.z_index, disabled)}
                ${this.numberControl('rotation', 'Rotation', element.rotation || 0, disabled)}
                ${isImage ? this.assetControl(element) : (isIconField ? '<div class="info-message">Icon enum field</div>' : `
                    <label>Font Size<input type="number" value="${PreviewRenderer.escape(style.fontSize ? parseInt(style.fontSize, 10) : '')}" onchange="app.templateCanvas.updateStyle('fontSize', this.value ? this.value + 'px' : '')" ${disabled}></label>
                    <label>Color<input type="color" value="${PreviewRenderer.escape(style.color || '#111111')}" onchange="app.templateCanvas.updateStyle('color', this.value)" ${disabled}></label>
                    <label>Align
                        <select onchange="app.templateCanvas.updateStyle('textAlign', this.value)" ${disabled}>
                            ${['left', 'center', 'right'].map(value => `<option value="${value}" ${style.textAlign === value ? 'selected' : ''}>${value}</option>`).join('')}
                        </select>
                    </label>
                    <label class="inline-filter"><input type="checkbox" ${this.isBehindTemplate(element) ? 'checked' : ''} onchange="app.templateCanvas.updateStyle('behindTemplate', this.checked ? 1 : '')" ${disabled}> Behind Template</label>
                `)}
                <label>Background<input value="${PreviewRenderer.escape(style.backgroundColor || '')}" placeholder="transparent or #ffffff" onchange="app.templateCanvas.updateStyle('backgroundColor', this.value)" ${disabled}></label>
                <label>Image Fit
                    <select onchange="app.templateCanvas.updateStyle('fitMode', this.value)" ${disabled}>
                        ${['cover', 'contain', 'stretch'].map(value => `<option value="${value}" ${style.fitMode === value ? 'selected' : ''}>${value}</option>`).join('')}
                    </select>
                </label>
                ${isImage ? `<button onclick="app.templateCanvas.fitSelectedToCanvas()" ${disabled}>Fit Canvas</button>` : ''}
                <button class="danger" onclick="app.templateCanvas.removeSelected()" ${disabled}>Remove From Canvas</button>
            </div>
        `;
    }

    numberControl(key, label, value, disabled = '') {
        return `<label>${label}<input type="number" step="1" value="${PreviewRenderer.escape(value ?? 0)}" onchange="app.templateCanvas.updateElement('${key}', this.value)" ${disabled}></label>`;
    }

    assetControl(element) {
        const options = (this.app.state.activeTemplateDetail.assets || this.app.state.assets || []).map(asset => {
            return `<option value="${asset.id}" ${Number(element.asset_id) === Number(asset.id) ? 'selected' : ''}>${PreviewRenderer.escape(asset.original_filename)}</option>`;
        }).join('');
        return `
            <label>Image Asset
                <select onchange="app.templateCanvas.updateElement('asset_id', this.value)" ${this.app.activeGame()?.can_edit ? '' : 'disabled'}>
                    ${options}
                </select>
            </label>
        `;
    }

    updateElement(key, value) {
        const element = this.selectedElement();
        if (!element) return;
        element[key] = Number(value);
        if (key === 'z_index') element.zIndex = Number(value);
        this.render();
        this.scheduleLayoutSave();
    }

    updateStyle(key, value) {
        const element = this.selectedElement();
        if (!element) return;
        const style = this.ensureStyleObject(element);
        if (value === '') delete style[key];
        else style[key] = value;
        if (key === 'behindTemplate' || key === 'fitMode') {
            this.render();
            this.scheduleLayoutSave(0);
            return;
        }
        this.renderInspector();
        this.scheduleLayoutSave();
    }

    removeSelected() {
        const template = this.app.state.activeTemplateDetail;
        if (!template || this.selectedIndex < 0) return;
        template.layout.splice(this.selectedIndex, 1);
        this.selectedIndex = -1;
        this.render();
        this.scheduleLayoutSave();
    }

    fitSelectedToCanvas() {
        const template = this.app.state.activeTemplateDetail;
        const element = this.selectedElement();
        if (!template || !element || element.element_type !== 'image') return;
        element.x = 0;
        element.y = 0;
        element.width = Number(template.canvas_width || 750);
        element.height = Number(template.canvas_height || 1050);
        element.rotation = 0;
        this.render();
        this.scheduleLayoutSave(0);
    }

    startDrag(event, index, mode) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.app.activeGame()?.can_edit) return;
        const template = this.app.state.activeTemplateDetail;
        const element = template.layout[index];
        this.selectedIndex = index;
        this.drag = {
            mode,
            startX: event.clientX,
            startY: event.clientY,
            original: { ...element }
        };
        document.onmousemove = e => this.onDrag(e);
        document.onmouseup = () => this.endDrag();
        this.renderInspector();
    }

    onDrag(event) {
        if (!this.drag) return;
        const element = this.selectedElement();
        if (!element) return;
        const dx = (event.clientX - this.drag.startX) / this.zoom;
        const dy = (event.clientY - this.drag.startY) / this.zoom;
        if (this.drag.mode === 'resize') {
            element.width = Math.max(20, Math.round(this.drag.original.width + dx));
            element.height = Math.max(20, Math.round(this.drag.original.height + dy));
        } else {
            element.x = Math.max(0, Math.round(this.drag.original.x + dx));
            element.y = Math.max(0, Math.round(this.drag.original.y + dy));
        }
        const node = document.querySelector(`.canvas-el[data-index="${this.selectedIndex}"]`);
        if (node) {
            node.style.left = `${element.x}px`;
            node.style.top = `${element.y}px`;
            node.style.width = `${element.width}px`;
            node.style.height = `${element.height}px`;
        }
        this.renderInspector();
    }

    endDrag() {
        this.drag = null;
        document.onmousemove = null;
        document.onmouseup = null;
        this.scheduleLayoutSave(0);
    }

    async uploadAndAddImage(input) {
        try {
            const asset = await this.app.assetPicker.uploadFromInput(input);
            if (!asset) return;
            const template = this.app.state.activeTemplateDetail;
            template.assets = this.app.state.assets;
            this.addImage(asset.id);
        } catch (error) {
            this.app.toast(error.message, 'error');
        } finally {
            input.value = '';
        }
    }

    scheduleLayoutSave(delay = 600) {
        const template = this.app.state.activeTemplateDetail;
        if (!template || !template.id) return;
        this.app.autosave.schedule(
            `template-layout:${template.id}`,
            () => template.layout || [],
            async layout => {
                this.app.state.activeTemplateDetail = await ApiClient.saveTemplateLayout(template.id, layout);
                this.app.state.templateDetails[template.id] = this.app.state.activeTemplateDetail;
                return this.app.state.activeTemplateDetail;
            },
            delay
        );
    }
}
