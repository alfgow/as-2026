(function () {
	"use strict";

	const $ = (sel, root = document) => root.querySelector(sel);
	const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
	const byId = (id) => document.getElementById(id);

	async function postJSON(url, body) {
		const r = await fetch(url, { method: "POST", body });
		const data = await r.json().catch(() => ({}));
		if (!r.ok)
			throw new Error(data.error || data.mensaje || `HTTP ${r.status}`);
		return data;
	}

	function showSwalLoading(msg) {
		Swal.fire({
			title: msg || "Procesando...",
			allowOutsideClick: false,
			allowEscapeKey: false,
			didOpen: () => Swal.showLoading(),
			background: "#18181b",
			color: "#fff",
		});
	}
	function showSwalOk(msg) {
		Swal.fire({
			icon: "success",
			title: msg || "¡Listo!",
			confirmButtonColor: "#de6868",
			background: "#18181b",
			color: "#fff",
		});
	}
	function showSwalErr(msg) {
		Swal.fire({
			icon: "error",
			title: "Error",
			text: msg || "Ocurrió un error",
			confirmButtonColor: "#de6868",
			background: "#18181b",
			color: "#fff",
		});
	}

	// =======================
	// DROPZONE CASERO
	// =======================
	function initCaseroDropzones() {
		$$("[id^='dropzone-']").forEach(function (dz) {
			let previewEl = null;

			dz.addEventListener("click", function () {
				let input = dz.querySelector("input[type='file']");
				if (!input) {
					input = document.createElement("input");
					input.type = "file";
					input.accept = ".jpg,.jpeg,.png,.webp,.pdf";
					input.className = "hidden";
					dz.appendChild(input);
					input.addEventListener("change", (e) =>
						mostrarPreviewArchivo(e.target.files[0], dz)
					);
				}
				input.value = "";
				input.click();
			});

			dz.addEventListener("dragover", (e) => {
				e.preventDefault();
				dz.classList.add("border-pink-600", "bg-pink-50/20");
			});
			dz.addEventListener("dragleave", (e) => {
				e.preventDefault();
				dz.classList.remove("border-pink-600", "bg-pink-50/20");
			});
			dz.addEventListener("drop", (e) => {
				e.preventDefault();
				dz.classList.remove("border-pink-600", "bg-pink-50/20");
				if (e.dataTransfer.files && e.dataTransfer.files[0]) {
					mostrarPreviewArchivo(e.dataTransfer.files[0], dz);
				}
			});

			function mostrarPreviewArchivo(file, dzEl) {
				const valid = [
					"image/jpeg",
					"image/png",
					"image/webp",
					"application/pdf",
				];
				if (!file || !valid.includes(file.type)) {
					mostrarMsg(
						dzEl.dataset.archivoId,
						"Archivo no válido. Usa JPG, PNG, WEBP o PDF.",
						"error"
					);
					return;
				}
				dzEl.selectedFile = file;
				if (previewEl) previewEl.remove();

				previewEl = document.createElement("div");
				previewEl.className =
					"w-full flex flex-col items-center gap-2 mt-2";

				if (file.type.startsWith("image/")) {
					const img = document.createElement("img");
					img.className = "rounded-lg max-h-32 object-contain shadow";
					img.src = URL.createObjectURL(file);
					previewEl.appendChild(img);
				} else {
					previewEl.innerHTML = `<div>
            <svg class="w-10 h-10 text-pink-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75V18a2.25 2.25 0 01-2.25 2.25H9a2.25 2.25 0 01-2.25-2.25V6.75m7.5-4.5h-7.5a2.25 2.25 0 00-2.25 2.25v14.25A2.25 2.25 0 009 21h6a2.25 2.25 0 002.25-2.25V4.5a2.25 2.25 0 00-2.25-2.25z"/>
            </svg></div>`;
				}

				const fname = document.createElement("div");
				fname.className =
					"text-xs text-pink-600 font-bold text-center truncate w-36";
				fname.innerText = file.name;
				previewEl.appendChild(fname);

				const btn = document.createElement("button");
				btn.type = "button";
				btn.innerText = "Quitar archivo";
				btn.className =
					"text-xs text-gray-300 hover:text-pink-600 font-semibold py-1";
				btn.onclick = () => {
					dzEl.selectedFile = null;
					previewEl.remove();
				};
				previewEl.appendChild(btn);

				dzEl.appendChild(previewEl);
				mostrarMsg(dzEl.dataset.archivoId, "¡Listo para subir!", "ok");
			}

			function mostrarMsg(archivoId, txt, tipo) {
				const msgEl = byId("mensaje-reemplazo-" + archivoId);
				if (!msgEl) return;
				msgEl.innerText = txt;
				msgEl.className =
					"text-xs text-center pt-2 " +
					(tipo === "error" ? "text-red-400" : "text-green-400");
			}
		});
	}

	// Botón subir de cada dropzone casera
	window.enviarDropzone = function (archivoId) {
		const dz = byId("dropzone-" + archivoId);
		const file = dz?.selectedFile;
		if (!dz || !file) {
			showSwalErr("Selecciona un archivo primero");
			return;
		}
		showSwalLoading("Subiendo archivo...");
		const form = byId("form-reemplazo-" + archivoId);
		const fd = new FormData(form);
		fd.append("archivo_id", archivoId);
		fd.append("archivo", file);

		postJSON(
			(window.ADMIN_BASE || window.BASE_URL || "") +
				"/inquilino/reemplazar_archivo",
			fd
		)
			.then(() => {
				Swal.close();
				showSwalOk("¡Archivo reemplazado!");
				setTimeout(() => location.reload(), 400);
			})
			.catch((err) => {
				Swal.close();
				showSwalErr(err.message || "Error al subir archivo.");
			});
	};

	// =======================
	// DROPZONE INLINE
	// =======================
	function bytes(n) {
		if (!n && n !== 0) return "";
		if (n < 1024) return n + " B";
		if (n < 1024 * 1024) return (n / 1024).toFixed(1) + " KB";
		return (n / 1024 / 1024).toFixed(1) + " MB";
	}

	function initInlineDropzone(dz) {
		if (!dz || dz.__inited) return;
		dz.__inited = true;

		const form = $(".dz-form", dz);
		const input = $(".dz-input", dz);
		const area = $(".dz-area", dz);
		const pick = $(".dz-pick", dz);
		const prev = $(".dz-preview", dz);
		const thumb = $(".dz-thumb", dz);
		const fileTx = $(".dz-file", dz);
		const btnSend = $(".dz-send", dz);
		const btnClr = $(".dz-clear", dz);

		const accept = dz.dataset.accept || "";
		const mode = dz.dataset.mode || "new"; // 'new' | 'replace'

		if (accept) input.setAttribute("accept", accept);

		let currentURL = null;

		function renderPreview(file) {
			if (!file) {
				// limpiar
				if (currentURL) {
					URL.revokeObjectURL(currentURL);
					currentURL = null;
				}
				thumb.classList.add("hidden");
				prev.classList.add("hidden");
				fileTx.textContent = "";
				return;
			}
			const isImg = /^image\//.test(file.type);
			if (isImg) {
				currentURL = URL.createObjectURL(file);
				thumb.src = currentURL;
				thumb.classList.remove("hidden");
			} else {
				thumb.classList.add("hidden");
			}
			fileTx.textContent =
				(file.name || "archivo") + " · " + bytes(file.size);
			prev.classList.remove("hidden");
		}

		function choose() {
			input.click();
		}
		function clear() {
			form.reset();
			renderPreview(null);
		}

		pick && pick.addEventListener("click", choose);
		area.addEventListener("click", (e) => {
			if (!e.target.closest(".dz-pick")) choose();
		});
		input.addEventListener("change", () => {
			renderPreview(input.files[0] || null);
		});
		["dragenter", "dragover"].forEach((ev) =>
			area.addEventListener(ev, (e) => {
				e.preventDefault();
				area.classList.add("ring-2", "ring-pink-400");
			})
		);
		["dragleave", "drop"].forEach((ev) =>
			area.addEventListener(ev, (e) => {
				e.preventDefault();
				area.classList.remove("ring-2", "ring-pink-400");
			})
		);
		area.addEventListener("drop", (e) => {
			if (e.dataTransfer?.files?.[0]) {
				input.files = e.dataTransfer.files;
				renderPreview(input.files[0]);
			}
		});

		btnClr && btnClr.addEventListener("click", clear);

		btnSend &&
			btnSend.addEventListener("click", async () => {
				const f = input.files && input.files[0];
				if (!f) {
					showSwalErr("Selecciona un archivo primero.");
					return;
				}

				const fd = new FormData(form);
				fd.set("archivo", f);
				try {
					showSwalLoading("Subiendo...");
					const base = window.ADMIN_BASE || window.BASE_URL || "";
					const url =
						mode === "replace"
							? base + "/inquilino/reemplazar_archivo"
							: base + "/inquilino/subir-archivo";
					await postJSON(url, fd);
					Swal.close();
					showSwalOk("¡Hecho!");
					setTimeout(() => location.reload(), 400);
				} catch (err) {
					Swal.close();
					showSwalErr(err.message || "No se pudo subir.");
				}
			});
	}

	window.toggleInlineDZ = function (id) {
		const el = byId(id);
		if (!el) return;
		el.classList.toggle("hidden");
	};

	// =======================
	// INIT
	// =======================
	document.addEventListener("DOMContentLoaded", () => {
		initCaseroDropzones();
		$$(".inline-dropzone").forEach(initInlineDropzone);
	});
})();
