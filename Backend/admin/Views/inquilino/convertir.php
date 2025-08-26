

<section class="px-4 md:px-8 py-10 text-white">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-indigo-300 flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 4v16m8-8H4" />
            </svg>
            Convertir inquilino a sistema nuevo
        </h1>
        <a href="<?= $baseUrl ?>/prospecto/old"
           class="text-sm text-indigo-400 hover:text-white underline font-medium transition">
            ← Regresar a prospectos antiguos
        </a>
    </div>

    <form method="POST" action="<?= $baseUrl ?>/prospecto/convertir" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-900 p-6 rounded-xl text-white" id="form-convertir">
  <input type="text" name="id_prospecto" value="<?= htmlspecialchars($inquilino['id']) ?>" hidden>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Asesor asignado</label>
    <select name="id_asesor"
            class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
        <?php foreach ($asesores as $asesor): ?>
            <option value="<?= $asesor['id'] ?>"
                <?= ($inquilino['id_asesor'] ?? '') == $asesor['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($asesor['nombre_asesor']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

  <div>
    <label class="text-indigo-200 text-sm capitalize">Tipo</label>
    <select name="tipo"
            class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
        <option value="Arrendatario" <?= ($inquilino['tipo'] ?? '') === 'Arrendatario' ? 'selected' : '' ?>>Arrendatario</option>
        <option value="Obligado Solidario" <?= ($inquilino['tipo'] ?? '') === 'Obligado Solidario' ? 'selected' : '' ?>>Obligado Solidario</option>
        <option value="Fiador" <?= ($inquilino['tipo'] ?? '') === 'Fiador' ? 'selected' : '' ?>>Fiador</option>
    </select>
</div>

  <div>
    <label class="text-indigo-200 text-sm capitalize">Nombre</label>
    <input type="text" name="nombre_inquilino" value="<?= htmlspecialchars($inquilino["nombre_inquilino"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Apellido Paterno</label>
    <input type="text" name="apellidop_inquilino" value="<?= htmlspecialchars($inquilino["apellidop_inquilino"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Apellido Materno</label>
    <input type="text" name="apellidom_inquilino" value="<?= htmlspecialchars($inquilino["apellidom_inquilino"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Representante Legal</label>
    <input type="text" name="representante" value="<?= htmlspecialchars($inquilino["representante"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
        <label class="text-indigo-200 text-sm capitalize">Estado civil</label>
        <select name="estadocivil"
                class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
            <option value="Soltero" <?= ($inquilino['estadocivil'] ?? '') === 'Soltero' ? 'selected' : '' ?>>Soltero</option>
            <option value="Casado" <?= ($inquilino['estadocivil'] ?? '') === 'Casado' ? 'selected' : '' ?>>Casado</option>
            <option value="Divorciado" <?= ($inquilino['estadocivil'] ?? '') === 'Divorciado' ? 'selected' : '' ?>>Divorciado</option>
        </select>
    </div>

  <div>
    <label class="text-indigo-200 text-sm capitalize">RFC</label>
    <input type="text" name="rfc" value="<?= htmlspecialchars($inquilino["rfc"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>

  <div>
    <label class="text-indigo-200 text-sm capitalize">Calle</label>
    <input type="text" name="calle" value="<?= htmlspecialchars($inquilino["direccion"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Núm. Ext.</label>
    <input type="text" name="num_exterior" value="<?= htmlspecialchars($inquilino["num_exterior"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Núm. Int.</label>
    <input type="text" name="num_interior" value="<?= htmlspecialchars($inquilino["num_interior"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Colonia</label>
    <input type="text" name="colonia" value="<?= htmlspecialchars($inquilino["colonia"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Alcaldía</label>
    <input type="text" name="alcaldia" value="<?= htmlspecialchars($inquilino["alcaldia"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Ciudad</label>
    <input type="text" name="ciudad" value="<?= htmlspecialchars($inquilino["ciudad"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Código Postal</label>
    <input type="text" name="codigo_postal" value="<?= htmlspecialchars($inquilino["codigo_postal"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">email</label>
    <input type="text" name="email" value="<?= htmlspecialchars($inquilino["email"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Celular</label>
    <input type="text" name="celular" value="<?= htmlspecialchars($inquilino["celular"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Nacionalidad</label>
    <input type="text" name="nacionalidad" value="<?= htmlspecialchars($inquilino["nacionalidad"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Tipo de Identificación</label>
    <select name="tipo_id"
            class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
        <option value="INE" <?= ($inquilino['tipo_id'] ?? '') === 'INE' ? 'selected' : '' ?>>INE</option>
        <option value="Pasaporte" <?= ($inquilino['tipo_id'] ?? '') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
        <option value="Forma Migratoria" <?= ($inquilino['tipo_id'] ?? '') === 'Forma Migratoria' ? 'selected' : '' ?>>Forma Migratoria</option>
    </select>
</div>

<div>
    <label class="text-indigo-200 text-sm capitalize">Número de identificación</label>
    <input type="text" name="num_id" value="<?= htmlspecialchars($inquilino["num_id"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>

  
  <div>
    <label class="text-indigo-200 text-sm capitalize">empresa</label>
    <input type="text" name="empresa" value="<?= htmlspecialchars($inquilino["empresa"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">direccion empresa</label>
    <input type="text" name="direccion_empresa" value="<?= htmlspecialchars($inquilino["direccion_empresa"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">telefono empresa</label>
    <input type="text" name="telefono_empresa" value="<?= htmlspecialchars($inquilino["telefono_empresa"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">puesto</label>
    <input type="text" name="puesto" value="<?= htmlspecialchars($inquilino["puesto"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">antiguedad</label>
    <input type="text" name="antiguedad" value="<?= htmlspecialchars($inquilino["antiguedad"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">sueldo</label>
    <input type="text" name="sueldo" value="<?= htmlspecialchars($inquilino["sueldo"] ?? 0) ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">otrosingresos</label>
    <input type="text" name="otrosingresos" value="<?= htmlspecialchars($inquilino["otrosingresos"] ?? 0) ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">jefe</label>
    <input type="text" name="jefe" value="<?= htmlspecialchars($inquilino["jefe"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">tel jefe</label>
    <input type="text" name="tel_jefe" value="<?= htmlspecialchars($inquilino["tel_jefe"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">web empresa</label>
    <input type="text" name="web_empresa" value="<?= htmlspecialchars($inquilino["web_empresa"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">Dónde vive actualmente?</label>
    <input type="text" name="vive_actualmente" value="<?= htmlspecialchars($inquilino["vive_actualmente"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">arrendador actual</label>
    <input type="text" name="arrendador_actual" value="<?= htmlspecialchars($inquilino["arrendador_actual"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">cel arrendador actual</label>
    <input type="text" name="cel_arrendador_actual" value="<?= htmlspecialchars($inquilino["cel_arrendador_actual"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">monto renta actual</label>
    <input type="text" name="monto_renta_actual" value="<?= htmlspecialchars($inquilino["monto_renta_actual"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">tiempo habitacion actual</label>
    <input type="text" name="tiempo_habitacion_actual" value="<?= htmlspecialchars($inquilino["tiempo_habitacion_actual"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">motivo arrendamiento</label>
    <input type="text" name="motivo_arrendamiento" value="<?= htmlspecialchars($inquilino["motivo_arrendamiento"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  <div>
    <label class="text-indigo-200 text-sm capitalize">terminos condiciones</label>
    <input type="text" name="terminos_condiciones" value="<?= htmlspecialchars($inquilino["terminos_condiciones"] ?? "") ?>"
           class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
  </div>
  
  
  <div class="md:col-span-2 text-right mt-4">
        <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-6 py-2 rounded-lg font-semibold shadow transition">
            Guardar y migrar
        </button>
  </div>
</form>
</section>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("form-convertir").addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    try {
        const response = await fetch("<?= $baseUrl ?>/prospecto/convertir", {
            method: "POST",
            body: formData
        });

        const data = await response.json();
        console.log(data);
        

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Migración exitosa!',
                text: `El inquilino ${data.nombre} fue migrado correctamente.`,
                confirmButtonColor: '#6366f1'
            }).then(() => {
                window.location.href = "<?= $baseUrl ?>/prospecto/" + data.slug;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Ocurrió un error al migrar.',
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo procesar la migración.',
        });
    }
});
</script>
