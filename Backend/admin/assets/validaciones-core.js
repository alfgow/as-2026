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

	setPill("archivos", sem.archivos);
	setPill("rostro", sem.rostro);
	setPill("identidad", sem.identidad);
	setPill("documentos", sem.documentos);
	setPill("ingresos", sem.ingresos);
	setPill("pago", sem.pago_inicial);
	setPill("demandas", sem.demandas);

	const categories = [
		"archivos",
		"rostro",
		"identidad",
		"documentos",
		"ingresos",
		"pago_inicial",
		"demandas",
	];
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

	// Guardamos detalles globales
	window.__VH_DETALLES__ = j.detalles || j;

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
}

// =====================
// Boot
// =====================
function __bootLoadStatus() {
	loadStatus().catch((e) => {
		console.error(e);
		setText(
			"#vh-resumen",
			"No fue posible cargar el estado de validaciones."
		);
	});
}
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

// Construcción de resumen humano
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

window.savePagoInicial = async function () {
	const chk = document.getElementById("toggle-pago");
	const fd = new FormData();
	fd.append("id_inquilino", String(VH_CTX.idInq));
	fd.append("proceso_pago_inicial", chk?.checked ? "1" : "0");
	fd.append(
		"pago_inicial_resumen",
		chk?.checked ? "Pago inicial confirmado" : "Pago inicial no confirmado"
	);
	const r = await fetch(`${VH_CTX.adminBase}/inquilino/editar-validaciones`, {
		method: "POST",
		body: fd,
		credentials: "include",
	});
	const j = await r.json();
	if (!j?.ok) throw new Error(j?.error || "No se pudo guardar");
	await loadStatus();
};
