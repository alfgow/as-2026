<?php
// Helpers mínimos de salida segura
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$idInquilino = (int)($inquilino['id'] ?? 0);
$slug        = (string)($inquilino['slug'] ?? '');
$adminBase   = isset($admin_base_url) ? (string)$admin_base_url : '';
$archivos    = $inquilino['archivos'] ?? [];

// Tipos requeridos para UI de archivos (orden recomendado)
$REQUIRED_TYPES = ['selfie','ine_frontal','ine_reverso','pasaporte','forma_migratoria','comprobante_ingreso'];
?>
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl  shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] min-h-screen py-10 px-2 md:px-10 font-sans">
    <div class="max-w-5xl mx-auto space-y-10">

        <!-- ENCABEZADO -->
       <div class="relative flex flex-col md:flex-row items-center md:items-start gap-6 p-6 rounded-3xl shadow-2xl bg-white/10 backdrop-blur-lg border border-indigo-400/10">

  <?php if (! empty($inquilino['selfie_url'] ?? "")): ?>
    <img src="<?php echo htmlspecialchars($inquilino['selfie_url']) ?>" alt="Foto"
         class="w-32 h-32 object-cover rounded-full shadow-lg ring-4 ring-indigo-400/40 border-4 border-white/10 bg-gray-800/50 backdrop-blur-sm transition-transform duration-200 hover:scale-105">
  <?php else: ?>
    <div class="w-32 h-32 flex items-center justify-center bg-indigo-600/10 text-indigo-300 text-5xl font-bold rounded-full border-4 border-white/10 ring-2 ring-indigo-500/30 shadow">
      <?php echo strtoupper(mb_substr($inquilino['nombre_inquilino'], 0, 1, 'UTF-8')) ?>
    </div>
  <?php endif; ?>

  <div class="flex-1 w-full">
    <!-- Nombre + TAG (TAG visible en desktop, oculto en móvil) -->
    <div class="flex flex-wrap items-center gap-2">
      <h1 class="text-3xl font-bold text-white tracking-tight drop-shadow">
        <?php echo ucwords("{$inquilino['nombre_inquilino']} {$inquilino['apellidop_inquilino']} {$inquilino['apellidom_inquilino']}") ?>
      </h1>

   
    </div>

    <!-- Línea meta -->
    <div class="flex flex-wrap gap-4 mt-2 text-sm text-indigo-100/70">
      <span>Registrado: <?php echo date('d/m/Y H:i', strtotime($inquilino['fecha'] ?? "")) ?></span>
      <span class="hidden md:inline">|</span>
      <span>IP: <?php echo $inquilino['ip'] ?></span>
    </div>

    <!-- === FRANJA DE ACCIONES (zona amarilla) ===
         - En móvil: TAG + Botón
         - En desktop: sólo Botón, alineado a la derecha -->
    <div class=" mt-3 flex flex-row  items-center justify-center  gap-3 md:justify-start">

      <!-- TAG sólo en < md -->
      <span class=" inline-block bg-gradient-to-r from-indigo-500 via-pink-400 to-fuchsia-400 text-xs font-bold px-3 py-1 rounded-full shadow uppercase tracking-wider">
        <?php echo strtoupper($inquilino['tipo'] ?? "") ?>
      </span>

      <!-- Botón renovación -->
      <a href="<?= $baseUrl ?>/prospectos/code?email=<?= rawurlencode($inquilino['email'] ?? '') ?>"
         class="inline-flex items-center gap-2 px-2  rounded-full
                text-white shadow-lg
                bg-gradient-to-r from-rose-500 to-rose-600
                hover:from-rose-600 hover:to-rose-700
                transition-transform duration-200 hover:scale-[1.02]">
        
        <span>Actualizar Datos</span>
      </a>
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
                <button id="btn-editar-datos" type="button" onclick="mostrarFormularioEdicion()"
                    class="bg-indigo-700 hover:bg-indigo-600 text-white px-4 py-1 rounded-full text-sm font-semibold shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5l-4 1 1-4L16.5 3.5z" />
                    </svg>
                </button>
            </div>
            <!-- Vista de datos normales -->
            <div id="datos-personales-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base z-10 relative pt-10">
                <div>
                    <span class="block text-gray-200 font-semibold">Nombre completo:</span>
                    <span class="block text-white/90"><?php echo $inquilino['nombre_inquilino'] . ' ' . $inquilino['apellidop_inquilino'] . ' ' . $inquilino['apellidom_inquilino'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Email:</span>
                    <span class="inline-flex items-center gap-2 text-white/90">
                        <span id="email-inquilino"><?php echo $inquilino['email'] ?></span>
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
                        <span id="telefono-inquilino"><?php echo $inquilino['celular'] ?></span>
                        <button onclick="copiarAlPortapapeles('telefono-inquilino')" class="hover:bg-indigo-600/30 rounded-full p-1" title="Copiar">
                            <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                            </svg>
                        </button>
                    </span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">RFC:</span>
                    <span class="block text-white/90"><?php echo $inquilino['rfc'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">CURP:</span>
                    <span class="block text-white/90"><?php echo $inquilino['curp'] ?: 'N/A' ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Nacionalidad:</span>
                    <span class="block text-white/90"><?php echo $inquilino['nacionalidad'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Estado civil:</span>
                    <span class="block text-white/90"><?php echo $inquilino['estadocivil'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Cónyuge:</span>
                    <span class="block text-white/90"><?php echo $inquilino['conyuge'] ?: 'N/A' ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Tipo de ID:</span>
                    <span class="block text-white/90"><?php echo strtoupper($inquilino['tipo_id']??"") ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número de ID:</span>
                    <span class="block text-white/90"><?php echo $inquilino['num_id'] ?></span>
                </div>
            </div>
            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-datos" class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl shadow-xl p-6 space-y-6 z-10 relative" autocomplete="off" onsubmit="guardarEdicionDatos(event)">
                <input type="hidden" name="id" value="<?php echo $inquilino['id']; ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nombre(s)</label>
                        <input type="text" name="nombre_inquilino" value="<?php echo htmlspecialchars($inquilino['nombre_inquilino']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Apellido Paterno</label>
                        <input type="text" name="apellidop_inquilino" value="<?php echo htmlspecialchars($inquilino['apellidop_inquilino']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Apellido Materno</label>
                        <input type="text" name="apellidom_inquilino" value="<?php echo htmlspecialchars($inquilino['apellidom_inquilino']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($inquilino['email']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Teléfono</label>
                        <input type="text" name="celular" value="<?php echo htmlspecialchars($inquilino['celular']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required maxlength="15" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">RFC</label>
                        <input type="text" name="rfc" value="<?php echo htmlspecialchars($inquilino['rfc']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">CURP</label>
                        <input type="text" name="curp" value="<?php echo htmlspecialchars($inquilino['curp']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nacionalidad</label>
                        <input type="text" name="nacionalidad" value="<?php echo htmlspecialchars($inquilino['nacionalidad']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Estado civil</label>
                        <select name="estadocivil" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required>
                            <?php
                                $estados = ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unión libre', 'Separado'];
                                foreach ($estados as $estado) {
                                    $selected = strtolower($inquilino['estadocivil']??"") == strtolower($estado) ? 'selected' : '';
                                    echo "<option value=\"$estado\" $selected>$estado</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Cónyuge</label>
                        <input type="text" name="conyuge" value="<?php echo htmlspecialchars($inquilino['conyuge'] ?? "") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Tipo de ID</label>
                        <select name="tipo_id" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" required>
                            <?php
                                $tipos_id = ['INE', 'Pasaporte', 'Forma Migratoria', 'Cédula Profesional'];
                                foreach ($tipos_id as $tipo) {
                                    $selected = strtolower($inquilino['tipo_id']??"") == strtolower($tipo) ? 'selected' : '';
                                    echo "<option value=\"$tipo\" $selected>$tipo</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número de ID</label>
                        <input type="text" name="num_id" value="<?php echo htmlspecialchars($inquilino['num_id']??"") ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-indigo-300/50 focus:ring-2 focus:ring-indigo-400 outline-none" />
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
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['calle'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número exterior:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['num_exterior'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Número interior:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['num_interior'] ?: 'N/A' ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Colonia:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['colonia'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Alcaldía:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['alcaldia'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Ciudad:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['ciudad'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Código Postal:</span>
                    <span class="block text-white/90"><?php echo $inquilino['direccion']['codigo_postal'] ?></span>
                </div>
            </div>

            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-domicilio"
            class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-green-300/30 rounded-2xl shadow-xl p-6 space-y-6"
            autocomplete="off"
            onsubmit="guardarEdicionDomicilio(event)">
                <input type="hidden" name="id_inquilino" value="<?php echo $inquilino['id']; ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- ...campos igual que antes... -->
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Calle</label>
                        <input type="text" name="calle" value="<?php echo htmlspecialchars($inquilino['direccion']['calle']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número exterior</label>
                        <input type="text" name="num_exterior" value="<?php echo htmlspecialchars($inquilino['direccion']['num_exterior']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Número interior</label>
                        <input type="text" name="num_interior" value="<?php echo htmlspecialchars($inquilino['direccion']['num_interior']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Colonia</label>
                        <input type="text" name="colonia" value="<?php echo htmlspecialchars($inquilino['direccion']['colonia']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Alcaldía</label>
                        <input type="text" name="alcaldia" value="<?php echo htmlspecialchars($inquilino['direccion']['alcaldia']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Ciudad</label>
                        <input type="text" name="ciudad" value="<?php echo htmlspecialchars($inquilino['direccion']['ciudad']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Código Postal</label>
                        <input type="text" name="codigo_postal" value="<?php echo htmlspecialchars($inquilino['direccion']['codigo_postal']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-green-300/50 focus:ring-2 focus:ring-green-400 outline-none" required>
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
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['empresa'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Puesto:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['puesto'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Dirección de empresa:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['direccion_empresa'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Antigüedad:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['antiguedad'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Sueldo mensual:</span>
                    <span class="block text-white/90">$<?php echo number_format($inquilino['trabajo']['sueldo'], 2) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Otros ingresos:</span>
                    <span class="block text-white/90">$<?php echo number_format($inquilino['trabajo']['otrosingresos'], 2) ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Nombre del jefe:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['nombre_jefe'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Teléfono de la empresa:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['telefono_empresa'] ?></span>
                </div>
                <div>
                    <span class="block text-gray-200 font-semibold">Teléfono del jefe:</span>
                    <span class="block text-white/90"><?php echo $inquilino['trabajo']['tel_jefe'] ?></span>
                </div>
                <div >
                    <span class="block text-gray-200 font-semibold">Sitio web de la empresa:</span>
                    <a href="<?php echo $inquilino['trabajo']['web_empresa'] ?>" target="_blank" class="text-blue-300 underline hover:text-blue-100 transition"><?php echo $inquilino['trabajo']['web_empresa'] ?></a>
                </div>

            </div>

            <!-- Formulario de edición (oculto al inicio) -->
            <form id="form-editar-trabajo"
                class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-yellow-300/40 rounded-2xl shadow-xl p-6 space-y-6"
                autocomplete="off"
                onsubmit="guardarEdicionTrabajo(event)">
                <input type="hidden" name="id_inquilino" value="<?php echo $inquilino['id']; ?>">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Empresa</label>
                        <input type="text" name="empresa" value="<?php echo htmlspecialchars($inquilino['trabajo']['empresa']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Puesto</label>
                        <input type="text" name="puesto" value="<?php echo htmlspecialchars($inquilino['trabajo']['puesto']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Dirección de empresa</label>
                        <input type="text" name="direccion_empresa" value="<?php echo htmlspecialchars($inquilino['trabajo']['direccion_empresa']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <!-- Teléfono de la Empresa -->
                    <div>
                        <label class="block text-sm font-medium text-indigo-200 mb-1">Teléfono de la Empresa</label>
                        <input type="text" 
                            name="telefono_empresa" 
                            value="<?= htmlspecialchars($inquilino['trabajo']['telefono_empresa'] ?? '') ?>" 
                            class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Antigüedad</label>
                        <input type="text" name="antiguedad" value="<?php echo htmlspecialchars($inquilino['trabajo']['antiguedad']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Sueldo mensual</label>
                        <input type="number" step="0.01" name="sueldo" value="<?php echo htmlspecialchars($inquilino['trabajo']['sueldo']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Otros ingresos</label>
                        <input type="number" step="0.01" name="otrosingresos" value="<?php echo htmlspecialchars($inquilino['trabajo']['otrosingresos']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Nombre del jefe</label>
                        <input type="text" name="nombre_jefe" value="<?php echo htmlspecialchars($inquilino['trabajo']['nombre_jefe']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-200 font-semibold mb-1">Teléfono del jefe</label>
                        <input type="text" name="tel_jefe" value="<?php echo htmlspecialchars($inquilino['trabajo']['tel_jefe']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-200 font-semibold mb-1">Sitio web de la empresa</label>
                        <input type="text" name="web_empresa" value="<?php echo htmlspecialchars($inquilino['trabajo']['web_empresa']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-yellow-300/40 focus:ring-2 focus:ring-yellow-400 outline-none">
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

    <?php if (isset($inquilino['tipo']) && $inquilino['tipo'] === 'Fiador'): ?>
    <!-- Vista datos fiador -->
    <div id="fiador-vista" class="mt-8 bg-white/15 backdrop-blur-lg border border-purple-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base pt-10">
        <div>
            <span class="block text-gray-200 font-semibold">Calle del inmueble:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['calle_inmueble'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Número exterior:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['num_ext_inmueble'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Número interior:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['num_int_inmueble'] ?: 'N/A' ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Colonia:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['colonia_inmueble'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Alcaldía:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['alcaldia_inmueble'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Estado:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['estado_inmueble'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Número de escritura:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['numero_escritura'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Número de notario:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['numero_notario'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Estado del notario:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['estado_notario'] ?></span>
        </div>
        <div>
            <span class="block text-gray-200 font-semibold">Folio real:</span>
            <span class="block text-white/90"><?php echo $inquilino['fiador']['folio_real'] ?></span>
        </div>
        <div class="md:col-span-2 mt-3">
            <span class="block text-gray-200 font-semibold mb-2">Documento cargado:</span>
            <?php
                $fiador_pdf = null;
                if (! empty($inquilino['archivos']??"")) {
                    foreach ($inquilino['archivos'] as $archivo) {
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
                            stroke="url(#fiador-doc-grad-<?php echo $fiador_pdf['id'] ?? rand(1, 9999); ?>)"
                            class="relative w-11 h-11 text-pink-400 drop-shadow-lg">
                            <defs>
                                <linearGradient id="fiador-doc-grad-<?php echo $fiador_pdf['id'] ?? rand(1, 9999); ?>" x1="0" x2="1" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#ec4899"/>
                                    <stop offset="80%" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                    </div>
                    <button type="button"
                        onclick="abrirModalPdf('<?php echo htmlspecialchars($inquilino['s3_base_url'] . $fiador_pdf['s3_key']??"") ?>', 'Documento del inmueble en garantía')"
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
    <form id="form-editar-fiador"
        class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-purple-300/40 rounded-2xl shadow-xl p-6 space-y-6"
        autocomplete="off"
        onsubmit="guardarEdicionFiador(event)">
        <input type="hidden" name="id_inquilino" value="<?php echo $inquilino['id']; ?>">
        <div class="grid md:grid-cols-2 gap-6">
            <!-- [ ... campos como los tienes ... ] -->
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Calle del inmueble</label>
                <input type="text" name="calle_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['calle_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Número exterior</label>
                <input type="text" name="num_ext_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['num_ext_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Número interior</label>
                <input type="text" name="num_int_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['num_int_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Colonia</label>
                <input type="text" name="colonia_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['colonia_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Alcaldía</label>
                <input type="text" name="alcaldia_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['alcaldia_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Estado</label>
                <input type="text" name="estado_inmueble" value="<?php echo htmlspecialchars($inquilino['fiador']['estado_inmueble']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Número de escritura</label>
                <input type="text" name="numero_escritura" value="<?php echo htmlspecialchars($inquilino['fiador']['numero_escritura']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Número de notario</label>
                <input type="text" name="numero_notario" value="<?php echo htmlspecialchars($inquilino['fiador']['numero_notario']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Estado del notario</label>
                <input type="text" name="estado_notario" value="<?php echo htmlspecialchars($inquilino['fiador']['estado_notario']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
            </div>
            <div>
                <label class="block text-gray-200 font-semibold mb-1">Folio real</label>
                <input type="text" name="folio_real" value="<?php echo htmlspecialchars($inquilino['fiador']['folio_real']??""); ?>" class="w-full bg-white/70 text-black px-3 py-2 rounded-lg border border-purple-300/40 focus:ring-2 focus:ring-purple-400 outline-none">
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
        <?php if (! empty($inquilino['historial_vivienda'][0])): ?>
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
        <?php if (! empty($inquilino['historial_vivienda'][0])):
                $vivienda = $inquilino['historial_vivienda'][0];
            ?>
																																														            <div id="historial-vivienda-vista">
																																														                <div class="  p-4 ">
																																														                    <div class="grid md:grid-cols-2 gap-6 text-base ">
																																														                        <div><span class="font-semibold text-yellow-100">¿Vive actualmente en este domicilio?</span> <span class="block text-white/90"><?php echo $vivienda['vive_actualmente'] ?: 'N/A' ?></span></div>
																																														                        <div><span class="font-semibold text-yellow-100">¿Renta actualmente?</span> <span class="block text-white/90"><?php echo $vivienda['renta_actualmente'] ?: 'N/A' ?></span></div>
																																														                        <div><span class="font-semibold text-yellow-100">Arrendador actual:</span> <span class="block text-white/90"><?php echo $vivienda['arrendador_actual'] ?: 'N/A' ?></span></div>
																																														                        <div><span class="font-semibold text-yellow-100">Celular del arrendador:</span> <span class="block text-white/90"><?php echo $vivienda['cel_arrendador_actual'] ?: 'N/A' ?></span></div>
																																														                        <div><span class="font-semibold text-yellow-100">Monto de renta:</span> <span class="block text-white/90">$<?php echo number_format((float) $vivienda['monto_renta_actual'], 2) ?></span></div>
																																														                        <div><span class="font-semibold text-yellow-100">Tiempo habitando:</span> <span class="block text-white/90"><?php echo $vivienda['tiempo_habitacion_actual'] ?: 'N/A' ?></span></div>
																																														                        <div class="md:col-span-2"><span class="font-semibold text-yellow-100">Motivo del arrendamiento:</span> <span class="block text-white/90"><?php echo $vivienda['motivo_arrendamiento'] ?: 'N/A' ?></span></div>
																																														                    </div>
																																														                </div>
																																														            </div>
																																														            <!-- Formulario de edición (oculto al inicio) -->
																																														            <form id="form-editar-historial" class="hidden mt-8 space-y-6" autocomplete="off" onsubmit="guardarEdicionHistorial(event)">
																																														                <input type="hidden" name="id" value="<?php echo $vivienda['id']; ?>">
																																														                <input type="hidden" name="id_inquilino" value="<?php echo $vivienda['id_inquilino']; ?>">
																																														                <div class="grid md:grid-cols-2 gap-6">
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">¿Vive actualmente en este domicilio?</label>
																																														                        <input type="text" name="vive_actualmente" value="<?php echo htmlspecialchars($vivienda['vive_actualmente'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">¿Renta actualmente?</label>
																																														                        <input type="text" name="renta_actualmente" value="<?php echo htmlspecialchars($vivienda['renta_actualmente'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">Arrendador actual</label>
																																														                        <input type="text" name="arrendador_actual" value="<?php echo htmlspecialchars($vivienda['arrendador_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">Celular del arrendador</label>
																																														                        <input type="text" name="cel_arrendador_actual" value="<?php echo htmlspecialchars($vivienda['cel_arrendador_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">Monto de renta</label>
																																														                        <input type="text" name="monto_renta_actual" value="<?php echo htmlspecialchars($vivienda['monto_renta_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div>
																																														                        <label class="block text-yellow-200 font-semibold mb-1">Tiempo habitando</label>
																																														                        <input type="text" name="tiempo_habitacion_actual" value="<?php echo htmlspecialchars($vivienda['tiempo_habitacion_actual'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
																																														                    </div>
																																														                    <div class="md:col-span-2">
																																														                        <label class="block text-yellow-200 font-semibold mb-1">Motivo del arrendamiento</label>
																																														                        <input type="text" name="motivo_arrendamiento" value="<?php echo htmlspecialchars($vivienda['motivo_arrendamiento'] ?: ''); ?>" class="w-full bg-white/70 px-3 py-2 rounded-lg border border-yellow-200/50 focus:ring-2 focus:ring-yellow-300 outline-none text-black">
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


        <!-- ARCHIVOS SUBIDOS -->
<section class="relative group">

    <!-- Tag de Archivos -->
    <div class="absolute -top-6 left-6 bg-pink-600/90 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
        <svg class="w-5 h-5 text-pink-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828V21h2V7h-4.828z" />
        </svg> Archivos
    </div>

    <!-- Contenido de Archivos -->
       <div class="mt-8 bg-white/15 backdrop-blur-lg border border-pink-200/40 rounded-2xl shadow-xl p-6">
<?php
    $idInquilino = (int)($inquilino['id'] ?? 0);
    $nombreProspecto = strtolower(preg_replace('/\s+/', '',
        ($inquilino['nombre_inquilino'] ?? '') .
        ($inquilino['apellidop_inquilino'] ?? '') .
        ($inquilino['apellidom_inquilino'] ?? '')
    ));

    // Index por tipo
    $archivos = $inquilino['archivos'] ?? [];
    $byType = []; $cnt=[];
    foreach ($archivos as $a) {
        $t = $a['tipo'] ?? '';
        if ($t==='') continue;
        $byType[$t][] = $a;
        $cnt[$t] = ($cnt[$t] ?? 0) + 1;
    }

    $hasPassport = !empty($byType['pasaporte']) || !empty($byType['passport']);
    $needINE     = !$hasPassport;
    $hasSelfie   = !empty($byType['selfie']);
    $compCount   = (int)($cnt['comprobante_ingreso'] ?? 0);
    $faltanComp  = max(0, 3 - $compCount);

    // Helper para aceptar tipos segun "tipo" lógico
    function acceptFor($tipo) {
        switch ($tipo) {
            case 'selfie': return 'image/*';
            case 'comprobante_ingreso': return 'application/pdf';
            case 'ine_frontal':
            case 'ine_reverso':
            case 'pasaporte':
            case 'passport': return 'image/*,application/pdf';
            default: return 'image/*,application/pdf';
        }
    }
?>
    <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3">

    <?php if (!empty($archivos)): ?>
        <?php foreach ($archivos as $archivo): ?>
            <?php
                $url         = $inquilino['s3_base_url'] . $archivo['s3_key'];
                $tipoArchivo = strtolower(pathinfo($archivo['s3_key'], PATHINFO_EXTENSION));
                $esImagen    = in_array($tipoArchivo, ['jpg','jpeg','png','webp']);
                $tipo        = $archivo['tipo'] ?? '';
                $dzId        = 'dz-replace-' . (int)$archivo['id'];
                $acceptAttr  = acceptFor($tipo);
            ?>
            <div class="bg-white/10 border border-pink-400/20 p-4 rounded-xl shadow-md flex flex-col items-center text-center relative group">
                
                <!-- Botón eliminar -->
                <button type="button"
                    class="absolute top-2 left-2 opacity-70 hover:opacity-100 bg-gray-700/90 hover:bg-red-600 text-white rounded-full p-2"
                    onclick="eliminarArchivo('<?php echo $archivo['id']; ?>')" title="Eliminar archivo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Botón reemplazar -->
                <button type="button"
                    class="absolute top-2 right-2 opacity-90 hover:opacity-100 bg-indigo-700/90 hover:bg-pink-600 text-white rounded-full p-2"
                    onclick="document.getElementById('<?php echo $dzId; ?>-input').click()" title="Reemplazar archivo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5a1 1 0 001 1h5M20 20v-5a1 1 0 00-1-1h-5M5 9l6-6M19 15l-6 6"/></svg>
                </button>

                <!-- Preview archivo actual -->
                <?php if ($esImagen): ?>
                    <img src="<?php echo htmlspecialchars($url) ?>"
                        alt="Archivo imagen"
                        class="rounded-lg max-h-44 object-contain mb-3 shadow-md cursor-zoom-in hover:scale-105 transition"
                        onclick="abrirModalImg('<?php echo htmlspecialchars($url) ?>', '<?php echo addslashes($tipo) ?>')" />
                <?php else: ?>
                    <div class="flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                <?php endif; ?>

                <p class="text-xs font-semibold text-gray-200 truncate w-full mb-2"><?php echo htmlspecialchars($tipo); ?></p>

                <!-- Acciones -->
                <?php if ($esImagen): ?>
                    <a href="#" onclick="abrirModalImg('<?php echo htmlspecialchars($url) ?>', '<?php echo addslashes($tipo) ?>'); return false;"
                       class="bg-pink-500 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full shadow">
                       Ver imagen
                    </a>
                <?php else: ?>
                    <a href="#" onclick="abrirModalPdf('<?php echo htmlspecialchars($url) ?>', '<?php echo addslashes($tipo) ?>'); return false;"
                       class="bg-pink-500 hover:bg-pink-600 text-white text-xs px-3 py-1 rounded-full shadow">
                       Ver archivo
                    </a>
                <?php endif; ?>

                <!-- Dropzone casero REEMPLAZO -->
                <input type="file" id="<?php echo $dzId; ?>-input" class="hidden"
                       accept="<?php echo htmlspecialchars($acceptAttr); ?>"
                       onchange="previewAndReplaceFile(this, '<?php echo $archivo['id']; ?>', '<?php echo $tipo; ?>', '<?php echo htmlspecialchars($nombreProspecto); ?>')">
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- Slots para archivos faltantes -->
 <?php
    // --------- Tarjetas que faltan (misma UI de dropzone casero) ----------
    $slots = [];
    if (!$hasSelfie) {
        $slots[] = ['tipo'=>'selfie','label'=>'Selfie'];
    }

    if ($needINE) {
        if (empty($byType['ine_frontal'])) {
            $slots[] = ['tipo'=>'ine_frontal','label'=>'INE - Frente'];
        }
        if (empty($byType['ine_reverso'])) {
            $slots[] = ['tipo'=>'ine_reverso','label'=>'INE - Reverso'];
        }
        if (!$hasPassport) {
            $slots[] = ['tipo'=>'pasaporte','label'=>'Pasaporte'];
        }
    }

    // Forma migratoria (frente y reverso)
    if (empty($byType['forma_frontal'])) {
        $slots[] = ['tipo'=>'forma_frontal','label'=>'Forma Migratoria - Frente'];
    }
    if (empty($byType['forma_reverso'])) {
        $slots[] = ['tipo'=>'forma_reverso','label'=>'Forma Migratoria - Reverso'];
    }

    // Escritura
    if (empty($byType['escritura'])) {
        $slots[] = ['tipo'=>'escritura','label'=>'Escritura de inmueble (PDF)'];
    }

    // Comprobantes de ingresos
    for ($i=0; $i<$faltanComp; $i++) {
        $slots[] = ['tipo'=>'comprobante_ingreso','label'=>'Comprobante de ingreso (PDF)'];
    }
?>

    <?php foreach ($slots as $idx=>$t): ?>
        <div class="bg-white/10 border border-pink-400/20 p-5 rounded-xl shadow-md flex flex-col items-center text-center">
            <svg class="w-12 h-12 text-pink-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <p class="text-sm font-semibold text-gray-200 mb-2"><?php echo htmlspecialchars($t['label']); ?></p>

            <!-- Dropzone casero NUEVO -->
            <input type="file" id="dz-new-<?php echo $t['tipo'].'-'.$idx; ?>" class="hidden"
                   accept="<?php echo htmlspecialchars(acceptFor($t['tipo'])); ?>"
                   onchange="previewAndUploadFile(this, '<?php echo $idInquilino; ?>', '<?php echo $t['tipo']; ?>', '<?php echo htmlspecialchars($nombreProspecto); ?>')">

            <button type="button"
                class="bg-gradient-to-r from-pink-500 to-indigo-600 hover:from-pink-600 hover:to-indigo-700 text-white text-xs px-3 py-2 rounded-lg shadow"
                onclick="document.getElementById('dz-new-<?php echo $t['tipo'].'-'.$idx; ?>').click()">
                Subir <?php echo htmlspecialchars($t['label']); ?>
            </button>
        </div>
    <?php endforeach; ?>


    <!-- Dropzone OTROS ARCHIVOS -->
    <div class="bg-white/10 border border-pink-400/20 p-6 rounded-xl shadow-md flex flex-col items-center text-center sm:col-span-2 md:col-span-3">
        <svg class="w-14 h-14 text-pink-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <p class="text-sm font-semibold text-gray-200 mb-2">Otros archivos (PDF o imagen)</p>

        <input type="file" id="dz-otros-archivos" class="hidden" accept=".pdf,image/*" multiple
               onchange="previewAndUploadOtros(this, '<?php echo $idInquilino; ?>', '<?php echo htmlspecialchars($nombreProspecto); ?>')">

        <button type="button"
            class="bg-gradient-to-r from-pink-500 to-indigo-600 hover:from-pink-600 hover:to-indigo-700 text-white text-xs px-3 py-2 rounded-lg shadow"
            onclick="document.getElementById('dz-otros-archivos').click()">
            Subir otros archivos
        </button>
    </div>

</div>

</div>                                                                  
    <!-- Fin de Contenido de Archivos -->
    

</section>






       <!-- ASESOR -->
<section class="relative group">
    <div class="absolute -top-6 left-6 bg-fuchsia-700/80 text-white px-4 py-1 rounded-full text-lg font-bold shadow-md backdrop-blur z-10 group-hover:scale-105 transition-transform flex items-center gap-2">
        <svg class="w-5 h-5 text-fuchsia-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5.121 17.804A1.5 1.5 0 016 17h12a1.5 1.5 0 01.879.296l2 1.5A1.5 1.5 0 0120.5 21h-17a1.5 1.5 0 01-.879-2.704l2-1.5zM12 12a5 5 0 100-10 5 5 0 000 10z" />
        </svg> Asesor
    </div>
    <?php $a = $inquilino['asesor'] ?? []; ?>
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
            <span class="block text-white/90"><?php echo $a['nombre_asesor'] ?? 'No asignado' ?></span>
        </div>
        <div>
            <span class="font-semibold text-fuchsia-100">Email:</span>
            <span class="inline-flex items-center gap-2 text-white/90">
                <span id="email-asesor"><?php echo $a['email'] ?? 'N/A' ?></span>
                <?php if (! empty($a['email']??"")): ?>
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
                <span id="celular-asesor"><?php echo $a['celular'] ?? 'N/A' ?></span>
                <?php if (! empty($a['celular']??"")): ?>
                <button type="button" class="ml-1 p-1 rounded-full hover:bg-fuchsia-700/30 transition" onclick="copiarAlPortapapeles('celular-asesor')" title="Copiar Celular">
                    <svg class="w-4 h-4 text-fuchsia-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                    </svg>
                </button>
                <?php endif; ?>
            </span>
        </div>
        <div>
            <span class="font-semibold text-fuchsia-100">Teléfono:</span>
            <span class="block text-white/90"><?php echo $a['telefono'] ?? 'N/A' ?></span>
        </div>
    </div>

    <!-- Formulario edición (oculto) -->
    <form id="form-editar-asesor" class="hidden mt-8 bg-white/15 backdrop-blur-lg border border-fuchsia-300/40 rounded-2xl shadow-xl p-6 grid md:grid-cols-2 gap-8 text-base"
          autocomplete="off" onsubmit="guardarEdicionAsesor(event)">
        <input type="hidden" name="id_inquilino" value="<?php echo $inquilino['id']; ?>">
        <div class="md:col-span-2">
            <label class="block font-semibold text-fuchsia-100 mb-2">Selecciona el asesor asignado</label>
            <select name="id_asesor" required class="w-full bg-white/70 text-black border border-fuchsia-300/40 rounded-lg px-3 py-2 focus:ring-2 focus:ring-fuchsia-400 outline-none">
                <option value="">Selecciona...</option>
                <?php foreach ($asesores as $asesor): ?>
                <option value="<?php echo $asesor['id']; ?>"<?php if (! empty($a['id']??"") && $a['id'] == $asesor['id']??"") {
        echo 'selected';
}
?>>
                    <?php echo htmlspecialchars($asesor['nombre_asesor']??"") . ' (' . htmlspecialchars($asesor['email']??"") . ')'; ?>
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


<script>
/**
 * Script unificado para vista de Inquilino
 * - Editar datos (personales, domicilio, trabajo, fiador, historial, validaciones)
 * - Reemplazo de archivos (dropzone casera)
 * - Validación AWS (rostro + identidad) y refresco de semáforo
 *
 * Requiere: SweetAlert2 (Swal) y que PHP defina $baseUrl antes de este script.
 */
(function () {
  'use strict';

  // =======================
  //  BASE / ENDPOINTS
  // =======================
  // Si ya existe BASE_URL global la usamos; si no, tomamos la que imprime PHP.
  const ADMIN_BASE =
    (typeof BASE_URL !== 'undefined' && BASE_URL)
      ? String(BASE_URL).replace(/\/+$/, '')
      : <?= json_encode(rtrim($baseUrl ?? '', '/')); ?>;

  // =======================
  //  HELPERS
  // =======================
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const byId = (id) => document.getElementById(id);

  function postJSON(url, body) {
    return fetch(url, { method: 'POST', body }).then(async (r) => {
      // intenta parsear JSON aunque el status no sea 2xx
      const data = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(data.error || data.mensaje || `HTTP ${r.status}`);
      return data;
    });
  }

  // =======================
  //  EDITAR: DATOS PERSONALES
  // =======================
  window.mostrarFormularioEdicion = function () {
    byId('datos-personales-vista')?.classList.add('hidden');
    byId('form-editar-datos')?.classList.remove('hidden');
    byId('btn-editar-datos')?.classList.add('hidden');
  };
  window.cancelarEdicionDatos = function () {
    byId('form-editar-datos')?.classList.add('hidden');
    byId('datos-personales-vista')?.classList.remove('hidden');
    byId('btn-editar-datos')?.classList.remove('hidden');
    if (byId('mensaje-edicion')) byId('mensaje-edicion').innerText = '';
  };
  window.guardarEdicionDatos = function (e) {
    e.preventDefault();
    const form = byId('form-editar-datos');
    const msg = byId('mensaje-edicion');
    if (!form) return;
    msg.className = 'text-sm text-center pt-2 text-yellow-500';
    msg.innerText = 'Guardando...';

    postJSON(ADMIN_BASE + '/inquilino/editar_datos_personales', new FormData(form))
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: '¡Datos actualizados!',
          text: 'Los datos personales se guardaron correctamente.',
          confirmButtonColor: '#6366f1',
          background: '#18181b',
          color: '#fff',
        }).then(() => location.reload());
      })
      .catch((err) => {
        Swal.fire({
          icon: 'error',
          title: '¡Error!',
          text: err.message || 'Error al guardar.',
          confirmButtonColor: '#de6868',
          background: '#18181b',
          color: '#fff',
        });
      });
  };

  // =======================
  //  EDITAR: DOMICILIO
  // =======================
  window.mostrarFormularioEdicionDomicilio = function () {
    byId('domicilio-vista')?.classList.add('hidden');
    byId('form-editar-domicilio')?.classList.remove('hidden');
    byId('btn-editar-domicilio')?.classList.add('hidden');
  };
  window.cancelarEdicionDomicilio = function () {
    byId('form-editar-domicilio')?.classList.add('hidden');
    byId('domicilio-vista')?.classList.remove('hidden');
    byId('btn-editar-domicilio')?.classList.remove('hidden');
    if (byId('mensaje-edicion-domicilio')) byId('mensaje-edicion-domicilio').innerText = '';
  };
  window.guardarEdicionDomicilio = function (e) {
    e.preventDefault();
    const form = byId('form-editar-domicilio');
    const msg = byId('mensaje-edicion-domicilio');
    if (!form) return;
    msg.className = 'text-sm text-center pt-2 text-yellow-500';
    msg.innerText = 'Guardando...';

    postJSON(ADMIN_BASE + '/inquilino/editar_domicilio', new FormData(form))
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: '¡Domicilio actualizado!',
          text: 'La información del domicilio se guardó correctamente.',
          confirmButtonColor: '#22c55e',
          background: '#18181b',
          color: '#fff',
        }).then(() => location.reload());
      })
      .catch((err) => {
        Swal.fire({
          icon: 'error',
          title: '¡Error!',
          text: err.message || 'Error al guardar.',
          confirmButtonColor: '#6366f1',
          background: '#18181b',
          color: '#fff',
        });
      });
  };

  // =======================
  //  EDITAR: TRABAJO
  // =======================
  window.mostrarFormularioEdicionTrabajo = function () {
    byId('trabajo-vista')?.classList.add('hidden');
    byId('form-editar-trabajo')?.classList.remove('hidden');
    byId('btn-editar-trabajo')?.classList.add('hidden');
  };
  window.cancelarEdicionTrabajo = function () {
    byId('form-editar-trabajo')?.classList.add('hidden');
    byId('trabajo-vista')?.classList.remove('hidden');
    byId('btn-editar-trabajo')?.classList.remove('hidden');
    if (byId('mensaje-edicion-trabajo')) byId('mensaje-edicion-trabajo').innerText = '';
  };
  window.guardarEdicionTrabajo = function (e) {
    e.preventDefault();
    const form = byId('form-editar-trabajo');
    const msg = byId('mensaje-edicion-trabajo');
    if (!form) return;
    msg.className = 'text-sm text-center pt-2 text-yellow-500';
    msg.innerText = 'Guardando...';

    postJSON(ADMIN_BASE + '/inquilino/editar_trabajo', new FormData(form))
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: '¡Información laboral actualizada!',
          text: 'Los datos de trabajo se guardaron correctamente.',
          confirmButtonColor: '#facc15',
          background: '#18181b',
          color: '#fff',
        }).then(() => location.reload());
      })
      .catch((err) => {
        Swal.fire({
          icon: 'error',
          title: '¡Error!',
          text: err.message || 'Error al guardar.',
          confirmButtonColor: '#6366f1',
          background: '#18181b',
          color: '#fff',
        });
      });
  };

  // =======================
  //  EDITAR: FIADOR
  // =======================
  window.mostrarFormularioEdicionFiador = function () {
    byId('fiador-vista')?.classList.add('hidden');
    byId('form-editar-fiador')?.classList.remove('hidden');
    byId('btn-editar-fiador')?.classList.add('hidden');
  };
  window.cancelarEdicionFiador = function () {
    byId('form-editar-fiador')?.classList.add('hidden');
    byId('fiador-vista')?.classList.remove('hidden');
    byId('btn-editar-fiador')?.classList.remove('hidden');
    if (byId('mensaje-edicion-fiador')) byId('mensaje-edicion-fiador').innerText = '';
  };
  window.guardarEdicionFiador = function (e) {
    e.preventDefault();
    const form = byId('form-editar-fiador');
    const msg = byId('mensaje-edicion-fiador');
    if (!form) return;
    msg.className = 'text-sm text-center pt-2 text-purple-400';
    msg.innerText = 'Guardando...';

    postJSON(ADMIN_BASE + '/inquilino/editar_fiador', new FormData(form))
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: '¡Datos del fiador actualizados!',
          text: 'Los datos del fiador se guardaron correctamente.',
          confirmButtonColor: '#a78bfa',
          background: '#18181b',
          color: '#fff',
        }).then(() => location.reload());
      })
      .catch((err) => {
        Swal.fire({
          icon: 'error',
          title: '¡Error!',
          text: err.message || 'Error al guardar.',
          confirmButtonColor: '#a78bfa',
          background: '#18181b',
          color: '#fff',
        });
      });
  };

  // =======================
  //  EDITAR: HISTORIAL VIVIENDA
  // =======================
  window.mostrarFormularioEdicionHistorial = function () {
    byId('historial-vivienda-vista')?.classList.add('hidden');
    byId('form-editar-historial')?.classList.remove('hidden');
    byId('btn-editar-historial')?.classList.add('hidden');
  };
  window.cancelarEdicionHistorial = function () {
    byId('form-editar-historial')?.classList.add('hidden');
    byId('historial-vivienda-vista')?.classList.remove('hidden');
    byId('btn-editar-historial')?.classList.remove('hidden');
    if (byId('mensaje-edicion-historial')) byId('mensaje-edicion-historial').innerText = '';
  };
  window.guardarEdicionHistorial = function (e) {
    e.preventDefault();
    const form = byId('form-editar-historial');
    if (!form) return;

    byId('mensaje-edicion-historial').innerText = 'Guardando...';

    postJSON(ADMIN_BASE + '/inquilino/editar_historial_vivienda', new FormData(form))
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: '¡Historial actualizado!',
          timer: 1200,
          showConfirmButton: false,
          background: '#1a1a23',
          color: '#ffe066',
        });
        setTimeout(() => location.reload(), 900);
      })
      .catch((err) => {
        byId('mensaje-edicion-historial').innerText = err.message || 'Error al guardar.';
      });
  };



  // =======================
  //  MODALES IMG / PDF + COPIAR
  // =======================
  window.abrirModalImg = function (url, caption = '') {
    const img = byId('img-modal-grande');
    if (!img) return;
    img.classList.remove('animate-fade-in');
    img.src = url;
    byId('modal-img-caption').innerText = caption;
    byId('modal-img').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    void img.offsetWidth;
    img.classList.add('animate-fade-in');
  };
  window.cerrarModalImg = function () {
    byId('modal-img')?.classList.add('hidden');
    if (byId('img-modal-grande')) byId('img-modal-grande').src = '';
    document.body.classList.remove('overflow-hidden');
  };
  byId('modal-img')?.addEventListener('click', (e) => { if (e.target === e.currentTarget) window.cerrarModalImg(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.cerrarModalImg(); });

  window.abrirModalPdf = function (url, caption = '') {
    const iframe = byId('iframe-pdf');
    if (!iframe) return;
    iframe.src = url;
    byId('modal-pdf-caption').innerText = caption;
    byId('modal-pdf').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  };
  window.cerrarModalPdf = function () {
    byId('modal-pdf')?.classList.add('hidden');
    if (byId('iframe-pdf')) byId('iframe-pdf').src = '';
    document.body.classList.remove('overflow-hidden');
  };
  byId('modal-pdf')?.addEventListener('click', (e) => { if (e.target === e.currentTarget) window.cerrarModalPdf(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.cerrarModalPdf(); });

  window.copiarAlPortapapeles = function (elementId) {
    const el = byId(elementId);
    const text = el?.innerText;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
      const oldBg = el.style.backgroundColor;
      el.style.backgroundColor = '#fbb6ce';
      setTimeout(() => { el.style.backgroundColor = oldBg; }, 200);
    });
  };

  // =======================
  //  REEMPLAZO DE ARCHIVOS (Dropzone)
  // =======================
  document.addEventListener('DOMContentLoaded', function () {
    $$("[id^='dropzone-']").forEach(function (dz) {
      let previewEl = null;

      dz.addEventListener('click', function () {
        let input = dz.querySelector("input[type='file']");
        if (!input) {
          input = document.createElement('input');
          input.type = 'file';
          input.accept = '.jpg,.jpeg,.png,.webp,.pdf';
          input.className = 'hidden';
          dz.appendChild(input);
          input.addEventListener('change', (e) => mostrarPreviewArchivo(e.target.files[0], dz));
        }
        input.value = '';
        input.click();
      });

      dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('border-pink-600', 'bg-pink-50/20'); });
      dz.addEventListener('dragleave', (e) => { e.preventDefault(); dz.classList.remove('border-pink-600', 'bg-pink-50/20'); });
      dz.addEventListener('drop', (e) => {
        e.preventDefault();
        dz.classList.remove('border-pink-600', 'bg-pink-50/20');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
          mostrarPreviewArchivo(e.dataTransfer.files[0], dz);
        }
      });

      function mostrarPreviewArchivo(file, dzEl) {
        const valid = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!file || !valid.includes(file.type)) {
          mostrarMsg(dzEl.dataset.archivoId, 'Archivo no válido. Usa JPG, PNG, WEBP o PDF.', 'error');
          return;
        }
        dzEl.selectedFile = file;
        if (previewEl) previewEl.remove();

        previewEl = document.createElement('div');
        previewEl.className = 'w-full flex flex-col items-center gap-2 mt-2';

        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.className = 'rounded-lg max-h-32 object-contain shadow';
          img.src = URL.createObjectURL(file);
          previewEl.appendChild(img);
        } else {
          previewEl.innerHTML = `<div>
            <svg class="w-10 h-10 text-pink-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75V18a2.25 2.25 0 01-2.25 2.25H9a2.25 2.25 0 01-2.25-2.25V6.75m7.5-4.5h-7.5a2.25 2.25 0 00-2.25 2.25v14.25A2.25 2.25 0 009 21h6a2.25 2.25 0 002.25-2.25V4.5a2.25 2.25 0 00-2.25-2.25z" />
            </svg></div>`;
        }

        const fname = document.createElement('div');
        fname.className = 'text-xs text-pink-600 font-bold text-center truncate w-36';
        fname.innerText = file.name;
        previewEl.appendChild(fname);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerText = 'Quitar archivo';
        btn.className = 'text-xs text-gray-300 hover:text-pink-600 font-semibold py-1';
        btn.onclick = function () { dzEl.selectedFile = null; previewEl.remove(); };
        previewEl.appendChild(btn);

        dzEl.appendChild(previewEl);
        mostrarMsg(dzEl.dataset.archivoId, '¡Listo para subir!', 'ok');
      }

      function mostrarMsg(archivoId, txt, tipo) {
        const msgEl = byId('mensaje-reemplazo-' + archivoId);
        if (!msgEl) return;
        msgEl.innerText = txt;
        msgEl.className = 'text-xs text-center pt-2 ' + (tipo === 'error' ? 'text-red-400' : 'text-green-400');
      }
    });
  });

  // Subida desde cada dropzone (botón "Subir")
  window.enviarDropzone = function (archivoId) {
    const dz = byId('dropzone-' + archivoId);
    const file = dz?.selectedFile;
    if (!dz || !file) {
      Swal.fire({ title: 'Selecciona un archivo primero', icon: 'warning', confirmButtonColor: '#de6868', background: '#18181b', color: '#fff' });
      return;
    }

    Swal.fire({ title: 'Subiendo archivo...', text: 'Por favor espera', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading(), background: '#18181b', color: '#fff' });

    const form = byId('form-reemplazo-' + archivoId);
    const fd = new FormData(form);
    fd.append('archivo_id', archivoId);
    fd.append('archivo', file);

    postJSON(ADMIN_BASE + '/inquilino/reemplazar_archivo', fd)
      .then(() => {
        Swal.close();
        Swal.fire({ title: '¡Archivo reemplazado!', icon: 'success', confirmButtonColor: '#de6868', background: '#18181b', color: '#fff' })
          .then(() => location.reload());
      })
      .catch((err) => {
        Swal.close();
        Swal.fire({ title: 'Error', text: err.message || 'Error al subir archivo.', icon: 'error', confirmButtonColor: '#de6868', background: '#18181b', color: '#fff' });
      });
  };


// ============== Data para archivos (opcional) ===============================
async function fetchArchivosResumen(base, slug){
    // Tipos que mostramos
    const MUST_TYPES = ['selfie','ine_frontal','ine_reverso','pasaporte','forma_migratoria'];

    async function fetchArchivosResumen(base, slug, hintsFromFace = {}) {

        // Tipos “requeridos” que mostramos en UI
const REQUIRED_FILE_TYPES = ['selfie','ine_frontal','ine_reverso','pasaporte','forma_migratoria'];

    async function fetchArchivosResumen(base, slug, hintsFromFace = {}) {
        const presentes = new Set();
        let comprobantes = 0;

        try {
            const r = await fetch(`${base}/inquilino/${encodeURIComponent(slug)}/validar?check=archivos`, { credentials:'include' });
            const j = await r.json();

            if (j?.ok) {
            // A) { items:[{tipo:"..."}, ...] }
            if (Array.isArray(j.items)) {
                j.items.forEach(it => {
                const t = String(it.tipo || '').trim();
                if (REQUIRED_FILE_TYPES.includes(t)) presentes.add(t);
                if (t === 'comprobante_ingreso') comprobantes++;
                });
            }
            // B) { data:{ items:[...] } }
            else if (j.data && Array.isArray(j.data.items)) {
                j.data.items.forEach(it => {
                const t = String(it.tipo || '').trim();
                if (REQUIRED_FILE_TYPES.includes(t)) presentes.add(t);
                if (t === 'comprobante_ingreso') comprobantes++;
                });
            }
            // C) { archivos:{ selfie:true, ..., comprobantes:[...] } }
            else if (j.archivos && typeof j.archivos === 'object') {
                REQUIRED_FILE_TYPES.forEach(k => { if (j.archivos[k]) presentes.add(k); });
                if (Array.isArray(j.archivos.comprobantes)) comprobantes = j.archivos.comprobantes.length;
                else if (typeof j.archivos.comprobantes_count === 'number') comprobantes = j.archivos.comprobantes_count;
            }
            // D) { presentes:[], faltantes:[], comprobantes(_count) }
            else {
                if (Array.isArray(j.presentes)) j.presentes.forEach(t => presentes.add(String(t)));
                if (Array.isArray(j.comprobantes)) comprobantes = j.comprobantes.length;
                else if (typeof j.comprobantes_count === 'number') comprobantes = j.comprobantes_count;
            }
            }
        } catch (_) {
            // dejamos los sets vacíos; devolvemos objeto igualmente
        }

        // Pistas del paso de rostro (por si el endpoint no reporta)
        if (hintsFromFace?.selfie_key)  presentes.add('selfie');
        if (hintsFromFace?.ine_frontal) presentes.add('ine_frontal');
        if (hintsFromFace?.ine_reverso) presentes.add('ine_reverso');

        const presentesArr = Array.from(presentes);
        const faltantesArr = REQUIRED_FILE_TYPES.filter(k => !presentes.has(k));

        return { presentes: presentesArr, faltantes: faltantesArr, comprobantes };
        }


    }


async function validateComprobantes(base, slug) {
  // Si tienes un endpoint “inteligente”, úsalo:
  try {
    const r = await fetch(`${base}/inquilino/${encodeURIComponent(slug)}/validar?check=validar_ingresos`, { credentials:'include' });
    const j = await r.json();
    if (j?.ok) {
      const x = j.resultado || {};
      return {
        ok: x.status === 'ok',
        status: x.status || 'review',
        archivos: +x.archivos || 0,
        nombre_ok: !!x.nombre_ok,
        reciente: !!x.reciente,
        ultima_fecha: x.ultima_fecha || null
      };
    }
  } catch (_) {}

  // Fallback por conteo (verde si hay al menos 1)
  const a = summary?.archivos || { presentes:[], faltantes:[], comprobantes:0 };
  return {
    ok: (a.comprobantes || 0) > 0,
    status: (a.comprobantes || 0) > 0 ? 'ok' : 'fail',
    archivos: a.comprobantes || 0,
    nombre_ok: undefined,
    reciente: undefined,
    ultima_fecha: null
  };
}


}

// ============== SweetAlert helpers ==========================================
function swalProgress(stepText) {
  Swal.fire({
    title: 'Validando…',
    html: `<div style="margin-top:4px">${stepText||''}</div>`,
    allowOutsideClick: false, allowEscapeKey: false,
    showConfirmButton: false, background: '#18181b', color:'#fff',
    didOpen: () => Swal.showLoading()
  });
}
function swalStep(text) { try{ Swal.update({ html: `<div style="margin-top:4px">${text}</div>` }); }catch{} }

// ============== Botón principal =============================================

function wireValidateButton() {
  // ---- Selectores base
  const btn    = document.getElementById('btn-validar-aws') || document.getElementById('btn-validar-rostro');
  const rootEl = document.getElementById('validacion-aws');
  const slug   = (rootEl?.dataset?.slug) || (window.location.pathname.match(/\/inquilino\/([^/]+)/)?.[1]) || '';
  if (!btn || !slug) return;
  if (btn.dataset.inited) return;
  btn.dataset.inited = '1';

  const ADMIN_BASE = (typeof ADMIN_BASE_URL !== 'undefined')
    ? ADMIN_BASE_URL
    : (typeof BASE_URL !== 'undefined' ? BASE_URL : <?= json_encode(rtrim($baseUrl ?? '', '/')); ?>);

  // ---- Constantes locales
  const REQUIRED_FILE_TYPES = ['selfie','ine_frontal','ine_reverso','pasaporte','forma_migratoria'];

  // ---- Normalizador súper tolerante del payload de archivos
  function normalizeArchivosPayload(payload) {
    // resultado neutro
    const out = { presentes: [], faltantes: REQUIRED_FILE_TYPES.slice(), comprobantes: 0 };
    if (!payload) return out;

    // 1) Si ya viene con presentes/faltantes
    if (Array.isArray(payload.presentes) || Array.isArray(payload.faltantes)) {
      const pr = (payload.presentes || []).filter(Boolean);
      out.presentes = pr;
      out.faltantes = REQUIRED_FILE_TYPES.filter(k => !pr.includes(k));
      const comp = payload.comprobantes;
      out.comprobantes = Array.isArray(comp) ? comp.length : (typeof comp === 'number' ? comp : 0);
      return out;
    }

    // 2) Si viene como objeto de banderas: { archivos: {selfie:true,..., comprobantes:[...] } }
    if (payload.archivos && !Array.isArray(payload.archivos) && typeof payload.archivos === 'object') {
      const flags = payload.archivos;
      out.presentes = REQUIRED_FILE_TYPES.filter(k => !!flags[k]);
      out.faltantes = REQUIRED_FILE_TYPES.filter(k => !out.presentes.includes(k));
      const comp = flags.comprobantes;
      out.comprobantes = Array.isArray(comp) ? comp.length : (typeof comp === 'number' ? comp : 0);
      return out;
    }

    // 3) Si viene como arreglo de objetos o strings: { archivos: [ {tipo:'selfie'}, ... ] } ó { archivos: ['selfie', ...] }
    if (Array.isArray(payload.archivos)) {
      const tipos = payload.archivos
        .map(it => (typeof it === 'string' ? it : it?.tipo))
        .filter(Boolean);
      out.presentes = REQUIRED_FILE_TYPES.filter(k => tipos.includes(k));
      out.faltantes = REQUIRED_FILE_TYPES.filter(k => !out.presentes.includes(k));
      out.comprobantes = tipos.filter(t => t === 'comprobante_ingreso').length;
      return out;
    }

    // 4) Si el propio payload ES el arreglo (sin envolver): ['selfie', ...] o [{tipo:'selfie'}, ...]
    if (Array.isArray(payload)) {
      const tipos = payload.map(it => (typeof it === 'string' ? it : it?.tipo)).filter(Boolean);
      out.presentes = REQUIRED_FILE_TYPES.filter(k => tipos.includes(k));
      out.faltantes = REQUIRED_FILE_TYPES.filter(k => !out.presentes.includes(k));
      out.comprobantes = tipos.filter(t => t === 'comprobante_ingreso').length;
      return out;
    }

    // 5) Otras llaves comunes
    const arr = payload.files || payload.data || null;
    if (Array.isArray(arr)) {
      const tipos = arr.map(it => (typeof it === 'string' ? it : it?.tipo)).filter(Boolean);
      out.presentes = REQUIRED_FILE_TYPES.filter(k => tipos.includes(k));
      out.faltantes = REQUIRED_FILE_TYPES.filter(k => !out.presentes.includes(k));
      out.comprobantes = tipos.filter(t => t === 'comprobante_ingreso').length;
      return out;
    }

    return out;
  }

  // ---- Fetch archivos con normalización
  async function fetchArchivosResumen(base, slug, hintsFromFace = {}) {
  const REQUIRED = ['selfie','ine_frontal','ine_reverso','pasaporte','forma_migratoria'];
  const presentes = new Set();
  let comprobantes = 0;

  function finish() {
    const pr = Array.from(presentes);
    const fa = REQUIRED.filter(k => !presentes.has(k));
    const out = { presentes: pr, faltantes: fa, comprobantes };
    window._lastArchNorm = out; // <- debug
    return out;
  }

  try {
    const r = await fetch(
      `${base}/inquilino/${encodeURIComponent(slug)}/validar?check=archivos`,
      { credentials: 'include' }
    );
    const j = await r.json().catch(() => null);
    window._lastArchResp = j; // <- debug
    if (!j || j.ok === false) throw new Error('respuesta no OK');

    // ---- CASO 1: payload.archivos es objeto con {selfie:{...}, ine_frontal:{...}, ...}
    const a =
      j.archivos ||
      (j.data && j.data.archivos) ||
      null;

    if (a && typeof a === 'object' && !Array.isArray(a)) {
      // cuenta presentes por verdad “truthy” (objeto o true)
      REQUIRED.forEach(k => { if (a[k]) presentes.add(k); });

      // varias llaves posibles para comprobantes
      const comps =
        a.comprobantes ||
        a.comprobantes_ingreso ||
        a.recibos ||
        a.files_comprobantes ||
        null;

      if (Array.isArray(comps)) comprobantes = comps.length;
      else if (typeof comps === 'number') comprobantes = comps;

      return finish();
    }

    // ---- CASO 2: { items: [ {tipo:'selfie',...}, ... ] } o { data: { items: [...] } }
    const items = Array.isArray(j.items) ? j.items
                 : (j.data && Array.isArray(j.data.items) ? j.data.items : null);
    if (items) {
      const tipos = items.map(it => (typeof it === 'string' ? it : it?.tipo)).filter(Boolean);
      tipos.forEach(t => { if (REQUIRED.includes(t)) presentes.add(t); });
      comprobantes = tipos.filter(t => t === 'comprobante_ingreso').length;
      return finish();
    }

    // ---- CASO 3: { presentes: [...], faltantes: [...], comprobantes(_count) }
    if (Array.isArray(j.presentes) || Array.isArray(j.faltantes)) {
      (j.presentes || []).forEach(t => presentes.add(String(t)));
      if (Array.isArray(j.comprobantes)) comprobantes = j.comprobantes.length;
      else if (typeof j.comprobantes_count === 'number') comprobantes = j.comprobantes_count;
      return finish();
    }

    // ---- CASO 4: arreglo plano en el tope: ['selfie', ...] o [{tipo:'selfie'}, ...]
    if (Array.isArray(j)) {
      const tipos = j.map(it => (typeof it === 'string' ? it : it?.tipo)).filter(Boolean);
      tipos.forEach(t => { if (REQUIRED.includes(t)) presentes.add(t); });
      comprobantes = tipos.filter(t => t === 'comprobante_ingreso').length;
      return finish();
    }

  } catch (_) {
    // seguimos a fallback
  }

  // ---- FALLBACK: usa las pistas del paso de rostro si las hay
  if (hintsFromFace?.selfie_key)  presentes.add('selfie');
  if (hintsFromFace?.ine_frontal) presentes.add('ine_frontal');
  if (hintsFromFace?.ine_reverso) presentes.add('ine_reverso');

  return finish();
}


  // ---- Click handler
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    if (btn.dataset.busy) return;

    btn.dataset.busy = '1';
    const oldTxt = btn.textContent;
    btn.textContent = 'Validando…';

    const urlFace  = `${ADMIN_BASE}/inquilino/${encodeURIComponent(slug)}/validar?check=save_face`;
    const urlMatch = `${ADMIN_BASE}/inquilino/${encodeURIComponent(slug)}/validar?check=save_match`;

    const summary = { ts: new Date().toISOString() };

    try {
      // Paso 1: Rostro
      swalProgress('Comparando selfie vs. foto del INE…');
      let r = await fetch(urlFace, { credentials:'include' });
      let j = await r.json();
      if (!j.ok) throw new Error(j.mensaje || 'Error en validación de rostro');

      const face = j.resultado || {};
      summary.rostro = {
        ok: face.estatus === 1,
        similarity: face.similarity,
        threshold: 90,
        confidence: face.confidence
      };

      // Paso 2: Identidad
      swalStep('Rostro OK. Verificando nombre y apellidos…');
      r = await fetch(urlMatch, { credentials:'include' });
      j = await r.json();
      if (!j.ok) throw new Error(j.mensaje || 'Error en validación de identidad');

      const idres = j.resultado || {};
      summary.identidad = { ok: !!idres.overall, detalles: idres.detalles || {} };

      // Paso 3: Archivos
      swalStep('Obteniendo archivos…');
      let arch = await fetchArchivosResumen(ADMIN_BASE, slug);

      // Fallback: si el endpoint no devolvió nada útil, usa claves que ya vienen en save_face
      if (!arch || (arch.presentes?.length ?? 0) === 0) {
        const pr = [];
        if (face?.selfie_key)    pr.push('selfie');
        if (face?.ine_frontal)   pr.push('ine_frontal');
        if (face?.ine_reverso)   pr.push('ine_reverso');
        arch = {
          presentes: pr,
          faltantes: REQUIRED_FILE_TYPES.filter(k => !pr.includes(k)),
          comprobantes: 0
        };
      }

      // Unir presentes (si vienen por ambas vías) y recalcular faltantes
      const presentesSet = new Set(arch.presentes || []);
      arch.presentes = Array.from(presentesSet);
      arch.faltantes = REQUIRED_FILE_TYPES.filter(k => !presentesSet.has(k));

      // Resumen para la UI
      summary.archivos  = { presentes: arch.presentes, faltantes: arch.faltantes };
      summary.ingresos  = { archivos: arch.comprobantes };

      // Pintar card
      if (typeof renderAwsSummaryCard === 'function') renderAwsSummaryCard(summary);

      // Semáforo
      if (typeof loadStatus === 'function') { swalStep('Actualizando estado…'); await loadStatus(); }

      // Swal final (coherente con presentes/faltantes calculados)
      const presentList  = summary.archivos.presentes.length ? summary.archivos.presentes.join(', ') : 'Ninguno';
      const missingList  = summary.archivos.faltantes.length ? summary.archivos.faltantes.join(', ') : 'Ninguno';

      Swal.fire({
        icon: 'success',
        title: 'Validación completada',
        html: `
          <div class="text-left text-sm">
            <ul class="space-y-1">
              <li>• <b>Rostro:</b> ${ (typeof summary.rostro?.similarity === 'number' ? summary.rostro.similarity.toFixed(2) + '%' : 'N/A') } (umbral ≥ ${ (summary.rostro?.threshold ?? 90) }%) ${summary.rostro?.ok ? '✅' : '⚠️'}</li>
              <li>• <b>Identidad:</b> nombres y apellidos ${summary.identidad?.ok ? 'coinciden ✅' : 'no coinciden ⚠️'}</li>
              <li>• <b>Archivos (presentes):</b> ${presentList}</li>
              <li>• <b>Archivos (faltan):</b> ${missingList}</li>
              <li>• <b>Comprobantes:</b> ${summary.ingresos?.archivos ?? 0}</li>
            </ul>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ver detalle',
        cancelButtonText: 'Cerrar',
        background: '#18181b',
        color: '#fff',
        confirmButtonColor: '#6366f1'
      }).then(res => {
        if(res.isConfirmed){
          document.getElementById('aws-summary-card')?.scrollIntoView({behavior:'smooth', block:'start'});
        }
      });

      // Botón fin
      btn.textContent = 'Validación completada';
      btn.disabled = true;
      btn.classList.add('opacity-50','cursor-not-allowed');

    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error durante la validación',
        text: err.message || 'Intenta de nuevo.',
        background: '#18181b', color:'#fff', confirmButtonColor:'#de6868'
      });
      btn.textContent = 'Reintentar validación';
    } finally {
      delete btn.dataset.busy;
      setTimeout(()=>{ if(!btn.disabled) btn.textContent = oldTxt; }, 2000);
    }
  });
}


// ============== Arranque =====================================================
document.addEventListener('DOMContentLoaded', wireValidateButton);




  // =======================
  //  INIT
  // =======================
  document.addEventListener('DOMContentLoaded', function () {
    // Refresca semáforo al cargar (si existen elementos)
    loadStatus();
    // Cablea botón de validación AWS
    wireValidateButton();
  });

})();
</script>
<script>
(function () {
  // Helpers ----------------------------------------------------
  function $(sel, root=document){ return root.querySelector(sel); }
  function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
  function bytes(n){ if(!n && n!==0) return ''; if(n<1024) return n+' B'; if(n<1024*1024) return (n/1024).toFixed(1)+' KB'; return (n/1024/1024).toFixed(1)+' MB'; }
  async function uploadFD(url, fd){
    const res = await fetch(url, { method:'POST', body: fd, credentials:'same-origin' });
    let data=null; try{ data = await res.json(); }catch(e){ /* ignore */ }
    if(!res.ok || (data && data.ok===false)){ throw new Error((data && (data.error||data.message)) || 'Error en la subida'); }
    return data || { ok:true };
  }
  function showLoadingSwal(title){
    if (!window.Swal) return;
    Swal.fire({
      title: title || 'Subiendo...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
      background: '#18181b', color:'#fff'
    });
  }
  function closeSwal(){ if(window.Swal) Swal.close(); }
  function okSwal(msg){
    if (!window.Swal) return alert(msg||'Listo');
    Swal.fire({icon:'success', title: msg||'Listo', timer:1400, showConfirmButton:false, background:'#18181b', color:'#fff'});
  }
  function errSwal(msg){
    if (!window.Swal) return alert(msg||'Error');
    Swal.fire({icon:'error', title:'Error', text: msg||'Intenta de nuevo', background:'#18181b', color:'#fff'});
  }

  // Dropzone casero --------------------------------------------
  function initInlineDropzone(dz){
    if (!dz || dz.__inited) return;
    dz.__inited = true;

    const form   = $('.dz-form', dz);
    const input  = $('.dz-input', dz);
    const area   = $('.dz-area', dz);
    const pick   = $('.dz-pick', dz);
    const prev   = $('.dz-preview', dz);
    const thumb  = $('.dz-thumb', dz);
    const fileTx = $('.dz-file', dz);
    const btnSend= $('.dz-send', dz);
    const btnClr = $('.dz-clear', dz);

    const accept = dz.dataset.accept || '';
    const mode   = dz.dataset.mode || 'new'; // 'new' | 'replace'

    if (accept) input.setAttribute('accept', accept);

    let currentURL = null;

    function renderPreview(file){
      if(!file){ // limpiar
        if (currentURL) { URL.revokeObjectURL(currentURL); currentURL=null; }
        thumb.classList.add('hidden');
        prev.classList.add('hidden');
        fileTx.textContent = '';
        return;
      }
      const isImg = /^image\//.test(file.type);
      if (isImg) {
        currentURL = URL.createObjectURL(file);
        thumb.src = currentURL;
        thumb.classList.remove('hidden');
      } else {
        thumb.classList.add('hidden');
      }
      fileTx.textContent = (file.name || 'archivo') + ' · ' + bytes(file.size);
      prev.classList.remove('hidden');
    }

    function choose(){ input.click(); }
    function clear(){
      form.reset(); renderPreview(null);
    }

    // Eventos
    pick && pick.addEventListener('click', choose);
    area.addEventListener('click', (e)=>{
      if (e.target.closest('.dz-pick')) return;
      choose();
    });
    input.addEventListener('change', ()=>{
      const f = input.files && input.files[0];
      renderPreview(f || null);
    });
    // Drag & drop
    ['dragenter','dragover'].forEach(ev => area.addEventListener(ev, (e)=>{ e.preventDefault(); area.classList.add('ring-2','ring-pink-400'); }));
    ;['dragleave','drop'].forEach(ev => area.addEventListener(ev, (e)=>{ e.preventDefault(); area.classList.remove('ring-2','ring-pink-400'); }));
    area.addEventListener('drop', (e)=>{
      if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files[0]) return;
      input.files = e.dataTransfer.files;
      renderPreview(input.files[0]);
    });

    btnClr && btnClr.addEventListener('click', clear);

    btnSend && btnSend.addEventListener('click', async ()=>{
      const f = input.files && input.files[0];
      if (!f) { errSwal('Selecciona un archivo primero.'); return; }

      const fd = new FormData(form);
      fd.set('archivo', f);

      try{
        showLoadingSwal('Subiendo...');
        const base = window.ADMIN_BASE || window.BASE_URL || '';
        const url  = mode === 'replace'
            ? (base + '/inquilino/reemplazar_archivo')
            : (base + '/inquilino/subir-archivo');

        await uploadFD(url, fd);
        closeSwal(); okSwal('¡Hecho!');
        setTimeout(()=>location.reload(), 400);
      }catch(err){
        closeSwal(); errSwal(err?.message || 'No se pudo subir.');
      }
    });
  }

  window.toggleInlineDZ = function(id){
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden');
  };

  // Inicializa todos los dropzones al cargar
  document.addEventListener('DOMContentLoaded', ()=>{
    $all('.inline-dropzone').forEach(initInlineDropzone);
  });
})();
</script>