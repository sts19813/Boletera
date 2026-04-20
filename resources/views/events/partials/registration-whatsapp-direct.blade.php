<div class="card shadow-sm mb-8">
    <div class="card-header bg-light">
        <h4 class="card-title fw-bold mb-0">Registro EAFC 26 - Super Willys</h4>
    </div>

    <div class="card-body">
        <div class="row g-5">
            <div class="col-md-6">
                <label class="form-label required">Nombre completo</label>
                <input type="text" name="full_name" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-3">
                <label class="form-label required">Edad</label>
                <input type="number" name="age" min="1" max="120" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-3">
                <label class="form-label required">ID del juego</label>
                <input type="text" name="game_id" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Ciudad</label>
                <input type="text" name="city" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Estado</label>
                <input type="text" name="state" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Telefono</label>
                <input type="tel" name="phone" class="form-control form-control-solid js-phone"
                    inputmode="numeric" maxlength="10" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Correo electronico</label>
                <input type="email" name="email" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Consola en la que juegas</label>
                <input type="text" name="console" class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Has participado en un torneo de ESTOM antes?</label>
                <select name="participated_before" class="form-select form-select-solid" required>
                    <option value="">Selecciona una opcion</option>
                    <option value="si">Si</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Cuantas veces? (solo numeros)</label>
                <input type="number" name="participation_count" min="0" step="1"
                    class="form-control form-control-solid" required>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Como nos conociste?</label>
                <select name="how_known" class="form-select form-select-solid" required>
                    <option value="">Selecciona una opcion</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="youtube">YouTube</option>
                    <option value="referido">Alguien me conto</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Usuario de Twitch o YouTube (opcional)</label>
                <input type="text" name="stream_user" class="form-control form-control-solid">
            </div>

            <div class="col-md-6">
                <label class="form-label required">
                    Sube tu recibo de Super Willys (imagen o PDF, consumo minimo de $100)
                </label>
                <input type="file" name="purchase_receipt" class="form-control form-control-solid"
                    accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/*" required>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-phone').forEach(input => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    });
</script>
