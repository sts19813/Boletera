<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\RegistrationForm;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationFormSchemaService
{
    private array $supported = ['text', 'number', 'date', 'email', 'tel', 'file', 'radio', 'checkbox', 'select', 'textarea', 'repeater'];

    public function defaultSchema(): array
    {
        return ['version' => 1, 'fields' => []];
    }

    public function normalizeSchema(mixed $schema): array
    {
        $raw = is_array($schema) ? $schema : [];
        $fields = collect(Arr::get($raw, 'fields', []))
            ->map(fn($f) => $this->normalizeField($f))
            ->filter()
            ->values()
            ->all();
        return ['version' => 1, 'fields' => $fields];
    }

    public function validateSubmissionForEvent(Eventos $event, mixed $data): array
    {
        if (!$event->is_registration || $event->registration_form_mode !== 'builder' || !$event->registrationForm) {
            return is_array($data) ? $data : [];
        }
        $payload = is_array($data) ? $data : [];
        $schema = $this->normalizeSchema($event->registrationForm->schema);

        if (($event->allows_multiple_registrations ?? false) && isset($payload['registrations']) && is_array($payload['registrations'])) {
            $errors = [];
            $validated = [];
            foreach (array_values($payload['registrations']) as $index => $row) {
                if (!is_array($row)) {
                    $errors["registrations.$index"][] = 'Formato inválido.';
                    continue;
                }
                $rowErrors = [];
                $validatedRow = $this->validateWithFields($schema['fields'], $row, $rowErrors);
                if (!empty($rowErrors)) {
                    foreach ($rowErrors as $key => $messages) {
                        $errors["registrations.$index.$key"] = $messages;
                    }
                }
                $validated[] = $validatedRow;
            }
            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
            return ['registrations' => $validated];
        }

        return $this->validateSubmission($event->registrationForm, $payload);
    }

    public function validateSubmission(RegistrationForm $form, mixed $data): array
    {
        $payload = is_array($data) ? $data : [];
        $schema = $this->normalizeSchema($form->schema);
        $errors = [];
        $output = $this->validateWithFields($schema['fields'], $payload, $errors);
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
        return $output;
    }

    private function validateWithFields(array $fields, array $payload, array &$errors): array
    {
        $output = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = Arr::get($payload, $name);
            $validated = $this->validateFieldValue($field, $value, $errors, $name);
            if ($validated !== null) {
                Arr::set($output, $name, $validated);
            }
        }
        return $output;
    }

    public function labelMap(RegistrationForm $form): array
    {
        $schema = $this->normalizeSchema($form->schema);
        $map = [];
        foreach ($schema['fields'] as $field) {
            $this->collectLabels($map, $field, $field['name']);
        }
        return $map;
    }

    private function collectLabels(array &$map, array $field, string $path): void
    {
        $map[$path] = $field['label'] ?? $field['name'];
        if (($field['type'] ?? null) !== 'repeater') {
            return;
        }
        foreach (($field['fields'] ?? []) as $child) {
            $childPath = $path . '.*.' . $child['name'];
            $this->collectLabels($map, $child, $childPath);
        }
    }

    private function normalizeField(mixed $field): ?array
    {
        if (!is_array($field)) {
            return null;
        }
        $type = strtolower(trim((string) ($field['type'] ?? 'text')));
        if (!in_array($type, $this->supported, true)) {
            return null;
        }
        $name = Str::of((string) ($field['name'] ?? ''))->trim()->replace(' ', '_')->lower()->value();
        if ($name === '' || preg_match('/^[a-z0-9_\.]+$/', $name) !== 1) {
            return null;
        }
        $result = [
            'id' => (string) ($field['id'] ?? Str::uuid()),
            'type' => $type,
            'name' => $name,
            'label' => trim((string) ($field['label'] ?? Str::headline($name))),
            'placeholder' => (string) ($field['placeholder'] ?? ''),
            'required' => (bool) ($field['required'] ?? false),
            'column' => (int) ($field['column'] ?? 12),
            'help' => (string) ($field['help'] ?? ''),
        ];
        if (in_array($type, ['number'], true)) {
            if (array_key_exists('min', $field) && $field['min'] !== '') {
                $result['min'] = (float) $field['min'];
            }
            if (array_key_exists('max', $field) && $field['max'] !== '') {
                $result['max'] = (float) $field['max'];
            }
        }
        if (in_array($type, ['text', 'email', 'tel', 'textarea'], true) && !empty($field['pattern'])) {
            $result['pattern'] = (string) $field['pattern'];
        }
        if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
            $result['multiple'] = (bool) ($field['multiple'] ?? false);
            $result['options'] = collect($field['options'] ?? [])->map(function ($opt) {
                if (is_array($opt)) {
                    $value = (string) ($opt['value'] ?? '');
                    $label = (string) ($opt['label'] ?? $value);
                } else {
                    $value = (string) $opt;
                    $label = $value;
                }
                return $value === '' ? null : ['value' => $value, 'label' => $label === '' ? $value : $label];
            })->filter()->values()->all();
        }
        if ($type === 'repeater') {
            $result['min_items'] = max(1, (int) ($field['min_items'] ?? 1));
            $result['max_items'] = max($result['min_items'], (int) ($field['max_items'] ?? $result['min_items']));
            $result['fields'] = collect($field['fields'] ?? [])->map(fn($child) => $this->normalizeField($child))->filter()->values()->all();
            if (empty($result['fields'])) {
                return null;
            }
        }
        $result['column'] = in_array($result['column'], [12, 6, 4, 3, 2], true) ? $result['column'] : 12;
        return $result;
    }

    private function validateFieldValue(array $field, mixed $value, array &$errors, string $path): mixed
    {
        $required = (bool) ($field['required'] ?? false);
        $type = $field['type'];
        if ($type === 'repeater') {
            $items = is_array($value) ? array_values($value) : [];
            $min = (int) ($field['min_items'] ?? 1);
            $max = (int) ($field['max_items'] ?? $min);
            if ($required && count($items) < $min) {
                $errors[$path][] = 'El campo es obligatorio.';
                return null;
            }
            if (!$required && empty($items)) {
                return null;
            }
            if (count($items) < $min || count($items) > $max) {
                $errors[$path][] = 'Cantidad fuera de rango.';
                return null;
            }
            $result = [];
            foreach ($items as $i => $item) {
                if (!is_array($item)) {
                    $errors[$path . '.' . $i][] = 'Formato inválido.';
                    continue;
                }
                $row = [];
                foreach ($field['fields'] as $child) {
                    $childPath = $path . '.' . $i . '.' . $child['name'];
                    $childVal = Arr::get($item, $child['name']);
                    $validatedChild = $this->validateFieldValue($child, $childVal, $errors, $childPath);
                    if ($validatedChild !== null) {
                        $row[$child['name']] = $validatedChild;
                    }
                }
                $result[] = $row;
            }
            return $result;
        }

        $empty = $value === null || $value === '' || (is_array($value) && empty($value));
        if ($required && $empty) {
            $errors[$path][] = 'El campo es obligatorio.';
            return null;
        }
        if ($empty) {
            return null;
        }

        if ($type === 'number') {
            if (!is_numeric($value)) {
                $errors[$path][] = 'Debe ser numérico.';
                return null;
            }
            $num = (float) $value;
            if (array_key_exists('min', $field) && $num < (float) $field['min']) {
                $errors[$path][] = 'Menor al mínimo permitido.';
            }
            if (array_key_exists('max', $field) && $num > (float) $field['max']) {
                $errors[$path][] = 'Mayor al máximo permitido.';
            }
            return $num;
        }

        if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
            $allowed = collect($field['options'] ?? [])->pluck('value')->all();
            $vals = is_array($value) ? array_values($value) : [$value];
            if (!empty(array_diff(array_map('strval', $vals), array_map('strval', $allowed)))) {
                $errors[$path][] = 'Opción inválida.';
                return null;
            }
            if ($type === 'checkbox' || ($field['multiple'] ?? false)) {
                return array_values(array_map('strval', $vals));
            }
            return (string) $vals[0];
        }

        $str = (string) $value;
        if ($type === 'email' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
            $errors[$path][] = 'Email inválido.';
        }
        if ($type === 'date' && strtotime($str) === false) {
            $errors[$path][] = 'Fecha inválida.';
        }
        if ($type === 'tel') {
            $digits = preg_replace('/\D+/', '', $str) ?? '';
            if (strlen($digits) < 10) {
                $errors[$path][] = 'Teléfono inválido.';
            }
            $str = $digits;
        }
        if (!empty($field['pattern']) && @preg_match('/' . $field['pattern'] . '/', '') !== false && preg_match('/' . $field['pattern'] . '/', $str) !== 1) {
            $errors[$path][] = 'Formato inválido.';
        }
        return $str;
    }
}
