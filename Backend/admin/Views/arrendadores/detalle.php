<?php

use App\Helpers\TextHelper;
?>
<section class="px-1 md:px-10 py-10 text-white space-y-10 lg:w-[80%] max-w-6xl mx-auto">


    <!-- Datos Personales -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl text-center">
        <?php
        $selfie = null;
        foreach ($arrendador['archivos'] as $archivo) {
            if ($archivo['tipo'] === 'selfie') {
                $selfie = $archivo['url'];
                break;
            }
        }
        ?>
        <?php if ($selfie): ?>
            <img src="<?= htmlspecialchars($selfie) ?>"
                alt="Selfie"
                class="w-32 h-32 mx-auto object-cover rounded-full shadow-lg ring-4 ring-indigo-400/40 border-4 border-white/10 mb-4 cursor-pointer" onclick="abrirModal('<?= htmlspecialchars($selfie) ?>')">
        <?php endif; ?>

        <div class=" flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M5.121 17.804A13.937 13.937 0 0112 15c2.485 0 4.797.657 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Arrendador: <?= TextHelper::titleCase($arrendador['profile']['nombre_arrendador']); ?>
            </h2>
            <button id="btn-edit-personales" type="button" onclick="mostrarFormPersonales()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
        </div>

        <div id="datos-personales-vista" class="grid sm:grid-cols-2 gap-4 text-indigo-100 text-left">
            <p><span class="font-semibold text-indigo-200">Email:</span> <?= htmlspecialchars($arrendador['profile']['email'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Celular:</span> <?= htmlspecialchars($arrendador['profile']['celular'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Dirección:</span> <?= TextHelper::titleCase($arrendador['profile']['direccion_arrendador'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Estado civil:</span> <?= TextHelper::ucfirst($arrendador['profile']['estadocivil'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Nacionalidad:</span> <?= TextHelper::ucfirst($arrendador['profile']['nacionalidad'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">RFC:</span> <?= TextHelper::upper($arrendador['profile']['rfc'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Tipo de ID:</span> <?= TextHelper::upper($arrendador['profile']['tipo_id'] ?? '') ?> - <?= TextHelper::upper($arrendador['profile']['num_id'] ?? '') ?></p>
            <p><span class="font-semibold text-indigo-200">Fecha registro:</span> <?= !empty($arrendador['profile']['fecha_registro']) ? date('d/m/Y H:i', strtotime($arrendador['profile']['fecha_registro'])) : '' ?></p>
        </div>

        <!-- Form edición -->
        <form id="form-datos-personales" class="hidden mt-4 space-y-4" onsubmit="guardarDatosPersonales(event)">
            <input type="hidden" name="id" value="<?= $arrendador['profile']['pk'] ?>">

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Nombre</label>
                    <input type="text" name="nombre_arrendador" value="<?= htmlspecialchars($arrendador['profile']['nombre_arrendador']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($arrendador['profile']['email']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div>
                    <label class="block text-sm mb-1">Celular</label>
                    <input type="text" name="celular" value="<?= htmlspecialchars($arrendador['profile']['celular']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div>
                    <label class="block text-sm mb-1">Estado civil</label>
                    <select name="estadocivil"
                        class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                        <option value="Soltero" <?= ($arrendador['profile']['estadocivil'] ?? '') === 'Soltero' ? 'selected' : '' ?>>Soltero</option>
                        <option value="Casado" <?= ($arrendador['profile']['estadocivil'] ?? '') === 'Casado' ? 'selected' : '' ?>>Casado</option>
                        <option value="Divorciado" <?= ($arrendador['profile']['estadocivil'] ?? '') === 'Divorciado' ? 'selected' : '' ?>>Divorciado</option>
                        <option value="Viudo" <?= ($arrendador['profile']['estadocivil'] ?? '') === 'Viudo' ? 'selected' : '' ?>>Viudo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Nacionalidad</label>
                    <input type="text" name="nacionalidad" value="<?= htmlspecialchars($arrendador['profile']['nacionalidad']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div>
                    <label class="block text-sm mb-1">RFC</label>
                    <input type="text" name="rfc" value="<?= htmlspecialchars($arrendador['profile']['rfc']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div>
                    <label class="block text-sm mb-1">Tipo de ID</label>
                    <select name="tipo_id"
                        class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                        <option value="INE" <?= strtoupper($arrendador['profile']['tipo_id'] ?? '') === 'INE' ? 'selected' : '' ?>>INE</option>
                        <option value="Pasaporte" <?= strtoupper($arrendador['profile']['tipo_id'] ?? '') === 'PASAPORTE' ? 'selected' : '' ?>>Pasaporte</option>
                        <option value="Forma Migratoria" <?= stripos($arrendador['profile']['tipo_id'] ?? '', 'migratoria') !== false ? 'selected' : '' ?>>Forma Migratoria</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Número de ID</label>
                    <input type="text" name="num_id" value="<?= htmlspecialchars($arrendador['profile']['num_id']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Dirección</label>
                    <textarea name="direccion_arrendador" rows="3"
                        class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100"><?= htmlspecialchars($arrendador['profile']['direccion_arrendador']) ?></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-4 pt-2">
                <button type="button" onclick="cancelarEdicionPersonales()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>

    <?php
    $asesorActual     = $asesorActual ?? null;
    $asesores         = $asesores ?? [];
    $asesorIdActual   = $asesorActual['id'] ?? ($arrendador['profile']['id_asesor'] ?? null);
    $asesorNombre     = $asesorActual['nombre_asesor'] ?? 'No asignado';
    $asesorEmail      = $asesorActual['email'] ?? 'N/A';
    $asesorCelular    = $asesorActual['celular'] ?? 'N/A';
    $arrendadorIdForm = (int)($arrendador['profile']['id'] ?? 0);
    ?>

    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 15c2.485 0 4.797.657 6.879 1.804" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Asesor asignado
            </h2>
            <button id="btn-edit-asesor" type="button" onclick="mostrarFormAsesor()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded-lg">Editar</button>
        </div>

        <div id="asesor-vista" class="grid sm:grid-cols-3 gap-4 text-indigo-100 text-left">
            <p><span class="font-semibold text-indigo-200">Nombre:</span> <?= htmlspecialchars($asesorNombre) ?></p>
            <p><span class="font-semibold text-indigo-200">Email:</span> <?= htmlspecialchars($asesorEmail ?: 'N/A') ?></p>
            <p><span class="font-semibold text-indigo-200">Celular:</span> <?= htmlspecialchars($asesorCelular ?: 'N/A') ?></p>
        </div>

        <form id="form-asesor" class="hidden mt-4 space-y-4" onsubmit="guardarAsesor(event)">
            <input type="hidden" name="id_arrendador" value="<?= $arrendadorIdForm ?>">

            <div>
                <label class="block text-sm mb-1">Selecciona el asesor</label>
                <select name="id_asesor" required class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    <option value="">Selecciona...</option>
                    <?php foreach ($asesores as $asesor):
                        $idAsesor = (int)($asesor['id'] ?? 0);
                        $selected = $asesorIdActual && $idAsesor === (int)$asesorIdActual ? 'selected' : '';
                        $label    = (string)($asesor['nombre_asesor'] ?? '');
                        if (!empty($asesor['email'])) {
                            $label .= ' (' . $asesor['email'] . ')';
                        }
                    ?>
                        <option value="<?= $idAsesor ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="mensaje-asesor" class="text-sm text-center"></div>

            <div class="flex justify-end gap-4 pt-2">
                <button type="button" onclick="cancelarAsesor()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>

    <div class="bg-gray-900 py-6 px-2 rounded-xl shadow-xl">
        <h2 class="text-xl font-semibold text-white mb-6">📂 Documentos</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <?php
            $tipos = [
                'identificacion_frontal' => 'INE Frente',
                'identificacion_reverso' => 'INE Reverso',
                'selfie'                 => 'Selfie',
                'poliza'                 => 'Póliza'
            ];

            $idArrendador = null;
            if (!empty($arrendador['profile']['pk']) && str_starts_with($arrendador['profile']['pk'], 'arr#')) {
                $idArrendador = str_replace('arr#', '', $arrendador['profile']['pk']);
            }

            foreach ($tipos as $tipo => $label):
                $archivo = null;
                foreach ($arrendador['archivos'] as $a) {
                    if ($a['tipo'] === $tipo) {
                        $archivo = $a;
                        break;
                    }
                }
            ?>
                <div class="bg-gray-800 rounded-xl py-5 px-2 flex flex-col justify-center items-center shadow-md hover:shadow-lg transition">
                    <p class="text-sm font-medium text-gray-300 mb-3"><?= $label ?></p>

                    <?php if ($archivo): ?>
                        <!-- Vista inicial con archivo existente -->
                        <div id="view-<?= $tipo ?>" class="flex flex-col items-center w-full">
                            <?php if ($tipo === 'poliza'): ?>
                                <a href="<?= $archivo['url'] ?>" target="_blank"
                                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow text-center mb-4">
                                    📥 Descargar Póliza
                                </a>
                            <?php else: ?>
                                <img src="<?= $archivo['url'] ?>"
                                    class="rounded-lg w-100 h-40 object-cover mb-4 border border-gray-700 cursor-pointer" onclick="abrirModal('<?= htmlspecialchars($archivo['url']) ?>')">
                            <?php endif; ?>

                            <div class="flex flex-col sm:flex-row gap-2 w-full">
                                <button type="button" onclick="mostrarForm('<?= $tipo ?>')"
                                    class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg shadow">
                                    Cambiar
                                </button>
                                <button class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg shadow">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <!-- Formulario oculto para cambiar archivo -->
                        <div id="form-<?= $tipo ?>" class="hidden w-full">
                            <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-600 rounded-lg p-4 w-full mb-4">

                                <p class="text-gray-400 text-xs mb-3">Selecciona un nuevo archivo</p>

                                <!-- Preview dinámico -->
                                <div class="w-32 h-32 flex items-center justify-center bg-gray-700 rounded-lg overflow-hidden mb-3">
                                    <img id="preview-<?= $tipo ?>" src="" alt="Vista previa"
                                        class="hidden w-full h-full object-cover">
                                    <span id="placeholder-<?= $tipo ?>" class="text-gray-500 text-sm">Vista previa</span>
                                </div>

                                <!-- Formulario -->
                                <form id="form-cambiar-<?= $tipo ?>" action="<?= $baseUrl ?>/arrendador/cambiar-archivo" method="POST" enctype="multipart/form-data"
                                    class="flex flex-col gap-3 w-full">
                                    <input type="hidden" name="id_arrendador" value="<?= htmlspecialchars($idArrendador) ?>">
                                    <input type="hidden" name="tipo" value="<?= $tipo ?>">

                                    <label for="file-<?= $tipo ?>"
                                        class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg shadow text-center">
                                        Seleccionar archivo
                                    </label>
                                    <input id="file-<?= $tipo ?>" type="file" name="archivo"
                                        class="hidden"
                                        accept="<?= $tipo === 'poliza' ? '.doc,.docx,.pdf' : 'image/*' ?>"
                                        onchange="mostrarPreview(event, '<?= $tipo ?>')" />

                                    <div class="flex flex-col sm:flex-row gap-2 w-full">
                                        <button type="submit"
                                            class=" flex-1 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow">
                                            Subir <?= $label ?>
                                        </button>
                                        <button type="button" onclick="cancelarForm('<?= $tipo ?>')"
                                            class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg shadow">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Empty state con vista previa -->
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-600 rounded-lg p-4 w-full mb-4">

                            <p class="text-gray-400 text-xs mb-3">No has subido <?= $label ?></p>

                            <!-- Preview dinámico -->
                            <div class="w-32 h-32 flex items-center justify-center bg-gray-700 rounded-lg overflow-hidden mb-3">
                                <img id="preview-<?= $tipo ?>" src="" alt="Vista previa"
                                    class="hidden w-full h-full object-cover">
                                <span id="placeholder-<?= $tipo ?>" class="text-gray-500 text-sm">Vista previa</span>
                            </div>

                            <!-- Formulario -->

                            <form action="<?= $baseUrl ?>/arrendador/cambiar-archivo" method="POST" enctype="multipart/form-data"
                                class="flex flex-col gap-3 w-full" data-doc-upload>
                                <input type="hidden" name="id_arrendador" value="<?= htmlspecialchars($idArrendador ?? '') ?>">
                                <input type="hidden" name="tipo" value="<?= $tipo ?>">

                                <!-- Botón Seleccionar archivo -->
                                <label id="btn-select-<?= $tipo ?>" for="file-<?= $tipo ?>"
                                    class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg shadow text-center">
                                    Seleccionar archivo
                                </label>

                                <!-- Input file real -->
                                <input id="file-<?= $tipo ?>" type="file" name="archivo"
                                    class="hidden"
                                    accept="<?= $tipo === 'poliza' ? '.doc,.docx,.pdf' : 'image/*' ?>"
                                    onchange="mostrarPreview(event, '<?= $tipo ?>')" />

                                <!-- Botón Cancelar (oculto por defecto) -->
                                <button id="btn-cancel-<?= $tipo ?>" type="button"
                                    onclick="cancelarSeleccion('<?= $tipo ?>')"
                                    class="hidden px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg shadow">
                                    Cancelar
                                </button>

                                <!-- Botón Subir (oculto hasta que haya archivo) -->
                                <button id="btn-subir-<?= $tipo ?>" type="submit"
                                    class="hidden px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow">
                                    Subir <?= $label ?>
                                </button>

                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- Pólizas -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-400">Pólizas registradas</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($arrendador['polizas'] as $p): ?>
                <div class="bg-white/5 border border-white/10 p-4 rounded-lg shadow space-y-2">
                    <p><span class="font-semibold text-indigo-200">Número:</span> <?= htmlspecialchars($p['numero_poliza']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Tipo:</span> <?= TextHelper::titleCase($p['tipo_poliza']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Vigencia:</span> <?= TextHelper::titleCase($p['vigencia']) ?></p>
                    <div class="flex justify-center pt-2">
                        <a href="<?= $baseUrl ?>/polizas/<?= rawurlencode($p['numero_poliza']) ?>"
                            class="inline-flex w-full sm:w-auto justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-medium text-sm transition" target="_blank">
                            Ver detalle
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Información bancaria -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-400">Información bancaria</h2>
            <button id="btn-edit-bancaria" type="button" onclick="mostrarInfoBancaria()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
        </div>
        <div id="info-bancaria-vista" class="grid md:grid-cols-3 gap-6">
            <p><span class="font-semibold text-indigo-200">Banco:</span> <?= TextHelper::upper($arrendador['profile']['banco'] ?? 'N/D') ?></p>
            <p><span class="font-semibold text-indigo-200">Cuenta:</span> <?= htmlspecialchars($arrendador['profile']['cuenta'] ?? 'N/D') ?></p>
            <p><span class="font-semibold text-indigo-200">CLABE:</span> <?= htmlspecialchars($arrendador['profile']['clabe'] ?? 'N/D') ?></p>
        </div>
        <form id="form-info-bancaria" class="hidden mt-4 grid md:grid-cols-3 gap-4" onsubmit="guardarInfoBancaria(event)">
            <input type="hidden" name="pk" value="<?= $arrendador['profile']['pk'] ?>">
            <input type="text" name="banco" value="<?= htmlspecialchars($arrendador['profile']['banco']) ?>" class="px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            <input type="text" name="cuenta" value="<?= htmlspecialchars($arrendador['profile']['cuenta']) ?>" class="px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            <input type="text" name="clabe" value="<?= htmlspecialchars($arrendador['profile']['clabe']) ?>" class="px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            <div class="md:col-span-3 flex justify-end gap-4">
                <button type="button" onclick="cancelarInfoBancaria()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>

    <!-- Inmuebles -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-400">Inmuebles registrados</h2>
            <button id="btn-agregar-inmueble" type="button" onclick="mostrarFormInmueble()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow">+ Agregar Inmueble</button>
        </div>

        <div id="inmuebles-vista" class="grid md:grid-cols-2 gap-6">
            <?php foreach ($arrendador['inmuebles'] as $inm): ?>
                <div class="bg-white/5 border border-white/10 p-4 rounded-lg shadow space-y-2">
                    <p><span class="font-semibold text-indigo-200">Dirección:</span> <?= TextHelper::titleCase($inm['direccion_inmueble']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Tipo:</span> <?= TextHelper::titleCase($inm['tipo']) ?></p>
                    <p><span class="font-semibold text-indigo-200">Renta:</span> $<?= number_format($inm['renta']) ?></p>
                    <div class="flex gap-2 mt-3">
                        <button type="button" onclick="editarInmueble('<?= htmlspecialchars($arrendador['profile']['pk']) ?>', '<?= htmlspecialchars($inm['sk']) ?>')" class="px-3 py-1 bg-pink-600 hover:bg-pink-500 rounded-lg text-sm">Editar</button>
                        <button type="button" onclick="eliminarInmueble('<?= $inm['sk'] ?>', '<?= $arrendador['profile']['pk'] ?>')" class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded-lg text-sm">Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="form-inmueble" class="hidden mt-6 space-y-4" onsubmit="guardarInmueble(event)">
            <input type="hidden" name="pk" value="<?= $arrendador['profile']['pk'] ?>">

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="calle" placeholder="Calle" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <input type="text" name="num_exterior" placeholder="Número exterior" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="num_interior" placeholder="Número interior (opcional)" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <input type="text" name="colonia" placeholder="Colonia" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="alcaldia" placeholder="Alcaldía" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <input type="text" name="ciudad" placeholder="Ciudad" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" name="codigo_postal" placeholder="Código Postal" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <select name="tipo" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    <option value="">Tipo de inmueble</option>
                    <option value="Departamento">Departamento</option>
                    <option value="Casa">Casa</option>
                    <option value="Local Comercial">Local Comercial</option>
                    <option value="Oficina">Oficina</option>
                    <option value="Terreno">Terreno</option>
                    <option value="Bodega">Bodega</option>
                </select>

            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="number" name="renta" placeholder="Renta mensual (MXN)" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <select name="mantenimiento" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    <option value="">¿Incluye mantenimiento?</option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                    <option value="na">No Aplica</option>
                </select>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="number" name="monto_mantenimiento" placeholder="Monto de mantenimiento (MXN)" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <select name="deposito" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    <option value="">Depósito requerido</option>
                    <option value="Un mes">Un mes</option>
                    <option value="Dos meses">Dos meses</option>
                    <option value="Otro">Otro</option>
                </select>

            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <input type="number" name="estacionamiento" placeholder="Número de estacionamientos" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                <select name="mascotas" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
                    <option value="">¿Permite mascotas?</option>
                    <option value="Sí">Sí</option>
                    <option value="No">No</option>
                </select>
            </div>

            <textarea name="comentarios" placeholder="Comentarios adicionales sobre el inmueble" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100"></textarea>

            <div class="flex justify-end gap-4">
                <button type="button" onclick="cancelarInmueble()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>


    <!-- Técnica -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl grid md:grid-cols-2 gap-6">
        <div>
            <h2 class="text-xl font-semibold text-indigo-400 mb-4">Información técnica</h2>
            <p><span class="font-semibold text-indigo-200">Device ID:</span> <?= htmlspecialchars($arrendador['profile']['device_id'] ?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">IP de registro:</span> <?= htmlspecialchars($arrendador['profile']['ip'] ?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">Estatus:</span> <?= htmlspecialchars($arrendador['profile']['estatus'] ?? "") ?></p>
            <p><span class="font-semibold text-indigo-200">Términos:</span> <?= htmlspecialchars($arrendador['profile']['terminos_condiciones'] ?? "") ?></p>
        </div>
        <div>
            <h2 class="text-xl font-semibold text-indigo-400 mb-4">Comentarios</h2>
            <div id="comentarios-vista" class="bg-white/10 rounded-lg p-4 text-indigo-100 italic">
                <?= $arrendador['profile']['comentarios'] ?? 'Sin comentarios adicionales.' ?>
            </div>
            <form id="form-comentarios" class="hidden mt-4 space-y-4" onsubmit="guardarComentarios(event)">
                <input type="hidden" name="pk" value="<?= $arrendador['profile']['pk'] ?>">
                <textarea name="comentarios" rows="3" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100"><?= htmlspecialchars($arrendador['profile']['comentarios'] ?? '') ?></textarea>
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="cancelarComentarios()" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg">Guardar</button>
                </div>
            </form>
            <button id="btn-edit-comentarios" type="button" onclick="mostrarComentarios()" class="mt-4 px-4 py-2 bg-pink-600 hover:bg-pink-500 rounded-lg">Editar</button>
        </div>
    </div>
    <div id="modal-editar-inmueble" data-arr-asesor="<?= htmlspecialchars($arrendador['profile']['asesor_pk'] ?? '') ?>" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 px-3">
        <div class="relative w-full max-w-3xl bg-[#1f1f2e] border border-indigo-900/40 rounded-2xl shadow-2xl overflow-hidden">
            <button type="button" class="absolute top-3 right-3 text-indigo-200 hover:text-pink-400 transition" onclick="cerrarModalInmueble()">
                <span class="sr-only">Cerrar</span>
                &times;
            </button>
            <div class="px-6 pt-8 pb-4 border-b border-white/10">
                <h3 class="text-xl font-semibold text-indigo-100">Editar inmueble</h3>
                <p class="text-sm text-indigo-300 mt-1">Actualiza la información y guarda los cambios para verlos reflejados de inmediato.</p>
            </div>
            <div id="modal-editar-inmueble-loader" class="hidden px-6 py-4 text-sm text-indigo-200">Cargando información del inmueble...</div>
            <form id="form-editar-inmueble" class="px-6 py-6 space-y-5">
                <input type="hidden" name="pk" value="<?= htmlspecialchars($arrendador['profile']['pk']) ?>">
                <input type="hidden" name="sk" value="">
                <input type="hidden" name="asesor_pk" value="<?= htmlspecialchars($arrendador['profile']['asesor_pk'] ?? '') ?>">

                <div>
                    <label class="block text-sm text-indigo-200 mb-1" for="edit-direccion-inmueble">Dirección completa</label>
                    <textarea id="edit-direccion-inmueble" name="direccion_inmueble" rows="2" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-tipo-inmueble">Tipo</label>
                        <select id="edit-tipo-inmueble" name="tipo" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Selecciona una opción</option>
                            <option value="Departamento">Departamento</option>
                            <option value="Casa">Casa</option>
                            <option value="Local Comercial">Local Comercial</option>
                            <option value="Oficina">Oficina</option>
                            <option value="Terreno">Terreno</option>
                            <option value="Bodega">Bodega</option>
                            <option value="Edificio">Edificio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-renta">Renta mensual (MXN)</label>
                        <input id="edit-renta" type="number" step="0.01" min="0" name="renta" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-mantenimiento">¿Incluye mantenimiento?</label>
                        <select id="edit-mantenimiento" name="mantenimiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                            <option value="na">No Aplica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-monto-mantenimiento">Monto mantenimiento (MXN)</label>
                        <input id="edit-monto-mantenimiento" type="number" step="0.01" min="0" name="monto_mantenimiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-deposito">Depósito (MXN)</label>
                        <input id="edit-deposito" type="number" step="0.01" min="0" name="deposito" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-estacionamiento">Estacionamientos</label>
                        <input id="edit-estacionamiento" type="number" min="0" name="estacionamiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-mascotas">¿Permite mascotas?</label>
                        <select id="edit-mascotas" name="mascotas" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="NO">No</option>
                            <option value="SI">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-comentarios">Comentarios</label>
                        <textarea id="edit-comentarios" name="comentarios" rows="2" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="px-4 py-2 rounded-lg bg-gray-600 hover:bg-gray-500 text-white" onclick="cerrarModalInmueble()">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white" data-submit>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de imagen -->
    <div id="imageModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
        <div class="relative max-w-4xl w-full mx-4">
            <button onclick="cerrarModal()"
                class="absolute -top-10 right-0 text-white text-3xl hover:text-pink-400">&times;</button>
            <img id="modalImage" src="" alt="Documento" class="rounded-xl shadow-lg max-h-[80vh] mx-auto">
        </div>
    </div>

</section>
<script src="<?= $baseUrl ?>/assets/propietarioDetalles.js"></script>