// =====================
// Validaciones: Archivos
// =====================

(function () {
	const { adminBase, slug } = window.VH_CTX;
	const $id = (id) => document.getElementById(id);

	function setImgClickable(id, file, label) {
		const el = $id(id);
		if (!el || !file?.url) return;
		el.src = file.url;
		el.style.cursor = "zoom-in";
		el.addEventListener("click", () =>
			verMediaEnModal({
				title: label || "Vista previa",
				url: file.url,
				mime: file.mime_type || "",
			})
		);
	}

	function setPdfClickable(id, file, label, accent = false) {
		const box = $id(id);
		if (!box) return;
		if (file?.url) {
			box.innerHTML = `
        <button type="button"
                class="flex h-full w-full items-center justify-center ${
					accent
						? "rounded-xl border border-rose-400/30 bg-rose-400/10"
						: "rounded-xl border border-white/10 bg-white/5"
				} text-lg">
          PDF
        </button>`;
			box.querySelector("button").addEventListener("click", () =>
				verMediaEnModal({
					title: label || "PDF",
					url: file.url,
					mime: file.mime_type || "application/pdf",
				})
			);
		} else {
			box.textContent = "PDF";
		}
	}

	function indexByTipo(files) {
		const by = { comprobantes: [] };
		for (const f of files || []) {
			if (f.tipo === "comprobante_ingreso") by.comprobantes.push(f);
			else by[f.tipo] = f;
		}
		return by;
	}

	function updateChipsArchivos(by) {
		const root = document.getElementById("chips-archivos");
		if (!root) return;
		const present = (keys) => (keys || []).some((k) => !!by[k]);

		vhSetChipState(
			root.querySelector('[data-key="selfie"]'),
			present(["selfie"]) ? "ok" : "fail"
		);
		vhSetChipState(
			root.querySelector('[data-key="ine_frontal"]'),
			present(["ine_frontal", "ine-front", "ine_front"]) ? "ok" : "fail"
		);
		vhSetChipState(
			root.querySelector('[data-key="ine_reverso"]'),
			present(["ine_reverso", "ine-back", "ine_back"]) ? "ok" : "fail"
		);
		vhSetChipState(
			root.querySelector('[data-key="pasaporte"]'),
			present(["pasaporte", "passport"]) ? "ok" : "fail"
		);
		vhSetChipState(
			root.querySelector('[data-key="fm"]'),
			present(["fm", "fm2", "fm3", "fm2_fm3"]) ? "ok" : "fail"
		);

		const comps = by.comprobantes || [];
		const elComp = root.querySelector('[data-key="comprobante_ingreso"]');
		if (elComp) {
			if (comps.length >= 3) vhSetChipState(elComp, "ok");
			else if (comps.length >= 1) vhSetChipState(elComp, "warn");
			else vhSetChipState(elComp, "fail");

			const base = elComp.getAttribute("data-label") || "Comprobantes";
			elComp.setAttribute("data-label", base);
			elComp.textContent = `${base}${
				comps.length ? ` (${comps.length})` : ""
			}`;
		}
	}

	let __vh_last_presign = 0;
	async function cargarPresignadas() {
		try {
			const res = await fetch(
				`${adminBase}/inquilino/${encodeURIComponent(
					slug
				)}/archivos-presignados`,
				{ credentials: "include" }
			);
			const j = await res.json();
			if (!j?.ok) return;

			const by = indexByTipo(j.files);

			if (by.selfie) setImgClickable("prev-selfie", by.selfie, "Selfie");
			if (by.ine_frontal)
				setImgClickable(
					"prev-ine-front",
					by.ine_frontal,
					"INE — frontal"
				);
			if (by.ine_reverso)
				setImgClickable(
					"prev-ine-back",
					by.ine_reverso,
					"INE — reverso"
				);

			setPdfClickable(
				"prev-comp-1",
				by.comprobantes?.[0],
				"Comprobante 1"
			);
			setPdfClickable(
				"prev-comp-2",
				by.comprobantes?.[1],
				"Comprobante 2"
			);
			setPdfClickable(
				"prev-comp-3",
				by.comprobantes?.[2],
				"Comprobante 3",
				true
			);

			updateChipsArchivos(by);
			__vh_last_presign = Date.now();
                } catch (e) {
                        // Error al cargar presignadas, se omite registro en consola
                }
	}

	// Auto-refresh cada 8 min
	setInterval(() => {
		if (Date.now() - __vh_last_presign > 8 * 60 * 1000) {
                        cargarPresignadas().catch(() => {});
		}
	}, 60 * 1000);

	if (document.readyState !== "loading") cargarPresignadas();
	else document.addEventListener("DOMContentLoaded", cargarPresignadas);

	// Exponer públicamente
	window.VH_ARCHIVOS = { cargarPresignadas };
})();
