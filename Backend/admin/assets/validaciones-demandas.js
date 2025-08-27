// =====================
// Validaciones: Demandas & Jurídico (con ID_INQ)
// =====================
//
// Este módulo maneja toda la lógica de:
// 1. Cargar chips de portales.
// 2. Ver el último reporte detallado (evidencias S3).
// 3. Ejecutar validación manual.
// 4. Mostrar resumen jurídico.
// 5. Auto-refresh tras ejecutar validación.
//
// Expuesto globalmente como window.VH_DEMANDAS
// =====================

(function () {
	const { baseUrl, idInq, slug } = window.VH_CTX; // 👈 usamos idInq para endpoints de Demandas

	// ----------------------
	// Helpers de UI
	// ----------------------
	function setChipsLoading(isLoading) {
		const $chips = document.getElementById("chipsPortales");
		if (!isLoading) {
			return;
		}
		$chips.innerHTML = `
      <div class="flex flex-wrap gap-2">
        <div class="skel h-7 w-28 rounded-full"></div>
        <div class="skel h-7 w-24 rounded-full"></div>
        <div class="skel h-7 w-36 rounded-full"></div>
        <div class="skel h-7 w-20 rounded-full"></div>
      </div>
    `;
	}

	const badge = (txt, tone = "slate") =>
		`<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-${tone}-600/30 text-${tone}-200 border border-${tone}-600/30">${txt}</span>`;

	// ----------------------
	// Cargar chips (resumen)
	// ----------------------
	async function cargarChips() {
		const $chips = document.getElementById("chipsPortales");
		setChipsLoading(true);

		try {
			const res = await fetch(
				`${baseUrl}/validaciones/demandas/resumen/${idInq}`
			);
			const data = await res.json();
			const items = Array.isArray(data.items) ? data.items : [];

			if (!items.length) {
				$chips.innerHTML = `<span class="text-gray-400 text-sm">Sin datos aún.</span>`;
				return;
			}

			const html = items
				.map((it) => {
					const portal = (it.portal || "").toUpperCase();
					const status = it.status || "no_data";
					const clasif = it.clasificacion || "sin_evidencia";
					const score = parseInt(it.score_max || 0, 10);

					const clsStatus = (s) =>
						s === "ok"
							? "bg-emerald-700 text-white"
							: s === "manual_required"
							? "bg-amber-600 text-black"
							: s === "error"
							? "bg-red-700 text-white"
							: "bg-slate-600 text-white";
					const clsClasif = (c) =>
						c === "match_alto"
							? "bg-red-600 text-white"
							: c === "posible_match"
							? "bg-yellow-400 text-black"
							: "bg-emerald-600 text-white";

					return `
          <div class="flex items-center gap-2 bg-white/5 border border-white/10 px-3 py-1.5 rounded-full">
            <span class="text-xs text-indigo-300 font-medium">${portal}</span>
            <span class="px-2 py-0.5 rounded-full text-xs ${clsStatus(
				status
			)}">${status}</span>
            <span class="px-2 py-0.5 rounded-full text-xs ${clsClasif(
				clasif
			)}">${clasif}</span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-700 text-white">score: ${score}</span>
          </div>`;
				})
				.join("");

			$chips.innerHTML = html;
		} catch (e) {
			console.error(e);
			$chips.innerHTML = `<span class="text-red-400 text-sm">Error al cargar chips</span>`;
		}
	}

	// ----------------------
	// Ver último reporte
	// ----------------------
	async function verUltimo() {
		const reporteEl = document.getElementById("reporteContainer");
		if (!reporteEl) return;

		reporteEl.classList.remove("hidden");
		reporteEl.innerHTML = '<div class="text-gray-400">Cargando…</div>';

		try {
			const r = await fetch(
				`${baseUrl}/validaciones/demandas/ultimo/${idInq}`
			);
			const data = await r.json();

			if (!data.ok || !data.reporte) {
				reporteEl.innerHTML =
					'<div class="text-gray-400">Sin reporte reciente.</div>';
				return;
			}

			const rep = data.reporte;
			const resultado = rep.resultado ? JSON.parse(rep.resultado) : null;
			const query = rep.query_usada ? JSON.parse(rep.query_usada) : null;

			const evidenciaLink = rep.evidencia_s3_key
				? `<a href="${linkS3(
						rep.evidencia_s3_key
				  )}" target="_blank" class="text-indigo-300 underline">Ver evidencia</a>`
				: "-";
			const rawJsonLink = rep.raw_json_s3_key
				? `<a href="${linkS3(
						rep.raw_json_s3_key
				  )}" target="_blank" class="text-indigo-300 underline">Ver JSON</a>`
				: "-";

			reporteEl.innerHTML = `
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <p><span class="text-indigo-300">Portal:</span> ${rep.portal}</p>
            <p><span class="text-indigo-300">Status:</span> ${rep.status}</p>
            <p><span class="text-indigo-300">Clasificación:</span> ${
				rep.clasificacion ?? "-"
			}</p>
            <p><span class="text-indigo-300">Score máx:</span> ${
				rep.score_max ?? 0
			}</p>
            <p><span class="text-indigo-300">Consulta:</span> ${
				query?.variante ?? "-"
			} (${query?.fecha ?? "-"})</p>
            <p><span class="text-indigo-300">Fecha búsqueda:</span> ${
				rep.searched_at
			}</p>
          </div>
          <div>
            <p><span class="text-indigo-300">Evidencia S3:</span> ${evidenciaLink}</p>
            <p><span class="text-indigo-300">RAW JSON S3:</span> ${rawJsonLink}</p>
            ${
				rep.error_message
					? `<p class="text-red-300"><span class="text-indigo-300">Error:</span> ${rep.error_message}</p>`
					: ""
			}
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-white font-semibold mb-2">Resultados</h3>
          ${
				Array.isArray(resultado) && resultado.length
					? `
            <div class="space-y-2">
              ${resultado
					.map(
						(it) => `
                <div class="bg-black/20 rounded-lg p-3">
                  <p class="text-sm"><span class="text-indigo-300">Expediente:</span> ${
						it.expediente ?? "-"
					}</p>
                  <p class="text-sm"><span class="text-indigo-300">Juzgado:</span> ${
						it.juzgado ?? "-"
					}</p>
                  <p class="text-sm"><span class="text-indigo-300">Tipo juicio:</span> ${
						it.tipo_juicio ?? "-"
					}</p>
                  <p class="text-sm"><span class="text-indigo-300">Actor:</span> ${
						it.actor ?? "-"
					}</p>
                  <p class="text-sm"><span class="text-indigo-300">Demandado:</span> ${
						it.demandado ?? "-"
					}</p>
                  <p class="text-sm"><span class="text-indigo-300">Fecha:</span> ${
						it.fecha ?? "-"
					}</p>
                  ${
						it.url
							? `<a class="text-indigo-300 underline text-xs" href="${it.url}" target="_blank" rel="noopener">Ver enlace</a>`
							: ""
					}
                </div>
              `
					)
					.join("")}
            </div>
          `
					: `<p class="text-gray-400 text-sm">Sin resultados.</p>`
			}
        </div>
      `;
		} catch (e) {
			reporteEl.innerHTML = `<div class="text-red-300">Error: ${e.message}</div>`;
		}
	}

	// ----------------------
	// Ejecutar validación manual
	// ----------------------
	async function ejecutarValidacion() {
		const btn = document.getElementById("btnRunValidacion");
		if (!btn) return;

		const metaEl = document.getElementById("vh-meta");
		const datos = metaEl
			? {
					nombre: metaEl.dataset.nombre || "",
					apellido_p: metaEl.dataset.apellidop || "",
					apellido_m: metaEl.dataset.apellidom || "",
					curp: metaEl.dataset.curp || "",
					rfc: metaEl.dataset.rfc || "",
					slug: metaEl.dataset.slug || "",
			  }
			: { nombre: "", apellido_p: "" };

		if (!datos.nombre || !datos.apellido_p) {
			Swal?.fire({
				icon: "warning",
				title: "Faltan datos",
				text: "Nombre y Apellido paterno son obligatorios.",
			});
			return;
		}

		btn.disabled = true;
		const prevTxt = btn.textContent;
		btn.textContent = "Ejecutando…";

		try {
			const body = new FormData();
			body.append("nombre", datos.nombre);
			body.append("apellido_p", datos.apellido_p);
			if (datos.apellido_m) body.append("apellido_m", datos.apellido_m);
			if (datos.curp) body.append("curp", datos.curp);
			if (datos.rfc) body.append("rfc", datos.rfc);
			if (datos.slug) body.append("slug", datos.slug);

			const res = await fetch(
				`${baseUrl}/validaciones/demandas/run/${idInq}`,
				{ method: "POST", body }
			);
			const data = await res.json();

			if (data.ok) {
				Swal?.fire({
					icon: "success",
					title: "🔎 Intentos registrados",
					text: "Se inició la validación.",
				});
				await cargarChips();
				postRunAutoRefresh();
			} else {
				Swal?.fire({
					icon: "error",
					title: "No se pudo iniciar",
					text: data.mensaje || "Intenta de nuevo.",
				});
			}
		} catch (e) {
			Swal?.fire({
				icon: "error",
				title: "Error de red",
				text: "No pudimos conectar con el servidor.",
			});
		} finally {
			btn.disabled = false;
			btn.textContent = prevTxt;
		}
	}

	// ----------------------
	// Resumen Jurídico (este sí usa slug)
	// ----------------------
	async function cargarResumenJuridico() {
		const endpoint = `${baseUrl}/inquilino/${slug}/validaciones/juridico`;
		const statusEl = document.getElementById("juridicoStatus");
		const resumenEl = document.getElementById("juridicoResumen");
		const evidEl = document.getElementById("juridicoEvidencias");

		try {
			const r = await fetch(endpoint, {
				headers: { Accept: "application/json" },
			});
			const data = await r.json();
			if (!data.ok) {
				statusEl && (statusEl.textContent = "error");
				resumenEl &&
					(resumenEl.innerHTML = `<span class="text-red-300">${
						data.mensaje || "No se pudo obtener la validación"
					}</span>`);
				return;
			}

			const rep = data.reporte || {};
			const evidencias = Array.isArray(rep.evidencias)
				? rep.evidencias
				: [];
			const statusTone =
				rep.status === "ok"
					? "green"
					: rep.status === "error"
					? "red"
					: "amber";
			statusEl &&
				(statusEl.innerHTML = badge(
					rep.status || "sin_datos",
					statusTone
				));

			const clasTone =
				rep.clasificacion === "alto"
					? "red"
					: rep.clasificacion === "medio"
					? "amber"
					: "slate";
			const score = rep.scoring ?? rep.score ?? rep.score_max ?? "-";
			resumenEl &&
				(resumenEl.innerHTML = `
        <div class="flex flex-wrap gap-2 items-center">
          ${badge("clasificación: " + (rep.clasificacion || "-"), clasTone)}
          ${badge("score: " + score, "indigo")}
          ${badge("evidencias: " + evidencias.length, "cyan")}
        </div>
      `);

			if (!evidencias.length) {
				evidEl &&
					(evidEl.innerHTML = `<p class="text-gray-400 text-sm">Sin evidencias.</p>`);
				return;
			}

			evidEl &&
				(evidEl.innerHTML = evidencias
					.map((ev) => {
						const fecha = ev.fecha || "-";
						const tribunal = ev.tribunal || "-";
						const expediente = ev.expediente || "-";
						const link = ev.link
							? `<a href="${ev.link}" target="_blank" rel="noopener" class="text-indigo-300 underline text-xs">ver enlace</a>`
							: "";
						const archivo = ev.archivo
							? `<div class="text-xs text-gray-400 break-all">archivo: ${ev.archivo}</div>`
							: "";
						return `
          <div class="bg-white/5 rounded-lg p-3">
            <div class="flex flex-wrap gap-2 mb-2">
              ${badge(tribunal, "purple")}
              ${badge("Exp.: " + expediente, "slate")}
              ${badge(fecha, "teal")}
            </div>
            <div class="flex items-center gap-3">
              ${link}
            </div>
            ${archivo}
          </div>
        `;
					})
					.join(""));
		} catch (e) {
			statusEl && (statusEl.textContent = "error");
			resumenEl &&
				(resumenEl.innerHTML = `<span class="text-red-300">${e.message}</span>`);
		}
	}

	// ----------------------
	// Auto-refresh tras run
	// ----------------------
	let autoTimer;
	function postRunAutoRefresh() {
		const start = Date.now();
		clearInterval(autoTimer);
		autoTimer = setInterval(() => {
			cargarChips();
			verUltimo();
			if (Date.now() - start > 90_000) clearInterval(autoTimer);
		}, 6_000);
	}

	// ----------------------
	// Binds
	// ----------------------
	document
		.getElementById("btnRunValidacion")
		?.addEventListener("click", ejecutarValidacion);
	document
		.getElementById("btnVerUltimo")
		?.addEventListener("click", verUltimo);

	// ----------------------
	// Init
	// ----------------------
	(async () => {
		await cargarChips();
		await cargarResumenJuridico();
	})();

	// ----------------------
	// Expose
	// ----------------------
	window.VH_DEMANDAS = {
		cargarChips,
		verUltimo,
		ejecutarValidacion,
		cargarResumenJuridico,
	};
})();
