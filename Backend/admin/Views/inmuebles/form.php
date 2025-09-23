<?php
$editing = $editMode ?? false;
$inmueble = $inmueble ?? [];
$arrendadores = $arrendadores ?? [];
$asesores = $asesores ?? [];
$asesoresPorPk = [];
foreach ($asesores as $asesor) {
    $pk = $asesor['pk'] ?? (isset($asesor['id']) ? 'ase#' . $asesor['id'] : null);
    if ($pk) {
        $asesoresPorPk[strtolower($pk)] = $asesor['nombre_asesor'] ?? '';
    }
}
?>
<div class="max-w-2xl mx-auto py-10">
    <h1 class="text-3xl font-bold text-indigo-300 mb-8"><?= $editing ? 'Editar' : 'Registrar' ?> Inmueble</h1>
    
    <form id="form-inmueble" class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-6">
        
        <?php if ($editing): ?>
            <input type="hidden" name="sk" value="<?= htmlspecialchars($inmueble['sk'] ?? '') ?>">
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
                    <option value="Si" <?= ($inmueble['mantenimiento'] ?? '') === 'Si' ? 'selected' : '' ?>>Si</option>
                    <option value="No" <?= ($inmueble['mantenimiento'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                    <option value="na" <?= ($inmueble['mantenimiento'] ?? '') === 'na' ? 'selected' : '' ?>>No Aplica</option>
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
                <?php
                    $currentPk = strtolower((string)($inmueble['pk'] ?? ($inmueble['arrendador_pk'] ?? '')));
                    if ($currentPk === '' && !empty($inmueble['id_arrendador'])) {
                        $currentPk = 'arr#' . strtolower((string)$inmueble['id_arrendador']);
                    }
                ?>
                <select name="pk" id="arrendador-select" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800" required>
                    <?php foreach ($arrendadores as $arr): ?>
                        <?php
                            $arrPk = $arr['pk'] ?? (isset($arr['id']) ? 'arr#' . $arr['id'] : '');
                            $arrPkLower = strtolower((string)$arrPk);
                            $asesorPkRaw = $arr['asesor'] ?? ($arr['asesor_pk'] ?? '');
                            $asesorPk = strtolower((string)$asesorPkRaw);
                            $asesorNombre = $asesorPk !== '' ? ($asesoresPorPk[$asesorPk] ?? '') : '';
                            $selected = $currentPk === $arrPkLower;
                        ?>
                        <option value="<?= htmlspecialchars($arrPkLower) ?>"
                                data-asesor="<?= htmlspecialchars($asesorPk) ?>"
                                data-asesor-nombre="<?= htmlspecialchars($asesorNombre) ?>"
                                <?= $selected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($arr['nombre_arrendador'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-indigo-300 mb-1">Asesor asociado</label>
                <input type="text" id="asesor-display" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-300 border border-indigo-800" value="" readonly placeholder="Se asignará automáticamente">
                <?php
                    $asesorInicial = strtolower((string)($inmueble['asesor'] ?? ($inmueble['asesor_pk'] ?? '')));
                    if ($asesorInicial === '' && !empty($inmueble['id_asesor'])) {
                        $asesorInicial = 'ase#' . strtolower((string)$inmueble['id_asesor']);
                    }
                ?>
                <input type="hidden" name="asesor_pk" id="asesor-pk" value="<?= htmlspecialchars($asesorInicial) ?>">
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

    const decimalFields = ['renta', 'monto_mantenimiento', 'deposito'];
    const toDecimalString = (value) => {
        if (value === null || value === undefined) {
            return '0.00';
        }
        const raw = String(value).trim();
        if (raw === '') {
            return '0.00';
        }
        const normalized = raw.replace(/[^0-9,.-]/g, '').replace(',', '.');
        const number = parseFloat(normalized);
        if (Number.isNaN(number)) {
            return '0.00';
        }
        return number.toFixed(2);
    };

    decimalFields.forEach((field) => {
        if (formData.has(field)) {
            formData.set(field, toDecimalString(formData.get(field)));
        }
    });
    const action = '<?= $baseUrl ?><?= $editing ? '/inmuebles/update' : '/inmuebles/store' ?>';
    const isEditing = <?= $editing ? 'true' : 'false' ?>;
    
    fetch(action, {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(res => {
        if(res.ok){
            Swal.fire('Inmueble editado con éxito', '', 'success').then(() => {
                if (isEditing && res.id) {
                    window.location = '<?= $baseUrl ?>/inmuebles/' + encodeURIComponent(res.id);
                    return;
                }

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

const arrendadorSelect = document.getElementById('arrendador-select');
const asesorHidden = document.getElementById('asesor-pk');
const asesorDisplay = document.getElementById('asesor-display');

const syncAsesor = () => {
    if (!arrendadorSelect) return;
    const selected = arrendadorSelect.selectedOptions[0];
    if (!selected) {
        if (asesorHidden) asesorHidden.value = '';
        if (asesorDisplay) asesorDisplay.value = '';
        return;
    }
    const asesorPk = selected.dataset.asesor ?? '';
    const asesorNombre = selected.dataset.asesorNombre ?? '';
    if (asesorHidden) {
        asesorHidden.value = asesorPk;
    }
    if (asesorDisplay) {
        asesorDisplay.value = asesorNombre;
    }
};

if (arrendadorSelect) {
    arrendadorSelect.addEventListener('change', syncAsesor);
    syncAsesor();
}
</script>
