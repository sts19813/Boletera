@php
    $builderForm = $lot->registrationForm;
    $builderTitle = trim((string) ($builderForm->name ?? ''));
    $builderDescription = trim((string) ($builderForm->description ?? ''));
@endphp

<div class="card shadow-sm mb-8">
    <div class="card-header bg-light">
        <h4 class="card-title fw-bold mb-0">
            {{ $builderTitle !== '' ? $builderTitle : 'Registro - ' . $lot->name }}
        </h4>
    </div>
    <div class="card-body">
        @if($builderDescription !== '')
            <div class="text-muted fs-7 mb-6">{{ $builderDescription }}</div>
        @endif
        <div id="registration-builder-root" data-schema='@json($builderForm?->schema ?? ["version" => 1, "fields" => []])'></div>
    </div>
</div>
