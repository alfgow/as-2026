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
		const toggle = document.getElementById("toggle-pago_inicial");
		if (!toggle) return;
		toggle.checked = !!checked;
		await window.saveSwitch('pago_inicial');
	}

	// Attach listener al switch
	function attachPagoInicialAutosave() {
		const chk = document.getElementById("toggle-pago_inicial");
		if (!chk || chk.getAttribute("onchange")) return;

		chk.addEventListener("change", async (e) => {
			const checked = !!e.target.checked;
			setText("#toggle-pago_inicial-label", "Guardando…");
			setText("#pago-status-msg", "");

			try {
				await savePagoInicial(checked);
				setText("#toggle-pago_inicial-label", checked ? "Confirmado" : "Pendiente");
				setText(
					"#pago-status-msg",
					checked
						? "Pago inicial guardado."
						: "Pago inicial desmarcado."
				);
				// refresca estado general
                                if (typeof loadStatus === "function")
                                        loadStatus().catch(() => {});
			} catch (err) {
				// revertir el switch
				e.target.checked = !checked;
				setText("#toggle-pago_inicial-label", e.target.checked ? "Confirmado" : "Pendiente");
				setText(
					"#pago-status-msg",
					"Error al guardar. Intenta de nuevo."
				);
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
