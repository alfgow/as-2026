(function () {
	"use strict";

	// =======================
	// Helpers
	// =======================
        const byId = (id) => document.getElementById(id);

        const getCurrentSlug = () => {
                if (window.INQ_DB_DUMP && typeof window.INQ_DB_DUMP === "object") {
                        const current = window.INQ_DB_DUMP.slug;
                        if (typeof current === "string" && current.trim() !== "") {
                                return current.trim();
                        }
                }
                if (typeof window.SLUG === "string" && window.SLUG.trim() !== "") {
                        return window.SLUG.trim();
                }

                const segments = window.location.pathname.split("/").filter(Boolean);
                const idx = segments.indexOf("inquilino");
                if (idx !== -1 && segments.length > idx + 1) {
                        return segments[idx + 1];
                }

                return null;
        };

        const redirectToSlug = (slug) => {
                if (!slug) return false;

                const pathParts = window.location.pathname.split("/");
                const idx = pathParts.indexOf("inquilino");
                if (idx !== -1 && pathParts.length > idx + 1) {
                        pathParts[idx + 1] = slug;
                        const newPath = pathParts.join("/") || "/";
                        const normalizedPath = newPath.startsWith("/") ? newPath : `/${newPath}`;
                        window.location.assign(normalizedPath + window.location.search + window.location.hash);
                        return true;
                }

                const base = (window.ADMIN_BASE || window.BASE_URL || "").replace(/\/$/, "");
                if (base) {
                        window.location.assign(`${base}/inquilino/${slug}`);
                        return true;
                }

                window.location.assign(`/inquilino/${slug}`);
                return true;
        };

	async function postJSON(url, body) {
		const r = await fetch(url, { method: "POST", body });
		const data = await r.json().catch(() => ({}));
		if (!r.ok)
			throw new Error(data.error || data.mensaje || `HTTP ${r.status}`);
		return data;
	}

	function showSwal(opts) {
		return Swal.fire(
			Object.assign(
				{
					background: "#18181b",
					color: "#fff",
				},
				opts
			)
		);
	}

	// =======================
	// Registro de secciones
	// =======================
	const SECCIONES = [
		{
			key: "datos",
			formId: "form-editar-datos",
			vistaId: "datos-personales-vista",
			btnId: "btn-editar-datos",
			url: "/inquilino/editar_datos_personales",
			successTitle: "¡Datos actualizados!",
			successText: "Los datos personales se guardaron correctamente.",
			confirmColor: "#6366f1",
		},
		{
			key: "domicilio",
			formId: "form-editar-domicilio",
			vistaId: "domicilio-vista",
			btnId: "btn-editar-domicilio",
			url: "/inquilino/editar_domicilio",
			successTitle: "¡Domicilio actualizado!",
			successText:
				"La información del domicilio se guardó correctamente.",
			confirmColor: "#22c55e",
		},
		{
			key: "trabajo",
			formId: "form-editar-trabajo",
			vistaId: "trabajo-vista",
			btnId: "btn-editar-trabajo",
			url: "/inquilino/editar_trabajo",
			successTitle: "¡Información laboral actualizada!",
			successText: "Los datos de trabajo se guardaron correctamente.",
			confirmColor: "#facc15",
		},
		{
			key: "fiador",
			formId: "form-editar-fiador",
			vistaId: "fiador-vista",
			btnId: "btn-editar-fiador",
			url: "/inquilino/editar_fiador",
			successTitle: "¡Datos del fiador actualizados!",
			successText: "Los datos del fiador se guardaron correctamente.",
			confirmColor: "#a78bfa",
		},
		{
			key: "historial",
			formId: "form-editar-historial",
			vistaId: "historial-vivienda-vista",
			btnId: "btn-editar-historial",
			url: "/inquilino/editar_historial_vivienda",
			successTitle: "¡Historial actualizado!",
			successText: "El historial de vivienda se guardó correctamente.",
			confirmColor: "#ffe066",
		},
		{
			key: "asesor",
			formId: "form-editar-asesor",
			vistaId: "asesor-vista",
			btnId: "btn-editar-asesor",
			url: "/inquilino/editar_asesor",
			successTitle: "¡Asesor actualizado!",
			successText: "El asesor se guardó correctamente.",
			confirmColor: "#d946ef",
		},
	];

	// =======================
	// Inicializador genérico
	// =======================
	function initEdicion(sec) {
		const form = byId(sec.formId);
		const vista = byId(sec.vistaId);
		const btn = byId(sec.btnId);
		if (!form || !vista || !btn) return;

		// Capitalizar key
		const keyCap = sec.key.charAt(0).toUpperCase() + sec.key.slice(1);

		// Mostrar form
		window[`mostrarFormularioEdicion${keyCap}`] = () => {
			vista.classList.add("hidden");
			form.classList.remove("hidden");
			btn.classList.add("hidden");
		};

		// Cancelar
		window[`cancelarEdicion${keyCap}`] = () => {
			form.classList.add("hidden");
			vista.classList.remove("hidden");
			btn.classList.remove("hidden");
			const msg = byId(`mensaje-edicion-${sec.key}`);
			if (msg) msg.innerText = "";
		};

		// Guardar
		window[`guardarEdicion${keyCap}`] = async (e) => {
			e.preventDefault();
			const msg = byId(`mensaje-edicion-${sec.key}`);
			if (msg) {
				msg.className = "text-sm text-center pt-2 text-yellow-500";
				msg.innerText = "Guardando...";
			}
                        try {
                                const previousSlug = getCurrentSlug();
                                const response = await postJSON(
                                        (window.ADMIN_BASE || window.BASE_URL || "") + sec.url,
                                        new FormData(form)
                                );
                                await showSwal({
                                        icon: "success",
                                        title: sec.successTitle,
                                        text: sec.successText,
                                        confirmButtonColor: sec.confirmColor,
                                });
                                if (sec.key === "datos") {
                                        const newSlug = typeof response.slug === "string" ? response.slug.trim() : "";
                                        if (newSlug !== "") {
                                                if (window.INQ_DB_DUMP && typeof window.INQ_DB_DUMP === "object") {
                                                        window.INQ_DB_DUMP.slug = newSlug;
                                                }
                                                if (typeof window.SLUG !== "undefined") {
                                                        window.SLUG = newSlug;
                                                }
                                                if (!previousSlug || previousSlug !== newSlug) {
                                                        if (redirectToSlug(newSlug)) {
                                                                return;
                                                        }
                                                }
                                        }
                                }
                                window.location.reload();
			} catch (err) {
				showSwal({
					icon: "error",
					title: "¡Error!",
					text: err.message || "Error al guardar.",
					confirmButtonColor: "#de6868",
				});
			}
		};
	}

	// =======================
	// Init
	// =======================
	document.addEventListener("DOMContentLoaded", () => {
		SECCIONES.forEach(initEdicion);
	});
})();
