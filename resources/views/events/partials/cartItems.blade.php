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

        @if(!empty($showCouponField))
            <div class="mb-4">
                <label class="form-label fw-bold mb-2">Cupón</label>
                <div class="d-flex gap-2">
                    <input type="text" id="couponCodeInput" class="form-control" placeholder="Código de cupón">
                    <button type="button" id="applyCouponBtn" class="btn btn-light-primary">Aplicar</button>
                    <button type="button" id="clearCouponBtn" class="btn btn-light">Quitar</button>
                </div>
                <div id="couponFeedback" class="fs-8 mt-2"></div>
            </div>
            <div class="separator separator-dashed my-4"></div>
        @endif

        <div class="d-flex justify-content-between mb-4 fw-semibold">
            <span>Total</span>
            <span id="cartTotal" class="text-primary">$0</span>
        </div>

        <div id="alertaVentaOnline"></div>

        @unlessrole('taquillero')
        <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold" disabled>
            Continuar pago
        </button>
        @endunlessrole

        @canany(['vender boletos', 'reimprimir boletos'])
            <div id="taquillaVentaWrapper">

                <div id="alertaCupoTaquilla"></div>

                <div class="mt-3">
                    <label class="form-label fw-bold mb-2">Venta Taquilla</label>

                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-lg fw-bold btn-metodo" data-metodo="cash">
                            💵 Efectivo
                        </button>

                        <button class="btn btn-primary btn-lg fw-bold btn-metodo" data-metodo="card">
                            💳 Tarjeta
                        </button>

                        @can('genera cortesias')
                            <button class="btn btn-secondary btn-lg fw-bold btn-metodo" data-metodo="cortesia">
                                🎟️ Cortesía
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-bold">
                        Nombre o correo <span class="text-muted">(opcional)</span>
                    </label>
                    <input type="text" id="ventaNombre" class="form-control"
                        placeholder="Ej. Juan Pérez o invitado@gmail.com">
                </div>

            </div>
        @endcanany

    </div>
</div>
