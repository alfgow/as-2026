// =====================
// Validaciones: Pago Inicial
// =====================

(function () {
	const { adminBase, idInq, slug } = window.VH_CTX;

	function setText(sel, txt) {
		const el = document.querySelector(sel);
		if (el) el.textContent = txt;
	}

	// Guardar pago inicial en backend
	async function savePagoInicial(checked) {
		const resumen = checked
			? "Se reportó pago inicial"
			: "Se retiró el reporte de pago inicial";
		const jsonPayload = JSON.stringify({
			proceso_pago_inicial: checked ? 1 : 0,
			resumen,
			fecha_registro: new Date().toISOString(),
		});

		const fd = new FormData();
		fd.append("id_inquilino", String(idInq));
		fd.append("proceso_pago_inicial", checked ? "1" : "0");
		fd.append("pago_inicial", jsonPayload);

		const r = await fetch(`${adminBase}/inquilino/editar-validaciones`, {
			method: "POST",
			body: fd,
			credentials: "include",
		});
		const j = await r.json();
		if (!j?.ok) throw new Error(j?.error || "No se pudo guardar cambios");
		return true;
	}

	// Attach listener al switch
	function attachPagoInicialAutosave() {
		const chk = document.getElementById("toggle-pago");
		if (!chk) return;

		chk.addEventListener("change", async (e) => {
			const checked = !!e.target.checked;
			setText("#toggle-pago-label", "Guardando…");
			setText("#pago-status-msg", "");

			try {
				await savePagoInicial(checked);
				setText("#toggle-pago-label", checked ? "OK" : "");
				setText(
					"#pago-status-msg",
					checked
						? "Pago inicial guardado."
						: "Pago inicial desmarcado."
				);
				// refresca estado general
				if (typeof loadStatus === "function")
					loadStatus().catch(console.error);
			} catch (err) {
				// revertir el switch
				e.target.checked = !checked;
				setText("#toggle-pago-label", e.target.checked ? "OK" : "");
				setText(
					"#pago-status-msg",
					"Error al guardar. Intenta de nuevo."
				);
				console.error(err);
			}
		});
	}

	if (document.readyState !== "loading") attachPagoInicialAutosave();
	else
		document.addEventListener(
			"DOMContentLoaded",
			attachPagoInicialAutosave
		);

	// Exponer
	window.VH_PAGO = { savePagoInicial };
})();
