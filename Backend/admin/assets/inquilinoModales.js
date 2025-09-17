(function () {
	"use strict";

	const byId = (id) => document.getElementById(id);

	// =======================
	// MODAL IMG
	// =======================
	window.abrirModalImg = function (url, caption = "") {
		const img = byId("img-modal-grande");
		if (!img) return;
		img.classList.remove("animate-fade-in");
		img.src = url;
		byId("modal-img-caption").innerText = caption;
		byId("modal-img").classList.remove("hidden");
		document.body.classList.add("overflow-hidden");
		void img.offsetWidth; // forza reflow
		img.classList.add("animate-fade-in");
	};

	window.cerrarModalImg = function () {
		byId("modal-img")?.classList.add("hidden");
		const img = byId("img-modal-grande");
		if (img) img.src = "";
		document.body.classList.remove("overflow-hidden");
	};

	// =======================
	// MODAL PDF
	// =======================
	window.abrirModalPdf = function (url, caption = "") {
		const iframe = byId("iframe-pdf");
		if (!iframe) return;
		iframe.src = url;
		byId("modal-pdf-caption").innerText = caption;
		byId("modal-pdf").classList.remove("hidden");
		document.body.classList.add("overflow-hidden");
	};

	window.cerrarModalPdf = function () {
		byId("modal-pdf")?.classList.add("hidden");
		const iframe = byId("iframe-pdf");
		if (iframe) iframe.src = "";
		document.body.classList.remove("overflow-hidden");
	};

	// =======================
	// COPIAR PORTAPAPELES
	// =======================
	window.copiarAlPortapapeles = function (elementId) {
		const el = byId(elementId);
		const text = el?.innerText;
		if (!text) return;
		navigator.clipboard.writeText(text).then(() => {
			const oldBg = el.style.backgroundColor;
			el.style.backgroundColor = "#fbb6ce";
			setTimeout(() => {
				el.style.backgroundColor = oldBg;
			}, 200);
		});
	};

	// =======================
	// INIT: cerrar con click o escape
	// =======================
	document.addEventListener("DOMContentLoaded", () => {
		byId("modal-img")?.addEventListener("click", (e) => {
			if (e.target === e.currentTarget) window.cerrarModalImg();
		});
		byId("modal-pdf")?.addEventListener("click", (e) => {
			if (e.target === e.currentTarget) window.cerrarModalPdf();
		});
		document.addEventListener("keydown", (e) => {
			if (e.key === "Escape") {
				window.cerrarModalImg();
				window.cerrarModalPdf();
			}
		});
	});
})();
