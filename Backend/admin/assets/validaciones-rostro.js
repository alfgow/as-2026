// =====================
// Validaciones: Rostro
// =====================

(function () {
	// Helpers numéricos seguros
	const _toNum = (v) => (Number.isFinite(Number(v)) ? Number(v) : NaN);
	const _toInt = (v) => (Number.isFinite(parseInt(v)) ? parseInt(v) : NaN);

	function _getRostroObj(raw) {
		let d = raw && (raw.json ?? raw);
		if (typeof d === "string") {
			try {
				d = JSON.parse(d);
			} catch {
				d = {};
			}
		}
		return d && typeof d === "object" ? d : {};
	}

	function extractRostro(detailsObj, resumenStr = "") {
		const d = _getRostroObj(detailsObj);

		let similarity = _toNum(
			d?.best?.similarity ?? d?.similarity ?? d?.score
		);
		let threshold = _toNum(d?.threshold ?? d?.umbral);
		let matches = _toInt(
			d?.match_count ?? d?.matches ?? d?.count ?? d?.matchesCount
		);

		const arrays = []
			.concat(Array.isArray(d.FaceMatches) ? [d.FaceMatches] : [])
			.concat(Array.isArray(d.MatchedFaces) ? [d.MatchedFaces] : [])
			.concat(Array.isArray(d.faceMatches) ? [d.faceMatches] : [])
			.concat(Array.isArray(d.matches) ? [d.matches] : []);
		const all = arrays.flat();

		if (!Number.isFinite(similarity) && all.length) {
			const sims = all
				.map((m) => _toNum(m?.Similarity ?? m?.similarity ?? m?.score))
				.filter(Number.isFinite);
			if (sims.length) similarity = Math.max(...sims);
		}
		if (!Number.isFinite(matches) && all.length) matches = all.length;

		if (!Number.isFinite(similarity) && resumenStr) {
			const m =
				resumenStr.match(/similitud\s*([\d.]+)\s*%/i) ||
				resumenStr.match(/(\d{1,3}(?:\.\d+)?)\s*%/);
			if (m) similarity = _toNum(m[1]);
		}
		if (!Number.isFinite(threshold) && resumenStr) {
			const m = resumenStr.match(/umbral\s*([\d.]+)\s*%/i);
			if (m) threshold = _toNum(m[1]);
		}
		if (!Number.isFinite(matches) && resumenStr) {
			const m = resumenStr.match(/(\d+)\s*coincidenc/i);
			if (m) matches = _toInt(m[1]);
		}

		similarity = Number.isFinite(similarity)
			? Math.max(0, Math.min(100, Math.round(similarity)))
			: null;
		threshold = Number.isFinite(threshold)
			? Math.max(0, Math.min(100, Math.round(threshold)))
			: 90;
		matches = Number.isFinite(matches)
			? Math.max(0, Math.round(matches))
			: 0;
		return { similarity, threshold, matches };
	}

	function evalRostroState(similarity, matches, threshold) {
		if ((matches ?? 0) >= 1 && (similarity ?? 0) >= threshold) return "ok";
		if (
			(matches ?? 0) >= 1 &&
			(similarity ?? 0) >= Math.max(0, threshold - 5)
		)
			return "warn";
		return "fail";
	}

	function updateChipsRostro(detalles, resumenes) {
		const rText = (resumenes?.rostro || "").trim();
		const { similarity, threshold, matches } = extractRostro(
			detalles?.rostro,
			rText
		);
		const state = evalRostroState(similarity, matches, threshold);

		const scoreEl = document.getElementById("chip-rostro-score");
		const matchesEl = document.getElementById("chip-rostro-matches");

		if (scoreEl) {
			vhSetChipState(scoreEl, state);
			scoreEl.textContent = `CompareFaces ≥ ${
				Number.isFinite(similarity) ? similarity : "—"
			}%`;
		}
		if (matchesEl) {
			vhSetChipState(matchesEl, state);
			matchesEl.textContent = `${matches} ${
				matches === 1 ? "coincidencia" : "coincidencias"
			}`;
		}

		const p = document.getElementById("txt-rostro");
		if (p) {
			if (rText) p.textContent = rText;
			else {
				const simStr = Number.isFinite(similarity)
					? `${similarity}%`
					: "—";
				p.textContent = `Rostro: similitud ${simStr} (umbral ${threshold}%), ${matches} ${
					matches === 1 ? "coincidencia" : "coincidencias"
				}.`;
			}
		}
	}

	// Exponer en global para loadStatus()
	window.updateChipsRostro = updateChipsRostro;
})();
