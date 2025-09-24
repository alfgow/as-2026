// =====================
// Globals context
// =====================
window.VH_CTX = {
	baseUrl: window.baseUrl,
	adminBase: window.ADMIN_BASE,
	slug: window.SLUG,
	idInq: window.ID_INQ,
};

// =====================
// Helpers UI
// =====================
window.vhSetChipState = function (el, state) {
	if (!el) return;
	el.classList.remove(
		"border-emerald-400/30",
		"bg-emerald-400/15",
		"border-amber-400/30",
		"bg-amber-400/15",
		"border-rose-400/30",
		"bg-rose-400/15",
		"border-white/10",
		"bg-white/5"
	);
	if (state === "ok")
		el.classList.add("border-emerald-400/30", "bg-emerald-400/15");
	else if (state === "warn")
		el.classList.add("border-amber-400/30", "bg-amber-400/15");
	else el.classList.add("border-rose-400/30", "bg-rose-400/15");
};

const $ = (s) => document.querySelector(s);
const setText = (sel, txt) => {
	const el = $(sel);
	if (el) el.textContent = txt;
};
const pct = (n) => Math.max(0, Math.min(100, Math.round(n)));

const globalLoader = document.getElementById('global-loader');

function vhShowLoader() {
	if (globalLoader) globalLoader.classList.remove('hidden');
}

function vhHideLoader() {
	if (globalLoader) globalLoader.classList.add('hidden');
}

window.vhShowLoader = vhShowLoader;
window.vhHideLoader = vhHideLoader;

function vhIcon(v) {
	const n = Number(v);
	if (n === 1) return "✅";
	if (n === 0) return "🚫";
	return "⏳";
}
function vhLabel(v) {
	const n = Number(v);
	if (n === 1) return "OK";
	if (n === 0) return "No OK";
	return "Pendiente";
}
function buildResumenHumano(sem = {}, R = {}) {
	if (R?.global && String(R.global).trim()) return R.global;
	const parts = [];
	const push = (k, label, extra = "") => {
		if (sem[k] === undefined) return;
		parts.push(
			`${vhIcon(sem[k])} ${label}${extra ? " (" + extra + ")" : ""}`
		);
	};
	const extraIngresos = /\b(\d+)\s*\/\s*(\d+)/.exec(R?.ingresos || "");
	const ingresosTag = extraIngresos ? `${extraIngresos[0]}` : "";
	push("identidad", "Identidad");
	push("rostro", "Rostro");
	push("documentos", "Documentos");
	push("archivos", "Archivos");
	push("ingresos", "Ingresos", ingresosTag);
	push("pago_inicial", "Pago inicial");
	push("demandas", "Demandas");
	return parts.join(" · ");
}

// =====================
// loadStatus
// =====================
async function loadStatus() {
	const { adminBase, slug } = window.VH_CTX;
	const url = `${adminBase}/inquilino/${encodeURIComponent(
		slug
	)}/validar?check=status`;
	const res = await fetch(url, { credentials: "include" });
	const j = await res.json();
	if (!j?.ok) throw new Error(j?.mensaje || "No fue posible obtener estado");

	const sem = j.semaforos || {};
	if (!Object.keys(sem).length && j.resumen) {
		for (const k of Object.keys(j.resumen))
			sem[k] = j.resumen[k]?.proceso ?? 2;
	}

	// 🔎 Ahora también contemplamos verificamex
	setPill("archivos", sem.archivos);
	setPill("rostro", sem.rostro);
	setPill("identidad", sem.identidad);
	setPill("verificamex", sem.verificamex);
	setPill("ingresos", sem.ingresos);
	setPill("pago", sem.pago_inicial);
	setPill("demandas", sem.demandas);

	const toggleMap = {
		archivos: "archivos",
		rostro: "rostro",
		identidad: "identidad",
		verificamex: "verificamex",
		ingresos: "ingresos",
		pago_inicial: "pago_inicial",
		demandas: "demandas",
	};

	Object.entries(toggleMap).forEach(([domKey, semKey]) => {
		if (typeof sem[semKey] === "undefined") return;
		const value = Number(sem[semKey]);
		const chk = document.getElementById(`toggle-${domKey}`);
		if (chk) chk.checked = value === 1;
		const label = document.getElementById(`toggle-${domKey}-label`);
		if (label) {
			label.textContent =
				value === 1 ? "Confirmado" : value === 0 ? "No OK" : "Pendiente";
		}
	});

	actualizarStatusAutomatico(sem);

	// 🔎 Categorías actualizadas (incluye verificamex)
	// Construir categorías dinámicas según backend
	const categories = [
		"archivos",
		"rostro",
		"identidad",
		"ingresos",
		"pago_inicial",
		"demandas",
	];

	// Agregar verificamex solo si backend lo envió
	if (typeof sem.verificamex !== "undefined") {
		categories.splice(3, 0, "verificamex");
		// 👆 lo meto en orden después de identidad
	}

	const completedCount = categories.reduce(
		(acc, k) => acc + (Number(sem?.[k]) === 1 ? 1 : 0),
		0
	);
	const totalCount = categories.length;
	const progressPct = pct((completedCount / totalCount) * 100);

	const bar = document.getElementById("vh-progress");
	if (bar) bar.style.width = progressPct + "%";
	setText(
		"#vh-progress-text",
		`${completedCount} de ${totalCount} validaciones completas`
	);

	const R = j.resumenes || {};
	const resumenHumano = buildResumenHumano(sem, R);
	setText("#vh-resumen", R.global ? R.global : resumenHumano);

	if (R.archivos) setText("#txt-archivos", R.archivos);
	if (R.rostro) setText("#txt-rostro", R.rostro);
	if (R.identidad) setText("#txt-identidad", R.identidad);
	if (R.verificamex) setText("#txt-verificamex", R.verificamex); // ✅ NUEVO
	if (R.documentos) setText("#txt-documentos", R.documentos);
	if (R.ingresos) setText("#txt-ingresos", R.ingresos);
	if (R.pago_inicial) setText("#txt-pago", R.pago_inicial);
	if (R.demandas) setText("#txt-demandas", R.demandas);

	const tsApi = j.updated_at || j.ts || null;
	const ts = tsApi
		? new Date(tsApi).toLocaleString()
		: new Date().toLocaleString();
	setText("#vh-ts", `Última actualización ${ts}`);
	setText("#vh-ts-bottom", `Última actualización ${ts}`);

        // Guardamos detalles globales fusionando con lo previamente cargado
        const incomingDetalles = (() => {
                if (j && typeof j === "object" && j.detalles && typeof j.detalles === "object") {
                        return j.detalles;
                }
                return j && typeof j === "object" ? j : {};
        })();

        const prevDetalles =
                window.__VH_DETALLES__ && typeof window.__VH_DETALLES__ === "object"
                        ? window.__VH_DETALLES__
                        : {};

        const mergedDetalles = { ...prevDetalles };
        Object.entries(incomingDetalles || {}).forEach(([clave, valor]) => {
                if (valor !== undefined && valor !== null) {
                        mergedDetalles[clave] = valor;
                } else if (!(clave in mergedDetalles)) {
                        mergedDetalles[clave] = valor;
                }
        });

        window.__VH_DETALLES__ = mergedDetalles;

	// 🔎 Normalizamos identidad
	const identidadInfo = j.detalles?.identidad || {};
	if (identidadInfo?.json?.curp) {
		identidadInfo.curp = identidadInfo.json.curp;
	}

	// Actualizar chips
	if (typeof updateChipsRostro === "function")
		updateChipsRostro(window.__VH_DETALLES__, R);
	if (typeof updateChipsIdentidad === "function")
		updateChipsIdentidad(identidadInfo, R);

	return { semaforos: sem, resumenes: R };
}

// =====================
// Boot
// =====================
// =====================
// Boot con loader integrado
// =====================
function __bootLoadStatus() {
	const loader = document.getElementById("vh-loader");
	if (loader) loader.classList.remove("hidden");

        loadStatus()
                .then(() => {
                        if (loader) loader.classList.add("hidden");
                })
                .catch(() => {
                        setText(
                                "#vh-resumen",
                                "No fue posible cargar el estado de validaciones."
                        );
                        if (loader) loader.classList.add("hidden");
                });
}

if (document.readyState !== "loading") __bootLoadStatus();
else document.addEventListener("DOMContentLoaded", __bootLoadStatus);

if (document.readyState !== "loading") __bootLoadStatus();
else document.addEventListener("DOMContentLoaded", __bootLoadStatus);

// Helpers globales de selección
window.$ = (s) => document.querySelector(s);
window.$$ = (s) => Array.from(document.querySelectorAll(s));

// =====================
// Helper: setPill (global)
// =====================
window.setPill = function (id, val) {
	const el = document.getElementById("pill-" + id);
	if (!el) return;
	const dot = el.querySelector("span.rounded-full");
	if (!dot) return;
	const v = String(val).toUpperCase();

	dot.classList.remove("bg-emerald-500", "bg-amber-500", "bg-rose-500");

	if (v === "1" || v === "OK") dot.classList.add("bg-emerald-500");
	else if (v === "0" || v === "NO_OK") dot.classList.add("bg-rose-500");
	else dot.classList.add("bg-amber-500");
};

// =====================
// Helpers adicionales globales
// =====================

// Formatear objetos bonitos (JSON pretty)
window.pretty = (o) => {
	try {
		return JSON.stringify(o, null, 2);
	} catch {
		return String(o ?? "");
	}
};

// Labels / Icons para resúmenes
window.vhIcon = (v) => {
	const n = Number(v);
	if (n === 1) return "✅";
	if (n === 0) return "🚫";
	return "⏳";
};

window.vhLabel = (v) => {
	const n = Number(v);
	if (n === 1) return "OK";
	if (n === 0) return "No OK";
	return "Pendiente";
};

// =====================
// buildResumenHumano (patch con verificamex)
// =====================
window.buildResumenHumano = function (sem = {}, R = {}) {
	if (R?.global && String(R.global).trim()) return R.global;
	const parts = [];
	const push = (k, label, extra = "") => {
		if (sem[k] === undefined) return;
		parts.push(
			`${vhIcon(sem[k])} ${label}${extra ? " (" + extra + ")" : ""}`
		);
	};

	const extraIngresos = /\b(\d+)\s*\/\s*(\d+)/.exec(R?.ingresos || "");
	const ingresosTag = extraIngresos ? `${extraIngresos[0]}` : "";

	push("identidad", "Identidad");
	push("rostro", "Rostro");
	push("documentos", "Documentos");
	push("archivos", "Archivos");
	push("verificamex", "VerificaMex"); // ✅ NUEVO
	push("ingresos", "Ingresos", ingresosTag);
	push("pago_inicial", "Pago inicial");
	push("demandas", "Demandas");

	return parts.join(" · ");
};

// Construcción de link S3 (usado en Demandas)
window.linkS3 = function (key) {
	if (!key) return "#";
	const bucket =
		window.S3_BUCKET_INQUILINOS ||
		(typeof S3_BUCKET_INQUILINOS !== "undefined"
			? S3_BUCKET_INQUILINOS
			: "");
	return bucket
		? `https://${bucket}.s3.amazonaws.com/${encodeURIComponent(key)}`
		: encodeURIComponent(key);
};

// =====================
// Exponer funciones globales para otros módulos
// =====================
window.recalc = async function (check) {
	const u = `${VH_CTX.adminBase}/inquilino/${encodeURIComponent(
		VH_CTX.slug
	)}/validar?check=${encodeURIComponent(check)}`;
	const r = await fetch(u, { credentials: "include" });
	try {
		await r.json();
	} catch {}
	await loadStatus();
};

window.savePagoInicial = function () {
	return window.saveSwitch('pago_inicial');
};

window.saveArchivos = function () {
	return window.saveSwitch('archivos');
};
window.saveSwitch = async function (campo) {
	const chk = document.getElementById(`toggle-${campo}`);
	if (!chk) return;
	const fd = new FormData();
	fd.append("id_inquilino", String(VH_CTX.idInq));

	// ✅ Mapear cada campo al nombre real de columna en la BD
	const map = {
		archivos: "proceso_validacion_archivos",
		rostro: "proceso_validacion_rostro",
		identidad: "proceso_validacion_id",
		ingresos: "proceso_validacion_ingresos",
		pago_inicial: "proceso_pago_inicial",
		demandas: "proceso_inv_demandas",
		verificamex: "proceso_validacion_verificamex",
	};

	const col = map[campo];
        if (!col) {
                return;
        }

	const estado = chk.checked ? "1" : "2";
	fd.append(col, estado);

	// 👇 Ajustar nombre del resumen según campo
	let resumenKey;
	switch (campo) {
		case "verificamex":
			resumenKey = "verificamex_resumen";
			break;
		case "pago_inicial":
			resumenKey = "pago_inicial_resumen";
			break;
		default:
			resumenKey = `${col.replace("proceso_", "")}_resumen`;
			break;
	}

	fd.append(
		resumenKey,
		chk?.checked
			? `Validación de ${campo} completada manualmente`
			: `Validación de ${campo} pendiente`
	);

	const payload = {
		origen: "manual",
		estado:
			estado === "1"
				? "confirmado"
				: (estado === "0" ? "no_ok" : "pendiente"),
		timestamp: new Date().toISOString(),
		campo,
	};

	fd.append(`${col}_json`, JSON.stringify(payload));

	try {
		vhShowLoader();
		const r = await fetch(`${VH_CTX.adminBase}/inquilino/editar-validaciones`, {
			method: "POST",
			body: fd,
			credentials: "include",
		});
		const j = await r.json().catch(() => ({}));
		vhHideLoader();
		if (!r.ok || !j?.ok) {
			throw new Error(
				j?.error || `No se pudo guardar validación de ${campo}`
			);
		}
		const status = await loadStatus();
		actualizarStatusAutomatico(status?.semaforos || {});
        } catch (error) {
                vhHideLoader();
                chk.checked = !chk.checked;
                alert(error.message || `No se pudo guardar validación de ${campo}`);
        }
};

function actualizarStatusAutomatico(sem = {}) {
	const keys = [
		'archivos',
		'rostro',
		'identidad',
		'verificamex',
		'ingresos',
		'pago_inicial',
		'demandas',
	];
	let verdes = 0;
	keys.forEach((k) => {
		if (Number(sem[k]) === 1) verdes++;
	});

	let nuevoStatus = null;
	if (verdes >= 6) nuevoStatus = '2'; // Aprobado
	else if (verdes >= 1) nuevoStatus = '3'; // En proceso
	else nuevoStatus = '1';

	const select = document.getElementById('select-status');
	if (!select) return;

	if (select.value === nuevoStatus) return;
	select.value = nuevoStatus;
	actualizarStatusBackend(nuevoStatus);
}

async function actualizarStatusBackend(status) {
	try {
		vhShowLoader();
		const fd = new FormData();
		fd.append('id_inquilino', String(VH_CTX.idInq));
		fd.append('status', status);
		const res = await fetch(`${VH_CTX.adminBase}/inquilino/editar-status`, {
			method: 'POST',
			body: fd,
			credentials: 'include',
		});
		const j = await res.json().catch(() => ({}));
		vhHideLoader();
		if (!res.ok || !j?.ok) {
			throw new Error(j?.error || 'No se pudo actualizar status');
		}
        } catch (err) {
                vhHideLoader();
        }
}

window.runIngresosProceso = async function (tipo) {
	vhShowLoader();
	try {
		const url = `${VH_CTX.adminBase}/inquilino/${encodeURIComponent(
			VH_CTX.slug
		)}/validar?check=${tipo}`;
		const res = await fetch(url, { credentials: "include" });
		const j = await res.json();

		if (!j?.ok) {
			throw new Error(j?.mensaje || "Error en el proceso de ingresos");
		}
		if (j?.resultado) {
			renderIngresos(j.resultado);
		}

		await loadStatus();
        } catch (e) {
                alert(e.message || "No fue posible validar los ingresos.");
        } finally {
                vhHideLoader();
        }
};
function renderIngresos(resultado) {
	if (!resultado) return;

	const declarado = parseFloat(
		window.__VH_DETALLES__?.ingresos?.declarado || 50000
	); // <- ejemplo, adáptalo a tu BD
	const meses = resultado.meses_en_rango6 || 0;
	const docs = resultado.docs || 0;

	// ingreso calculado = sumatoria estimada de montos detectados
	const parsedDocs = resultado.detalles || [];
	const calculado = parsedDocs.reduce(
		(acc, d) => acc + (d.parsed?.monto || 0),
		0
	);

	const diferencia =
		declarado > 0
			? (((calculado - declarado) / declarado) * 100).toFixed(1)
			: 0;

	document.getElementById("ingreso-declarado").textContent =
		"$" + declarado.toLocaleString("es-MX");
	document.getElementById("ingreso-calculado").textContent =
		"$" + calculado.toLocaleString("es-MX");
	document.getElementById("ingreso-diferencia").textContent =
		diferencia + "%";
}
document.addEventListener("DOMContentLoaded", () => {
	const selStatus = document.getElementById("select-status");
	if (!selStatus) return;

	selStatus.addEventListener("change", async () => {
		const nuevoStatus = selStatus.value;
                try {
                        await actualizarStatusBackend(nuevoStatus);
                } catch (e) {
                        alert(e.message || "Error al actualizar el status");
                }
        });
});
