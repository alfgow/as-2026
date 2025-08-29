// =====================
// Validaciones: Demandas & Jurídico (Google API)
// =====================
//
// Este módulo maneja toda la lógica de:
// 1. Ejecutar validación manual (Google).
// 2. Ver el último reporte guardado en la BD.
// 3. Mostrar resumen jurídico y resultados filtrados.
// 4. Renderizar historial en la vista.
//
// =====================

(function () {
	const { baseUrl, idInq, slug } = window.VH_CTX;

	const badge = (txt, tone = "slate") =>
		`<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-${tone}-600/30 text-${tone}-200 border border-${tone}-600/30">${txt}</span>`;

	// ----------------------
	// Ver último reporte
	// ----------------------
	async function verUltimo() {
		try {
			// Loader inicial
			Swal.fire({
				title: "Cargando último reporte...",
				text: "Por favor espera unos segundos.",
				allowOutsideClick: false,
				didOpen: () => {
					Swal.showLoading();
				},
			});

			const r = await fetch(
				`${baseUrl}/validaciones/demandas/ultimo/${idInq}`
			);
			const data = await r.json();

			Swal.close(); // 🔴 siempre cerramos el loader

			const reporteEl = document.getElementById("reporteContainer");
			if (!reporteEl) return;

			reporteEl.classList.remove("hidden");

			if (!data.ok || !data.reporte) {
				reporteEl.innerHTML = `<div class="text-gray-400">Sin reporte reciente.</div>`;
				return;
			}

			const rep = data.reporte;
			const resultados = Array.isArray(rep.resultado)
				? rep.resultado
				: [];

			// 🟢 Resumen jurídico
			const resumenEl = document.getElementById("juridicoResumen");
			const statusEl = document.getElementById("juridicoStatus");
			const evidEl = document.getElementById("juridicoEvidencias");

			if (statusEl) statusEl.textContent = rep.status || "sin_datos";
			if (resumenEl) {
				resumenEl.innerHTML = `
                <div class="flex flex-wrap gap-2 items-center">
                    ${badge(
						"clasificación: " + (rep.clasificacion || "-"),
						rep.clasificacion === "match_alto"
							? "red"
							: rep.clasificacion === "posible_match"
							? "amber"
							: "slate"
					)}
                    ${badge("score: " + (rep.score_max ?? 0), "indigo")}
                    ${badge("resultados: " + resultados.length, "cyan")}
                </div>
            `;
			}

			// 🟣 Resultados listados
			if (evidEl) {
				if (resultados.length) {
					evidEl.innerHTML = resultados
						.map(
							(r) =>
								`<div class="bg-white/5 rounded-lg p-3">
                                <a href="${r.link}" target="_blank" rel="noopener" class="text-indigo-300 underline font-medium">${r.titulo}</a>
                                <p class="text-sm text-gray-300 mt-1">${r.snippet}</p>
                            </div>`
						)
						.join("");
				} else {
					evidEl.innerHTML = `<p class="text-gray-400 text-sm">No se encontraron resultados relevantes.</p>`;
				}
			}

			// 📦 Mostrar siempre algo en el contenedor
			reporteEl.innerHTML =
				resultados.length > 0
					? `<div class="space-y-3">
                    ${resultados
						.map(
							(r) =>
								`<div class="bg-black/20 rounded-lg p-3">
                                    <a href="${r.link}" target="_blank" rel="noopener" class="text-indigo-300 underline font-medium">${r.titulo}</a>
                                    <p class="text-sm text-gray-300 mt-1">${r.snippet}</p>
                                </div>`
						)
						.join("")}
                   </div>`
					: `<p class="text-gray-400 text-sm">⚖️ No se encontraron coincidencias jurídicas para este inquilino.</p>`;
		} catch (e) {
			Swal.close(); // 🔴 cerramos también en caso de error
			const reporteEl = document.getElementById("reporteContainer");
			if (reporteEl)
				reporteEl.innerHTML = `<div class="text-red-300">Error: ${e.message}</div>`;
			Swal.fire({
				icon: "error",
				title: "Error",
				text: "No se pudo cargar el reporte.",
			});
		}
	}

	// ----------------------
	// Ejecutar validación
	// ----------------------
	async function ejecutarValidacion() {
		const btn = document.getElementById("btnRunValidacion");
		if (!btn) return;

		const metaEl = document.getElementById("vh-meta");
		const datos = metaEl
			? {
					nombre: metaEl.dataset.nombre || "",
					apellido_p: metaEl.dataset.apellido_p || "",
					apellido_m: metaEl.dataset.apellido_m || "",
					curp: metaEl.dataset.curp || "",
					rfc: metaEl.dataset.rfc || "",
					slug: metaEl.dataset.slug || "",
			  }
			: { nombre: "", apellido_p: "" };

		if (!datos.nombre || !datos.apellido_p) {
			Swal.fire({
				icon: "warning",
				title: "Faltan datos",
				text: "Nombre y Apellido paterno son obligatorios.",
			});
			return;
		}

		try {
			Swal.fire({
				title: "Ejecutando validación...",
				text: "Esto puede tardar unos segundos.",
				allowOutsideClick: false,
				didOpen: () => {
					Swal.showLoading();
				},
			});

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

			Swal.close();

			if (data.ok) {
				Swal.fire({
					icon: "success",
					title: "🔎 Validación completada",
					text: `Se encontraron ${data.total} resultados.`,
				});
				// 🔄 Refrescamos chips/resumen desde el backend
				if (window.VH_DEMANDAS?.cargarChips) {
					await window.VH_DEMANDAS.cargarChips();
				}
				if (window.VH_DEMANDAS?.cargarHistorial) {
					await window.VH_DEMANDAS.cargarHistorial();
				}
				// Y volvemos a mostrar el último reporte
				await window.VH_DEMANDAS.verUltimo();
			} else {
				Swal.fire({
					icon: "error",
					title: "No se pudo iniciar",
					text: data.mensaje || "Intenta de nuevo.",
				});
			}
		} catch (e) {
			Swal.fire({
				icon: "error",
				title: "Error de red",
				text: "No pudimos conectar con el servidor.",
			});
		}
	}

	// =====================
	// Nueva función: cargarHistorial()
	// =====================
	async function cargarHistorial() {
		const cont = document.getElementById("historialContainer");
		if (!cont) return;

		try {
			const r = await fetch(
				`${baseUrl}/validaciones/demandas/historial/${idInq}`,
				{
					headers: { Accept: "application/json" },
				}
			);
			const data = await r.json();

			if (!data.ok || !Array.isArray(data.historial)) {
				cont.innerHTML =
					'<div class="text-center text-gray-400">⚠️ No hay registros de validaciones legales para este inquilino.</div>';
				return;
			}

			const filas = data.historial
				.map(
					(item) => `
				<tr>
					<td class="px-4 py-2">${item.searched_at}</td>
					<td class="px-4 py-2">
						<span class="px-2 py-1 rounded-full text-xs 
							${
								item.clasificacion === "match_alto"
									? "bg-red-600 text-white"
									: item.clasificacion === "posible_match"
									? "bg-yellow-400 text-black"
									: "bg-green-600 text-white"
							}">
							${item.clasificacion ?? "sin_evidencia"}
						</span>
					</td>
					<td class="px-4 py-2">
						<span class="px-2 py-1 rounded-full text-xs
							${
								item.status === "ok"
									? "bg-emerald-600 text-white"
									: item.status === "error"
									? "bg-red-600 text-white"
									: item.status === "manual_required"
									? "bg-amber-500 text-black"
									: "bg-slate-600 text-white"
							}">
							${item.status}
						</span>
					</td>
					<td class="px-4 py-2 text-gray-400">
						${
							item.resultado &&
							JSON.parse(item.resultado).length > 0
								? JSON.parse(item.resultado).length +
								  " coincidencia(s)"
								: "⚠️ Sin resultados"
						}
					</td>
				</tr>`
				)
				.join("");

			cont.innerHTML = `
			<div class="overflow-x-auto rounded-xl border border-gray-700">
				<table class="min-w-full text-sm text-left text-gray-300">
					<thead class="bg-gray-800 text-gray-400 uppercase text-xs">
						<tr>
							<th class="px-4 py-2">Fecha</th>
							<th class="px-4 py-2">Clasificación</th>
							<th class="px-4 py-2">Estatus</th>
							<th class="px-4 py-2">Resultados</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-700 bg-gray-900">
						${filas}
					</tbody>
				</table>
			</div>
		`;
		} catch (e) {
			cont.innerHTML = `<div class="text-red-300">Error: ${e.message}</div>`;
		}
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

	// ----------------------
	// Expose
	// ----------------------
	window.VH_DEMANDAS = { verUltimo, ejecutarValidacion, cargarHistorial };
})();
document.addEventListener("DOMContentLoaded", async () => {
	await window.VH_DEMANDAS.cargarHistorial();
	await window.VH_DEMANDAS.verUltimo();
});
document.addEventListener("DOMContentLoaded", () => {
	const chk = document.getElementById("toggle-demandas");
	if (!chk) return;
	chk.addEventListener("change", () => {
		saveSwitch("demandas");
	});
});
