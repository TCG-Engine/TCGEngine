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
        return Object.entries(merged)
            .filter(([, value]) => value !== undefined && value !== null && value !== '')
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
        const scale = options.scale || 1;
        const width = Number(template.canvas_width || 750);
        const height = Number(template.canvas_height || 1050);
        const bg = template.canvas_background_color || '#ffffff';

        const elements = (template.layout || []).filter(element => element.is_visible !== false).map(element => {
            const field = fieldsById[element.field_id];
            if (!field) return '';
            const style = element.style_json || {};
            const base = {
                left: `${element.x}px`,
                top: `${element.y}px`,
                width: `${element.width}px`,
                height: `${element.height}px`,
                zIndex: element.z_index,
                transform: `rotate(${element.rotation || 0}deg)`
            };
            if (field.field_type === 'image') {
                const assetUrl = this.valueFor(field, valuesByFieldId);
                const fit = style.fitMode || 'cover';
                return `<div class="preview-el image-el" data-element-id="${element.id}" style="${this.css(style, base)}">${assetUrl ? `<img src="${this.escape(assetUrl)}" style="object-fit:${this.escapeCss(fit)}" alt="">` : ''}</div>`;
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
