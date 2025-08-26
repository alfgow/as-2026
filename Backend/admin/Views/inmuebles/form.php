<?php
$editing = $editMode ?? false;
?>
<div class="max-w-2xl mx-auto py-10">
    <h1 class="text-3xl font-bold text-indigo-300 mb-8"><?= $editing ? 'Editar' : 'Registrar' ?> Inmueble</h1>
    
    <form id="form-inmueble" class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-6">
        
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $inmueble['id'] ?>">
        <?php endif; ?>

        <!-- Dirección -->
        <div>
            <label class="block text-indigo-300 mb-1">Dirección</label>
            <textarea name="direccion_inmueble" required class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" rows="2"><?= htmlspecialchars($inmueble['direccion_inmueble'] ?? '') ?></textarea>
        </div>

        <!-- Tipo y Renta -->
        <div class="grid md:grid-cols-2 gap-4">
            <!-- Tipo -->
            <div>
                <label class="block text-indigo-300 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <option value="Departamento" <?= ($inmueble['tipo'] ?? '') === 'Departamento' ? 'selected' : '' ?>>Departamento</option>
                    <option value="Casa" <?= ($inmueble['tipo'] ?? '') === 'Casa' ? 'selected' : '' ?>>Casa</option>
                    <option value="Oficina" <?= ($inmueble['tipo'] ?? '') === 'Oficina' ? 'selected' : '' ?>>Oficina</option>
                    <option value="Local Comercial" <?= ($inmueble['tipo'] ?? '') === 'Local Comercial' ? 'selected' : '' ?>>Local Comercial</option>
                    <option value="Edificio" <?= ($inmueble['tipo'] ?? '') === 'Edificio' ? 'selected' : '' ?>>Edificio</option>
                </select>
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Renta</label>
                <input type="text" name="renta" value="<?= htmlspecialchars($inmueble['renta'] ?? '') ?>" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
            </div>
        </div>

        <!-- Mantenimiento y Monto -->
        <div class="grid md:grid-cols-2 gap-4">
            <!-- Incluye Mantenimiento -->
            <div>
                <label class="block text-indigo-300 mb-1">¿Incluye Mantenimiento?</label>
                <select name="mantenimiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <option value="SI" <?= ($inmueble['mantenimiento'] ?? '') === 'SI' ? 'selected' : '' ?>>Sí</option>
                    <option value="NO" <?= ($inmueble['mantenimiento'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <!-- Monto mantenimiento -->
            <div>
                <label class="block text-indigo-300 mb-1">Monto mantenimiento</label>
                <input type="number" name="monto_mantenimiento" min="0" step="0.01" value="<?= htmlspecialchars($inmueble['monto_mantenimiento'] ?? '') ?>" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
            </div>

        </div>

        <!-- Depósito -->
        <div>
            <label class="block text-indigo-300 mb-1">Depósito en Garantía</label>
            <select name="deposito" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                <option value="1" <?= ($inmueble['deposito'] ?? '') == '1' ? 'selected' : '' ?>>Un mes</option>
                <option value="2" <?= ($inmueble['deposito'] ?? '') == '2' ? 'selected' : '' ?>>Dos meses</option>
                <option value="3" <?= ($inmueble['deposito'] ?? '') == '3' ? 'selected' : '' ?>>Tres meses</option>
            </select>
        </div>

        <!-- Estacionamiento y Mascotas -->
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-indigo-300 mb-1">Estacionamiento (número de cajones)</label>
                <input type="number" name="estacionamiento" min="0" value="<?= htmlspecialchars($inmueble['estacionamiento'] ?? 0) ?>" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
            </div>
            <div>
                <label class="block text-indigo-300 mb-1">¿Se permiten mascotas?</label>
                <select name="mascotas" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <option value="SI" <?= ($inmueble['mascotas'] ?? '') === 'SI' ? 'selected' : '' ?>>Sí</option>
                    <option value="NO" <?= ($inmueble['mascotas'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>

        <!-- Arrendador y Asesor -->
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-indigo-300 mb-1">Arrendador</label>
                <select name="id_arrendador" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <?php foreach ($arrendadores as $arr): ?>
                        <option value="<?= $arr['id'] ?>" <?= ($inmueble['id_arrendador'] ?? '') == $arr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($arr['nombre_arrendador']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-indigo-300 mb-1">Asesor</label>
                <select name="id_asesor" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <?php foreach ($asesores as $as): ?>
                        <option value="<?= $as['id'] ?>" <?= ($inmueble['id_asesor'] ?? '') == $as['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($as['nombre_asesor']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Comentarios -->
        <div>
            <label class="block text-indigo-300 mb-1">Comentarios</label>
            <textarea name="comentarios" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" rows="3"><?= htmlspecialchars($inmueble['comentarios'] ?? '') ?></textarea>
        </div>

        <!-- Botón -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow">
                Guardar
            </button>
        </div>
    </form>
</div>

<!-- SCRIPT: Guardado por AJAX -->
<script>
document.getElementById('form-inmueble').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const action = '<?= $baseUrl ?><?= $editing ? '/inmuebles/update' : '/inmuebles/store' ?>';
    
    fetch(action, {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(res => {
        if(res.ok){
            Swal.fire('Inmueble editado con éxito', '', 'success').then(() => {
                window.location = '<?= $baseUrl ?>/inmuebles';
            });
        } else {
            Swal.fire('Error', 'No se pudo guardar', 'error');
        }
    }).catch(err => {
        console.error(err);
        Swal.fire('Error', 'Ocurrió un error inesperado', 'error');
    });
});
</script>