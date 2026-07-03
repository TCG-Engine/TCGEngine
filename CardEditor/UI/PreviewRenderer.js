const PreviewRenderer = {
    escape(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[ch]));
    },

    css(style = {}, fallback = {}) {
        const merged = { ...fallback, ...(style || {}) };
        const ignoredKeys = new Set(['fitMode', 'behindTemplate']);
        return Object.entries(merged)
            .filter(([, value]) => value !== undefined && value !== null && value !== '')
            .filter(([key]) => !ignoredKeys.has(key))
            .map(([key, value]) => `${key.replace(/[A-Z]/g, m => '-' + m.toLowerCase())}:${this.escapeCss(value)}`)
            .join(';');
    },

    escapeCss(value) {
        return String(value).replace(/[;"{}]/g, '');
    },

    valueFor(field, valuesByFieldId) {
        const entry = valuesByFieldId[field.id];
        if (!entry) return field.default_value || '';
        if (field.field_type === 'number') return entry.value_number ?? '';
        if (field.field_type === 'boolean') return entry.value_boolean ? 'Yes' : 'No';
        if (field.field_type === 'multiselect') return Array.isArray(entry.value_json) ? entry.value_json.join(', ') : '';
        return entry.value_text ?? '';
    },

    enumForField(field, template) {
        const enumId = field?.settings_json?.enumId;
        if (!enumId) return null;
        return (template.enums || []).find(item => Number(item.id) === Number(enumId)) || null;
    },

    enumOptionForValue(field, template, value) {
        const enumDef = this.enumForField(field, template);
        if (!enumDef) return null;
        return (enumDef.options || []).find(option => option.value === value) || null;
    },

    effectiveZIndex(element) {
        const layer = Number(element.z_index || 0);
        if (element.element_type !== 'image' && this.isBehindTemplate(element)) return layer;
        if (element.element_type === 'image') return 100000 + layer;
        return 200000 + layer;
    },

    isBehindTemplate(element) {
        const value = element?.style_json?.behindTemplate;
        return value === true || value === 1 || value === '1' || value === 'true';
    },

    render(container, template, values = [], options = {}) {
        if (!container) return;
        if (!template) {
            container.innerHTML = '<div class="empty-state">No template selected</div>';
            return;
        }

        const valuesByFieldId = {};
        values.forEach(value => { valuesByFieldId[value.field_id] = value; });
        const fieldsById = {};
        (template.fields || []).forEach(field => { fieldsById[field.id] = field; });
        const assetsById = {};
        (template.assets || options.assets || []).forEach(asset => { assetsById[asset.id] = asset; });
        const scale = options.scale || 1;
        const width = Number(template.canvas_width || 750);
        const height = Number(template.canvas_height || 1050);
        const bg = template.canvas_background_color || '#ffffff';

        const elements = (template.layout || []).filter(element => element.is_visible !== false).map(element => {
            const style = element.style_json || {};
            const base = {
                left: `${element.x}px`,
                top: `${element.y}px`,
                width: `${element.width}px`,
                height: `${element.height}px`,
                zIndex: this.effectiveZIndex(element),
                transform: `rotate(${element.rotation || 0}deg)`
            };
            if (element.element_type === 'image') {
                const asset = assetsById[element.asset_id];
                const fit = style.fitMode || 'cover';
                return `<div class="preview-el image-el" data-element-id="${element.id}" style="${this.css(style, base)}">${asset ? `<img src="${this.escape(asset.url)}" style="object-fit:${this.escapeCss(fit)}" alt="">` : ''}</div>`;
            }
            const field = fieldsById[element.field_id];
            if (!field) return '';
            if (field.field_type === 'image') {
                const assetUrl = this.valueFor(field, valuesByFieldId);
                const fit = style.fitMode || 'cover';
                return `<div class="preview-el image-el" data-element-id="${element.id}" style="${this.css(style, base)}">${assetUrl ? `<img src="${this.escape(assetUrl)}" style="object-fit:${this.escapeCss(fit)}" alt="">` : ''}</div>`;
            }
            if (field.field_type === 'icon_enum') {
                const value = this.valueFor(field, valuesByFieldId);
                const option = this.enumOptionForValue(field, template, value);
                const fit = style.fitMode || 'contain';
                const url = option?.asset?.url || '';
                return `<div class="preview-el image-el" data-element-id="${element.id}" style="${this.css(style, base)}">${url ? `<img src="${this.escape(url)}" style="object-fit:${this.escapeCss(fit)}" alt="">` : ''}</div>`;
            }
            const textStyle = {
                fontFamily: 'Arial, sans-serif',
                fontSize: '24px',
                color: '#111111',
                lineHeight: '1.25',
                padding: '8px',
                overflow: 'hidden',
                ...base
            };
            return `<div class="preview-el text-el" data-element-id="${element.id}" style="${this.css(style, textStyle)}">${this.escape(this.valueFor(field, valuesByFieldId))}</div>`;
        }).join('');

        container.innerHTML = `
            <div class="preview-scale" style="width:${width * scale}px;height:${height * scale}px;">
                <div class="card-preview-canvas" style="width:${width}px;height:${height}px;background:${this.escapeCss(bg)};transform:scale(${scale});transform-origin:top left;">
                    ${elements}
                </div>
            </div>
        `;
    }
};
