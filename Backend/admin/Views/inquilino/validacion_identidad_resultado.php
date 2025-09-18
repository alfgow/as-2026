<?php
require_once __DIR__ . '/../../Helpers/TextHelper.php';

use App\Helpers\TextHelper;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Resultado de Validación</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white text-gray-800">

    <div class="min-h-screen p-6 flex flex-col items-center">

        <!-- Logo y encabezado -->
        <div class="text-center mb-6">
            <img src="https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png"
                alt="Logo"
                class="w-20 h-20 rounded-full mx-auto mb-4 shadow-lg">
            <h1 class="text-2xl font-bold text-indigo-700">Validación de Identidad</h1>
            <p id="estadoValidacion" class="text-sm mt-1"></p>
        </div>

        <!-- Imágenes -->
        <div class="flex justify-center items-center gap-5 flex-wrap" id="contenedorImagenes"></div>

        <!-- Datos Generales -->
        <div class="w-full max-w-5xl bg-white border border-gray-200 rounded-2xl shadow-xl p-6" id="datosGenerales"></div>

        <div id="datosINEserver" class="w-full max-w-5xl bg-white border border-gray-200 rounded-2xl shadow-xl p-6"></div>

        <div id="datosINE" class="w-full max-w-5xl bg-white border border-gray-200 rounded-2xl shadow-xl p-6"></div>
    </div>

    <!-- Footer Corporativo -->
    <footer class="w-full bg-gray-100 border-t border-gray-200 py-6 text-center mt-10">
        <p class="text-sm text-gray-600">
            &copy; <?= date('Y') ?> Arrendamiento Seguro · Todos los derechos reservados
        </p>
    </footer>

</body>

</html>

<script>
    // 👇 Inyectamos PHP -> JS
    const data = <?= json_encode($inquilino, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>;

    if (!data) {
        document.getElementById("estadoValidacion").textContent = "No se encontraron datos de validación.";
    } else {
        const validaciones = data.validaciones_data || {};
        const verificacionExitosa = Object.values(validaciones).some(v => v?.payload?.estado === "confirmado");

        document.getElementById("estadoValidacion").innerHTML = verificacionExitosa ?
            `<span class="text-green-600 font-semibold text-2xl">Verificación exitosa ✔️</span>` :
            `<span class="text-red-600 font-semibold text-2xl">Verificación pendiente ✖️</span>`;

        // -------------------------------
        // Imágenes desde $inquilino['archivos']
        // -------------------------------
        const archivos = data.archivos || [];

        // Armamos en orden específico
        const fotos = [];

        const ineFrente = archivos.find(a => a.tipo === "ine_frontal");
        const selfie = archivos.find(a => a.tipo === "selfie");
        const ineReverso = archivos.find(a => a.tipo === "ine_reverso");

        if (ineFrente) fotos.push({
            label: "INE Frente",
            url: ineFrente.url
        });
        if (selfie) fotos.push({
            label: "Selfie",
            url: selfie.url
        });
        if (ineReverso) fotos.push({
            label: "INE Reverso",
            url: ineReverso.url
        });

        let imgHTML = "";
        fotos.forEach(f => {
            const isSelfie = f.label.toLowerCase().includes("selfie");
            const imageClass = isSelfie ?
                "w-40 h-40 object-cover rounded-full border border-indigo-300 shadow-md ring-2 ring-indigo-100 hover:scale-105 transition-transform duration-200" :
                "w-64 h-40 object-cover rounded-2xl border border-indigo-300 shadow-md ring-2 ring-indigo-100 hover:scale-105 transition-transform duration-200";

            imgHTML += `
        <div class="text-center">
            <p class="text-sm text-black mb-1">${f.label}</p>
            <img src="${f.url}" class="${imageClass}">
        </div>
    `;
        });

        document.getElementById("contenedorImagenes").innerHTML = imgHTML;


        // -------------------------------
        // Datos Generales
        // -------------------------------
        const campos = [{
                label: "Nombre completo",
                valor: data.nombre_inquilino || "-"
            },
            {
                label: "Email",
                valor: data.email || "-"
            },
            {
                label: "Celular",
                valor: data.celular || "-"
            },
            {
                label: "RFC",
                valor: data.rfc || "-"
            },
            {
                label: "CURP",
                valor: data.curp || "-"
            },
            {
                label: "Nacionalidad",
                valor: data.nacionalidad || "-"
            },
            {
                label: "Estado Civil",
                valor: data.estadocivil || "-"
            },
            {
                label: "Tipo ID",
                valor: data.tipo_id || "-"
            },
            {
                label: "Número ID",
                valor: data.num_id || "-"
            },
            {
                label: "Dirección",
                valor: data.direccion ?
                    `${data.direccion.calle || ""} ${data.direccion.num_exterior || ""} ${data.direccion.num_interior || ""}, ${data.direccion.colonia || ""}, ${data.direccion.alcaldia || ""}, ${data.direccion.ciudad || ""}, CP ${data.direccion.codigo_postal || ""}` : "-"
            },
            {
                label: "IP",
                valor: data.ip || "-"
            },
            {
                label: "Device ID",
                valor: data.device_id || "-"
            }
        ];

        let camposHTML = `
            <div class="grid grid-cols-2 gap-4">
                ${campos.map(c => `
                    <p><span class="text-indigo-700 font-semibold">${c.label}:</span> ${c.valor}</p>
                `).join("")}
            </div>
        `;

        document.getElementById("datosGenerales").innerHTML = `
            <h2 class="text-xl font-bold text-red-600 mb-4">Datos Generales</h2>
            ${camposHTML}
        `;

        // -------------------------------
        // Validaciones (render dinámico)
        // -------------------------------
        if (validaciones) {
            const secciones = Object.entries(validaciones).map(([tipo, val]) => {
                return `
                    <div class="w-full max-w-5xl bg-white border border-gray-200 rounded-2xl shadow-xl p-6 my-6">
                        <h2 class="text-xl font-bold text-red-600 mb-4 capitalize">Validación ${tipo}</h2>
                        <p><span class="font-semibold text-indigo-700">Resumen:</span> ${val.resumen || "-"}</p>
                        <p><span class="font-semibold text-indigo-700">Estado:</span> ${val.payload?.estado || "-"}</p>
                        <p><span class="font-semibold text-indigo-700">Usuario:</span> ${val.payload?.usuario || "-"}</p>
                        <p><span class="font-semibold text-indigo-700">Origen:</span> ${val.payload?.origen || "-"}</p>
                        <p><span class="font-semibold text-indigo-700">Fecha actualización:</span> ${val.updated_at || "-"}</p>
                    </div>
                `;
            }).join("");

            document.getElementById("datosINE").innerHTML = secciones;
        }
    }
</script>