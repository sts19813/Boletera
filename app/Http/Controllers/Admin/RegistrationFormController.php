<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationForm;
use App\Services\RegistrationFormSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationFormController extends Controller
{
    public function __construct(private RegistrationFormSchemaService $schemaService)
    {
    }

    public function index(): View
    {
        $forms = RegistrationForm::query()->orderByDesc('updated_at')->paginate(20);
        return view('admin.registration_forms.index', compact('forms'));
    }

    public function create(): View
    {
        $form = new RegistrationForm([
            'is_active' => true,
            'schema' => $this->schemaService->defaultSchema(),
            'ui_settings' => [],
        ]);
        return view('admin.registration_forms.create', compact('form'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        RegistrationForm::create($data);
        return redirect()->route('admin.registration-forms.index')->with('success', 'Formulario creado correctamente.');
    }

    public function edit(RegistrationForm $registrationForm): View
    {
        $form = $registrationForm;
        return view('admin.registration_forms.edit', compact('form'));
    }

    public function update(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $data = $this->validated($request, $registrationForm->id);
        $registrationForm->update($data);
        return redirect()->route('admin.registration-forms.index')->with('success', 'Formulario actualizado correctamente.');
    }

    public function destroy(RegistrationForm $registrationForm): RedirectResponse
    {
        $registrationForm->delete();
        return redirect()->route('admin.registration-forms.index')->with('success', 'Formulario eliminado.');
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        $rawSchema = json_decode((string) $request->input('schema_json', '{}'), true);
        if (!is_array($rawSchema)) {
            $rawSchema = [];
        }
        $schema = $this->schemaService->normalizeSchema($rawSchema);
        $name = trim((string) $request->input('name'));
        $slug = trim((string) $request->input('slug'));
        if ($slug === '') {
            $slug = Str::slug($name);
        }
        $request->merge(['name' => $name, 'slug' => $slug]);
        $rule = 'required|string|max:120|unique:registration_forms,slug' . ($ignoreId ? ',' . $ignoreId : '');
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'slug' => $rule,
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['schema'] = $schema;
        $validated['ui_settings'] = ['field_count' => count($schema['fields'])];
        $validated['is_active'] = $request->boolean('is_active');
        return $validated;
    }
}
