<div class="card card-flush shadow-sm cart-sticky">

    <div class="card-header">
        <h3 class="card-title fw-bold d-flex align-items-center gap-2">
            <i class="ki-duotone ki-basket fs-3 text-primary"></i>
            Boletos seleccionados
        </h3>
    </div>

    <div class="card-body">

        <ul id="cartItems" class="kt-cart-items mb-6"></ul>

        <div class="separator separator-dashed my-4"></div>

        <div class="d-flex justify-content-between mb-4 fw-semibold">
            <span>Total</span>
            <span id="cartTotal" class="text-primary">$0</span>
        </div>

        @if(!auth()->check() || !auth()->user()->is_admin)
            <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold" disabled>
                Continuar pago
            </button>
        @endif

        @if(auth()->check() && auth()->user()->is_admin)

            <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold d-none" disabled>
                Continuar pago
            </button>

            <div class="mt-3">
                <label class="form-label fw-bold mb-2">Tipo de venta</label>

                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-lg fw-bold btn-metodo" data-metodo="cash">
                        💵 Efectivo
                    </button>

                    <button class="btn btn-primary btn-lg fw-bold btn-metodo" data-metodo="card">
                        💳 Tarjeta
                    </button>

                    <button class="btn btn-secondary btn-lg fw-bold btn-metodo d-none" data-metodo="cortesia">
                        🎟️ Cortesía
                    </button>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-bold">
                    Nombre o correo <span class="text-muted">(opcional)</span>
                </label>
                <input type="text" id="ventaNombre" class="form-control" placeholder="Ej. Juan Pérez o invitado@gmail.com">
            </div>

        @endif
    </div>
</div>