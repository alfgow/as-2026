const baseUrl = document.getElementById("baseUrl").value;
const container = document.getElementById("resultadosValidacion");
const btnContainer = document.getElementById("btnPdfContainer");

function renderResultadosValidacion(data) {
	// --- Imágenes (Frente, Selfie, Reverso) ---
	const cardImagenes = `
        <div class="w-full flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8 mb-6">
            <div class="flex flex-col items-center bg-[#232348] rounded-xl p-4 shadow-md w-full max-w-xs">
                <img src="data:image/jpeg;base64,${data.images.image_front}" 
                    class="w-full max-w-[180px] h-auto object-cover rounded-lg mb-3 border-4 border-indigo-500 bg-white shadow"
                    alt="Frente">
                <div class="text-indigo-100 font-semibold text-base text-center">Frente</div>
            </div>
            <div class="flex flex-col items-center bg-[#322845] rounded-xl p-5 shadow-md w-full max-w-xs">
                <img src="data:image/jpeg;base64,${data.images.image_selfie}" 
                    class="w-28 h-28 object-cover rounded-full border-4 border-pink-300 mb-3 shadow"
                    alt="Selfie">
                <div class="text-pink-200 font-semibold text-base text-center">Selfie</div>
            </div>
            <div class="flex flex-col items-center bg-[#232348] rounded-xl p-4 shadow-md w-full max-w-xs">
                <img src="data:image/jpeg;base64,${data.images.image_back}" 
                    class="w-full max-w-[180px] h-auto object-cover rounded-lg mb-3 border-4 border-indigo-500 bg-white shadow"
                    alt="Reverso">
                <div class="text-indigo-100 font-semibold text-base text-center">Reverso</div>
            </div>
        </div>
    `;

	// --- Datos del documento ---
	const rows = (data.documentInformation.documentData || [])
		.map(
			(d) => `
                <div class="flex justify-between py-0.5 text-sm">
                    <div class="text-indigo-200 font-medium">${d.name}</div>
                    <div class="text-white">${d.value}</div>
                </div>
            `
		)
		.join("");
	const cardDatos = `
        <div class="rounded-2xl bg-[#232350] p-4 shadow w-full min-w-0">
            <div class="mb-3 text-indigo-300 font-bold text-base">Datos del Documento</div>
            ${rows || '<div class="text-indigo-400">Sin datos.</div>'}
        </div>
    `;

	// --- Validaciones ---
	const verifs = (data.documentInformation.documentVerifications || [])
		.map(
			(v) => `
                <div class="flex justify-between py-0.5 text-sm">
                    <div class="text-indigo-200 font-medium">${v.name}</div>
                    <div class="font-bold ${
						v.result === "APROBADO" || v.result === "OK"
							? "text-green-400"
							: "text-green-400"
					}">${v.result}</div>
                </div>
            `
		)
		.join("");
	const cardVerifs = `
        <div class="rounded-2xl bg-[#232350] p-4 shadow w-full min-w-0">
            <div class="mb-3 text-indigo-300 font-bold text-base">Validaciones</div>
            ${verifs || '<div class="text-indigo-400">Sin validaciones.</div>'}
        </div>
    `;

	// --- INE Nominal List ---
	const ineNl =
		data.ineNominalList && data.ineNominalList.data
			? Object.entries(data.ineNominalList.data)
					.map(
						([k, v]) => `
                          <div class="flex justify-between py-0.5 text-sm">
                              <div class="text-indigo-200 font-medium">${k}</div>
                              <div class="text-white">${v}</div>
                          </div>
                      `
					)
					.join("")
			: "";
	const cardINE = ineNl
		? `<div class="w-full rounded-2xl bg-[#222446] p-4 shadow my-4">
                <div class="mb-2 text-yellow-300 font-bold text-base">INE Nominal List</div>
                ${ineNl}
           </div>`
		: "";

	// --- RENAPO ---
	let renapo = "";
	if (data.renapo && data.renapo.registros && data.renapo.registros.length) {
		const r = data.renapo.registros[0];
		renapo = Object.entries(r)
			.filter(([k, v]) => typeof v !== "object")
			.map(
				([k, v]) => `
                    <div class="flex justify-between py-0.5 text-sm">
                        <div class="text-indigo-200 font-medium">${k}</div>
                        <div class="text-white">${v}</div>
                    </div>
                `
			)
			.join("");
	}
	const cardRENAPO = renapo
		? `<div class="w-full rounded-2xl bg-[#193654] p-4 shadow my-4">
                <div class="mb-2 text-cyan-300 font-bold text-base">RENAPO</div>
                ${renapo}
           </div>`
		: "";

	// --- Mensaje ---
	const cardMensaje = `
        <div class="w-full rounded-2xl bg-[#222446] text-white py-3 text-center mb-6 text-lg font-semibold shadow">
            ${data.message}
        </div>
    `;

	// --- Layout principal: imágenes arriba, resto en grid (responsive) ---
	return `
        ${cardMensaje}
        ${cardImagenes}
        <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-5 mb-2">
            ${cardDatos}
            ${cardVerifs}
        </div>
        ${cardINE}
        ${cardRENAPO}
    `;
}

// --- PDF EXPORTACIÓN ---
function generarPdf() {
	const card = document.querySelector(".card-central");
	if (!card) {
		alert("No se encontró el área a exportar");
		return;
	}
	const btn = document.getElementById("btnPdfContainer");
	let prevDisplay = btn.style.display;
	btn.style.display = "none";

	const opt = {
		margin: 0,
		filename: "validacion-identidad.pdf",
		image: { type: "jpeg", quality: 1 },
		html2canvas: { scale: 2, useCORS: true, backgroundColor: "#18182b" },
		jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
	};

	// Espera imágenes cargadas antes de exportar
	const imgs = card.querySelectorAll("img");
	Promise.all(
		Array.from(imgs).map((img) => {
			return new Promise((res) => {
				if (img.complete) res();
				else img.onload = img.onerror = res;
			});
		})
	).then(() => {
		html2pdf()
			.set(opt)
			.from(card)
			.save()
			.then(() => {
				btn.style.display = prevDisplay;
			});
	});
}

// ---- RENDER PRINCIPAL ----
window.addEventListener("DOMContentLoaded", () => {
	const stored = localStorage.getItem("validacion_identidad_result");
	if (!stored) return;
	try {
		const data = JSON.parse(stored);
		container.innerHTML = renderResultadosValidacion(data);
		btnContainer.style.display = "flex";
		document
			.getElementById("btn-descargar-pdf")
			.addEventListener("click", generarPdf);
        } catch (e) {
                // Error silenciado intencionalmente
        }
});

function fillPdfCard(data) {
	document.getElementById("pdfSelfie").src = data.images.image_selfie
		? "data:image/jpeg;base64," + data.images.image_selfie
		: "";
	document.getElementById("pdfFrente").src = data.images.image_front
		? "data:image/jpeg;base64," + data.images.image_front
		: "";
	document.getElementById("pdfReverso").src = data.images.image_back
		? "data:image/jpeg;base64," + data.images.image_back
		: "";
	document.getElementById("pdfNombre").textContent =
		(data.nombre || "") + " " + (data.apellido || "");
	document.getElementById("pdfEmail").textContent = data.email || "";
	document.getElementById("pdfMensaje").textContent = data.message || "";
	// Datos
	let d =
		data.documentInformation && data.documentInformation.documentData
			? data.documentInformation.documentData
			: [];
	document.getElementById("pdfDatos").innerHTML = d
		.map((x) => `<div><b>${x.name}:</b> ${x.value}</div>`)
		.join("");
	// Validaciones
	let v =
		data.documentInformation &&
		data.documentInformation.documentVerifications
			? data.documentInformation.documentVerifications
			: [];
	document.getElementById("pdfVerifs").innerHTML = v
		.map(
			(x) =>
				`<div><b>${x.name}:</b> <span style="color:${
					x.result == "APROBADO" || x.result == "OK"
						? "#32d88a"
						: "#fa6666"
				}">${x.result}</span></div>`
		)
		.join("");
}

function exportarPdf() {
	const web = document.getElementById("webCard");
	const pdf = document.getElementById("pdfCard");
	web.style.display = "none";
	pdf.style.display = "block";

	const opt = {
		margin: 0,
		filename: "validacion-identidad.pdf",
		image: { type: "jpeg", quality: 1 },
		html2canvas: { scale: 2, useCORS: true, backgroundColor: "#222446" },
		jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
	};
	html2pdf()
		.set(opt)
		.from(pdf)
		.save()
		.then(() => {
			pdf.style.display = "none";
			web.style.display = "block";
		});
}

// Ejemplo: cargar data
window.addEventListener("DOMContentLoaded", () => {
	const stored = localStorage.getItem("validacion_identidad_result");
	if (!stored) return;
	try {
		const data = JSON.parse(stored);
		fillPdfCard(data); // Prepara los datos para el card PDF
		// Tu render web aquí...
		document
			.getElementById("btn-descargar-pdf")
			.addEventListener("click", exportarPdf);
        } catch (e) {
                // Error silenciado intencionalmente
        }
});
