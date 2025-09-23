// assets/js/polizas.js

(function () {
        const BASE_URL = window.BASE_URL || "";

	// ---------------- Utilidades de fechas ----------------
	const MESES = [
		"Enero",
		"Febrero",
		"Marzo",
		"Abril",
		"Mayo",
		"Junio",
		"Julio",
		"Agosto",
		"Septiembre",
		"Octubre",
		"Noviembre",
		"Diciembre",
	];

	function formatearFecha(yyyy_mm_dd) {
		if (!yyyy_mm_dd) return "";
		const [y, m, d] = yyyy_mm_dd.split("-");
		const idx = parseInt(m, 10) - 1;
		return `${d} de ${MESES[idx]} de ${y}`;
	}

	function setFechaFinYVigencia({ inicioEl, finEl, vigenciaEl }) {
		if (!inicioEl || !finEl) return;
		if (!inicioEl.value) {
			finEl.value = "";
			if (vigenciaEl) vigenciaEl.value = "";
			return;
		}
		const [y, m, d] = inicioEl.value.split("-").map(Number);
		const f = new Date(y + 1, m - 1, d);
		f.setDate(f.getDate() - 1);
		const yf = f.getFullYear();
		const mf = String(f.getMonth() + 1).padStart(2, "0");
		const df = String(f.getDate()).padStart(2, "0");
		finEl.value = `${yf}-${mf}-${df}`;
		if (vigenciaEl) {
			vigenciaEl.value = `del ${formatearFecha(
				inicioEl.value
			)} al ${formatearFecha(finEl.value)}`;
		}
	}

	// ---------------- Cálculo de monto de póliza ----------------
	function calcularPoliza(montoRenta, tipoPoliza) {
		const renta = parseFloat(montoRenta) || 0;
		const tramos = {
			Clásica: [
				3700,
				4300,
				4500,
				5200,
				5500,
				8100,
				9300,
				10000,
				12000,
				renta * 0.25,
			],
			Plus: [
				4800,
				5500,
				7500,
				8600,
				9400,
				11000,
				11500,
				13750,
				14250,
				renta * 0.3,
			],
		};
		const rangos = [
			10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000,
		];
		const precios = tramos[tipoPoliza] || [];
		for (let i = 0; i < rangos.length; i++) {
			if (renta <= rangos[i]) return (precios[i] ?? 0).toFixed(2);
		}
		return (precios[precios.length - 1] ?? 0).toFixed(2);
	}

	// ---------------- Enlazadores para EDITAR ----------------
	function initEditarFormulario() {
		const form = document.getElementById("form-editar-poliza");
		if (!form) return;

                const inmuebleSelect = document.getElementById("inmueble-select");
                const rentaHidden = document.getElementById("monto-renta-hidden");
                const rentaDisplay = document.getElementById("monto-renta-display");
                const refreshRentBtn = document.getElementById("btn-refrescar-renta");
                const tipoPoliza = form.tipo_poliza;
                const montoPoliza = document.getElementById("monto-poliza");
                const fechaInicio = document.getElementById("fecha-inicio");
                const fechaFin = document.getElementById("fecha-fin");
                const vigenciaText = document.getElementById("vigencia-texto");
                const numeroPolizaInput = form.querySelector('input[name="numero_poliza"]');

                let montoPolizaEditadoManualmente = false;

                montoPoliza?.addEventListener("input", () => {
                        montoPolizaEditadoManualmente = true;
                });

                const obtenerRentaLimpia = (valor) =>
                        (valor || "").toString().replace(/[^0-9.]/g, "");

                const actualizarDisplayRenta = (valorLimpio) => {
                        if (!rentaDisplay) return;
                        if (!valorLimpio) {
                                rentaDisplay.value = "";
                                return;
                        }

                        const numero = Number(valorLimpio);
                        const formateado = Number.isFinite(numero)
                                ? numero.toLocaleString("en-US", {
                                          minimumFractionDigits: 2,
                                          maximumFractionDigits: 2,
                                  })
                                : valorLimpio;
                        rentaDisplay.value = `$${formateado}`;
                };

                const recalcularMontoPoliza = () => {
                        const rentaBase = rentaHidden?.value;
                        if (
                                !tipoPoliza ||
                                !montoPoliza ||
                                !rentaBase ||
                                montoPolizaEditadoManualmente
                        ) {
                                return;
                        }

                        montoPoliza.value = calcularPoliza(
                                rentaBase,
                                tipoPoliza.value
                        );
                };

                // Cambiar inmueble → refrescar renta y cálculo
                inmuebleSelect?.addEventListener("change", () => {
                        const opt = inmuebleSelect.options[inmuebleSelect.selectedIndex];
                        const renta = opt?.getAttribute("data-monto") || "";
                        const limpia = obtenerRentaLimpia(renta);

                        // Siempre guardar valor crudo en el hidden
                        if (rentaHidden) {
                                rentaHidden.value = limpia; // ej. "19150"
                        }

                        // Mostrar formateado solo en display (para el usuario)
                        actualizarDisplayRenta(limpia);

                        montoPolizaEditadoManualmente = false;

                        // Recalcular monto de póliza en base a la renta cruda
                        recalcularMontoPoliza();
                });

                // Cambiar tipo póliza → recalcular
                tipoPoliza?.addEventListener("change", () => {
                        montoPolizaEditadoManualmente = false;
                        recalcularMontoPoliza();
                });

                const toggleRefreshLoading = (isLoading) => {
                        if (!refreshRentBtn) return;
                        refreshRentBtn.disabled = isLoading;
                        refreshRentBtn.classList.toggle("opacity-60", isLoading);
                        refreshRentBtn.classList.toggle("cursor-not-allowed", isLoading);
                        const icon = refreshRentBtn.querySelector("svg");
                        if (icon) {
                                icon.classList.toggle("animate-spin", isLoading);
                        }
                };

                refreshRentBtn?.addEventListener("click", async () => {
                        const numeroPoliza = numeroPolizaInput?.value || "";
                        if (!numeroPoliza) {
                                Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: "No se pudo identificar la póliza.",
                                });
                                return;
                        }

                        const inmuebleId = inmuebleSelect?.value || "";
                        if (!inmuebleId) {
                                Swal.fire({
                                        icon: "warning",
                                        title: "Selecciona un inmueble",
                                        text: "Debes elegir un inmueble para refrescar la renta.",
                                });
                                return;
                        }

                        toggleRefreshLoading(true);

                        try {
                                const url = new URL(
                                        `${BASE_URL}/polizas/${encodeURIComponent(numeroPoliza)}/renta`
                                );
                                if (inmuebleId) {
                                        url.searchParams.set("id_inmueble", inmuebleId);
                                }

                                const response = await fetch(url.toString(), {
                                        headers: { "X-Requested-With": "XMLHttpRequest" },
                                });

                                let data;
                                try {
                                        data = await response.json();
                                } catch (err) {
                                        throw new Error("Respuesta inválida del servidor");
                                }

                                if (!response.ok || !data?.ok) {
                                        throw new Error(data?.error || "No se pudo obtener la renta");
                                }

                                const rentaDesdeApi = data.monto_renta_numerica || data.monto_renta || "";
                                const limpia = obtenerRentaLimpia(rentaDesdeApi);

                                if (rentaHidden) {
                                        rentaHidden.value = limpia;
                                }

                                actualizarDisplayRenta(limpia);
                                montoPolizaEditadoManualmente = false;
                                recalcularMontoPoliza();

                                Swal.fire({
                                        icon: "success",
                                        title: "Renta actualizada",
                                        text: "El monto se sincronizó desde el inmueble.",
                                });
                        } catch (error) {
                                Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text:
                                                error && error.message
                                                        ? error.message
                                                        : "No se pudo refrescar la renta.",
                                });
                        } finally {
                                toggleRefreshLoading(false);
                        }
                });

                // Fechas
                fechaInicio?.addEventListener("change", () =>
                        setFechaFinYVigencia({
                                inicioEl: fechaInicio,
                                finEl: fechaFin,
                                vigenciaEl: vigenciaText,
                        })
                );
                fechaFin?.addEventListener("change", () =>
                        setFechaFinYVigencia({
                                inicioEl: fechaInicio,
                                finEl: fechaFin,
                                vigenciaEl: vigenciaText,
                        })
                );
                setFechaFinYVigencia({
                        inicioEl: fechaInicio,
                        finEl: fechaFin,
                        vigenciaEl: vigenciaText,
                });
                // inicial
                actualizarDisplayRenta(obtenerRentaLimpia(rentaHidden?.value));
                if (!montoPoliza?.value) {
                        recalcularMontoPoliza();
                }

		// Submit AJAX (editar)
		form.addEventListener("submit", async (e) => {
			e.preventDefault();

			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn?.setAttribute("disabled", "disabled");
			submitBtn?.classList.add("opacity-60", "cursor-not-allowed");

			try {
				const fd = new FormData(form);

				const resp = await fetch(form.action, {
					method: "POST",
					body: fd,
					headers: { "X-Requested-With": "XMLHttpRequest" },
				});

				// Intenta parsear JSON (y arroja error claro si viene algo raro)
				let res;
				try {
					res = await resp.json();
				} catch {
					throw new Error("Respuesta inválida del servidor");
				}

				if (!resp.ok) {
					// HTTP no-2xx
					throw new Error(
						res?.error || resp.statusText || "Error en la solicitud"
					);
				}

				if (res.ok) {
					Swal.fire({
						icon: "success",
						title: "Éxito",
						text: "Cambios guardados",
					}).then(() => {
						const numero = new URLSearchParams(fd).get(
							"numero_poliza"
						);
						const destino = numero
							? `${BASE_URL}/polizas/${encodeURIComponent(
									numero
							  )}`
							: `${BASE_URL}/polizas`;
						window.location = destino;
					});
				} else {
					Swal.fire({
						icon: "error",
						title: "Error",
						text: res.error || "No se pudo guardar",
					});
				}
			} catch (err) {
				Swal.fire({
					icon: "error",
					title: "Error",
					text: err && err.message ? err.message : "Ocurrió un error",
				});
			} finally {
				// Siempre re-habilita el botón si seguimos en la misma página
				submitBtn?.removeAttribute("disabled");
				submitBtn?.classList.remove("opacity-60", "cursor-not-allowed");
			}
		});
	}

	// ---------------- Enlazadores para RENOVAR ----------------
	function initRenovarFormulario() {
		const form = document.getElementById("form-renovar-poliza");
		if (!form) return;

		const rentaHidden = document.getElementById("monto-renta-hidden");
		const montoPoliza = document.getElementById("monto-poliza");
		const fechaInicio = document.getElementById("fecha-inicio");
		const fechaFin = document.getElementById("fecha-fin");
		const vigenciaText = document.getElementById("vigencia-texto");

		// Inicial: calcular monto
		if (montoPoliza) {
			// El tipo viene fijo desde PHP; si necesitas alternar, agrega data-tipo-poliza en el form
			const tipo =
				form.dataset.tipoPoliza ||
				form.querySelector('input[name="tipo_poliza"]')?.value ||
				"Clásica";
			montoPoliza.value = calcularPoliza(rentaHidden?.value, tipo);
		}

		// Fechas
		fechaInicio?.addEventListener("change", () =>
			setFechaFinYVigencia({
				inicioEl: fechaInicio,
				finEl: fechaFin,
				vigenciaEl: vigenciaText,
			})
		);
		fechaFin?.addEventListener("change", () =>
			setFechaFinYVigencia({
				inicioEl: fechaInicio,
				finEl: fechaFin,
				vigenciaEl: vigenciaText,
			})
		);
		setFechaFinYVigencia({
			inicioEl: fechaInicio,
			finEl: fechaFin,
			vigenciaEl: vigenciaText,
		});

		// Submit AJAX (renovar)
		form.addEventListener("submit", async (e) => {
			e.preventDefault();

			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn?.setAttribute("disabled", "disabled");
			submitBtn?.classList.add("opacity-60", "cursor-not-allowed");

			try {
				const fd = new FormData(form);
				const resp = await fetch(form.action, {
					method: "POST",
					body: fd,
				});

				let res;
				try {
					res = await resp.json();
				} catch {
					throw new Error("Respuesta inválida del servidor");
				}

				if (!resp.ok) {
					throw new Error(
						res?.error || resp.statusText || "Error en la solicitud"
					);
				}

				if (res.ok) {
					Swal.fire(
						"Perfecto, se ha renovado la póliza",
						"",
						"success"
					).then(() => {
						window.location = `${BASE_URL}/polizas`;
					});
				} else {
					Swal.fire(
						"Error",
						res.error || "No se pudo guardar",
						"error"
					);
				}
			} catch (err) {
				Swal.fire({
					icon: "error",
					title: "Error",
					text: err && err.message ? err.message : "Ocurrió un error",
				});
			} finally {
				submitBtn?.removeAttribute("disabled");
				submitBtn?.classList.remove("opacity-60", "cursor-not-allowed");
			}
		});
	}

	// ---------------- Bootstrap por vista ----------------
	document.addEventListener("DOMContentLoaded", () => {
		initEditarFormulario();
		initRenovarFormulario();
	});
})();
