// --- Carga previa de validación anterior ---
window.ultimoResultadoValidacion =
	window.ultimoResultadoValidacion ||
	(localStorage.getItem("ultimaValidacion")
		? JSON.parse(localStorage.getItem("ultimaValidacion"))
		: null);

// --- Elementos base ---
const ineFrente = document.getElementById("ineFrente");
const ineReverso = document.getElementById("ineReverso");
const selfie = document.getElementById("selfie");
const btnValidar = document.getElementById("btn-validar-identidad");
const resultados = document.getElementById("resultadosValidacion");
const baseUrl = document.getElementById("baseUrl").value;

// --- Modal de imagen fullscreen ---
window.abrirModalImagen = function (src, titulo) {
	const modal = document.getElementById("modalImagen");
	const img = document.getElementById("imagenGrande");
	const title = document.getElementById("modalTitulo");
	img.src = src;
	title.textContent = titulo || "";
	modal.classList.remove("hidden");
};
window.cerrarModalImagen = function () {
	document.getElementById("modalImagen").classList.add("hidden");
};

// --- Dropzone casero universal ---
function initImageDropzone(zoneId, inputId, previewId) {
	const dz = document.getElementById(zoneId);
	const input = document.getElementById(inputId);
	const preview = document.getElementById(previewId);
	const btnCancel = dz.querySelector(".dz-cancel");

	function mostrarVista() {
		dz.classList.add("hidden");
		document.getElementById(`view-${inputId}`).classList.remove("hidden");
	}

	dz.addEventListener("click", (e) => {
		if (e.target.classList.contains("dz-cancel")) return;
		input.click();
	});
	dz.addEventListener("dragover", (e) => {
		e.preventDefault();
		dz.classList.add("bg-indigo-900/30");
	});
	dz.addEventListener("dragleave", (e) => {
		e.preventDefault();
		dz.classList.remove("bg-indigo-900/30");
	});
	dz.addEventListener("drop", (e) => {
		e.preventDefault();
		dz.classList.remove("bg-indigo-900/30");
		if (e.dataTransfer.files.length) {
			setPreview(e.dataTransfer.files[0]);
		}
	});
	input.addEventListener("change", () => {
		if (input.files && input.files[0]) {
			setPreview(input.files[0]);
		}
	});

	function setPreview(file) {
		const reader = new FileReader();
		reader.onload = (ev) => {
			preview.src = ev.target.result;
			btnCancel.classList.remove("hidden");
		};
		reader.readAsDataURL(file);
	}

	btnCancel.addEventListener("click", (e) => {
		e.stopPropagation();
		preview.src = input.getAttribute("data-original") || preview.src;
		input.value = "";
		btnCancel.classList.add("hidden");
		mostrarVista();
	});
}

// --- Verifica si hay archivo o imagen cargada ---
function archivoPresenteOImagenDemo(input, previewId) {
	const preview = document.getElementById(previewId);
	const fileCargado = input?.files?.[0];
	const imagenCargada = preview?.src?.length > 0;
	return !!fileCargado || imagenCargada;
}

// --- Llamada a VerificaMex con FormData ---
async function validarConVerificaMex(ineFrente, ineReverso, selfie) {
	const formData = new FormData();
	formData.append("ine_front", ineFrente.files[0]);
	formData.append("ine_back", ineReverso.files[0]);
	formData.append("selfie", selfie.files[0]);
	formData.append("model", "basic");

	const response = await fetch(
		"https://api.verificamex.com/v1/validations/basic",
		{
			method: "POST",
			headers: {
				Authorization:
					"Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMTNjYTM4OWViMDY3N2QwMjA3NTU0ZDJmNmNhNTZiYjMyMzcxMTMyODgwZWM4M2IwZGMyM2EzMzVhMjU0MjY0YTdjYjEzOGY0OTMwNzFjODQiLCJpYXQiOjE3NTQxNzAwMzMuNzU1MzA4LCJuYmYiOjE3NTQxNzAwMzMuNzU1MzMxLCJleHAiOjE3ODU3MDYwMzMuNzM1Mjk0LCJzdWIiOiIxMDIiLCJzY29wZXMiOltdfQ.lmCnvwpFs6Em7DaoA1uGx23ZZqg2w-sZZ_RWuKcQFaeK1dSEmMoMLy1QDbcm35mD8_xdGWVHQ8R9Q2_zHFGlOR5SBOJMhgIPQZvXkMTxg6kEIb5EHGAxdzIzMJh7BqW0ea4iiI3I9C6ZRlvkircoVRwab2jykYUkANFe7K_wwG69k_qVyXG4FlT2NqN3fazJSH06vtJ4TCIwOngXBqtShHVWvDAM5-jhrH3Q99XiZJ87kFeWWckACZOB_5Nt-BuwbHuY6z-M8fJNwmY4x4liTLilLRxTpNNO3h--W-F1cWkE3jMJ6W7vFrYn6D78gyNjG3lkV_T0WA2oS8Q-2QUv-5yiMBbJhw4aYm5i2hgi4lyIZjcKkXtg1QnOamWUd9NSenQhm0_9jTCTBDIPQqyUL39lVeoEToWvOnnkNKaecN4SKVqX-YZlJ5hXuosYC5C2V8hQ1kfu1JbhZULGd__YHY2QqnhVdbBxAvAkNclt1TTsuIMa_hOb9P4lucZxIYjxL5euExxn7H0r9LNPOLp18Kd2tpMot2BhVC9UiQdGGhC9iAvzRw8mpVOOvyMIZaP_gmAD3Zas1Zzcl-5KomHSXfOMruMTmCk8Z7akH8qSeWWYHCEJf7_IDCI218Q-feDdzeLh0aEUjisBUruUN-McrGMvCupQaevWfertRNQft94", // ⚠️ Reemplaza con tu token real
			},
			body: formData,
		}
	);

	const json = await response.json();
	return json;
}

// --- Inicializar al cargar DOM ---
document.addEventListener("DOMContentLoaded", () => {
	// Guarda los src originales
	document.querySelectorAll("input[type='file']").forEach((input) => {
		const viewImg = document.querySelector(`#view-${input.id} img`);
		if (viewImg) input.setAttribute("data-original", viewImg.src);
	});

	// Inicializar dropzones
	initImageDropzone("dropzone-ineFrente", "ineFrente", "previewFront");
	initImageDropzone("dropzone-ineReverso", "ineReverso", "previewBack");
	initImageDropzone("dropzone-selfie", "selfie", "previewSelfie");

	// Botones "Editar"
	document.querySelectorAll(".btn-edit-photo").forEach((btn) => {
		btn.addEventListener("click", () => {
			const target = btn.dataset.target;
			const view = document.getElementById(`view-${target}`);
			const dz = document.getElementById(`dropzone-${target}`);
			if (view) view.classList.add("hidden");
			if (dz) dz.classList.remove("hidden");
		});
	});

	// Evento de validación
	if (btnValidar) {
		btnValidar.addEventListener("click", async (e) => {
			e.preventDefault();

			if (
				!archivoPresenteOImagenDemo(ineFrente, "previewFront") ||
				!archivoPresenteOImagenDemo(ineReverso, "previewBack") ||
				!archivoPresenteOImagenDemo(selfie, "previewSelfie")
			) {
				Swal.fire({
					icon: "warning",
					title: "Faltan archivos",
					text: "Por favor, sube INE frente, reverso y selfie.",
				});
				return;
			}

			try {
				const json = await validarConVerificaMex(
					ineFrente,
					ineReverso,
					selfie
				);
				const data = json.data;

				if (!data || !data.status) {
					Swal.fire({
						icon: "error",
						title: "Credencial Inválida",
						text: `${json.message || data.message}`,
					});
					return;
				}

				const docData = data.documentInformation?.documentData || [];
				const cveElector1 = docData
					.find((e) => e.name === "Clave de Elector")
					?.value?.trim();
				const cveElector2 =
					data.ineNominalList?.data?.["Clave de elector"]?.trim();

				if (
					!cveElector1 ||
					!cveElector2 ||
					cveElector1 !== cveElector2
				) {
					Swal.fire({
						icon: "error",
						title: "Credencial Inválida",
						text: "Las claves de elector no coinciden.",
					});
					return;
				}

				localStorage.setItem(
					"validacion_identidad_result",
					JSON.stringify(data)
				);
				window.location.href = `${window.location.pathname.replace(
					/\/$/,
					""
				)}/resultado`;
                        } catch (err) {
                                Swal.fire({
                                        icon: "error",
                                        title: "Error de validación",
                                        text: "Hubo un problema al validar la identidad.",
				});
			}
		});
	}
});

// --- Render y guardar resultados ---
function renderResultadosValidacion(data) {
	return `<pre class="text-xs text-white bg-gray-800 rounded p-4 overflow-x-auto">${JSON.stringify(
		data,
		null,
		2
	)}</pre>`;
}

function mostrarResultadosValidacion(data) {
	document.getElementById("vista-resultados").innerHTML =
		renderResultadosValidacion(data);
	window.ultimoResultadoValidacion = data;
	localStorage.setItem("ultimaValidacion", JSON.stringify(data));
}
