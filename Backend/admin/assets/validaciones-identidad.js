// =====================
// validaciones-identidad.js
// =====================

// Actualiza chips de Identidad
window.updateChipsIdentidad = function (info, R) {
	const chipNombres = document.getElementById("chip-identidad-nombres");
	const chipCurp = document.getElementById("chip-identidad-curp");

	// defaults
	vhSetChipState(chipNombres, "warn");
	vhSetChipState(chipCurp, "warn");

	// --- Nombres ---
	const det = info?.json?.detalles || {};

	if (det.apellidop && det.apellidom && det.nombres) {
		vhSetChipState(chipNombres, "ok");
	} else if (det.apellidop || det.apellidom || det.nombres) {
		vhSetChipState(chipNombres, "warn");
	} else {
		vhSetChipState(chipNombres, "fail");
	}

	// --- CURP ---
	const curp = info?.json?.curp || null;
	if (curp && typeof curp === "string" && curp.trim().length === 18) {
		vhSetChipState(chipCurp, "ok");
	} else if (curp) {
		vhSetChipState(chipCurp, "warn");
	} else {
		vhSetChipState(chipCurp, "fail");
	}
};

// =====================
// Botones de acción
// =====================

// Leer nombres/apellidos via Textract KV
window.leerNombresApellidos = async function () {
	const btn = document.getElementById("btn-leer-nombres");
	if (btn) btn.disabled = true;

	try {
		await recalc("kv"); // trigger en backend
        } catch (e) {
                // Error al recalcular nombres, se omite registro en consola
        }
	if (btn) btn.disabled = false;
};

// Leer CURP / CIC via OCR parse
window.leerCurpCic = async function () {
	const btn = document.getElementById("btn-leer-curp");
	if (btn) btn.disabled = true;

	try {
		await recalc("save_curp_cic"); // trigger en backend
        } catch (e) {
                // Error al recalcular CURP/CIC, se omite registro en consola
        }
	if (btn) btn.disabled = false;
};
