// === VALIDACIÓN DE IDENTIDAD Y PREVIEWS ===

// --- Validar identidad ---
if (btnValidar) {
	btnValidar.addEventListener("click", async function (e) {
		e.preventDefault();
		Swal.fire({
			title: "Validando identidad...",
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading(),
		});
		let ineFrenteBase64 = await getBase64(ineFrente);
		let ineReversoBase64 = await getBase64(ineReverso);
		let selfieBase64 = await getBase64(selfie);

		async function fetchImgBase64(src) {
			if (!src || src.includes("demo")) return null;
			const response = await fetch(src);
			const blob = await response.blob();
			return new Promise((resolve) => {
				const reader = new FileReader();
				reader.onloadend = () => resolve(reader.result.split(",")[1]);
				reader.readAsDataURL(blob);
			});
		}
		if (!ineFrenteBase64)
			ineFrenteBase64 = await fetchImgBase64(
				document.getElementById("previewFront").src
			);
		if (!ineReversoBase64)
			ineReversoBase64 = await fetchImgBase64(
				document.getElementById("previewBack").src
			);
		if (!selfieBase64)
			selfieBase64 = await fetchImgBase64(
				document.getElementById("previewSelfie").src
			);

		let jsonData = {
			ine_front: ineFrenteBase64,
			ine_back: ineReversoBase64,
			selfie: selfieBase64,
			model: "E",
		};
		if (!jsonData.ine_front || !jsonData.ine_back || !jsonData.selfie) {
			Swal.close();
			Swal.fire(
				"Faltan archivos",
				"Debes cargar las 3 imágenes para validar.",
				"warning"
			);
			return;
		}
		try {
			const res = await fetch(`${baseUrl}/assets/json.json`);
			const data = await res.json();
			Swal.close();
			if (!data.data || !data.data.status) {
				Swal.fire(
					"Error",
					data.message || "No se pudo validar la identidad",
					"error"
				);
				return;
			}
			window.validacionData = data.data;
			resultados.classList.remove("hidden");
			resultados.innerHTML = renderResultadosValidacion(data.data);
			document
				.getElementById("btnPdfContainer")
				.classList.remove("hidden");
                } catch (err) {
                        Swal.close();
                        Swal.fire(
                                "Error de red",
                                "No se pudo cargar el JSON de pruebas",
                                "error"
                        );
                }
        });
}

// --- Render de resultados de validación ---
function renderResultadosValidacion(data) {
	return `
    <div class="min-h-screen flex flex-col items-center py-8 px-2 ">
      <div id="pdfCard" class="w-full max-w-7xl rounded-2xl shadow-2xl p-8 flex flex-col gap-6 bg-white">
        <div class="flex flex-col sm:flex-row items-center gap-6">
          <div class="flex-shrink-0 w-32 h-32 rounded-xl overflow-hidden bg-gray-700 flex items-center justify-center">
            <img id="imgPortrait" src="data:image/jpeg;base64,${
				data.images["document_portrait_image"]
			}" alt="Foto INE" class="object-cover w-full h-full" />
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-indigo-700 mb-1 flex items-center gap-2">
              <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M5.121 17.804A7.962 7.962 0 0112 15c1.657 0 3.188.507 4.379 1.365M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              ${
					data.documentInformation.documentData.find(
						(e) => e.name === "Nombre Completo"
					)?.value || ""
				}
            </h2>
            <span class="text-green-600 font-semibold text-lg">
              <svg class="inline w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              ${data.status ? "Credencial válida" : "No válida"}
            </span>
            <div class="text-gray-500 text-sm mt-2">${data.message}</div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
          <div class="flex flex-col items-center">
            <span class="text-gray-400 mb-1">INE Frente</span>
            <img id="imgFront" src="data:image/jpeg;base64,${
				data.images["image_front"]
			}" class="rounded-xl shadow-lg w-full max-w-xs" />
          </div>
          <div class="flex flex-col items-center">
            <span class="text-gray-400 mb-1">Selfie</span>
            <img id="imgSelfie" src="data:image/jpeg;base64,${
				data.images["image_selfie"]
			}"
			}" class="rounded-xl shadow-lg w-full max-w-xs" />
          </div>
          <div class="flex flex-col items-center">
            <span class="text-gray-400 mb-1">INE Reverso</span>
            <img id="imgBack" src="data:image/jpeg;base64,${
				data.images["image_back"]
			}" class="rounded-xl shadow-lg w-full max-w-xs" />
          </div>
        </div>
      </div>
    </div>
  `;
}

// ==== "Ver PDF" abre vista bonita en otra tab (html2print/guardar) ====

const btnVerPdf = document.getElementById("btn-ver-pdf");
if (btnVerPdf) {
	btnVerPdf.addEventListener("click", function () {
		const data = window.validacionData;
		const html = `
        <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reporte de Validación de Identidad</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto my-8 p-8 bg-white rounded-2xl shadow-2xl space-y-10">

    <!-- LOGO Y TITULO -->
    <div class="flex flex-col items-center gap-2 mb-4">
      <img src="https://alfgow.s3.mx-central-1.amazonaws.com/LogoFinal-AS_con_fondo_blanco.png-removebg-preview+(1)+(1).png"
           alt="Arrendamiento Seguro"
           class="h-24 mb-2"/>
      <h1 class="text-3xl font-bold text-indigo-700">Reporte de Validación de Identidad</h1>
      <div class="text-gray-500 text-sm">Generado el: <span id="fecha-gen"></span></div>
    </div>

    <!-- RESUMEN GENERAL -->
    <section class="bg-white rounded-xl shadow p-6">
      <div class="flex items-center gap-4 mb-4">
        <img src="data:image/jpeg;base64,{{document_portrait_image}}"
             alt="Retrato documento"
             class="rounded-full h-20 w-20 object-cover border-2 border-indigo-300 shadow"/>
        <div>
          <h2 class="text-2xl font-bold text-indigo-800 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-width="2" d="M5.121 17.804A7.962 7.962 0 0112 15c1.657 0 3.188.507 4.379 1.365M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            VILLANUEVA QUIROZ ALFONSO
          </h2>
          <span class="text-green-600 font-semibold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Credencial válida
          </span>
          <div class="text-gray-500 text-sm">La credencial es válida</div>
        </div>
      </div>
      <div class="flex flex-wrap gap-4 mt-4 justify-between">
        <div>
          <div class="text-indigo-700 font-semibold">Similitud selfie-documento:</div>
          <div class="text-2xl font-bold text-indigo-900">99.97%</div>
        </div>
        <div>
          <div class="text-indigo-700 font-semibold">Resultado facial:</div>
          <div class="text-green-600 font-bold">Coincide la persona con la selfie</div>
        </div>
      </div>
    </section>

    <!-- IMÁGENES -->
    <section class="bg-white rounded-xl shadow p-6">
      <h3 class="text-xl font-bold text-indigo-700 mb-3">Imágenes del Documento</h3>
      <div class="flex flex-wrap gap-6 justify-center">
        <div class="flex flex-col items-center">
          <img src="data:image/jpeg;base64,{{image_front}}" class="rounded-xl shadow w-40 h-28 object-cover border" alt="INE Frente" />
          <span class="text-gray-500 mt-2">INE Frente</span>
        </div>
        <div class="flex flex-col items-center">
          <img src="data:image/jpeg;base64,{{image_selfie}}" class="rounded-xl shadow w-32 h-32 object-cover border" alt="Selfie" />
          <span class="text-gray-500 mt-2">Selfie</span>
        </div>
        <div class="flex flex-col items-center">
          <img src="data:image/jpeg;base64,{{image_back}}" class="rounded-xl shadow w-40 h-28 object-cover border" alt="INE Reverso" />
          <span class="text-gray-500 mt-2">INE Reverso</span>
        </div>
      </div>
    </section>

    <!-- DATOS DEL INE (OCR/MRZ) -->
    <section class="bg-white rounded-xl shadow p-6">
      <h3 class="text-xl font-bold text-indigo-700 mb-4">Datos del INE</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <div><span class="font-semibold text-indigo-800">Nombre:</span> VILLANUEVA QUIROZ ALFONSO</div>
          <div><span class="font-semibold text-indigo-800">CURP:</span> VIQA890831HDFLRL03</div>
          <div><span class="font-semibold text-indigo-800">Sexo:</span> H</div>
          <div><span class="font-semibold text-indigo-800">Nacionalidad:</span> MEX</div>
          <div><span class="font-semibold text-indigo-800">Fecha de Nacimiento:</span> 31/08/1989</div>
          <div><span class="font-semibold text-indigo-800">Expira:</span> 31/12/2029</div>
        </div>
        <div>
          <div><span class="font-semibold text-indigo-800">Clave de Elector:</span> VLQRAL89083109H400</div>
          <div><span class="font-semibold text-indigo-800">Número de Documento:</span> 192441502</div>
          <div><span class="font-semibold text-indigo-800">Dirección:</span> COL GUERRERO 06300 CUAUHTEMOC, CDMX</div>
          <div><span class="font-semibold text-indigo-800">Provincia/Estado:</span> 09</div>
          <div><span class="font-semibold text-indigo-800">Municipio:</span> 015</div>
          <div><span class="font-semibold text-indigo-800">Sección:</span> 4689</div>
        </div>
      </div>
      <div class="mt-4">
        <h4 class="text-indigo-700 font-semibold mb-2">Información Técnica (OCR/MRZ)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
          <div><span class="font-semibold">MRZ:</span> IDMEX1924415024&lt;&lt;4689078854743</div>
          <div><span class="font-semibold">Fecha Nacimiento:</span> 890831 (31/08/1989)</div>
          <div><span class="font-semibold">Fecha Expiración:</span> 291231 (31/12/2029)</div>
          <div><span class="font-semibold">Número de Duplicados:</span> 04</div>
        </div>
      </div>
    </section>

    <!-- VALIDACIONES DEL DOCUMENTO -->
    <section class="bg-white rounded-xl shadow p-6">
      <h3 class="text-xl font-bold text-indigo-700 mb-4">Validaciones del Documento</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <div class="font-semibold text-indigo-600">Integridad:</div>
          <div>Nombre Completo: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Sexo: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Número de Documento: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Fecha de Expiración: <span class="text-green-700 font-semibold">Ok</span></div>
        </div>
        <div class="flex flex-col gap-2">
          <div class="font-semibold text-indigo-600">Formato MRZ:</div>
          <div>Dígito de control: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Fecha de Nacimiento: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Fecha de Expiración: <span class="text-green-700 font-semibold">Ok</span></div>
          <div>Dimensiones correctas: <span class="text-green-700 font-semibold">Ok</span></div>
        </div>
      </div>
      <div class="mt-3">
        <div class="font-semibold text-indigo-600 mb-1">Comprobación Facial:</div>
        <span class="text-green-700 font-semibold">99.97% Similitud. Coincide la persona con la selfie.</span>
      </div>
    </section>

    <!-- INE NOMINAL LIST -->
    <section class="bg-white rounded-xl shadow p-6">
      <h3 class="text-xl font-bold text-indigo-700 mb-3">INE - Información Nominal</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
        <div><span class="font-semibold">CIC:</span> 192441502</div>
        <div><span class="font-semibold">Clave de elector:</span> VLQRAL89083109H400</div>
        <div><span class="font-semibold">Número de emisión:</span> 4</div>
        <div><span class="font-semibold">Distrito Federal:</span> 12</div>
        <div><span class="font-semibold">Distrito Local:</span> 9</div>
        <div><span class="font-semibold">Número OCR:</span> 4689078854743</div>
        <div><span class="font-semibold">Año de registro:</span> 2007</div>
        <div><span class="font-semibold">Año de emisión:</span> 2019</div>
      </div>
      <div class="mt-2 text-green-700 text-sm font-semibold">
        La credencial es válida
      </div>
    </section>

    <!-- RENAPO -->
    <section class="bg-white rounded-xl shadow p-6">
      <h3 class="text-xl font-bold text-indigo-700 mb-3">RENAPO</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
        <div><span class="font-semibold">CURP:</span> VIQA890831HDFLRL03</div>
        <div><span class="font-semibold">Nombre:</span> ALFONSO</div>
        <div><span class="font-semibold">Apellido Paterno:</span> VILLANUEVA</div>
        <div><span class="font-semibold">Apellido Materno:</span> QUIROZ</div>
        <div><span class="font-semibold">Sexo:</span> Hombre</div>
        <div><span class="font-semibold">Fecha Nacimiento:</span> 1989-08-31</div>
        <div><span class="font-semibold">Nacionalidad:</span> MEX</div>
        <div><span class="font-semibold">Status:</span> Registro de cambio no afectando a CURP</div>
        <div><span class="font-semibold">Documento Probatorio:</span> Acta de nacimiento</div>
      </div>
      <div class="mt-2 text-green-700 text-sm font-semibold">
        Búsqueda exitosa por curp
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="text-center mt-10 text-xs text-gray-400">
      Arrendamiento Seguro &copy; 2025 &bull; www.arrendamientoseguro.app
    </footer>
  </div>

  <script>
    // Mostrar fecha/hora actual
    document.getElementById('fecha-gen').textContent = new Date().toLocaleString('es-MX');
  </script>
</body>
</html>

        `;
		const newWindow = window.open("", "_blank");
		if (newWindow) {
			newWindow.document.open();
			newWindow.document.write(html);
			newWindow.document.close();
		} else {
			alert("Por favor, permite ventanas emergentes para ver el PDF.");
		}
	});
}

// ==== PDF descarga directa: igual que tenías, solo lo dejamos ====
document
	.getElementById("btn-descargar-pdf")
	.addEventListener("click", function () {
		Swal.fire({
			title: "Generando documento PDF...",
			text: "Esto puede tardar unos segundos.",
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading(),
		});
		generarPDFValidacion(window.validacionData).then(() => {
			Swal.close();
		});
	});

// --- FUNCION DE PDF (idéntica a la versión anterior) ---
async function generarPDFValidacion(jsonCompleto) {
	Swal.fire({
		title: "Generando documento PDF...",
		allowOutsideClick: false,
		didOpen: () => Swal.showLoading(),
	});

	// --- Preparar datos y helpers ---
	let parsedData = null;
	if (jsonCompleto.documentInformation) {
		parsedData = jsonCompleto;
	} else if (jsonCompleto.data && jsonCompleto.data.documentInformation) {
		parsedData = jsonCompleto.data;
	}
	if (!parsedData || !parsedData.documentInformation) {
		Swal.close();
		Swal.fire(
			"Error",
			"No se encontró la información del documento",
			"error"
		);
		return;
	}
	const data = parsedData;
	const get = (name, fallback = "-") => {
		const found = data.documentInformation.documentData.find(
			(e) => e.name === name
		);
		return found ? found.value : fallback;
	};
	const images = data.images || {};

	// --- PDF setup ---
	const { jsPDF } = window.jspdf;
	const doc = new jsPDF({
		orientation: "portrait",
		unit: "mm",
		format: "a4",
	});
	const pageW = doc.internal.pageSize.getWidth();
	const marginX = 20;
	let y = 18;
	const cardGap = 14;

	// --- Logo centrado ---
	const logoUrl = `${baseUrl}/assets/logo.png`;
	const logo = await new Promise((res) => {
		let img = new window.Image();
		img.crossOrigin = "anonymous";
		img.src = logoUrl;
		img.onload = () => res(img);
	});
	// Mantener proporción, más chico
	const logoW = 44,
		logoH = 26;
	const logoX = (pageW - logoW) / 2;
	doc.addImage(logo, "PNG", logoX, y, logoW, logoH, "", "FAST");
	y += logoH + 6;

	// --- Título principal ---
	doc.setFont("helvetica", "bold");
	doc.setFontSize(20);
	doc.setTextColor(72, 74, 186); // Indigo
	doc.text("Reporte de Validación de Identidad", pageW / 2, y, {
		align: "center",
	});
	y += 9;
	doc.setFont("helvetica", "normal");
	doc.setFontSize(12);
	doc.setTextColor(60, 64, 70);
	doc.text(
		`Generado el: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}`,
		pageW / 2,
		y,
		{ align: "center" }
	);
	y += 7;

	// --- Estado de validación ---
	doc.setFont("helvetica", "bold");
	doc.setFontSize(13);
	doc.setTextColor(
		data.status ? 44 : 200,
		data.status ? 160 : 70,
		data.status ? 220 : 60
	);
	doc.text(`${data.message}`, pageW / 2, y, { align: "center" });
	y += 10;

	// --- "Card" Datos del INE ---
	function printCard(title, fields, y) {
		const cardW = pageW - marginX * 2,
			cardH = fields.length * 9 + 21;
		// Fondo card
		doc.setFillColor(244, 246, 249);
		doc.roundedRect(marginX, y, cardW, cardH, 8, 8, "F");
		let infoY = y + 13,
			infoX = marginX + 10;
		doc.setFont("helvetica", "bold");
		doc.setFontSize(15);
		doc.setTextColor(72, 74, 186);
		doc.text(title, infoX, infoY);
		infoY += 8;
		doc.setFont("helvetica", "normal");
		doc.setFontSize(12.5);
		for (let i = 0; i < fields.length; i++) {
			doc.setTextColor(72, 74, 186);
			doc.setFont("helvetica", "bold");
			doc.text(fields[i][0] + ":", infoX, infoY);
			doc.setTextColor(44, 62, 80);
			doc.setFont("helvetica", "normal");
			doc.text(fields[i][1] || "-", infoX + 52, infoY);
			infoY += 8.2;
		}
		return y + cardH + cardGap;
	}
	y = printCard(
		"Datos del INE",
		[
			["Nombre", get("Nombre Completo")],
			["CURP", get("Número Personal")],
			["Sexo", get("Sexo")],
			["Nacionalidad", get("Nacionalidad")],
			["Fecha de Nacimiento", get("Fecha de Nacimiento")],
			["Expira", get("Fecha de Expiración")],
			["Clave de Elector", get("Clave de Elector")],
			["Dirección", get("Dirección")],
		],
		y
	);

	// --- Imágenes centradas (otra "card") ---
	if (
		images.image_front ||
		images.image_back ||
		images.image_selfie ||
		images.document_portrait_image
	) {
		let imgLabels = [];
		if (images.image_front)
			imgLabels.push({ label: "INE Frente", base64: images.image_front });
		if (images.image_selfie)
			imgLabels.push({ label: "Selfie", base64: images.image_selfie });
		if (images.image_back)
			imgLabels.push({ label: "INE Reverso", base64: images.image_back });
		if (images.document_portrait_image)
			imgLabels.push({
				label: "Retrato Doc.",
				base64: images.document_portrait_image,
			});

		const imgCardH = 62;
		doc.setFillColor(244, 246, 249);
		doc.roundedRect(marginX, y, pageW - marginX * 2, imgCardH, 8, 8, "F");
		let imgY = y + 13;
		doc.setFont("helvetica", "bold");
		doc.setFontSize(15);
		doc.setTextColor(72, 74, 186);
		doc.text("Imágenes del Documento", marginX + 10, imgY);
		imgY += 4;
		let imgW = 28,
			imgH = 28,
			gap = 21;
		let totalWidth = imgLabels.length * imgW + (imgLabels.length - 1) * gap;
		let startX = pageW / 2 - totalWidth / 2;
		for (let i = 0; i < imgLabels.length; i++) {
			let img =
				"data:image/jpeg;base64," +
				imgLabels[i].base64.replace(
					/^data:image\/(jpeg|png);base64,/,
					""
				);
			doc.addImage(img, "JPEG", startX, imgY + 6, imgW, imgH, "", "FAST");
			// Etiqueta bajo la imagen
			doc.setFont("helvetica", "normal");
			doc.setFontSize(10.5);
			doc.setTextColor(72, 74, 186);
			doc.text(
				imgLabels[i].label,
				startX + imgW / 2,
				imgY + 6 + imgH + 6,
				{ align: "center" }
			);
			startX += imgW + gap;
		}
		y += imgCardH + cardGap;
	}

	// --- Validaciones del documento ("card" con lista) ---
	let verifs = data.documentInformation.documentVerifications;
	let vLines =
		verifs.length +
		verifs.reduce(
			(acc, v) => acc + (v.inputFields ? v.inputFields.length : 0),
			0
		);
	let vCardH = Math.max(42, 13 + vLines * 6.6 + 4);
	if (y + vCardH > 270) {
		doc.addPage();
		y = 20;
	}
	doc.setFillColor(244, 246, 249);
	doc.roundedRect(marginX, y, pageW - marginX * 2, vCardH, 8, 8, "F");
	let vy = y + 13,
		vx = marginX + 10;
	doc.setFont("helvetica", "bold");
	doc.setFontSize(15);
	doc.setTextColor(72, 74, 186);
	doc.text("Validaciones del Documento", vx, vy);
	vy += 8;
	doc.setFont("helvetica", "normal");
	doc.setFontSize(10.5);
	for (let v of verifs) {
		doc.setTextColor(54, 180, 163);
		doc.text("•", vx, vy);
		doc.setTextColor(72, 74, 186);
		doc.setFont("helvetica", "bold");
		doc.text(v.name, vx + 5, vy);
		doc.setTextColor(38, 50, 56);
		doc.setFont("helvetica", "normal");
		doc.text(`[${v.result}]`, vx + 95, vy);
		vy += 6.1;
		if (v.inputFields && v.inputFields.length > 0) {
			for (let f of v.inputFields) {
				doc.setTextColor(180, 180, 180);
				doc.text(`- ${f.name.replace(/^\d+_/, "")}: `, vx + 9, vy);
				doc.setTextColor(44, 62, 80);
				let value = f.value?.toString().substring(0, 48) || "";
				doc.text(value, vx + 49, vy);
				vy += 5.3;
			}
		}
		if (vy > 265) {
			doc.addPage();
			vy = 23;
		}
	}
	y += vCardH + cardGap;

	// --- INE Nominal List ("card") ---
	if (data.ineNominalList && data.ineNominalList.data) {
		const nl = data.ineNominalList;
		let nlines = Object.keys(nl.data).length + 1;
		let nlH = 13 + nlines * 7 + 8;
		if (y + nlH > 270) {
			doc.addPage();
			y = 20;
		}
		doc.setFillColor(244, 246, 249);
		doc.roundedRect(marginX, y, pageW - marginX * 2, nlH, 8, 8, "F");
		let nly = y + 13;
		doc.setFont("helvetica", "bold");
		doc.setFontSize(15);
		doc.setTextColor(72, 74, 186);
		doc.text("INE - Información Nominal", marginX + 10, nly);
		nly += 8;
		doc.setFont("helvetica", "normal");
		doc.setFontSize(11);
		for (let [k, v] of Object.entries(nl.data)) {
			doc.setTextColor(72, 74, 186);
			doc.setFont("helvetica", "bold");
			doc.text(k + ":", marginX + 10, nly);
			doc.setTextColor(44, 62, 80);
			doc.setFont("helvetica", "normal");
			doc.text(v || "-", marginX + 10 + 40, nly);
			nly += 7.2;
		}
		y += nlH + cardGap;
	}

	// --- RENAPO ("card") ---
	if (data.renapo && data.renapo.registros && data.renapo.registros.length) {
		const r = data.renapo.registros[0];
		let rH = 62;
		if (y + rH > 270) {
			doc.addPage();
			y = 20;
		}
		doc.setFillColor(244, 246, 249);
		doc.roundedRect(marginX, y, pageW - marginX * 2, rH, 8, 8, "F");
		let ry = y + 13;
		doc.setFont("helvetica", "bold");
		doc.setFontSize(15);
		doc.setTextColor(72, 74, 186);
		doc.text("RENAPO", marginX + 10, ry);
		ry += 8;
		doc.setFont("helvetica", "normal");
		doc.setFontSize(11);
		let camposRenapo = [
			["CURP", r.curp],
			["Nombre", r.nombres],
			["Apellido Paterno", r.primerApellido],
			["Apellido Materno", r.segundoApellido],
			["Sexo", r.sexo],
			["F. Nacimiento", r.fechaNacimiento],
			["Nacionalidad", r.nacionalidad],
			["Doc. Probatorio", r.docProbatorioDesc],
			["Status", r.statusCurpDesc],
		];
		for (let field of camposRenapo) {
			doc.setTextColor(72, 74, 186);
			doc.setFont("helvetica", "bold");
			doc.text(field[0] + ":", marginX + 10, ry);
			doc.setTextColor(44, 62, 80);
			doc.setFont("helvetica", "normal");
			doc.text(field[1] || "-", marginX + 10 + 40, ry);
			ry += 7;
		}
		y += rH + cardGap;
	}

	// --- Footer ---
	doc.setFont("helvetica", "normal");
	doc.setFontSize(10);
	doc.setTextColor(135, 140, 140);
	doc.text(
		"Arrendamiento Seguro | www.arrendamientoseguro.app",
		pageW / 2,
		292,
		{ align: "center" }
	);

	Swal.close();
	let nombre =
		get("Nombre Completo").replace(/\s+/g, "_") || "validacion_identidad";
	doc.save(`validacion_identidad_${nombre}.pdf`);
}
