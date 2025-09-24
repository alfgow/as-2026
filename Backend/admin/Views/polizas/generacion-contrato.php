<?php


function montoNumeroYTexto($valor)
{
    $monto = (float) str_replace(['$', ',', ' '], '', (string)$valor);
    $entero   = floor($monto);
    $centavos = (int) round(($monto - $entero) * 100);
    $fmt = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
    $texto = strtoupper($fmt->format($entero));
    $texto = str_replace('UNO', 'UN', $texto);
    return '$' . number_format($monto, 2) . ' (' . sprintf('%s PESOS %02d/100 M.N.', $texto, $centavos) . ')';
}

?>

<section class="px-4 md:px-8 py-10 text-white">
    <div class="max-w-3xl mx-auto bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-6 shadow-xl space-y-8">
        <h1 class="text-3xl font-bold text-indigo-300 text-center flex items-center justify-center gap-3">
            <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4" />
            </svg>
            Generación de Contrato
        </h1>

        <form id="form-contrato" action="<?= $baseUrl ?>/polizas/generar-pdf-contrato" method="POST" class="space-y-6" novalidate>
            <!-- Info fija (texto) -->
            <div class="space-y-8">

                <!-- Datos Póliza -->
                <div class="bg-gray-900 p-4 rounded-xl border border-gray-700">
                    <h2 class="text-yellow-300 font-semibold mb-3 text-center">Datos Póliza</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <p><span class="font-medium text-indigo-400">Póliza:</span> <?= htmlspecialchars($poliza['numero_poliza'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">Tipo de póliza:</span> <?= htmlspecialchars($poliza['tipo_poliza'] ?? '') ?></p>
                        <p class="md:col-span-2"><span class="font-medium text-indigo-400">Vigencia:</span> <?= htmlspecialchars($poliza['vigencia'] ?? '') ?></p>
                        <p class="md:col-span-2"><span class="font-medium text-indigo-400">Costo Póliza:</span> <?= montoNumeroYTexto($poliza['monto_poliza'] ?? '') ?></p>
                    </div>
                </div>

                <!-- Datos Arrendador -->
                <div class="bg-gray-900 p-4 rounded-xl border border-gray-700">
                    <h2 class="text-yellow-300 font-semibold mb-3 text-center">Datos Arrendador</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <p><span class="font-medium text-indigo-400">Arrendador:</span> <?= htmlspecialchars($poliza['nombre_arrendador'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">ID Arrendador:</span> <?= htmlspecialchars(($poliza['tipo_id_arrendador'] ?? '') . ' ' . ($poliza['num_id_arrendador'] ?? '')) ?></p>
                        <p class="md:col-span-2"><span class="font-medium text-indigo-400">Dirección arrendador:</span> <?= htmlspecialchars($poliza['direccion_arrendador'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">Banco:</span> <?= htmlspecialchars(($poliza['banco_arrendador'] ?? '')) ?></p>
                        <p><span class="font-medium text-indigo-400">Cuenta:</span> <?= htmlspecialchars(($poliza['cuenta_arrendador'] ?? '')) ?></p>
                        <p><span class="font-medium text-indigo-400">CLABE:</span> <?= htmlspecialchars(($poliza['clabe_arrendador'] ?? '')) ?></p>
                    </div>
                </div>

                <!-- Datos Inquilino -->
                <div class="bg-gray-900 p-4 rounded-xl border border-gray-700">
                    <h2 class="text-yellow-300 font-semibold mb-3 text-center">Datos Inquilino</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <p><span class="font-medium text-indigo-400">Inquilino:</span> <?= htmlspecialchars($poliza['nombre_inquilino_completo'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">ID Inquilino:</span> <?= htmlspecialchars(($poliza['tipo_id_inquilino'] ?? '') . ' ' . ($poliza['num_id_inquilino'] ?? '')) ?></p>
                        <p><span class="font-medium text-indigo-400">Fiador:</span> <?= htmlspecialchars($poliza['nombre_fiador'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">ID Fiador:</span> <?= htmlspecialchars(($poliza['tipo_id_fiador'] ?? '') . ' ' . ($poliza['num_id_fiador'] ?? '')) ?></p>
                        <p><span class="font-medium text-indigo-400">Dirección Fiador:</span> <?= htmlspecialchars($poliza['direccion_fiador'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">Obligado solidario:</span> <?= htmlspecialchars($poliza['nombre_obligado_completo'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">ID Obligado Solidario:</span> <?= htmlspecialchars(($poliza['tipo_id_obligado'] ?? '') . ' ' . ($poliza['num_id_obligado'] ?? '')) ?></p>
                        <p><span class="font-medium text-indigo-400">Dirección Obligado solidario:</span> <?= htmlspecialchars($poliza['direccion_obligado'] ?? '') ?></p>
                    </div>
                </div>

                <!-- Datos Inmueble -->
                <div class="bg-gray-900 p-4 rounded-xl border border-gray-700">
                    <h2 class="text-yellow-300 font-semibold mb-3 text-center">Datos Inmueble</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <p class="md:col-span-2"><span class="font-medium text-indigo-400">Dirección del inmueble:</span> <?= htmlspecialchars($poliza['direccion_inmueble'] ?? '') ?></p>
                        <p><span class="font-medium text-indigo-400">Monto de renta:</span> <?= montoNumeroYTexto($poliza['monto_renta'] ?? 0) ?></p>
                        <p><span class="font-medium text-indigo-400">Cuota de mantenimiento:</span> <?= montoNumeroYTexto($poliza['monto_mantenimiento'] ?? 0) ?></p>
                        <p><span class="font-medium text-indigo-400">Incluye mantenimiento:</span> <?= htmlspecialchars($poliza['mantenimiento_inmueble'] ?? 0) ?></p>
                        <p><span class="font-medium text-indigo-400">Estacionamiento:</span> <?= htmlspecialchars($poliza['estacionamiento_inmueble'] ?? 0) ?></p>
                        <p><span class="font-medium text-indigo-400">Mascotas:</span> <?= htmlspecialchars($poliza['mascotas_inmueble'] ?? 0) ?></p>
                    </div>
                </div>

            </div>

            <!-- Campo editable: tipo de contrato -->
            <div>
                <label for="tipo_contrato" class="block mb-2 text-indigo-200">Tipo de contrato:</label>
                <select name="tipo_contrato" id="tipo_contrato" required
                    class="w-full bg-gray-900 border border-gray-700 text-white p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="" disabled selected>Selecciona una opción</option>
                    <option value="normal_pf">1. Arrendador e Inquilino - P.F.</option>
                    <option value="os_pf">2. Arrendador, Inquilino y Obligado Solidario - P.F.</option>
                    <option value="fiador_pf">3. Arrendador, Inquilino y Fiador - P.F.</option>
                    <option value="os_fiador_pf">🚨4. Arrendador, Inquilino, Obligado Solidario y Fiador - P.F.</option>
                    <option value="arr_pm_inq_pf">🚨5. Arrendador P.M. e Inquilino - P.F.</option>
                    <option value="inq_pm_arr_pf">🚨5. Arrendador P.F. e Inquilino - P.M.</option>
                    <option value="normal_pm">🚨5. Arrendador e Inquilino - P.M.</option>
                    <option value="os_pm">🚨6. Arrendador, Inquilino y Obligado Solidario - P.M.</option>
                    <option value="fiador_pm">🚨7. Arrendador, Inquilino y Fiador - P.M.</option>
                    <option value="os_fiador_pm">🚨8. Arrendador, Inquilino, Obligado Solidario y Fiador - P.M.</option>
                </select>
            </div>

            <!-- Botón -->
            <div class="text-center">
                <button type="submit"
                    class="inline-block px-6 py-3 bg-indigo-700 hover:bg-indigo-600 rounded-lg text-white font-semibold shadow transition">
                    Generar contrato
                </button>
            </div>

            <!-- Campos ocultos -->
            <input type="hidden" name="numero_poliza" value="<?= htmlspecialchars($poliza['numero_poliza']) ?>">
        </form>
    </div>
</section>

<script>
    document.getElementById('form-contrato').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const contentType = response.headers.get("Content-Type");

                if (contentType.includes("application/json")) {
                    const data = await response.json();
                    if (data.status === 'error') {
                        Swal.fire({
                            icon: null,
                            title: '😖',
                            text: data.mensaje,
                            customClass: {
                                title: 'text-6xl' // tamaño grande usando Tailwind o CSS
                            }
                        });
                    }
                } else {
                    // Si no es JSON, asumimos que es un archivo (docx)
                    const blob = await response.blob();
                    const filename = response.headers.get('Content-Disposition')
                        ?.split('filename=')[1] ?? 'contrato.docx';

                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename.replace(/['"]/g, ''); // limpia comillas
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);

                    Swal.fire({
                        icon: 'success',
                        title: 'Contrato generado',
                        text: 'La descarga comenzará en unos segundos.'
                    });
                }
            })
            .catch(error => {
                console.error('Error al generar contrato:', error);
                Swal.fire({
                    icon: 'error',
                    title: '¡Ups!',
                    text: 'Ocurrió un error al generar el contrato.'
                });
            });
    });
</script>