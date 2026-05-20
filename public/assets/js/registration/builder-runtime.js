(function () {
    function qty() {
        const item = window.cartState?.items?.find(function (t) { return t.id === 'registration'; });
        return Math.max(1, Number(item?.qty || 1));
    }
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (m) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]; });
    }
    function col(v) {
        return [12, 6, 4, 3, 2].includes(Number(v)) ? Number(v) : 12;
    }
    function optsHtml(field, nameBase, required) {
        const options = Array.isArray(field.options) ? field.options : [];
        const multiple = Boolean(field.multiple || field.type === 'checkbox');
        const name = multiple ? nameBase + '[]' : nameBase;
        const req = required ? ' data-required-group="1"' : '';
        const itemType = field.type === 'radio' ? 'radio' : 'checkbox';
        return options.map(function (o, i) {
            return '<label class="form-check form-check-custom form-check-solid me-6">'
                + '<input class="form-check-input" type="' + itemType + '" name="' + esc(name) + '" value="' + esc(o.value) + '"' + (required && i === 0 ? ' required' : '') + req + '>'
                + '<span class="form-check-label">' + esc(o.label || o.value) + '</span></label>';
        }).join('');
    }
    function inputHtml(field, nameBase) {
        const type = field.type || 'text';
        const required = Boolean(field.required);
        const req = required ? ' required' : '';
        const ph = field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '';
        if (type === 'textarea') {
            return '<textarea name="' + esc(nameBase) + '" class="form-control form-control-solid"' + req + ph + '></textarea>';
        }
        if (type === 'select') {
            const options = Array.isArray(field.options) ? field.options : [];
            const multiple = Boolean(field.multiple);
            return '<select name="' + esc(multiple ? nameBase + '[]' : nameBase) + '" class="form-select form-select-solid"' + req + (multiple ? ' multiple' : '') + '>'
                + '<option value="">Selecciona una opción</option>'
                + options.map(function (o) { return '<option value="' + esc(o.value) + '">' + esc(o.label || o.value) + '</option>'; }).join('')
                + '</select>';
        }
        if (type === 'radio' || type === 'checkbox') {
            return '<div class="d-flex flex-wrap gap-3">' + optsHtml(field, nameBase, required) + '</div>';
        }
        const htmlType = ['text', 'number', 'date', 'email', 'tel', 'file'].includes(type) ? type : 'text';
        const attrs = [];
        if (field.min !== undefined && htmlType === 'number') attrs.push('min="' + esc(field.min) + '"');
        if (field.max !== undefined && htmlType === 'number') attrs.push('max="' + esc(field.max) + '"');
        if (field.pattern && ['text', 'email', 'tel'].includes(htmlType)) attrs.push('pattern="' + esc(field.pattern) + '"');
        if (htmlType === 'tel') attrs.push('inputmode="numeric"');
        if (htmlType === 'file') attrs.push('data-file-field="1"');
        return '<input type="' + htmlType + '" name="' + esc(nameBase) + '" class="form-control form-control-solid" ' + attrs.join(' ') + req + ph + '>';
    }
    function fieldBlock(field, nameBase) {
        return '<div class="col-md-' + col(field.column) + '"><label class="form-label' + (field.required ? ' required' : '') + '">' + esc(field.label || field.name) + '</label>' + inputHtml(field, nameBase) + (field.help ? '<div class="text-muted fs-8 mt-1">' + esc(field.help) + '</div>' : '') + '</div>';
    }
    function rowFields(fields, rowName) {
        return '<div class="row g-5">' + fields.map(function (child) { return fieldBlock(child, rowName + '[' + child.name + ']'); }).join('') + '</div>';
    }
    function repeaterBlock(field, nameBase) {
        const min = Math.max(1, Number(field.min_items || 1));
        const max = Math.max(min, Number(field.max_items || min));
        return '<div class="col-md-12"><div class="card shadow-sm mb-6" data-repeater="' + esc(field.name) + '" data-name-base="' + esc(nameBase) + '" data-min="' + min + '" data-max="' + max + '"><div class="card-header bg-light d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">' + esc(field.label || field.name) + '</h5><button type="button" class="btn btn-light-primary btn-sm js-repeater-add">Agregar</button></div><div class="card-body"><div class="d-flex flex-column gap-6 js-repeater-rows"></div></div></div></div>';
    }
    function fieldToHtml(field, nameBase) {
        if (field.type === 'repeater') return repeaterBlock(field, nameBase);
        return fieldBlock(field, nameBase);
    }
    function parseSchema(root) {
        try { return JSON.parse(root.dataset.schema || '{}'); } catch { return { fields: [] }; }
    }
    function addRepeaterRow(card, field, index) {
        const rows = card.querySelector('.js-repeater-rows');
        const base = card.dataset.nameBase || field.name;
        const row = document.createElement('div');
        row.className = 'border rounded p-4';
        row.dataset.index = String(index);
        row.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-4"><strong>Elemento ' + (index + 1) + '</strong><button type="button" class="btn btn-light-danger btn-sm js-repeater-remove">Quitar</button></div>' + rowFields(field.fields || [], base + '[' + index + ']');
        rows.appendChild(row);
        refreshRepeater(card, field);
    }
    function refreshRepeater(card, field) {
        const rows = [...card.querySelectorAll('.js-repeater-rows > div')];
        const min = Number(card.dataset.min || 1);
        const max = Number(card.dataset.max || min);
        rows.forEach(function (row, idx) {
            row.dataset.index = String(idx);
            row.querySelector('strong').textContent = 'Elemento ' + (idx + 1);
            const base = card.dataset.nameBase || field.name;
            (field.fields || []).forEach(function (child) {
                const prefix = base + '[' + idx + '][' + child.name + ']';
                row.querySelectorAll('[name]').forEach(function (el) {
                    if ((el.name || '').includes('][' + child.name + ']')) el.name = child.type === 'checkbox' ? prefix + '[]' : prefix;
                });
            });
            const rm = row.querySelector('.js-repeater-remove');
            if (rm) rm.disabled = rows.length <= min;
        });
        const add = card.querySelector('.js-repeater-add');
        if (add) add.disabled = rows.length >= max;
    }
    function hydrateRepeaters(root, schema) {
        root.querySelectorAll('[data-repeater]').forEach(function (card) {
            const field = (schema.fields || []).find(function (f) { return f.name === card.dataset.repeater; });
            if (!field) return;
            const min = Math.max(1, Number(field.min_items || 1));
            for (let i = 0; i < min; i++) addRepeaterRow(card, field, i);
            card.addEventListener('click', function (e) {
                if (e.target.closest('.js-repeater-add')) addRepeaterRow(card, field, card.querySelectorAll('.js-repeater-rows > div').length);
                if (e.target.closest('.js-repeater-remove')) {
                    e.target.closest('.js-repeater-remove').closest('div.border')?.remove();
                    refreshRepeater(card, field);
                }
            });
        });
    }
    function normalizeTelAndFiles(form) {
        form.querySelectorAll('input[type="tel"]').forEach(function (input) { input.addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); }); });
        form.querySelectorAll('input[data-file-field="1"]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = this.name + '_filename';
                hidden.value = this.files[0].name;
                this.parentElement.querySelectorAll('input[type="hidden"][name="' + this.name + '_filename"]').forEach(function (n) { n.remove(); });
                this.parentElement.appendChild(hidden);
            });
        });
    }
    window.renderRegistrationBuilderForm = function () {
        const root = document.getElementById('registration-builder-root');
        const form = document.getElementById('registrationForm');
        if (!root || !form) return;
        const schema = parseSchema(root);
        const fields = Array.isArray(schema.fields) ? schema.fields : [];
        const multiple = Boolean(window.registrationConfig?.allowsMultiple);
        const count = multiple ? qty() : 1;
        if (!multiple) {
            root.innerHTML = '<div class="row g-5">' + fields.map(function (f) { return fieldToHtml(f, f.name); }).join('') + '</div>';
        } else {
            root.innerHTML = Array.from({ length: count }).map(function (_, idx) {
                return '<div class="card shadow-sm mb-6"><div class="card-header bg-light"><h5 class="card-title mb-0">Registro ' + (idx + 1) + '</h5></div><div class="card-body"><div class="row g-5">' + fields.map(function (f) { return fieldToHtml(f, 'registrations[' + idx + '][' + f.name + ']'); }).join('') + '</div></div></div>';
            }).join('');
        }
        hydrateRepeaters(root, { fields: fields });
        normalizeTelAndFiles(form);
        window.registrationFormSchema = { fields: fields };
    };
    window.validateRegistrationBuilderForm = function (form) {
        if (!form || !window.registrationFormSchema) return true;
        const grouped = {};
        form.querySelectorAll('[data-required-group="1"]').forEach(function (i) { grouped[i.name] = grouped[i.name] || []; grouped[i.name].push(i); });
        for (const name in grouped) {
            if (!grouped[name].some(function (i) { return i.checked; })) {
                grouped[name][0].focus();
                return false;
            }
        }
        return true;
    };
    document.addEventListener('DOMContentLoaded', function () {
        const cartList = document.getElementById('cartItems');
        if (!cartList) return;
        const observer = new MutationObserver(function () {
            if (window.isRegistration && window.registrationFormMode === 'builder' && window.registrationConfig?.allowsMultiple) window.renderRegistrationBuilderForm?.();
        });
        observer.observe(cartList, { childList: true, subtree: true });
    });
})();
