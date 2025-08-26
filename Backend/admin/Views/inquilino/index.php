

<section class="px-4 md:px-8 py-10 text-white">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-indigo-300 flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 17v-2a4 4 0 014-4h4m0 0l-5-5m5 5l-5 5" />
            </svg>
            Lista de inquilinos
        </h1>
    </div>

    <?php
    // Normaliza GET
    $q       = trim($_GET['q'] ?? '');
    $tipo    = $_GET['tipo']    ?? '';
    $estatus = $_GET['estatus'] ?? '';

    // Helper para construir URL manteniendo filtros
    $buildUrl = function(array $override = []) use ($q, $tipo, $estatus, $baseUrl) {
        $params = array_merge(
            ['q' => $q, 'tipo' => $tipo, 'estatus' => $estatus],
            $override
        );
        return $baseUrl . '/inquilino/index?' . http_build_query($params);
    };
    ?>

    <div class="space-y-6">
        <!-- Filtros -->
        <form method="GET" action="<?= $baseUrl ?>/inquilino/index"
              class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <label class="block text-sm text-indigo-200 mb-1">Buscar</label>
                <input type="text" name="q"
                       value="<?= htmlspecialchars($q) ?>"
                       placeholder="Nombre, email o teléfono"
                       class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <!-- Tipo (valores corregidos) -->
            <div>
                <label class="block text-sm text-indigo-200 mb-1">Tipo</label>
                <select name="tipo"
                        class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
                    <option value="">Todos</option>
                    <option value="Arrendatario"      <?= $tipo === 'Arrendatario' ? 'selected' : '' ?>>Arrendatario</option>
                    <option value="Fiador"            <?= $tipo === 'Fiador' ? 'selected' : '' ?>>Fiador</option>
                    <option value="Obligado Solidario"<?= $tipo === 'Obligado Solidario' ? 'selected' : '' ?>>Obligado Solidario</option>
                </select>
            </div>

            <!-- Estatus (si tu modelo los entiende) -->
            <div>
                <label class="block text-sm text-indigo-200 mb-1">Estatus</label>
                <select name="estatus"
                        class="w-full bg-gray-800 border border-indigo-700 text-white px-4 py-2 rounded-lg">
                    <option value="">Todos</option>
                    <option value="1"   <?= $estatus === 'Nuevo' ? 'selected' : '' ?>>Nuevo</option>
                    <option value="2"  <?= $estatus === 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="3" <?= $estatus === 'Rechazado' ? 'selected' : '' ?>>Rechazado</option>
                    <option value="4" <?= $estatus === 'Problemático' ? 'selected' : '' ?>>Problemático</option>
                </select>
            </div>

            <!-- Botón -->
            <div class="md:col-span-4 text-right">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-lg font-semibold shadow transition mt-2 md:mt-0">
                    Aplicar filtros
                </button>
            </div>
        </form>

        <?php if (!empty($inquilinos)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($inquilinos as $p): ?>
                    <?php
                    $nombreCompleto = trim(
                        ($p['nombre_inquilino'] ?? '') . ' ' .
                        ($p['apellidop_inquilino'] ?? '') . ' ' .
                        ($p['apellidom_inquilino'] ?? '')
                    );
                    $fechaReg = !empty($p['fecha']) ? date('d/m/Y', strtotime($p['fecha'])) : '-';
                    $email    = $p['email']   ?? '-';
                    $celular  = $p['celular'] ?? '-';


                    $mapaEstatus = [
                        '1' => [
                            'icono' => '<svg class="w-4 h-4" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"></path></svg>',
                            'clase' => 'bg-indigo-700 text-indigo-100'
                        ],
                        '2' => [
                            'icono' => '<svg class="w-4 h-4" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"></path></svg>',
                            'clase' => 'bg-green-700 text-green-100'
                        ],
                        '3' => [
                            'icono' => '<svg class="w-4 h-4" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>',
                            'clase' => 'bg-red-700 text-red-100'
                        ],
                        '4' => [
                            'icono' => '<svg class="w-4 h-4" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path></svg>',
                            'clase' => 'bg-yellow-700 text-yellow-100'
                        ],
                    ];


                    $estatusID    = (string)($p['status'] ?? '');
                    $estatusTexto = $mapaEstatus[$estatusID]['texto'] ?? 'Desconocido';
                    $estatusClase = $mapaEstatus[$estatusID]['clase'] ?? 'bg-gray-600 text-white';

                    $mapaTipo = [
                        'Arrendatario' => ['clase' => 'bg-blue-700 text-blue-100', 'texto' => 'Arrendatario'],
                        'Fiador' => ['clase' => 'bg-purple-700 text-purple-100', 'texto' => 'Fiador'],
                        'Obligado Solidario' => ['clase' => 'bg-pink-700 text-pink-100', 'texto' => 'Obligado Solidario'],
                    ];

                    // Detecta el tipo y asigna clase
                    $tipoTexto = $mapaTipo[$p['tipo']]['texto'] ?? $p['tipo'];
                    $tipoClase = $mapaTipo[$p['tipo']]['clase'] ?? 'bg-gray-700 text-gray-100';



                    ?>
                    <div class="bg-gray-900 rounded-2xl shadow-lg p-5 flex flex-col justify-between border border-white/10">
                        <div>
                            <h2 class="text-lg font-bold text-indigo-300 mb-2 flex items-center gap-2">
                                <?= htmlspecialchars($nombreCompleto ?: 'Sin nombre') ?>
                            </h2>
                            <p class="text-sm text-gray-300 mb-1">Email: <?= htmlspecialchars($email) ?></p>
                            <p class="text-sm text-gray-300 mb-1">Teléfono: <?= htmlspecialchars($celular) ?></p>
                            <p class="text-sm text-gray-400 italic">Fecha de Registro: <?= $fechaReg ?></p>
                        </div>
                        <div class="mt-4 flex justify-between items-center">
                            <!-- Botón Ver detalle -->
                            <a href="<?= $baseUrl ?>/inquilino/<?= urlencode($p['slug']) ?>"
                            class="px-4 py-2 rounded-lg font-semibold shadow-lg text-white text-sm
                                    bg-gradient-to-r from-indigo-500 via-indigo-600 to-pink-500
                                    hover:from-indigo-400 hover:via-indigo-500 hover:to-pink-400
                                    transition duration-300 ease-in-out">
                                Ver detalle
                            </a>

                            <!-- Contenedor de Tipo y Estatus -->
                            <div class="flex items-center gap-2">
                                <!-- Badge tipo -->
                                <span class="text-xs px-2 py-1 rounded-full <?= $tipoClase ?>">
                                    <?= htmlspecialchars($tipoTexto) ?>
                                </span>

                                <!-- Badge estatus -->
                                <span class="flex items-center justify-center w-8 h-8 rounded-full <?= $estatusClase ?>">
                                    <?= $mapaEstatus[$estatusID]['icono'] ?? '' ?>
                                </span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-gray-400 text-sm italic py-10">
                No se encontraron inquilinos con ese criterio.
            </div>
        <?php endif; ?>

        <!-- Paginación estilo imagen 2 -->
        <?php if (($totalPaginas ?? 1) > 1): ?>
            <?php
            // Ventana de páginas
            $paginaActual = max(1, (int)($paginaActual ?? 1));
            $totalPaginas = (int)$totalPaginas;
            $window = 1; // páginas a los lados de la actual

            // Calcula las páginas a mostrar
            $pages = [];

            $pages[] = 1;
            for ($i = $paginaActual - $window; $i <= $paginaActual + $window; $i++) {
                if ($i > 1 && $i < $totalPaginas) $pages[] = $i;
            }
            if ($totalPaginas > 1) $pages[] = $totalPaginas;

            $pages = array_values(array_unique(array_filter($pages, fn($n)=>$n>=1 && $n<= $totalPaginas)));
            sort($pages);

            // Construye segmentos con elipsis
            $segments = [];
            $prev = null;
            foreach ($pages as $pnum) {
                if ($prev !== null && $pnum > $prev + 1) $segments[] = '...';
                $segments[] = $pnum;
                $prev = $pnum;
            }
            ?>
            <nav class="mt-8 flex justify-center items-center gap-2 text-sm text-indigo-300">
                <!-- Prev -->
                <a href="<?= $buildUrl(['pagina' => max(1, $paginaActual - 1)]) ?>"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-indigo-800 hover:bg-indigo-800 <?= $paginaActual === 1 ? 'pointer-events-none opacity-40' : '' ?>">
                    <span class="sr-only">Anterior</span>
                    &lt;
                </a>

                <!-- Números -->
                <?php foreach ($segments as $seg): ?>
                    <?php if ($seg === '...'): ?>
                        <span class="px-2">…</span>
                    <?php else: ?>
                        <a href="<?= $buildUrl(['pagina' => $seg]) ?>"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-indigo-800
                                  <?= $seg == $paginaActual ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-indigo-800' ?>">
                            <?= $seg ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Next -->
                <a href="<?= $buildUrl(['pagina' => min($totalPaginas, $paginaActual + 1)]) ?>"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-indigo-800 hover:bg-indigo-800 <?= $paginaActual === $totalPaginas ? 'pointer-events-none opacity-40' : '' ?>">
                    <span class="sr-only">Siguiente</span>
                    &gt;
                </a>
            </nav>
        <?php endif; ?>
    </div>
</section>
