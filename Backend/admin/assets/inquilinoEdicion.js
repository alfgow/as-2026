(function () {
	"use strict";

	// =======================
	// Helpers
	// =======================
	const byId = (id) => document.getElementById(id);

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
				await postJSON(
					(window.ADMIN_BASE || window.BASE_URL || "") + sec.url,
					new FormData(form)
				);
				await showSwal({
					icon: "success",
					title: sec.successTitle,
					text: sec.successText,
					confirmButtonColor: sec.confirmColor,
				});
				location.reload();
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
