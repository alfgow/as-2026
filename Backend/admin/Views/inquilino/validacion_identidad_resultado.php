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
		<img src="https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png" alt="Logo" class="w-20 h-20 rounded-full mx-auto mb-4 shadow-lg">
		<h1 class="text-2xl font-bold text-indigo-700">Validación de Identidad</h1>
		<p id="estadoValidacion" class="text-sm mt-1"></p>
	</div>

	<!-- Imágenes -->
	<div class="flex justify-center items-center gap-5" id="contenedorImagenes"></div>

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
	const data = JSON.parse(localStorage.getItem("validacion_identidad_result") || "null");

	if (!data) {
		document.getElementById("estadoValidacion").textContent = "No se encontraron datos de validación.";
	} else {
		const docData = data.documentInformation?.documentData || [];
		const renapo = data.renapo?.registros?.[0] || {};
		const ineNominal = data.ineNominalList?.data || {};
		const verificacionExitosa = data.status;

		document.getElementById("estadoValidacion").innerHTML = verificacionExitosa
			? `<span class="text-green-600 font-semibold text-2xl">Verificación exitosa ✔️</span>`
			: `<span class="text-red-600 font-semibold text-2xl">Verificación fallida ✖️</span>`;

		// Imágenes
		const fotos = {
	"INE Frente": data.images?.image_front,
	"Selfie": data.images?.image_selfie,
	"INE Reverso": data.images?.image_back,
};

let imgHTML = "";
for (const [label, b64] of Object.entries(fotos)) {
	if (b64) {
		const isSelfie = label.toLowerCase().includes("selfie");
		const imageClass = isSelfie
			? "w-40 h-40 object-cover rounded-full border border-indigo-300 shadow-md ring-2 ring-indigo-100 hover:scale-105 transition-transform duration-200"
			: "w-64 h-40 object-cover rounded-2xl border border-indigo-300 shadow-md ring-2 ring-indigo-100 hover:scale-105 transition-transform duration-200";

		imgHTML += `
			<div class="text-center">
				<p class="text-sm text-black mb-1">${label}</p>
				<img src="data:image/jpeg;base64,${b64}" class="${imageClass}">
			</div>
		`;
	}
}

		document.getElementById("contenedorImagenes").innerHTML = imgHTML;

		// Utilidades
		function getValor(tipo) {
			return docData.find(e => e.type === tipo)?.value || '-';
		}
        

		const campos = [
			{ label: "Nombre", valor: getValor("Name") },
			{ label: "Apellido Paterno", valor: getValor("FatherSurname") },
			{ label: "Apellido Materno", valor: getValor("MotherSurname") },
			{ label: "CURP", valor: renapo.curp || getValor("PersonalNumber") },
			{ label: "Sexo", valor: renapo.sexo || getValor("Sex") },
			{ label: "Fecha de Nacimiento", valor: renapo.fechaNacimiento || getValor("DateOfBirth") },
			{ label: "Domicilio", valor: getValor("PermanentAddress") },
			{ label: "Sección", valor: getValor("Section") },
			{ label: "CIC", valor: ineNominal.CIC || "-" },
			{ label: "Folio", valor: getValor("DocumentNumber") },
			{ label: "Año de Registro", valor: ineNominal["Año de registro"] || "-" },
			{ label: "Vigencia", valor: getValor("DateOfExpiry") },
			{ label: "Clave de Elector (OCR)", valor: getValor("Voter_Key") },
			{ label: "Clave de Elector (Lista Nominal)", valor: ineNominal["Clave de elector"] || "-" },
		];

		let camposHTML = `
			<div class="grid grid-cols-2  gap-4">
				${campos.map(c => `
					<p><span class="text-indigo-700 font-semibold">${c.label}:</span> ${c.valor}</p>
				`).join("")}
			</div>
		`;

		document.getElementById("datosGenerales").innerHTML = `
			<h2 class="text-xl font-bold text-red-600 mb-4">Datos Generales</h2>
			${camposHTML}
		`;

        // Asume que `documentData` es tu arreglo original (el que me pasaste)
const datosINE = data.documentInformation?.documentData || [];

const camposINE = datosINE.map(campo => ({
	label: campo.name,
	valor: campo.value || '-'
}));

let ineHTML = `
	<h2 class="text-xl font-bold text-red-600 mb-4">Datos Obtenidos de la INE</h2>
	<div class="grid grid-cols-2 gap-4">
		${camposINE.map(c => `
			<p><span class="text-indigo-700 font-semibold">${c.label}:</span> ${c.valor}</p>
		`).join("")}
	</div>
`;

document.getElementById("datosINE").innerHTML = ineHTML;



	}

    document.addEventListener("DOMContentLoaded", () => {
	const ineNominalData = data.ineNominalList?.data || null;

if (ineNominalData) {
	const camposINEserver = Object.entries(ineNominalData).map(([label, valor]) => ({
		label,
		valor
	}));

	const ineServerHTML = `
		<h2 class="text-xl font-bold text-red-600 mb-4 mt-10">Información obtenida del Servidor de INE</h2>
		<div class="grid grid-cols-2 gap-4">
			${camposINEserver.map(c => `
				<p><span class="text-indigo-700 font-semibold">${c.label}:</span> ${c.valor}</p>
			`).join("")}
		</div>
	`;

	const contenedor = document.getElementById("datosINEserver");
	if (contenedor) contenedor.innerHTML = ineServerHTML;
}

});

</script>

</body>
</html>
