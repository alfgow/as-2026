<?php
require_once __DIR__ . '/../../Helpers/TextHelper.php';

use App\Helpers\TextHelper;

$profile      = $profile ?? [];
$archivos     = $archivos ?? [];
$validaciones = $validaciones ?? [];
$polizas      = $polizas ?? [];
$selfieUrl    = $selfieUrl ?? null;

$s3BaseUrl = $profile['s3_base_url'] ?? '';

$resolverUrlArchivo = static function (array $archivo) use ($s3BaseUrl): string {
    $url = $archivo['url'] ?? '';
    if ($url !== '') {
        return (string) $url;
    }

    $key = $archivo['s3_key'] ?? '';
    if ($key === '') {
        return '';
    }

    if ($s3BaseUrl !== '') {
        return rtrim((string) $s3BaseUrl, '/ ') . '/' . ltrim((string) $key, '/');
    }

    return (string) $key;
};
?>
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl  shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] min-h-screen py-10 px-2 md:px-10 font-sans">
    <div class="max-w-5xl mx-auto space-y-10">

        <!-- ENCABEZADO -->
        <div class="relative flex flex-col md:flex-row items-center md:items-start gap-6 p-6 rounded-3xl shadow-2xl bg-white/10 backdrop-blur-lg border border-indigo-400/10">

            <?php if (! empty($selfieUrl ?? "")): ?>
                <img src="<?= ($selfieUrl) ?>" alt="Foto"
                    class="w-32 h-32 object-cover rounded-full shadow-lg ring-4 ring-indigo-400/40 border-4 border-white/10 bg-gray-800/50 backdrop-blur-sm transition-transform duration-200 hover:scale-105">
            <?php else: ?>
                <div class="w-32 h-32 flex items-center justify-center bg-indigo-600/10 text-indigo-300 text-5xl font-bold rounded-full border-4 border-white/10 ring-2 ring-indigo-500/30 shadow">
                    <?= strtoupper(mb_substr($profile['nombre'], 0, 1, 'UTF-8')) ?>
                </div>
            <?php endif; ?>

            <div class="flex-1 w-full">
                <!-- Nombre + TAG (TAG visible en desktop, oculto en móvil) -->
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-3xl font-bold text-white tracking-tight drop-shadow text-center">
                        <?= ucwords("{$profile['nombre']}") ?>
                    </h1>
                </div>

                <!-- Línea meta -->
                <div class="flex flex-wrap gap-4 mt-2 text-sm text-indigo-100/70">
                    <span>Registrado: <?= date('d/m/Y H:i', strtotime($profile['fecha'] ?? "")) ?></span>
                    <span class="hidden md:inline">|</span>
                    <span>IP: <?= $profile['ip'] ?></span>
                </div>

                <!-- === FRANJA DE ACCIONES === -->
                <div class="mt-3 w-full">
                    <div class="flex flex-col md:flex-row items-center md:items-stretch justify-center md:justify-start gap-3 w-full md:w-auto max-w-sm md:max-w-none mx-auto md:mx-0">

                        <!-- TAG sólo en < md -->
                        <span class="flex items-center justify-center w-full md:w-auto bg-gradient-to-r from-indigo-500 via-pink-400 to-fuchsia-400
        text-sm font-semibold px-5 py-2.5 rounded-full shadow uppercase tracking-wider text-center">
                            <?= TextHelper::ucfirst($profile['tipo'] ?? "") ?>
                        </span>

                        <!-- Botón renovación -->
                        <a href="<?= $baseUrl ?>/prospectos/code?email=<?= rawurlencode($profile['email'] ?? '') ?>"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full w-full md:w-auto
            text-white shadow-lg text-sm font-semibold text-center
            bg-gradient-to-r from-rose-500 to-rose-600
            hover:from-rose-600 hover:to-rose-700
            transition-transform duration-200 hover:scale-[1.02]">
                            <span>Actualizar Datos</span>
                        </a>

                        <!-- Botón validaciones -->
                        <a href="<?= $baseUrl ?>/inquilino/<?= urlencode($slug) ?>/validaciones"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full w-full md:w-auto
            text-white shadow-lg text-sm font-semibold text-center
            bg-gradient-to-r from-indigo-600 to-fuchsia-600
            hover:from-indigo-700 hover:to-fuchsia-700
            transition-transform duration-200 hover:scale-[1.02]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0
z" />
                            </svg>
                            <span>Validaciones</span>
                        </a>

                        <!-- Botón eliminar prospecto -->
                        <button
                            type="button"
                            id="btn-eliminar-prospecto"
                            data-id-inquilino="<?= (int)($profile['id'] ?? 0) ?>"
                            data-nombre="<?= htmlspecialchars($profile['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full w-full md:w-auto
            text-white shadow-lg text-sm font-semibold text-center
            bg-gradient-to-r from-red-600 to-red-700
            hover:from-red-700 hover:to-red-800
            transition-transform duration-200 hover:scale-[1.02]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Eliminar prospecto</span>
                        </button>
                    </div>
                </div>
                <!-- === /FRANJA DE ACCIONES === -->

            </div>
        </div>

        <!-- DATOS PERSONALES -->
        <section class="relative group" id="datos-personales-section">
            <!-- Etiqueta flotante -->
            <div class="absolute -top-6 left-6 bg-indigo-700/70 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-20 group-hover:scale-105 transition-transform flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5.121 17.804A1.5 1.5 0 016 17h12a1.5 1.5 0 01.879.296l2 1.5A1.5 1.5 0 0120.5 21h-17a1.5 1.5 0 01-.879-2.704l2-1.5z" />
                </svg> Datos Personales
            </div>
            <!-- Botón Editar arriba a la derecha -->
            <div class="absolute top-3 right-6 z-20">
                <button id="btn-editar-datos" type="button" onclick="mostrarFormularioEdicionDatos()"
                    class="bg-indigo-700 hover:bg-indigo-600 text-white px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>
                </button>
            </div>
            <!-- Vista de datos normales -->
            <div id="datos-personales-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base z-10 relative pt-10">
                <!-- Nuevo campo: Tipo -->
                <div>
                    <span class="block text-gray-200 font-semibold">Tipo:</span>
                    <span class="block text-white/90">
                        <?= ucfirst($profile['tipo']); ?>
                    </span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Nombre completo:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['nombre'])  ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Email:</span>
                    <span class="inline-flex items-center gap-2 text-white/90">
                        <span id="email-inquilino"><?= $profile['email'] ?></span>
                        <button onclick="copiarAlPortapapeles('email-inquilino')" class="hover:bg-indigo-600/30 rounded-full p-1" title="Copiar">
                            <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16h8m-4-4h8M5 8h14M7 4v16c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V4" />
                            </svg>
                        </button>
                    </span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Teléfono:</span>
                    <span class="inline-flex items-center gap-2 text-white/90">
                        <span id="telefono-inquilino"><?= $profile['celular'] ?></span>
                        <button onclick="copiarAlPortapapeles('telefono-inquilino')" class="hover:bg-indigo-600/30 rounded-full p-1" title="Copiar">
                            <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                            </svg>
                        </button>
                    </span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">RFC:</span>
                    <span class="block text-white/90"><?= TextHelper::upper($profile['rfc']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">CURP:</span>
                    <?php
                    $curp = $profile['curp'] ?? "-";
                    ?>
                    <span class="block text-white/90"><?= TextHelper::upper($curp) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Nacionalidad:</span>
                    <span class="block text-white/90"><?= TextHelper::ucfirst($profile['nacionalidad']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Estado civil:</span>
                    <span class="block text-white/90"><?= TextHelper::ucfirst($profile['estadocivil']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Cónyuge:</span>
                    <?php
                    $conyuge = $profile['conyuge'] ?? "-";
                    ?>
                    <span class="block text-white/90"><?= TextHelper::titleCase($conyuge) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Tipo de ID:</span>
                    <span class="block text-white/90"><?= TextHelper::upper($profile['tipo_id']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número de ID:</span>
                    <span class="block text-white/90"><?= TextHelper::upper($profile['num_id']) ?></span>
                </div>
            </div>
            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-datos" class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl shadow-xl p-6 space-y-6 z-10 relative" autocomplete="off" onsubmit="guardarEdicionDatos(event)">
                <input type="hidden" name="id" value="<?= (int)($profile['id'] ?? 0); ?>">
                <input type="hidden" name="pk" value="<?= htmlspecialchars($profile['pk'] ?? ''); ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Nuevo select Tipo -->
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Tipo</label>
                        <select name="tipo"
                            class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 
                           focus:ring-2 focus:ring-indigo-400 outline-none" required>
                            <?php
                            $tipos = ['Arrendatario', 'Obligado Solidario', 'Fiador'];
                            foreach ($tipos as $tipo) {
                                $selected = strtolower($profile['tipo']) === strtolower($tipo) ? 'selected' : '';
                                echo "<option value=\"$tipo\" $selected>$tipo</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nombre(s)</label>
                        <input type="text" name="nombre_inquilino" value="<?= htmlspecialchars($profile['nombre_inquilino'] ?? $profile['nombre'] ?? '') ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Apellido Paterno</label>
                        <input type="text" name="apellidop_inquilino" value="<?= htmlspecialchars($profile['apellidop_inquilino'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Apellido Materno</label>
                        <input type="text" name="apellidom_inquilino" value="<?= htmlspecialchars($profile['apellidom_inquilino'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Teléfono</label>
                        <input type="text" name="celular" value="<?= htmlspecialchars($profile['celular'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required maxlength="15" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">RFC</label>
                        <input type="text" name="rfc" value="<?= htmlspecialchars($profile['rfc'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">CURP</label>
                        <input type="text" name="curp" value="<?= htmlspecialchars($profile['curp'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nacionalidad</label>
                        <input type="text" name="nacionalidad" value="<?= htmlspecialchars($profile['nacionalidad'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Estado civil</label>
                        <select name="estadocivil" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required>
                            <?php
                            $estados = ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unión libre', 'Separado'];
                            foreach ($estados as $estado) {
                                $selected = strtolower($profile['estadocivil'] ?? "") == strtolower($estado) ? 'selected' : '';
                                echo "<option value=\"$estado\" $selected>$estado</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Cónyuge</label>
                        <input type="text" name="conyuge" value="<?= htmlspecialchars($profile['conyuge'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Tipo de ID</label>
                        <select name="tipo_id" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required>
                            <?php
                            $tipos_id = ['INE', 'Pasaporte', 'Forma Migratoria', 'Cédula Profesional'];
                            foreach ($tipos_id as $tipo) {
                                $selected = strtolower($profile['tipo_id'] ?? "") == strtolower($tipo) ? 'selected' : '';
                                echo "<option value=\"$tipo\" $selected>$tipo</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número de ID</label>
                        <input type="text" name="num_id" value="<?= htmlspecialchars($profile['num_id'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                    <button type="button" onclick="cancelarEdicionDatos()" class="w-full md:w-auto bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2 rounded-lg font-semibold shadow transition">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition">
                        Guardar cambios
                    </button>
                </div>
                <div id="mensaje-edicion" class="text-sm text-center pt-2"></div>
            </form>
        </section>

        <!-- DOMICILIO ACTUAL -->
        <section class="relative group">
            <!-- Título y botón editar -->
            <div class="absolute -top-6 left-6 bg-green-700/80 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
                <svg class="w-5 h-5 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg> Domicilio
            </div>
            <div class="absolute top-3 right-6 z-20">
                <button id="btn-editar-domicilio" onclick="mostrarFormularioEdicionDomicilio()"
                    class="bg-green-700 hover:bg-green-600 text-white px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>

                </button>
            </div>

            <!-- Vista datos domicilio -->
            <div id="domicilio-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-green-300/30 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base pt-10">
                <div>
                    <span class="block text-gray-200 font-semibold">Calle:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['calle']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número exterior:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['num_exterior']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número interior:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['num_interior']) ?: 'N/A' ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Colonia:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['colonia']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Alcaldía:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['alcaldia']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Ciudad:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['direccion']['ciudad']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Código Postal:</span>
                    <span class="block text-white/90"><?= $profile['direccion']['codigo_postal'] ?></span>
                </div>
            </div>

            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-domicilio"
                class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-green-300/30 rounded-2xl shadow-xl p-6 space-y-6"
                autocomplete="off"
                onsubmit="guardarEdicionDomicilio(event)">
                <input type="hidden" name="id_inquilino" value="<?= $profile['id']; ?>">
                <input type="hidden" name="pk" value="<?= htmlspecialchars($profile['pk'] ?? ''); ?>">
                <input type="hidden" name="id" value="<?= (int)($profile['id'] ?? 0); ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- ...campos igual que antes... -->
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Calle</label>
                        <input type="text" name="calle" value="<?= htmlspecialchars($profile['direccion']['calle'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número exterior</label>
                        <input type="text" name="num_exterior" value="<?= htmlspecialchars($profile['direccion']['num_exterior'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número interior</label>
                        <input type="text" name="num_interior" value="<?= htmlspecialchars($profile['direccion']['num_interior'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Colonia</label>
                        <input type="text" name="colonia" value="<?= htmlspecialchars($profile['direccion']['colonia'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Alcaldía</label>
                        <input type="text" name="alcaldia" value="<?= htmlspecialchars($profile['direccion']['alcaldia'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Ciudad</label>
                        <input type="text" name="ciudad" value="<?= htmlspecialchars($profile['direccion']['ciudad'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Código Postal</label>
                        <input type="text" name="codigo_postal" value="<?= htmlspecialchars($profile['direccion']['codigo_postal'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                    <button type="button" onclick="cancelarEdicionDomicilio()" class="w-full md:w-auto bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Cancelar</button>
                    <button type="submit" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Guardar cambios</button>
                </div>
                <div id="mensaje-edicion-domicilio" class="text-sm text-center pt-2"></div>
            </form>
        </section>

        <!-- TRABAJO -->
        <section class="relative group">
            <!-- Título y botón editar -->
            <div class="absolute -top-6 left-6 bg-yellow-500/80 text-gray-900 px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2a4 4 0 018 0v2m-4-6a4 4 0 100-8 4 4 0 000 8zM3 21v-2a4 4 0 014-4h4" />
                </svg> Trabajo
            </div>
            <div class="absolute top-3 right-6 z-20">
                <button id="btn-editar-trabajo" onclick="mostrarFormularioEdicionTrabajo()"
                    class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>
                </button>
            </div>

            <!-- Vista datos trabajo -->
            <div id="trabajo-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-yellow-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base pt-10">
                <div>
                    <span class="block text-gray-200 font-semibold">Empresa:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['trabajo']['empresa']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Puesto:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['trabajo']['puesto']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Dirección de empresa:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['trabajo']['direccion_empresa']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Antigüedad:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['trabajo']['antiguedad']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Sueldo mensual:</span>
                    <span class="block text-white/90"><?= TextHelper::formatCurrency($profile['trabajo']['sueldo']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Otros ingresos:</span>
                    <?php
                    $otrosIngresos = $profile['trabajo']['otrosingresos'] ?? 0;
                    ?>
                    <span class="block text-white/90"><?= TextHelper::formatCurrency($otrosIngresos) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Nombre del jefe:</span>
                    <span class="block text-white/90"><?= TextHelper::titleCase($profile['trabajo']['nombre_jefe']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Teléfono de la empresa:</span>
                    <span class="block text-white/90"><?= $profile['trabajo']['telefono_empresa'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Teléfono del jefe:</span>
                    <span class="block text-white/90"><?= $profile['trabajo']['tel_jefe'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Sitio web de la empresa:</span>
                    <a href="<?= $profile['trabajo']['web_empresa'] ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center px-4 py-2 mt-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 via-pink-500 to-fuchsia-500 rounded-lg shadow w-full sm:w-auto text-center break-all max-w-full transition-transform duration-200 hover:scale-[1.02] hover:from-indigo-600 hover:via-pink-600 hover:to-fuchsia-600">
                        <?= $profile['trabajo']['web_empresa'] ?>
                    </a>
                </div>

            </div>

            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-trabajo"
                class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-yellow-300/40 rounded-2xl shadow-xl p-6 space-y-6"
                autocomplete="off"
                onsubmit="guardarEdicionTrabajo(event)">
                <input type="hidden" name="id_inquilino" value="<?= $profile['id']; ?>">
                <input type="hidden" name="pk" value="<?= htmlspecialchars($profile['pk'] ?? ''); ?>">
                <input type="hidden" name="id" value="<?= (int)($profile['id'] ?? 0); ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Empresa</label>
                        <input type="text" name="empresa" value="<?= htmlspecialchars($profile['trabajo']['empresa'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Puesto</label>
                        <input type="text" name="puesto" value="<?= htmlspecialchars($profile['trabajo']['puesto'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Dirección de empresa</label>
                        <input type="text" name="direccion_empresa" value="<?= htmlspecialchars($profile['trabajo']['direccion_empresa'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <!-- Teléfono de la Empresa -->
                    <div>
                        <label class="block text-sm font-medium text-indigo-200 mb-1">Teléfono de la Empresa</label>
                        <input type="text"
                            name="telefono_empresa"
                            value="<?= htmlspecialchars($profile['trabajo']['telefono_empresa'] ?? '') ?>"
                            class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Antigüedad</label>
                        <input type="text" name="antiguedad" value="<?= htmlspecialchars($profile['trabajo']['antiguedad'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Sueldo mensual</label>
                        <input type="number" step="0.01" name="sueldo" value="<?= htmlspecialchars($profile['trabajo']['sueldo'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Otros ingresos</label>
                        <input type="number" step="0.01" name="otrosingresos" value="<?= htmlspecialchars($profile['trabajo']['otrosingresos'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nombre del jefe</label>
                        <input type="text" name="nombre_jefe" value="<?= htmlspecialchars($profile['trabajo']['nombre_jefe'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Teléfono del jefe</label>
                        <input type="text" name="tel_jefe" value="<?= htmlspecialchars($profile['trabajo']['tel_jefe'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-200 font-semibold mb-1">Sitio web de la empresa</label>
                        <input type="text" name="web_empresa" value="<?= htmlspecialchars($profile['trabajo']['web_empresa'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                    <button type="button" onclick="cancelarEdicionTrabajo()" class="w-full md:w-auto bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Cancelar</button>
                    <button type="submit" class="w-full md:w-auto bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-5 py-2 rounded-lg font-semibold shadow transition">Guardar cambios</button>
                </div>
                <div id="mensaje-edicion-trabajo" class="text-sm text-center pt-2"></div>
            </form>
        </section>

        <!-- FIADOR -->
        <section class="relative group mt-14">
            <!-- Título y botón editar -->
            <div class="absolute -top-6 left-6 bg-purple-600/80 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V9a1 1 0 00-1-1h-6V4a1 1 0 00-1-1H6a1 1 0 00-1 1v15a1 1 0 001 1h6a1 1 0 001-1v-4h6a1 1 0 001-1z" />
                </svg> Fiador
            </div>
            <div class="absolute top-3 right-6 z-20">
                <button id="btn-editar-fiador" onclick="mostrarFormularioEdicionFiador()"
                    class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>

                </button>
            </div>

            <?php if (isset($profile['tipo']) && $profile['tipo'] === 'Fiador'): ?>
                <!-- Vista datos fiador -->
                <div id="fiador-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-purple-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base pt-10">
                    <div>
                        <span class="block text-gray-200 font-semibold">Calle del inmueble:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['calle_inmueble'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Número exterior:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['num_ext_inmueble'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Número interior:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['num_int_inmueble'] ?: 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Colonia:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['colonia_inmueble'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Alcaldía:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['alcaldia_inmueble'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Estado:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['estado_inmueble'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Código postal:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['cp_inmueble'] ?: 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Número de escritura:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['numero_escritura'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Fecha de escritura:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['fecha_escritura'] ?: 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Número de notario:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['numero_notario'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Nombre del notario:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['nombre_notario'] ?: 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Estado del notario:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['estado_notario'] ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-200 font-semibold">Folio real:</span>
                        <span class="block text-white/90"><?= $profile['fiador']['folio_real'] ?></span>
                    </div>
                    <div class="md:col-span-2 mt-3">
                        <span class="block text-gray-200 font-semibold mb-2">Documento cargado:</span>
                        <?php
                        $fiador_pdf = null;
                        if (! empty($profile['archivos'] ?? "")) {
                            foreach ($profile['archivos'] as $archivo) {
                                $ext = strtolower(pathinfo($archivo['s3_key'], PATHINFO_EXTENSION));
                                if ($archivo['tipo'] === 'pdf' && $ext === 'pdf') {
                                    $fiador_pdf = $archivo;
                                    break;
                                }
                            }
                        }
                        ?>
                        <?php if ($fiador_pdf): ?>
                            <div class="flex items-center gap-5">
                                <div class="relative flex items-center justify-center mb-3">
                                    <span class="absolute inset-0 flex items-center justify-center">
                                        <span class="block w-14 h-14 bg-gradient-to-tr from-pink-400/30 via-indigo-300/20 to-pink-600/40 rounded-full blur-[2px] opacity-80"></span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="url(#fiador-doc-grad-<?= $fiador_pdf['id'] ?? rand(1, 9999); ?>)"
                                        class="relative w-11 h-11 text-pink-400 drop-shadow-lg">
                                        <defs>
                                            <linearGradient id="fiador-doc-grad-<?= $fiador_pdf['id'] ?? rand(1, 9999); ?>" x1="0" x2="1" y1="0" y2="1">
                                                <stop offset="0%" stop-color="#ec4899" />
                                                <stop offset="80%" stop-color="#6366f1" />
                                            </linearGradient>
                                        </defs>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                                    </svg>
                                </div>
                                <?php $fiadorPdfUrl = $resolverUrlArchivo($fiador_pdf); ?>
                                <button type="button"
                                    onclick="abrirModalPdf('<?= htmlspecialchars($fiadorPdfUrl) ?>', 'Documento del inmueble en garantía')"
                                    class="inline-block px-5 py-2 bg-gradient-to-r from-fuchsia-500 via-pink-500 to-rose-500 text-white font-bold rounded-lg shadow hover:scale-105 transition-transform">
                                    Ver documento (PDF)
                                </button>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400">No disponible</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario de edición -->
                <?php
                $fechaEscrituraCruda = $profile['fiador']['fecha_escritura'] ?? '';
                $fechaEscrituraInput = '';
                if ($fechaEscrituraCruda !== '') {
                    $formatosFecha = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y'];
                    foreach ($formatosFecha as $formato) {
                        $dt = \DateTime::createFromFormat($formato, (string) $fechaEscrituraCruda);
                        if ($dt instanceof \DateTime) {
                            $fechaEscrituraInput = $dt->format('Y-m-d');
                            break;
                        }
                    }
                    if ($fechaEscrituraInput === '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $fechaEscrituraCruda, $m)) {
                        $fechaEscrituraInput = sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
                    }
                }
                ?>
                <form id="form-editar-fiador"
                    class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-purple-300/40 rounded-2xl shadow-xl p-6 space-y-6"
                    autocomplete="off"
                    onsubmit="guardarEdicionFiador(event)">
                    <input type="hidden" name="id_inquilino" value="<?= $profile['id']; ?>">
                    <input type="hidden" name="pk" value="<?= htmlspecialchars($profile['pk'] ?? ''); ?>">
                    <input type="hidden" name="id" value="<?= (int)($profile['id'] ?? 0); ?>">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- [ ... campos como los tienes ... ] -->
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Calle del inmueble</label>
                            <input type="text" name="calle_inmueble" value="<?= htmlspecialchars($profile['fiador']['calle_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Número exterior</label>
                            <input type="text" name="num_ext_inmueble" value="<?= htmlspecialchars($profile['fiador']['num_ext_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Número interior</label>
                            <input type="text" name="num_int_inmueble" value="<?= htmlspecialchars($profile['fiador']['num_int_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Colonia</label>
                            <input type="text" name="colonia_inmueble" value="<?= htmlspecialchars($profile['fiador']['colonia_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Alcaldía</label>
                            <input type="text" name="alcaldia_inmueble" value="<?= htmlspecialchars($profile['fiador']['alcaldia_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Estado</label>
                            <input type="text" name="estado_inmueble" value="<?= htmlspecialchars($profile['fiador']['estado_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Código postal</label>
                            <input type="text" name="cp_inmueble" value="<?= htmlspecialchars($profile['fiador']['cp_inmueble'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Número de escritura</label>
                            <input type="text" name="numero_escritura" value="<?= htmlspecialchars($profile['fiador']['numero_escritura'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Fecha de escritura</label>
                            <input type="date" name="fecha_escritura" value="<?= htmlspecialchars($fechaEscrituraInput); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Número de notario</label>
                            <input type="text" name="numero_notario" value="<?= htmlspecialchars($profile['fiador']['numero_notario'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Nombre del notario</label>
                            <input type="text" name="nombre_notario" value="<?= htmlspecialchars($profile['fiador']['nombre_notario'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Estado del notario</label>
                            <input type="text" name="estado_notario" value="<?= htmlspecialchars($profile['fiador']['estado_notario'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-200 font-semibold mb-1">Folio real</label>
                            <input type="text" name="folio_real" value="<?= htmlspecialchars($profile['fiador']['folio_real'] ?? ""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                        <button type="button" onclick="cancelarEdicionFiador()" class="w-full md:w-auto bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Cancelar</button>
                        <button type="submit" class="w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Guardar cambios</button>
                    </div>
                    <div id="mensaje-edicion-fiador" class="text-sm text-center pt-2"></div>
                </form>
        </section>

    <?php endif; ?>
    <!-- HISTORIAL DE VIVIENDA -->
    <section class="relative group">
        <div class="absolute -top-6 left-6 bg-yellow-400/80 text-gray-800 px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg> Historial de Vivienda
        </div>
        <div class="absolute top-3 right-6 z-20">
            <?php if (! empty($profile['historial_vivienda'])): ?>
                <button id="btn-editar-historial" onclick="mostrarFormularioEdicionHistorial()"
                    class="bg-yellow-400 hover:bg-yellow-300 text-yellow-900 px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>

                </button>
            <?php endif; ?>
        </div>
        <div class="mt-8 bg-white/15 backdrop-blur-lg border border-yellow-200/40 rounded-2xl shadow-xl p-6">
            <?php if (! empty($profile['historial_vivienda'])):
                $vivienda = $profile['historial_vivienda'];
            ?>
                <div id="historial-vivienda-vista">
                    <div class="  p-4 ">
                        <div class="grid md:grid-cols-2 gap-6 text-base ">
                            <div><span class="font-semibold text-yellow-100">¿Dónde vive actualmente en este domicilio?</span> <span class="block text-white/90"><?= TextHelper::ucfirst($vivienda['vive_actualmente']) ?: 'N/A' ?></span></div>
                            <div><span class="font-semibold text-yellow-100">¿Renta actualmente?</span> <span class="block text-white/90"><?= TextHelper::ucfirst($vivienda['renta_actualmente']) ?: 'N/A' ?></span></div>
                            <div><span class="font-semibold text-yellow-100">Arrendador actual:</span> <span class="block text-white/90"><?= TextHelper::titleCase($vivienda['arrendador_actual']) ?: 'N/A' ?></span></div>
                            <div><span class="font-semibold text-yellow-100">Celular del arrendador:</span> <span class="block text-white/90"><?= $vivienda['cel_arrendador_actual'] ?: 'N/A' ?></span></div>
                            <div><span class="font-semibold text-yellow-100">Monto de renta:</span> <span class="block text-white/90"><?= TextHelper::formatCurrency($vivienda['monto_renta_actual']) ?></span></div>
                            <div><span class="font-semibold text-yellow-100">Tiempo habitando:</span> <span class="block text-white/90"><?= TextHelper::titleCase($vivienda['tiempo_habitacion_actual']) ?: 'N/A' ?></span></div>
                            <div class="md:col-span-2"><span class="font-semibold text-yellow-100">Motivo del arrendamiento:</span> <span class="block text-white/90"><?= TextHelper::ucfirst($vivienda['motivo_arrendamiento']) ?: 'N/A' ?></span></div>
                        </div>
                    </div>
                </div>
                <!-- Formulario de edición (oculto al inicio) -->
                <form id="form-editar-historial" class="hidden mt-8 space-y-6" autocomplete="off" onsubmit="guardarEdicionHistorial(event)">
                    <input type="hidden" name="id_inquilino" value="<?= (int)($profile['id'] ?? 0); ?>">
                    <input type="hidden" name="pk" value="<?= htmlspecialchars($profile['pk'] ?? ''); ?>">
                    <input type="hidden" name="id" value="<?= (int)($profile['id'] ?? 0); ?>">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">¿Vive actualmente en este domicilio?</label>
                            <input type="text" name="vive_actualmente" value="<?= htmlspecialchars($vivienda['vive_actualmente'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">¿Renta actualmente?</label>
                            <input type="text" name="renta_actualmente" value="<?= htmlspecialchars($vivienda['renta_actualmente'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">Arrendador actual</label>
                            <input type="text" name="arrendador_actual" value="<?= htmlspecialchars($vivienda['arrendador_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">Celular del arrendador</label>
                            <input type="text" name="cel_arrendador_actual" value="<?= htmlspecialchars($vivienda['cel_arrendador_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">Monto de renta</label>
                            <input type="text" name="monto_renta_actual" value="<?= htmlspecialchars($vivienda['monto_renta_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div>
                            <label class="block text-yellow-200 font-semibold mb-1">Tiempo habitando</label>
                            <input type="text" name="tiempo_habitacion_actual" value="<?= htmlspecialchars($vivienda['tiempo_habitacion_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-yellow-200 font-semibold mb-1">Motivo del arrendamiento</label>
                            <input type="text" name="motivo_arrendamiento" value="<?= htmlspecialchars($vivienda['motivo_arrendamiento'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                        <button type="button" onclick="cancelarEdicionHistorial()" class="w-full md:w-auto bg-yellow-800 hover:bg-yellow-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Cancelar</button>
                        <button type="submit" class="w-full md:w-auto bg-yellow-500 hover:bg-yellow-400 text-yellow-900 px-5 py-2 rounded-lg font-semibold shadow transition">Guardar cambios</button>
                    </div>
                    <div id="mensaje-edicion-historial" class="text-sm text-center pt-2"></div>
                </form>
            <?php else: ?>
                <p class="text-gray-300">No hay historial de vivienda disponible.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $byType = [];
    foreach ($archivos as $a) {
        $t = $a['tipo'] ?? '';
        if ($t !== '') {
            $byType[$t][] = $a;
        }
    }
    ?>
    <!-- ARCHIVOS SUBIDOS -->
    <section class="relative">
        <!-- Tag título -->
        <div class="absolute -top-6 left-6 bg-pink-600/90 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur">
            Archivos
        </div>

        <!-- Contenedor -->
        <div class="mt-8 bg-white/15 backdrop-blur-lg border border-pink-200/40 rounded-2xl shadow-xl p-6">
            <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3">

                <!-- Selfie -->
                <div id="card-selfie"
                    class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                    rounded-xl p-4 bg-white/5 min-h-[220px] 
                    transition-transform duration-200 hover:scale-105 hover:animate-shake">

                    <h3 class="font-semibold text-sm text-pink-900 mb-2">Selfie *</h3>

                    <!-- Contenedor preview -->
                    <div id="selfie-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                        <?php if (!empty($byType['selfie'])):
                            $selfie = $byType['selfie'][0];
                        ?>
                            <img src="<?= htmlspecialchars($selfie['url']) ?>" alt="Selfie"
                                class="max-h-28 rounded shadow cursor-zoom-in"
                                onclick="abrirModalImg('<?= htmlspecialchars($selfie['url']) ?>','Selfie')" />
                        <?php else: ?>
                            <span class="text-xs text-gray-400">Toma una foto o sube archivo</span>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div id="selfie-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                        <?php if (!empty($byType['selfie'])): ?>
                            <button type="button"
                                class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                onclick="document.getElementById('dz-selfie').click()">
                                Reemplazar
                            </button>
                            <input type="file" id="dz-selfie" class="hidden"
                                accept="image/*"
                                data-id="<?= htmlspecialchars($selfie['id'] ?? $selfie['sk'] ?? '') ?>"
                                data-inquilino="<?= (int)$profile['id'] ?>"
                                data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                onchange="handleSelfieSelect(this)">

                        <?php else: ?>
                            <button type="button"
                                class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                onclick="document.getElementById('dz-selfie').click()">
                                Subir Selfie
                            </button>
                            <input type="file" id="dz-selfie" class="hidden"
                                accept="image/*"
                                data-inquilino="<?= (int)$profile['id'] ?>"
                                data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                onchange="handleSelfieSelect(this)">
                        <?php endif; ?>
                    </div>
                </div>



                <?php
                $tipoId = strtolower(trim($profile['tipo_id'] ?? ''));

                if (strpos($tipoId, 'ine') !== false) {
                    // Mostrar INE Frente + Reverso
                ?>

                    <!-- INE Frente -->
                    <div id="card-ine-frente"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                    rounded-xl p-4 bg-white/5 min-h-[220px] 
                    transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">INE Frente *</h3>

                        <!-- Contenedor preview -->
                        <div id="ine-frente-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($byType['ine_frontal'])):
                                $ineFrente = $byType['ine_frontal'][0];
                            ?>
                                <img src="<?= htmlspecialchars($ineFrente['url']) ?>" alt="INE Frente"
                                    class="max-h-28 rounded shadow cursor-zoom-in"
                                    onclick="abrirModalImg('<?= htmlspecialchars($ineFrente['url']) ?>','INE Frente')" />
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Toma una foto o sube archivo</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="ine-frente-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($byType['ine_frontal'])): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-ine-frente').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-ine-frente" class="hidden"
                                    accept="image/*"
                                    data-id="<?= htmlspecialchars($ineFrente['id'] ?? $ineFrente['sk'] ?? '') ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleIneFrontalSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-ine-frente').click()">
                                    Subir INE Frente
                                </button>
                                <input type="file" id="dz-ine-frente" class="hidden"
                                    accept="image/*"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleIneFrontalSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- INE Reverso -->
                    <div id="card-ine-reverso"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                            rounded-xl p-4 bg-white/5 min-h-[220px] 
                            transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">INE Reverso *</h3>

                        <!-- Contenedor preview -->
                        <div id="ine-reverso-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($byType['ine_reverso'])):
                                $ineReverso = $byType['ine_reverso'][0];
                            ?>
                                <img src="<?= htmlspecialchars($ineReverso['url']) ?>" alt="INE Reverso"
                                    class="max-h-28 rounded shadow cursor-zoom-in"
                                    onclick="abrirModalImg('<?= htmlspecialchars($ineReverso['url']) ?>','INE Reverso')" />
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Toma una foto o sube archivo</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="ine-reverso-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($byType['ine_reverso'])): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-ine-reverso').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-ine-reverso" class="hidden"
                                    accept="image/*"
                                    data-id="<?= htmlspecialchars($ineReverso['id'] ?? $ineReverso['sk'] ?? '') ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleIneReversoSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-ine-reverso').click()">
                                    Subir INE Reverso
                                </button>
                                <input type="file" id="dz-ine-reverso" class="hidden"
                                    accept="image/*"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleIneReversoSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>

                <?php

                } elseif (strpos($tipoId, 'pasaporte') !== false) {
                    // Mostrar Pasaporte
                ?>
                    <!-- Pasaporte -->
                    <div id="card-pasaporte"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                        rounded-xl p-4 bg-white/5 min-h-[220px] 
                        transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">Pasaporte *</h3>

                        <!-- Contenedor preview -->
                        <div id="pasaporte-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($byType['pasaporte'])):
                                $pasaporte = $byType['pasaporte'][0];
                            ?>
                                <img src="<?= htmlspecialchars($pasaporte['url']) ?>" alt="Pasaporte"
                                    class="max-h-28 rounded shadow cursor-zoom-in"
                                    onclick="abrirModalImg('<?= htmlspecialchars($pasaporte['url']) ?>','Pasaporte')" />
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sube una foto del pasaporte</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="pasaporte-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($byType['pasaporte'])): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-pasaporte').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-pasaporte" class="hidden"
                                    accept="image/*"
                                    data-id="<?= htmlspecialchars($pasaporte['id'] ?? $pasaporte['sk'] ?? '') ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handlePasaporteSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-pasaporte').click()">
                                    Subir Pasaporte
                                </button>
                                <input type="file" id="dz-pasaporte" class="hidden"
                                    accept="image/*"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handlePasaporteSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>

                <?php

                } elseif (strpos($tipoId, 'forma migratoria') !== false) {
                    // Mostrar Forma Migratoria (Frente + Reverso)
                ?>

                    <!-- Forma Migratoria Frente -->
                    <div id="card-forma-frontal"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                        rounded-xl p-4 bg-white/5 min-h-[220px] 
                        transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">Forma Migratoria - Frente *</h3>

                        <!-- Contenedor preview -->
                        <div id="forma-frontal-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($byType['forma_frontal'])):
                                $formaFrente = $byType['forma_frontal'][0];
                            ?>
                                <img src="<?= htmlspecialchars($formaFrente['url']) ?>" alt="Forma Migratoria Frente"
                                    class="max-h-28 rounded shadow cursor-zoom-in"
                                    onclick="abrirModalImg('<?= htmlspecialchars($formaFrente['url']) ?>','Forma Migratoria Frente')" />
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sube una foto de la Forma Migratoria (Frente)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="forma-frontal-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($byType['forma_frontal'])): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-forma-frontal').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-forma-frontal" class="hidden"
                                    accept="image/*"
                                    data-id="<?= htmlspecialchars($formaFrente['id'] ?? $formaFrente['sk'] ?? '') ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleFormaFrontalSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-forma-frontal').click()">
                                    Subir Frente
                                </button>
                                <input type="file" id="dz-forma-frontal" class="hidden"
                                    accept="image/*"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleFormaFrontalSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Forma Migratoria Reverso -->
                    <div id="card-forma-reverso"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 
                        rounded-xl p-4 bg-white/5 min-h-[220px] 
                        transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">Forma Migratoria - Reverso *</h3>

                        <!-- Contenedor preview -->
                        <div id="forma-reverso-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($byType['forma_reverso'])):
                                $formaReverso = $byType['forma_reverso'][0];
                            ?>
                                <img src="<?= htmlspecialchars($formaReverso['url']) ?>" alt="Forma Migratoria Reverso"
                                    class="max-h-28 rounded shadow cursor-zoom-in"
                                    onclick="abrirModalImg('<?= htmlspecialchars($formaReverso['url']) ?>','Forma Migratoria Reverso')" />
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sube una foto de la Forma Migratoria (Reverso)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="forma-reverso-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($byType['forma_reverso'])): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-forma-reverso').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-forma-reverso" class="hidden"
                                    accept="image/*"
                                    data-id="<?= htmlspecialchars($formaReverso['id'] ?? $formaReverso['sk'] ?? '') ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleFormaReversoSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-forma-reverso').click()">
                                    Subir Reverso
                                </button>
                                <input type="file" id="dz-forma-reverso" class="hidden"
                                    accept="image/*"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleFormaReversoSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>

                <?php
                } else {
                    // Fallback si no se reconoce el tipo
                    echo "<p class='text-xs text-gray-400'>Tipo de identificación no reconocido.</p>";
                }
                ?>

                <?php
                $tipoInquilino = strtolower(trim($profile['tipo'] ?? ''));
                if ($tipoInquilino === 'fiador') {
                    $archivoEscritura = $byType['escritura'][0] ?? $byType['pdf'][0] ?? null;
                    $escrituraDatos = is_array($archivoEscritura) ? $archivoEscritura : [];
                    $escrituraId = $escrituraDatos['id'] ?? $escrituraDatos['sk'] ?? '';
                    $escrituraUrl = $archivoEscritura ? $resolverUrlArchivo($escrituraDatos) : '';
                    $escrituraS3Key = $escrituraDatos['s3_key'] ?? '';
                    $escrituraExtension = strtolower(pathinfo($escrituraS3Key, PATHINFO_EXTENSION));
                    $escrituraEsImagen = in_array($escrituraExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                ?>
                    <div id="card-escritura"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60 rounded-xl p-4 bg-white/5 min-h-[220px] transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">Escritura *</h3>

                        <!-- Contenedor preview -->
                        <div id="escritura-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (! empty($archivoEscritura)): ?>
                                <?php if ($escrituraEsImagen): ?>
                                    <img src="<?= htmlspecialchars($escrituraUrl) ?>" alt="Escritura"
                                        class="max-h-28 rounded shadow cursor-zoom-in"
                                        onclick="abrirModalImg('<?= htmlspecialchars($escrituraUrl) ?>','Escritura')" />
                                <?php else: ?>
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="w-10 h-10 text-pink-500 mb-1 cursor-pointer"
                                            onclick="abrirModalPdf('<?= htmlspecialchars($escrituraUrl) ?>','Escritura')">
                                            <path fill-rule="evenodd"
                                                d="M9 1.5H5.625c-1.036 0-1.875.84-1.875
                                                     1.875v17.25c0 1.035.84 1.875
                                                     1.875 1.875h12.75c1.035 0 1.875-.84
                                                     1.875-1.875V12.75A3.75 3.75 0 0
                                                     0 16.5 9h-1.875a1.875 1.875 0 0
                                                     1-1.875-1.875V5.25A3.75 3.75 0 0
                                                     0 9 1.5Zm6.61 10.936a.75.75
                                                     0 1 0-1.22-.872l-3.236 4.53L9.53
                                                     14.47a.75.75 0 0 0-1.06 1.06l2.25
                                                     2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-xs text-gray-400">Archivo PDF</span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sube la escritura del fiador</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="escritura-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (! empty($archivoEscritura)): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-escritura').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-escritura" class="hidden"
                                    accept="application/pdf"
                                    data-id="<?= htmlspecialchars($escrituraId) ?>"
                                    data-inquilino="<?= (int) $profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleEscrituraSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-escritura').click()">
                                    Subir Escritura
                                </button>
                                <input type="file" id="dz-escritura" class="hidden"
                                    accept="application/pdf"
                                    data-inquilino="<?= (int) $profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    onchange="handleEscrituraSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }
                ?>


                <?php
                $comprobantesIngreso = $byType['comprobante_ingreso'] ?? [];
                $comprobantesIngresoCount = is_array($comprobantesIngreso) ? count($comprobantesIngreso) : 0;
                $comprobantesIngresoSlots = max(1, $comprobantesIngresoCount + 1);

                for ($index = 1; $index <= $comprobantesIngresoSlots; $index++):
                    $comprobante = $comprobantesIngreso[$index - 1] ?? null;
                    $compId = $comprobante['id'] ?? $comprobante['sk'] ?? '';
                    $compUrl = $comprobante['url'] ?? '';
                ?>
                    <div id="card-comp-ingreso-<?= $index ?>"
                        class="flex flex-col items-center border-2 border-dashed border-pink-400/60
                        rounded-xl p-4 bg-white/5 min-h-[220px]
                        transition-transform duration-200 hover:scale-105 hover:animate-shake">

                        <h3 class="font-semibold text-sm text-pink-900 mb-2">Comprobante ingresos <?= $index ?> *</h3>

                        <!-- Contenedor preview -->
                        <div id="comp-ingreso-<?= $index ?>-preview" class="flex-1 flex items-center justify-center w-full mb-3">
                            <?php if (!empty($comprobante)): ?>
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        fill="currentColor" class="w-10 h-10 text-pink-500 mb-1 cursor-pointer"
                                        onclick="abrirModalPdf('<?= htmlspecialchars($compUrl) ?>','Comprobante ingresos <?= $index ?>')">
                                        <path fill-rule="evenodd"
                                            d="M9 1.5H5.625c-1.036 0-1.875.84-1.875
                             1.875v17.25c0 1.035.84 1.875
                             1.875 1.875h12.75c1.035 0 1.875-.84
                             1.875-1.875V12.75A3.75 3.75 0 0
                             0 16.5 9h-1.875a1.875 1.875 0 0
                             1-1.875-1.875V5.25A3.75 3.75 0 0
                             0 9 1.5Zm6.61 10.936a.75.75
                             0 1 0-1.22-.872l-3.236 4.53L9.53
                             14.47a.75.75 0 0 0-1.06 1.06l2.25
                             2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-gray-400">Archivo PDF</span>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sube un comprobante en PDF</span>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones -->
                        <div id="comp-ingreso-<?= $index ?>-actions" class="flex flex-wrap justify-center sm:justify-start gap-2 w-full">
                            <?php if (!empty($comprobante)): ?>
                                <button type="button"
                                    class="bg-indigo-600 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-comp-ingreso-<?= $index ?>').click()">
                                    Reemplazar
                                </button>
                                <input type="file" id="dz-comp-ingreso-<?= $index ?>" class="hidden"
                                    accept="application/pdf"
                                    data-id="<?= htmlspecialchars($compId) ?>"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    data-slot="<?= $index ?>"
                                    onchange="handleCompIngresoSelect(this)">
                            <?php else: ?>
                                <button type="button"
                                    class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow w-full sm:w-auto text-center"
                                    onclick="document.getElementById('dz-comp-ingreso-<?= $index ?>').click()">
                                    Subir PDF
                                </button>
                                <input type="file" id="dz-comp-ingreso-<?= $index ?>" class="hidden"
                                    accept="application/pdf"
                                    data-inquilino="<?= (int)$profile['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                    data-slot="<?= $index ?>"
                                    onchange="handleCompIngresoSelect(this)">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
                <!-- Otros Archivos -->
                <div id="card-otros" class="sm:col-span-2 md:col-span-3 flex flex-col border-2 border-dashed            border-pink-400/60 
                        rounded-xl p-4 bg-white/5 
                        transition-transform duration-200 hover:scale-105 hover:animate-shake">

                    <h3 class="font-semibold text-sm text-pink-900 mb-4">Otros archivos (PDF o imagen)</h3>

                    <div id="otros-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <?php if (!empty($byType['otro'])): ?>
                            <?php foreach ($byType['otro'] as $otro):
                                $url = $resolverUrlArchivo($otro);
                                $ext = strtolower(pathinfo($otro['s3_key'], PATHINFO_EXTENSION));
                                $esImagen = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                            ?>
                                <div class="relative bg-white/10 border border-pink-400/20 p-3 rounded-lg flex flex-col items-center">
                                    <!-- Botón eliminar -->
                                    <button type="button"
                                        class="absolute top-1 right-1 bg-red-600/80 hover:bg-red-700 text-white rounded-full p-1"
                                        onclick="eliminarOtroArchivo('<?= (int)$otro['id'] ?>')"
                                        title="Eliminar archivo">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <!-- Preview -->
                                    <?php if ($esImagen): ?>
                                        <img src="<?= htmlspecialchars($url) ?>" alt="Otro archivo"
                                            class="max-h-24 rounded shadow cursor-zoom-in mb-2"
                                            onclick="abrirModalImg('<?= htmlspecialchars($url) ?>','Otro archivo')" />
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="w-10 h-10 text-pink-500 mb-2 cursor-pointer"
                                            onclick="abrirModalPdf('<?= htmlspecialchars($url) ?>','Otro archivo')">
                                            <path fill-rule="evenodd"
                                                d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 
                                     1.875v17.25c0 1.035.84 1.875 
                                     1.875 1.875h12.75c1.035 0 1.875-.84 
                                     1.875-1.875V12.75A3.75 3.75 0 0 
                                     0 16.5 9h-1.875a1.875 1.875 0 0 
                                     1-1.875-1.875V5.25A3.75 3.75 0 0 
                                     0 9 1.5Zm6.61 10.936a.75.75 
                                     0 1 0-1.22-.872l-3.236 4.53L9.53 
                                     14.47a.75.75 0 0 0-1.06 1.06l2.25 
                                     2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>

                                    <span class="text-xs text-gray-400 truncate w-full"><?= htmlspecialchars($otro['tipo']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Card para subir nuevo -->
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-pink-400/40 rounded-lg p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-pink-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-xs text-gray-400 mb-2">Agregar otro archivo</span>
                            <button type="button"
                                class="bg-gradient-to-r from-pink-500 to-indigo-600 text-white text-xs px-3 py-2 rounded-lg shadow"
                                onclick="document.getElementById('dz-otro').click()">
                                Subir
                            </button>
                            <input type="file" id="dz-otro" class="hidden"
                                accept="application/pdf,image/*"
                                data-inquilino="<?= (int)$profile['id'] ?>"
                                data-nombre="<?= htmlspecialchars($nombreInquilino) ?>"
                                onchange="handleOtroSelect(this)">
                        </div>
                    </div>
                </div>




            </div>

        </div>
    </section>

    <!-- ASESOR -->
    <section class="relative group">
        <div class="absolute -top-6 left-6 bg-fuchsia-700/80 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
            <svg class="w-5 h-5 text-fuchsia-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5.121 17.804A1.5 1.5 0 016 17h12a1.5 1.5 0 01.879.296l2 1.5A1.5 1.5 0 0120.5 21h-17a1.5 1.5 0 01-.879-2.704l2-1.5zM12 12a5 5 0 100-10 5 5 0 000 10z" />
            </svg> Asesor
        </div>
        <?php $a = $profile['asesor'] ?? []; ?>
        <div class="absolute top-3 right-6 z-20">
            <button id="btn-editar-asesor" onclick="mostrarFormularioEdicionAsesor()"
                class="bg-fuchsia-700 hover:bg-fuchsia-800 text-white px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                </svg>

            </button>
        </div>

        <!-- Vista (datos actuales) -->
        <div id="asesor-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-fuchsia-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base">
            <div>
                <span class="font-semibold text-fuchsia-100">Nombre del asesor:</span>
                <span class="block text-white/90"><?= $a['nombre_asesor'] ?? 'No asignado' ?></span>
            </div>
            <div>
                <span class="font-semibold text-fuchsia-100">Email:</span>
                <span class="inline-flex items-center gap-2 text-white/90">
                    <span id="email-asesor"><?= $a['email'] ?? 'N/A' ?></span>
                    <?php if (! empty($a['email'] ?? "")): ?>
                        <button type="button" class="ml-1 p-1 rounded-full hover:bg-fuchsia-700/30 transition" onclick="copiarAlPortapapeles('email-asesor')" title="Copiar Email">
                            <svg class="w-4 h-4 text-fuchsia-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16h8m-4-4h8M5 8h14M7 4v16c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V4" />
                            </svg>
                        </button>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <span class="font-semibold text-fuchsia-100">Celular:</span>
                <span class="inline-flex items-center gap-2 text-white/90">
                    <span id="celular-asesor"><?= $a['celular'] ?? 'N/A' ?></span>
                    <?php if (! empty($a['celular'] ?? "")): ?>
                        <button type="button" class="ml-1 p-1 rounded-full hover:bg-fuchsia-700/30 transition" onclick="copiarAlPortapapeles('celular-asesor')" title="Copiar Celular">
                            <svg class="w-4 h-4 text-fuchsia-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                            </svg>
                        </button>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Formulario edición (oculto) -->
        <form id="form-editar-asesor" class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-fuchsia-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base"
            autocomplete="off" onsubmit="guardarEdicionAsesor(event)">
            <input type="hidden" name="id_inquilino" value="<?= $profile['id']; ?>">
            <div class="md:col-span-2">
                <label class="block font-semibold text-fuchsia-100 mb-2">Selecciona el asesor asignado</label>
                <select name="id_asesor" required class="w-full bg-white/70 text-black border border-fuchsia-300/40 rounded-lg px-3 py-2 focus:ring-2 focus:ring-fuchsia-400 outline-none">
                    <option value="">Selecciona...</option>
                    <?php foreach ($asesores as $asesor): ?>
                        <option value="<?= $asesor['id']; ?>" <?php if (! empty($a['id'] ?? "") && $a['id'] == $asesor['id'] ?? "") {
                                                                    echo 'selected';
                                                                }
                                                                ?>>
                            <?= htmlspecialchars($asesor['nombre_asesor'] ?? "") . ' (' . htmlspecialchars($asesor['email'] ?? "") . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2 flex flex-col md:flex-row gap-4 pt-4 justify-center items-center">
                <button type="button" onclick="cancelarEdicionAsesor()" class="w-full md:w-auto bg-fuchsia-700 hover:bg-fuchsia-800 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Cancelar</button>
                <button type="submit" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition">Guardar cambios</button>
            </div>
            <div id="mensaje-edicion-asesor" class="md:col-span-2 text-sm text-center pt-2"></div>
        </form>
    </section>


    <!-- ACCIÓN -->
    <div class="pt-4 text-center">
        <a href="/admin" class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-700 via-pink-700 to-fuchsia-600 hover:scale-105 transition-transform font-bold rounded-2xl text-white text-lg shadow-xl border-none ring-2 ring-white/5 focus:ring-4 focus:ring-pink-400">
            ← Volver al panel
        </a>
    </div>
    </div>
</div>

<?php
$dbDumpPayload = [
    'profile'      => $profile,
    'archivos'     => $archivos,
    'validaciones' => $validaciones,
    'polizas'      => $polizas,
    'selfie_url'   => $selfieUrl,
    'slug'         => $slug,
];

$dbDumpJs = json_encode($dbDumpPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- MODAL VER DB -->
<div id="modal-db" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md hidden">
    <div class="relative w-full max-w-4xl h-[80vh] bg-gray-900/95 rounded-2xl shadow-2xl border border-emerald-400/30 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-emerald-200">Registro completo (Dynamo)</h2>
            <div class="flex items-center gap-2">
                <button type="button" id="copy-modal-db"
                    class="px-3 py-1.5 rounded-full bg-emerald-500/20 hover:bg-emerald-500/30 text-sm text-emerald-100 font-medium transition"
                    aria-label="Copiar JSON">
                    Copiar JSON
                </button>
                <button type="button" id="close-modal-db"
                    class="p-2 rounded-full hover:bg-emerald-500/20 transition" aria-label="Cerrar">
                    <svg class="w-6 h-6 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-auto bg-black/40 border border-emerald-400/10 rounded-xl p-4 text-sm text-emerald-100">
            <pre id="db-json" class="whitespace-pre-wrap break-words font-mono"></pre>
        </div>
    </div>
</div>

<!-- MODAL PARA VER IMAGEN EN GRANDE -->
<div id="modal-img" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md hidden">
    <div class="relative max-w-3xl w-[90vw] flex flex-col items-center">
        <button onclick="cerrarModalImg()" class="absolute -top-5 -right-5 bg-gray-900/90 text-white rounded-full p-2 shadow-xl hover:bg-pink-700 transition" aria-label="Cerrar">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <img id="img-modal-grande" src="" alt="Imagen ampliada" class="max-h-[80vh] rounded-xl shadow-2xl border-8 border-white/10 object-contain" />

        <div id="modal-img-caption" class="mt-3 text-white/90 text-sm font-semibold"></div>
    </div>
</div>

<!-- MODAL PARA VER PDF EN GRANDE -->
<div id="modal-pdf" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md hidden">
    <div class="relative w-full max-w-4xl h-[90vh] flex flex-col bg-gray-900/90 rounded-2xl shadow-2xl">
        <button onclick="cerrarModalPdf()" class="absolute -top-6 -right-4 bg-gray-900/80 text-white rounded-full p-2 shadow-xl hover:bg-pink-700 transition" aria-label="Cerrar PDF">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <iframe id="iframe-pdf" src="" class="w-full h-full rounded-b-2xl border-none bg-gray-800" frameborder="0"></iframe>
        <div id="modal-pdf-caption" class="px-6 py-2 text-white/90 text-sm font-semibold bg-gray-900/80 rounded-b-2xl"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoEdicion.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoModales.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoDropzones.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoSelfie.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoIneFrente.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoIneReverso.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoFormaFrente.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoFromaReverso.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoPasaporte.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoCompIngreso.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoEscritura.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoOtros.js"></script>
<script src="<?= $baseUrl ?>/assets/inquilinoImgValidacionIngresos.js"></script>
<script>
    window.INQ_DB_DUMP = <?= $dbDumpJs ?: '{}' ?>;

    (function() {
        const btnEliminarProspecto = document.getElementById('btn-eliminar-prospecto');

        if (btnEliminarProspecto) {
            btnEliminarProspecto.addEventListener('click', async () => {
                const idProspecto = btnEliminarProspecto.dataset.idInquilino || '';
                if (!idProspecto) {
                    return;
                }

                const baseUrl = btnEliminarProspecto.dataset.baseUrl || (window.ADMIN_BASE || '');
                const nombreProspecto = btnEliminarProspecto.dataset.nombre || '';
                const mensajeConfirmacion = nombreProspecto ?
                    `¿Deseas eliminar a ${nombreProspecto}? Esta acción no se puede deshacer.` :
                    '¿Deseas eliminar este prospecto? Esta acción no se puede deshacer.';

                let confirmado = false;

                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Eliminar prospecto',
                        text: mensajeConfirmacion,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                    });
                    confirmado = !!result.isConfirmed;
                } else {
                    confirmado = window.confirm(mensajeConfirmacion);
                }

                if (!confirmado) {
                    return;
                }

                btnEliminarProspecto.disabled = true;
                btnEliminarProspecto.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    const params = new URLSearchParams();
                    params.append('id', idProspecto);

                    const resp = await fetch(`${baseUrl}/inquilino/eliminar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: params.toString(),
                    });

                    const data = await resp.json().catch(() => ({}));

                    if (!resp.ok || !data?.ok) {
                        throw new Error(data?.error || 'No se pudo eliminar el prospecto.');
                    }

                    if (window.Swal) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Prospecto eliminado',
                            text: nombreProspecto !== '' ?
                                `Se eliminaron los datos de ${nombreProspecto}.` : 'Se eliminaron los datos del prospecto.',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }

                    window.location.href = `${baseUrl}/inquilino`;
                } catch (error) {
                    const mensaje = error instanceof Error ?
                        error.message :
                        'Ocurrió un error al eliminar el prospecto.';

                    if (window.Swal) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'No se pudo eliminar',
                            text: mensaje,
                        });
                    } else {
                        alert(mensaje);
                    }
                } finally {
                    btnEliminarProspecto.disabled = false;
                    btnEliminarProspecto.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        }

        const btn = document.getElementById('btn-ver-db');
        const modal = document.getElementById('modal-db');
        const pre = document.getElementById('db-json');
        const close = document.getElementById('close-modal-db');
        const copy = document.getElementById('copy-modal-db');
        let lastJson = '{}';

        function abrirModalDb() {
            if (!modal || !pre) return;
            let contenido = '{}';
            try {
                contenido = JSON.stringify(window.INQ_DB_DUMP || {}, null, 2);
            } catch (err) {
                try {
                    contenido = JSON.stringify(JSON.parse(String(window.INQ_DB_DUMP || '{}')), null, 2);
                } catch (inner) {
                    contenido = String(window.INQ_DB_DUMP || '{}');
                }
            }
            pre.textContent = contenido;
            lastJson = contenido;
            modal.classList.remove('hidden');
        }

        function cerrarModalDb() {
            modal?.classList.add('hidden');
        }

        btn?.addEventListener('click', abrirModalDb);
        close?.addEventListener('click', cerrarModalDb);
        modal?.addEventListener('click', function(event) {
            if (event.target === modal) {
                cerrarModalDb();
            }
        });

        async function copiarJson() {
            const texto = lastJson || JSON.stringify(window.INQ_DB_DUMP || {}, null, 2);
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(texto);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = texto;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copiado',
                        text: 'El JSON se copió al portapapeles.',
                        timer: 1400,
                        showConfirmButton: false,
                        background: '#0f172a',
                        color: '#e2e8f0'
                    });
                } else if (copy) {
                    const original = copy.textContent;
                    copy.textContent = 'Copiado';
                    setTimeout(() => {
                        copy.textContent = original ?? 'Copiar JSON';
                    }, 1500);
                }
            } catch (err) {
                console.error('copy error', err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo copiar',
                        text: err?.message || 'Intenta manualmente.',
                        background: '#0f172a',
                        color: '#e2e8f0'
                    });
                }
            }
        }

        copy?.addEventListener('click', copiarJson);

        window.cerrarModalDb = cerrarModalDb;
    })();
</script>