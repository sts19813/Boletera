@php
    $schemaValue = old('schema_json', json_encode($form->schema ?? ['version' => 1, 'fields' => []], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm mb-5">
    <div class="card-header"><h4 class="card-title fw-bold">General</h4></div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Nombre</label>
                <input name="name" class="form-control" value="{{ old('name', $form->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Slug</label>
                <input name="slug" class="form-control" value="{{ old('slug', $form->slug) }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <label class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                    <span class="form-check-label ms-2">Activo</span>
                </label>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-bold">Descripción</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $form->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title fw-bold">Constructor</h4>
        <button type="button" id="addFieldRow" class="btn btn-light-primary btn-sm">Agregar campo</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle" id="fieldsTable">
                <thead>
                    <tr>
                        <th>Tipo</th><th>Nombre</th><th>Etiqueta</th><th>Col</th><th>Req</th><th>Opciones</th><th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <input type="hidden" name="schema_json" id="schema_json" value="{{ $schemaValue }}">
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title fw-bold">Schema JSON</h4>
        <div class="d-flex gap-2">
            <button type="button" id="syncRowsToJson" class="btn btn-light-primary btn-sm">Sincronizar</button>
            <button type="button" id="loadJsonToRows" class="btn btn-light btn-sm">Cargar a filas</button>
        </div>
    </div>
    <div class="card-body">
        <textarea id="schema_editor" class="form-control" rows="12">{{ $schemaValue }}</textarea>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header"><h4 class="card-title fw-bold">Preview</h4></div>
    <div class="card-body"><div class="row g-4" id="schema_preview"></div></div>
</div>

<div class="text-end mt-5"><button type="submit" class="btn btn-primary">Guardar</button></div>

@push('scripts')
<script>
(() => {
    const types = ['text','number','date','email','tel','file','radio','checkbox','select','textarea','repeater'];
    const cols = [12,6,4,3,2];
    const table = document.querySelector('#fieldsTable tbody');
    const editor = document.getElementById('schema_editor');
    const hidden = document.getElementById('schema_json');
    const preview = document.getElementById('schema_preview');
    const rowTpl = (f={}) => `<tr>
        <td><select class="form-select f-type">${types.map(t=>`<option value="${t}" ${f.type===t?'selected':''}>${t}</option>`).join('')}</select></td>
        <td><input class="form-control f-name" value="${f.name??''}"></td>
        <td><input class="form-control f-label" value="${f.label??''}"></td>
        <td><select class="form-select f-col">${cols.map(c=>`<option value="${c}" ${Number(f.column??12)===c?'selected':''}>${c}</option>`).join('')}</select></td>
        <td class="text-center"><input type="checkbox" class="f-required" ${f.required?'checked':''}></td>
        <td><input class="form-control f-options" placeholder="valor:Etiqueta|valor2:Etiqueta2" value="${(f.options??[]).map(o=>`${o.value}:${o.label}`).join('|')}"></td>
        <td><button type="button" class="btn btn-light-danger btn-sm f-remove">Quitar</button></td>
    </tr>`;
    const parseOptions = v => (v||'').split('|').map(x=>x.trim()).filter(Boolean).map(item=>{const p=item.split(':');const value=(p.shift()||'').trim();const label=(p.join(':')||value).trim();return value?{value,label}:null;}).filter(Boolean);
    const rowsToSchema = () => ({version:1,fields:[...table.querySelectorAll('tr')].map(tr=>{
        const type=tr.querySelector('.f-type').value;
        const field={type,name:tr.querySelector('.f-name').value.trim(),label:tr.querySelector('.f-label').value.trim(),column:Number(tr.querySelector('.f-col').value||12),required:tr.querySelector('.f-required').checked};
        if (['select','radio','checkbox'].includes(type)) field.options=parseOptions(tr.querySelector('.f-options').value);
        return field;
    }).filter(f=>f.name)});
    const jsonToRows = (schema) => {
        table.innerHTML='';
        (schema.fields||[]).forEach(f=>table.insertAdjacentHTML('beforeend',rowTpl(f)));
    };
    const escapeHtml = s => String(s??'').replace(/[&<>"]/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[m]));
    const fieldPreview = f => {
        const req = f.required ? 'required' : '';
        const label = escapeHtml(f.label||f.name||'Campo');
        if (f.type === 'textarea') return `<textarea class="form-control" ${req}></textarea>`;
        if (['select','radio','checkbox'].includes(f.type)) {
            const opts=(f.options||[]).map(o=>`<option value="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join('');
            if (f.type==='select') return `<select class="form-select" ${req}><option value="">Selecciona</option>${opts}</select>`;
            const inputType=f.type==='radio'?'radio':'checkbox';
            return `<div>${(f.options||[]).map(o=>`<label class="form-check form-check-inline"><input class="form-check-input" type="${inputType}" name="pv_${escapeHtml(f.name)}" value="${escapeHtml(o.value)}"><span class="form-check-label">${escapeHtml(o.label)}</span></label>`).join(' ')}</div>`;
        }
        if (f.type==='repeater') return '<div class="text-muted">Repeater</div>';
        return `<input type="${escapeHtml(f.type||'text')}" class="form-control" ${req}>`;
    };
    const renderPreview = () => {
        let schema;
        try { schema = JSON.parse(editor.value || '{}'); } catch { schema = {fields:[]}; }
        preview.innerHTML = (schema.fields||[]).map(f=>`<div class="col-md-${[12,6,4,3,2].includes(Number(f.column))?Number(f.column):12}"><label class="form-label fw-bold">${escapeHtml(f.label||f.name||'Campo')}</label>${fieldPreview(f)}</div>`).join('');
        hidden.value = JSON.stringify(schema);
    };
    document.getElementById('addFieldRow')?.addEventListener('click',()=>table.insertAdjacentHTML('beforeend',rowTpl({type:'text',column:12})));
    table.addEventListener('click',e=>{if(e.target.closest('.f-remove')) e.target.closest('tr').remove();});
    document.getElementById('syncRowsToJson')?.addEventListener('click',()=>{editor.value=JSON.stringify(rowsToSchema(),null,2);renderPreview();});
    document.getElementById('loadJsonToRows')?.addEventListener('click',()=>{try{jsonToRows(JSON.parse(editor.value||'{}'));}catch{}});
    editor.addEventListener('input',renderPreview);
    document.querySelector('form')?.addEventListener('submit',()=>{renderPreview(); hidden.value = editor.value;});
    try { jsonToRows(JSON.parse(editor.value||'{}')); } catch {}
    renderPreview();
})();
</script>
@endpush
