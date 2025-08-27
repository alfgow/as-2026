// =====================
// Validaciones: Modal Viewer
// =====================

(function () {
	const modal = document.getElementById("vh-modal");
	const box = document.getElementById("vh-modal-box");
	const overlay = document.getElementById("vh-modal-overlay");
	const titleEl = document.getElementById("vh-modal-title");

	let contentEl = document.getElementById("vh-modal-content");
	if (!contentEl) {
		const pre = document.getElementById("vh-modal-pre");
		contentEl = document.createElement("div");
		contentEl.id = "vh-modal-content";
		contentEl.className = "rounded-xl overflow-hidden";
		if (pre && pre.parentNode) pre.parentNode.insertBefore(contentEl, pre);
	}
	const preEl = document.getElementById("vh-modal-pre");

	const reflow = (el) => void el.offsetHeight;

	// Abrir modal
	window.abrirVHModal = function ({
		title = "Detalle",
		text = "",
		html = "",
	} = {}) {
		titleEl.textContent = title;
		if (html) {
			contentEl.innerHTML = html;
			if (preEl) {
				preEl.textContent = "";
				preEl.classList.add("hidden");
			}
		} else {
			if (preEl) {
				preEl.textContent = String(text);
				preEl.classList.remove("hidden");
			}
			contentEl.innerHTML = "";
		}
		modal.classList.remove("hidden");
		reflow(modal);
		modal.classList.remove("opacity-0");
		box.classList.remove("translate-y-3");
		document.body.classList.add("overflow-hidden");
	};

	// Cerrar modal
	window.cerrarVHModal = function () {
		modal.classList.add("opacity-0");
		box.classList.add("translate-y-3");
		setTimeout(() => {
			modal.classList.add("hidden");
			document.body.classList.remove("overflow-hidden");
			contentEl.innerHTML = "";
			if (preEl) preEl.textContent = "";
		}, 200);
	};

	// Aliases
	window.abrirModal = window.abrirVHModal;
	window.cerrarModal = window.cerrarVHModal;

	// Eventos
	overlay?.addEventListener("click", cerrarVHModal);
	document.addEventListener("keydown", (e) => {
		if (e.key === "Escape" && !modal.classList.contains("hidden"))
			cerrarVHModal();
	});

	// Helpers globales
	window.mostrarDetalleJSON = function (title, data) {
		const txt =
			typeof data === "string" ? data : JSON.stringify(data, null, 2);
		abrirVHModal({ title, text: txt });
	};

	window.verMediaEnModal = function ({
		title = "Vista previa",
		url = "",
		mime = "",
	} = {}) {
		if (!url) return;
		const isImg =
			mime?.startsWith?.("image/") ||
			/\.(jpe?g|png|webp|gif|bmp)$/i.test(url.split("?")[0] || "");
		const html = isImg
			? `<div class="bg-black/30 grid place-items-center rounded-xl">
           <img src="${url}" alt="" class="max-h-[70vh] w-auto object-contain">
         </div>`
			: `<iframe src="${url}#toolbar=1&navpanes=0&scrollbar=1"
                 class="w-full h-[70vh] rounded-xl border border-white/10 bg-black/30"></iframe>`;
		abrirVHModal({ title, html });
	};
})();
