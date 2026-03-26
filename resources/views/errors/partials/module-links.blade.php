@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();

    $links = [];

    if ($isAuthenticated) {
        $canAccessAdmin = $user->hasRole('admin') || $user->hasRole('organizer') || $user->can('crear eventos');
        $canAccessTaquilla = $user->hasRole('admin') || $user->hasRole('taquillero') || $user->hasRole('viewer') || $user->can('vender boletos');
        $canAccessScanner = $user->hasRole('admin') || $user->hasRole('scanner') || $user->can('escanear boletos');

        if ($canAccessAdmin) {
            $links[] = [
                'label' => 'Ir a módulo Admin',
                'url' => route('events.index'),
                'class' => 'error-btn-primary',
            ];
        }

        if ($canAccessTaquilla) {
            $links[] = [
                'label' => 'Ir a Taquilla',
                'url' => route('taquilla.index'),
                'class' => 'error-btn-secondary',
            ];
        }

        if ($canAccessScanner) {
            $links[] = [
                'label' => 'Ir a Scanner',
                'url' => url('/checkin'),
                'class' => 'error-btn-secondary',
            ];
        }

        if ($user->can('ver registros') || $user->hasRole('cumbres') || $user->hasRole('viewer')) {
            $links[] = [
                'label' => 'Ir a Registros',
                'url' => route('admin.registrations.index'),
                'class' => 'error-btn-secondary',
            ];
        }
    }
@endphp

<div class="error-actions">
    @if ($isAuthenticated && count($links))
        @foreach ($links as $link)
            <a href="{{ $link['url'] }}" class="error-btn {{ $link['class'] }}">{{ $link['label'] }}</a>
        @endforeach
    @else
        <a href="https://www.stomtickets.com/" class="error-btn error-btn-primary">Regresar al inicio</a>
    @endif
</div>
