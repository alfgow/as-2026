
<section class="px-4 md:px-10 py-10 text-white space-y-10  lg:w-[80%] max-w-5xl mx-auto">

    <!-- Título -->
    <h1 class="text-4xl font-extrabold flex items-center gap-3 text-indigo-300">
        <svg class="w-9 h-9 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M5.121 17.804A13.937 13.937 0 0112 15c2.485 0 4.797.657 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Detalle del arrendador
    </h1>

    <!-- Datos Personales -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl flex flex-col-reverse md:grid md:grid-cols-2 gap-6">

    <!-- 📄 Datos personales -->
    <div>
        <h2 class="text-xl font-semibold text-indigo-400 mb-4">Datos personales</h2>
        <div id="datos-personales-vista" class="space-y-2">
            <p><span class="font-semibold text-indigo-200">Nombre:</span> <?= htmlspecialchars($arrendador['nombre_arrendador']) ?></p>
            <p><span class="font-semibold text-indigo-200">Email:</span> <?= htmlspecialchars($arrendador['email']) ?></p>
            <p><span class="font-semibold text-indigo-200">Celular:</span> <?= htmlspecialchars($arrendador['celular']) ?></p>
            <p><span class="font-semibold text-indigo-200">Teléfono:</span> <?= $arrendador['telefono'] ?: 'N/D' ?></p>
            <p><span class="font-semibold text-indigo-200">Dirección:</span> <?= htmlspecialchars($arrendador['direccion_arrendador']) ?></p>
            <p><span class="font-semibold text-indigo-200">Estado civil:</span> <?= htmlspecialchars($arrendador['estadocivil']) ?></p>
            <p><span class="font-semibold text-indigo-200">Nacionalidad:</span> <?= htmlspecialchars($arrendador['nacionalidad']) ?></p>
            <p><span class="font-semibold text-indigo-200">RFC:</span> <?= htmlspecialchars($arrendador['rfc']) ?></p>
            <p><span class="font-semibold text-indigo-200">Tipo de ID:</span> <?= htmlspecialchars($arrendador['tipo_id']) ?> - <?= htmlspecialchars($arrendador['num_id']) ?></p>
            <p><span class="font-semibold text-indigo-200">Fecha de registro:</span> <?= date('d/m/Y H:i', strtotime($arrendador['fecha_registro'])) ?></p>
        </div>

        <!-- Botón y formulario como lo tenías -->
        <form id="form-datos-personales" class="hidden space-y-4 mt-4" onsubmit="guardarDatosPersonales(event)">
                <input type="hidden" name="id" value="<?= $arrendador['id'] ?>">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Nombre</label>
                        <input type="text" name="nombre_arrendador" value="<?= htmlspecialchars($arrendador['nombre_arrendador']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($arrendador['email']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Celular</label>
                        <input type="text" name="celular" value="<?= htmlspecialchars($arrendador['celular']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="<?= htmlspecialchars($arrendador['telefono']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm mb-1">Dirección</label>
                        <input type="text" name="direccion_arrendador" value="<?= htmlspecialchars($arrendador['direccion_arrendador']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Estado civil</label>
                        <input type="text" name="estadocivil" value="<?= htmlspecialchars($arrendador['estadocivil']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Nacionalidad</label>
                        <input type="text" name="nacionalidad" value="<?= htmlspecialchars($arrendador['nacionalidad']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">RFC</label>
                        <input type="text" name="rfc" value="<?= htmlspecialchars($arrendador['rfc']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Tipo de ID</label>
                        <input type="text" name="tipo_id" value="<?= htmlspecialchars($arrendador['tipo_id']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Número de ID</label>
                        <input type="text" name="num_id" value="<?= htmlspecialchars($arrendador['num_id']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    </div>
                </div>
                <div class="flex justify-end gap-4 pt-2">
                    <button type="button" onclick="cancelarEdicionPersonales()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
                </div>
            </form>
        <button id="btn-edit-personales" type="button" onclick="mostrarFormPersonales()" class="mt-4 px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
    </div>

    <!-- 📷 Documentos -->
    <div class="flex flex-col items-center gap-6">
        <?php
            $selfie = null;
            $documento = null;
            foreach ($arrendador['archivos'] as $archivo) {
                if ($archivo['tipo'] === 'selfie') {
                    $selfie = $archivo['s3_key'];
                } else {
                    $documento = $archivo['s3_key'];
                }
            }
            $s3BaseUrl = 'https://as-s3-arrendadores.s3.mx-central-1.amazonaws.com/';
        ?>
        <div class="text-center">
            <p class="text-sm text-indigo-200 mb-2">Selfie</p>
            <?php if ($selfie): ?>
                <img src="<?= $s3BaseUrl.$selfie ?>" alt="Selfie" class="rounded-full h-40 w-40 object-cover border-2 border-indigo-500 shadow-lg">
            <?php else: ?>
                <p class="text-gray-400 italic">No disponible</p>
            <?php endif; ?>
        </div>
        <div class="text-center">
            <p class="text-sm text-indigo-200 mb-2">Documento cargado</p>
            <?php if ($documento): ?>
                <img src="<?= $s3BaseUrl.$documento ?>" alt="Documento" class="rounded-lg h-40 object-contain border border-indigo-500 shadow">
            <?php else: ?>
                <p class="text-gray-400 italic">No disponible</p>
            <?php endif; ?>
        </div>
    </div>
    </div>


    <!-- Datos Bancarios -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
        <h2 class="text-xl font-semibold text-indigo-400 mb-4">Información bancaria</h2>
        <div id="info-bancaria-vista" class="grid md:grid-cols-3 gap-6">
            <p><span class="font-semibold text-indigo-200">Banco:</span> <?= htmlspecialchars($arrendador['banco']) ?: 'N/D' ?></p>
            <p><span class="font-semibold text-indigo-200">Cuenta:</span> <?= htmlspecialchars($arrendador['cuenta']) ?: 'N/D' ?></p>
            <p><span class="font-semibold text-indigo-200">CLABE:</span> <?= htmlspecialchars($arrendador['clabe']) ?: 'N/D' ?></p>
        </div>

        <form id="form-info-bancaria" class="hidden mt-4 grid md:grid-cols-3 gap-4" onsubmit="guardarInfoBancaria(event)">
            <input type="hidden" name="id" value="<?= $arrendador['id'] ?>">
            <div>
                <label class="block text-sm mb-1">Banco</label>
                <input type="text" name="banco" value="<?= htmlspecialchars($arrendador['banco']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>
            <div>
                <label class="block text-sm mb-1">Cuenta</label>
                <input type="text" name="cuenta" value="<?= htmlspecialchars($arrendador['cuenta']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>
            <div>
                <label class="block text-sm mb-1">CLABE</label>
                <input type="text" name="clabe" value="<?= htmlspecialchars($arrendador['clabe']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>
            <div class="md:col-span-3 flex justify-end gap-4">
                <button type="button" onclick="cancelarInfoBancaria()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
            </div>
        </form>

        <button id="btn-edit-bancaria" type="button" onclick="mostrarInfoBancaria()" class="mt-4 px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
    </div>

    <!-- Inmuebles -->
    <?php if (!empty($arrendador['inmuebles'])): ?>
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-400 flex items-center gap-3">
                Inmuebles registrados
            </h2>
            <!-- Botón agregar inmueble -->
            <button
                type="button"
                class="
                    flex items-center justify-center
                    bg-emerald-500 hover:bg-emerald-600 transition
                    text-white font-bold
                    rounded-full lg:rounded-lg
                    w-12 h-12 lg:w-auto lg:h-10 px-0 lg:px-6
                    shadow-lg
                    focus:outline-none focus:ring-2 focus:ring-emerald-300
                    text-2xl lg:text-base
                    group
                "
            >
                <span class="lg:hidden">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </span>
                <span class="hidden lg:inline font-semibold tracking-wide">
                    <svg class="inline w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar
                </span>
            </button>
        </div>
        <!-- Cards centrados según cantidad -->
        <?php
            $numInmuebles = count($arrendador['inmuebles']);
            $containerClass = $numInmuebles === 1
                ? 'flex justify-center'
                : 'grid md:grid-cols-2 gap-6';
        ?>
        <div class="<?= $containerClass ?>">
            <?php foreach ($arrendador['inmuebles'] as $inm): ?>
                <div class="bg-white/5 border border-white/10 backdrop-blur-md p-4 rounded-lg shadow space-y-1 w-full max-w-md">
                    <p><span class="font-semibold text-indigo-200">Dirección:</span> <?= htmlspecialchars($inm['direccion_inmueble']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Tipo:</span> <?= htmlspecialchars($inm['tipo']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Renta:</span> $<?= number_format($inm['renta']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Mantenimiento:</span> <?= htmlspecialchars($inm['mantenimiento']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Depósito:</span> <?= htmlspecialchars($inm['deposito']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Estacionamiento:</span> <?= $inm['estacionamiento'] ? 'Sí' : 'No' ?></p>
                    <p><span class="font-semibold text-indigo-200">Mascotas:</span> <?= htmlspecialchars($inm['mascotas']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Comentarios:</span> <?= htmlspecialchars($inm['comentarios']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>




    <!-- Pólizas -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
    <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-400 flex items-center gap-3">
                Pólizas registradas
            </h2>
            <!-- Botón agregar inmueble -->
            <button
                type="button"
                class="
                    flex items-center justify-center
                    bg-emerald-500 hover:bg-emerald-600 transition
                    text-white font-bold
                    rounded-full lg:rounded-lg
                    w-12 h-12 lg:w-auto lg:h-10 px-0 lg:px-6
                    shadow-lg
                    focus:outline-none focus:ring-2 focus:ring-emerald-300
                    text-2xl lg:text-base
                    group
                "
            >
                <span class="lg:hidden">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </span>
                <span class="hidden lg:inline font-semibold tracking-wide">
                    <svg class="inline w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar
                </span>
            </button>
        </div>
      
    <?php if (!empty($arrendador['polizas'])): ?>
        <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($arrendador['polizas'] as $p): ?>
                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-xl p-4 shadow space-y-2">
                    <p><span class="font-semibold text-indigo-200">Tipo:</span> <?= htmlspecialchars($p['tipo_poliza'] ?? '---') ?></p>
                    <p><span class="font-semibold text-indigo-200">Número:</span> <?= htmlspecialchars($p['numero_poliza'] ?? '---') ?></p>
                    <p><span class="font-semibold text-indigo-200">Vigencia:</span> <?= htmlspecialchars($p['vigencia'] ?? '---') ?></p>
                     
                        <?php
                            $estado = $p['estado'];
                            $badgeColor = estadoBadgeColor($estado);
                            $textoEstado = estadoPolizaTexto($estado);
                        ?>
                        <div class="flex md:items-center md:justify-center lg:justify-start gap-2">
                            <!-- Círculo visible solo en sm y md -->
                            <span class="w-3 h-3 rounded-full <?= estadoBadgeColor($p['estado']) ?> inline-block lg:hidden" title="<?= estadoPolizaTexto($p['estado']) ?>"></span>

                            <!-- Texto visible solo en lg+ -->
                            <span class="hidden lg:inline-block px-3 py-1 rounded-full text-sm font-semibold <?= estadoBadgeColor($p['estado']) ?> text-white shadow">
                                <?= estadoPolizaTexto($p['estado']) ?>
                            </span>
                        </div>


                    <a href="<?= $baseUrl ?>/polizas/<?= urlencode($p['numero_poliza']) ?>"
                       class="inline-block mt-2 px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm shadow transition" target="_blank">
                        Ver póliza
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-400 italic">No se han registrado pólizas para este arrendador.</p>
    <?php endif; ?>
    </div>


    <!-- Técnica / Comentarios -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl grid md:grid-cols-2 gap-6">
        <div>
            <h2 class="text-xl font-semibold text-indigo-400 mb-4">Información técnica</h2>
            <p><span class="font-semibold text-indigo-200">Device ID:</span> <?= htmlspecialchars($arrendador['device_id'] ?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">IP de registro:</span> <?= htmlspecialchars($arrendador['ip']?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">Estatus:</span> <?= htmlspecialchars($arrendador['estatus']?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">Términos:</span> <?= htmlspecialchars($arrendador['terminos_condiciones']?? "") ?></p>
        </div>
        <div>
            <h2 class="text-xl font-semibold text-indigo-400 mb-4">Comentarios</h2>
            <div id="comentarios-vista" class="bg-white/10 rounded-lg p-4 text-indigo-100 italic">
                <?= $arrendador['comentarios'] ?: 'Sin comentarios adicionales.' ?>
            </div>

            <form id="form-comentarios" class="hidden mt-4 space-y-4" onsubmit="guardarComentarios(event)">
                <input type="hidden" name="id" value="<?= $arrendador['id'] ?>">
                <textarea name="comentarios" rows="3" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100"><?= htmlspecialchars($arrendador['comentarios']) ?></textarea>
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="cancelarComentarios()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
                </div>
            </form>

            <button id="btn-edit-comentarios" type="button" onclick="mostrarComentarios()" class="mt-4 px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    window.mostrarFormPersonales = function() {
        document.getElementById('datos-personales-vista').classList.add('hidden');
        document.getElementById('form-datos-personales').classList.remove('hidden');
        document.getElementById('btn-edit-personales').classList.add('hidden');
    };

    window.cancelarEdicionPersonales = function() {
        document.getElementById('form-datos-personales').classList.add('hidden');
        document.getElementById('datos-personales-vista').classList.remove('hidden');
        document.getElementById('btn-edit-personales').classList.remove('hidden');
    };

    window.guardarDatosPersonales = function(e) {
        e.preventDefault();
        const form = document.getElementById('form-datos-personales');
        const data = new FormData(form);
        fetch(BASE_URL + '/arrendador/actualizar-datos-personales', {method: 'POST', body: data})
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                   Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        background: '#1f1f2e', // fondo oscuro
                        color: '#fde8e8ca',     // texto en rosa-base
                        iconColor: '#a5b4fc',   // índigo claro
                        showConfirmButton: false,
                        timer: 2000,
                        position: 'center',     // al centro de la pantalla
                        toast: false,           // no como toast
                        customClass: {
                            popup: 'rounded-2xl shadow-lg border border-indigo-500/30'
                        }
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.error || 'No se pudo guardar',
                        background: '#1f1f2e', // fondo oscuro
                        color: '#fde8e8ca',    // texto rosa base
                        iconColor: '#de6868',  // rojo del sistema (rosa fuerte)
                        position: 'center',
                        toast: false,
                        showConfirmButton: true,
                        confirmButtonColor: '#de6868', // botón estilo sistema
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-red-500/30'
                        }
                    });
                }
            });
    };

    window.mostrarInfoBancaria = function() {
        document.getElementById('info-bancaria-vista').classList.add('hidden');
        document.getElementById('form-info-bancaria').classList.remove('hidden');
        document.getElementById('btn-edit-bancaria').classList.add('hidden');
    };

    window.cancelarInfoBancaria = function() {
        document.getElementById('form-info-bancaria').classList.add('hidden');
        document.getElementById('info-bancaria-vista').classList.remove('hidden');
        document.getElementById('btn-edit-bancaria').classList.remove('hidden');
    };

    window.guardarInfoBancaria = function(e) {
        e.preventDefault();
        const form = document.getElementById('form-info-bancaria');
        const data = new FormData(form);
        fetch(BASE_URL + '/arrendador/actualizar-info-bancaria', {method:'POST', body:data})
            .then(r=>r.json())
            .then(res=>{
                if(res.ok){
                    Swal.fire({icon:'success',title:'Actualizado',toast:true,position:'top-end',timer:2000,showConfirmButton:false});
                    setTimeout(()=>location.reload(),2000);
                }else{
                    Swal.fire({icon:'error',title:'Error',text:res.error||'No se pudo guardar'});
                }
            });
    };

    window.mostrarComentarios = function() {
        document.getElementById('comentarios-vista').classList.add('hidden');
        document.getElementById('form-comentarios').classList.remove('hidden');
        document.getElementById('btn-edit-comentarios').classList.add('hidden');
    };

    window.cancelarComentarios = function() {
        document.getElementById('form-comentarios').classList.add('hidden');
        document.getElementById('comentarios-vista').classList.remove('hidden');
        document.getElementById('btn-edit-comentarios').classList.remove('hidden');
    };

    window.guardarComentarios = function(e) {
        e.preventDefault();
        const form = document.getElementById('form-comentarios');
        const data = new FormData(form);
        fetch(BASE_URL + '/arrendador/actualizar-comentarios', {method:'POST', body:data})
            .then(r=>r.json())
            .then(res=>{
                if(res.ok){
                    Swal.fire({icon:'success',title:'Actualizado',toast:true,position:'top-end',timer:2000,showConfirmButton:false});
                    setTimeout(()=>location.reload(),2000);
                }else{
                    Swal.fire({icon:'error',title:'Error',text:res.error||'No se pudo guardar'});
                }
            });
    };
});
</script>
