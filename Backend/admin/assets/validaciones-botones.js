// =====================
// Validaciones – Botones y Acciones
// =====================
//
// Este módulo gestiona los botones generales y específicos de validaciones.
// Se añadió feedback con SweetAlert2 loaders en cada proceso.
// =====================

(function () {
	// ----------------------
	// Helpers
	// ----------------------
	async function runWithLoader({ title, text, action }) {
		Swal.fire({
			title: title || "Procesando...",
			text: text || "Por favor espera unos segundos",
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading(),
		});

		try {
			await action();
			Swal.close();
			Swal.fire({
				icon: "success",
				title: "Proceso completado",
				text: "Los resultados han sido actualizados",
			});
                } catch (err) {
                        Swal.close();
                        Swal.fire({
                                icon: "error",
                                title: "Error en proceso",
				text: err.message || "No se pudo completar el proceso",
			});
		}
	}

	// ----------------------
	// Recalcular procesos específicos (.vh-recalc)
	// ----------------------
	$$(".vh-recalc").forEach((btn) => {
		btn.addEventListener("click", async () => {
			const check = btn.dataset.check || "status";
			await runWithLoader({
				title: "Recalculando...",
				text: `Validando ${check} del inquilino`,
				action: async () => {
					await recalc(check);
				},
			});
		});
	});

	// ----------------------
	// Ver detalle en modal (.vh-detalle)
	// ----------------------
	$$(".vh-detalle").forEach((btn) => {
		btn.addEventListener("click", () => {
			const cat = btn.dataset.cat || "detalle";
			const data = (window.__VH_DETALLES__ &&
				(window.__VH_DETALLES__[cat]?.json ||
					window.__VH_DETALLES__[cat])) || { info: "Sin detalle" };
			mostrarDetalleJSON(`Detalle: ${cat}`, data);
		});
	});

	// ----------------------
	// Botón "Recalcular proceso global"
	// ----------------------
	document
		.getElementById("btn-recalc")
		?.addEventListener("click", async () => {
			await runWithLoader({
				title: "Recalculando...",
				text: "Validando nuevamente todos los procesos",
				action: async () => {
					await recalc("resumen_full");
				},
			});
		});

	// ----------------------
	// Botón "Regenerar resúmenes"
	// ----------------------
	document
		.getElementById("btn-resumen")
		?.addEventListener("click", async () => {
			await runWithLoader({
				title: "Regenerando resúmenes...",
				text: "Actualizando los resúmenes de validación",
				action: async () => {
					await recalc("resumen_full");
				},
			});
		});

	// ----------------------
	// Botón "Continuar"
	// ----------------------
	document.getElementById("btn-continuar")?.addEventListener("click", () => {
		location.href = `${VH_CTX.adminBase}/inquilino/${VH_CTX.slug}`;
	});

	// ----------------------
	// Botón "Guardar pago inicial"
	// ----------------------
	document
		.getElementById("btn-guardar-pago")
		?.addEventListener("click", async () => {
			await runWithLoader({
				title: "Guardando...",
				text: "Registrando estado del pago inicial",
				action: async () => {
					await savePagoInicial();
				},
			});
		});
})();
