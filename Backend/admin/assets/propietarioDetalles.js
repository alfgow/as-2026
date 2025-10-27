document.addEventListener("DOMContentLoaded", () => {
	const form = document.getElementById("form-cambiar-<?= $tipo ?>");
	if (form) {
		form.addEventListener("submit", async function (e) {
			e.preventDefault(); // 🚫 Evita que vaya a la vista JSON

			const formData = new FormData(form);

			try {
				const response = await fetch(form.action, {
					method: "POST",
					body: formData,
				});
				const result = await response.json();

				if (result.ok) {
					Swal.fire({
						icon: "success",
						title: "¡Archivo actualizado!",
						text: "El archivo se reemplazó correctamente.",
						confirmButtonColor: "#16a34a",
					}).then(() => {
						window.location.reload();
					});
				} else {
					Swal.fire({
						icon: "error",
						title: "Error",
						text:
							result.error || "No se pudo actualizar el archivo.",
						confirmButtonColor: "#dc2626",
					});
				}
			} catch (err) {
				Swal.fire({
					icon: "error",
					title: "Error",
					text: "Hubo un problema en la conexión.",
					confirmButtonColor: "#dc2626",
				});
			}
		});
	}
});

document.addEventListener("DOMContentLoaded", () => {
	// =====================
	// Datos Personales
	// =====================
	window.mostrarFormPersonales = function () {
		document
			.getElementById("datos-personales-vista")
			.classList.add("hidden");
		document
			.getElementById("form-datos-personales")
			.classList.remove("hidden");
		document.getElementById("btn-edit-personales").classList.add("hidden");
	};

	window.cancelarEdicionPersonales = function () {
		document
			.getElementById("form-datos-personales")
			.classList.add("hidden");
		document
			.getElementById("datos-personales-vista")
			.classList.remove("hidden");
		document
			.getElementById("btn-edit-personales")
			.classList.remove("hidden");
	};

	window.guardarDatosPersonales = function (e) {
		e.preventDefault();
		const form = document.getElementById("form-datos-personales");
		const data = new FormData(form);

		fetch(BASE_URL + "/arrendador/actualizar-datos-personales", {
			method: "POST",
			body: data,
		})
			.then((r) => r.json())
                        .then((res) => {
                                if (res.ok) {
                                        Swal.fire({
                                                icon: "success",
                                                title: "¡Actualizado!",
                                                text: "Info actualizada exitosamente.",
                                                background: "#1f1f2e",
                                                color: "#fde8e8ca",
                                                iconColor: "#a5b4fc",
                                                showConfirmButton: false,
                                                timer: 2000,
                                                position: "center",
                                                customClass: {
                                                        popup: "rounded-2xl shadow-lg border border-indigo-500/30",
                                                },
                                        });

                                        const nuevaRuta = (() => {
                                                if (!res.slug) {
                                                        return null;
                                                }

                                                if (typeof window.joinAdmin === "function") {
                                                        return window.joinAdmin(`arrendadores/${res.slug}`);
                                                }

                                                return `${BASE_URL}/arrendadores/${res.slug}`;
                                        })();

                                        setTimeout(() => {
                                                if (nuevaRuta) {
                                                        window.location.href = nuevaRuta;
                                                } else {
                                                        window.location.reload();
                                                }
                                        }, 2000);
                                } else {
                                        Swal.fire({
                                                icon: "error",
                                                title: "Error",
                                                text: res.error || "No se pudo guardar",
                                        });
				}
			});
	};

	// =====================
	// Info Bancaria
	// =====================
	window.mostrarInfoBancaria = function () {
		document.getElementById("info-bancaria-vista").classList.add("hidden");
		document
			.getElementById("form-info-bancaria")
			.classList.remove("hidden");
		document.getElementById("btn-edit-bancaria").classList.add("hidden");
	};

	window.cancelarInfoBancaria = function () {
		document.getElementById("form-info-bancaria").classList.add("hidden");
		document
			.getElementById("info-bancaria-vista")
			.classList.remove("hidden");
		document.getElementById("btn-edit-bancaria").classList.remove("hidden");
	};

	window.guardarInfoBancaria = function (e) {
		e.preventDefault();
		const form = document.getElementById("form-info-bancaria");
		const data = new FormData(form);

		fetch(BASE_URL + "/arrendador/actualizar-info-bancaria", {
			method: "POST",
			body: data,
		})
			.then((r) => r.json())
			.then((res) => {
				if (res.ok) {
					Swal.fire({
						icon: "success",
						title: "Actualizada",
						text: "Información Bancaria Actualizada.",
						background: "#1f1f2e",
						color: "#fde8e8ca",
						iconColor: "#a5b4fc",
						confirmButtonColor: "#4f46e5",
					});
					setTimeout(() => location.reload(), 2000);
				} else {
					Swal.fire({
						icon: "error",
						title: "Error",
						text: res.error || "No se pudo guardar",
					});
				}
			});
	};

	// =====================
	// Comentarios
	// =====================
	window.mostrarComentarios = function () {
		document.getElementById("comentarios-vista").classList.add("hidden");
		document.getElementById("form-comentarios").classList.remove("hidden");
		document.getElementById("btn-edit-comentarios").classList.add("hidden");
	};

	window.cancelarComentarios = function () {
		document.getElementById("form-comentarios").classList.add("hidden");
		document.getElementById("comentarios-vista").classList.remove("hidden");
		document
			.getElementById("btn-edit-comentarios")
			.classList.remove("hidden");
	};

        window.guardarComentarios = function (e) {
                e.preventDefault();
                const form = document.getElementById("form-comentarios");
                const data = new FormData(form);

                fetch(BASE_URL + "/arrendador/actualizar-comentarios", {
                        method: "POST",
                        body: data,
                })
                        .then((r) => r.json())
                        .then((res) => {
                                if (res.ok) {
                                        Swal.fire({
                                                icon: "success",
                                                title: "Éxito",
                                                text: "Comentario agregado.",
                                                background: "#1f1f2e",
                                                color: "#fde8e8ca",
                                                iconColor: "#a5b4fc",
                                                confirmButtonColor: "#4f46e5",
                                        });
                                        setTimeout(() => location.reload(), 2000);
                                } else {
                                        Swal.fire({
                                                icon: "error",
                                                title: "Error",
                                                text: res.error || "No se pudo guardar",
                                        });
                                }
                        });
        };

        // =====================
        // Asesor asignado
        // =====================
        window.mostrarFormAsesor = function () {
                document.getElementById("asesor-vista")?.classList.add("hidden");
                document.getElementById("form-asesor")?.classList.remove("hidden");
                document.getElementById("btn-edit-asesor")?.classList.add("hidden");
        };

        window.cancelarAsesor = function () {
                document.getElementById("form-asesor")?.classList.add("hidden");
                document.getElementById("asesor-vista")?.classList.remove("hidden");
                document.getElementById("btn-edit-asesor")?.classList.remove("hidden");
                const msg = document.getElementById("mensaje-asesor");
                if (msg) {
                        msg.textContent = "";
                        msg.className = "text-sm text-center";
                }
        };

        window.guardarAsesor = function (e) {
                e.preventDefault();
                const form = document.getElementById("form-asesor");
                if (!form) {
                        return;
                }

                const mensaje = document.getElementById("mensaje-asesor");
                if (mensaje) {
                        mensaje.textContent = "Guardando...";
                        mensaje.className = "text-sm text-center text-yellow-400";
                }

                fetch(BASE_URL + "/arrendador/actualizar-asesor", {
                        method: "POST",
                        body: new FormData(form),
                })
                        .then((r) => r.json())
                        .then((res) => {
                                if (res.ok) {
                                        if (mensaje) {
                                                mensaje.textContent = "Asesor actualizado";
                                                mensaje.className = "text-sm text-center text-green-400";
                                        }
                                        Swal.fire({
                                                icon: "success",
                                                title: "¡Asesor actualizado!",
                                                text: "El asesor se cambió correctamente.",
                                                background: "#1f1f2e",
                                                color: "#fde8e8ca",
                                                iconColor: "#38bdf8",
                                                confirmButtonColor: "#4f46e5",
                                        }).then(() => {
                                                window.location.reload();
                                        });
                                } else {
                                        if (mensaje) {
                                                mensaje.textContent = res.error || "No se pudo guardar";
                                                mensaje.className = "text-sm text-center text-red-400";
                                        }
                                        Swal.fire({
                                                icon: "error",
                                                title: "Error",
                                                text: res.error || "No se pudo guardar",
                                        });
                                }
                        })
                        .catch(() => {
                                if (mensaje) {
                                        mensaje.textContent = "Error al conectar";
                                        mensaje.className = "text-sm text-center text-red-400";
                                }
                                Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: "Hubo un problema en la conexión.",
                                });
                        });
        };

        // =====================
        // Modal imágenes
        // =====================
        window.abrirModal = function (src) {
		const modal = document.getElementById("imageModal");
		const modalImg = document.getElementById("modalImage");
		modalImg.src = src;
		modal.classList.remove("hidden");
		modal.classList.add("flex");
	};

	window.cerrarModal = function () {
		const modal = document.getElementById("imageModal");
		modal.classList.add("hidden");
		modal.classList.remove("flex");
	};

	// =====================
	// Inmuebles
	// =====================
	window.mostrarFormInmueble = function () {
		document.getElementById("form-inmueble").classList.remove("hidden");
		document.getElementById("btn-agregar-inmueble").classList.add("hidden");
		document.getElementById("inmuebles-vista").classList.add("hidden");
	};

	window.cancelarInmueble = function () {
		document.getElementById("form-inmueble").classList.add("hidden");
		document
			.getElementById("btn-agregar-inmueble")
			.classList.remove("hidden");
		document.getElementById("inmuebles-vista").classList.remove("hidden");
	};

        window.guardarInmueble = function (e) {
                e.preventDefault();
                const form = document.getElementById("form-inmueble");
                const data = new FormData(form);

                fetch(BASE_URL + "/inmueble/guardar-ajax", {
			method: "POST",
			body: data,
		})
			.then((r) => r.json())
			.then((res) => {
				if (res.ok) {
					Swal.fire({
						icon: "success",
						title: "Inmueble agregado",
						text: "El inmueble se registró correctamente.",
						background: "#1f1f2e",
						color: "#fde8e8ca",
						iconColor: "#a5b4fc",
						confirmButtonColor: "#4f46e5",
					});
					setTimeout(() => location.reload(), 2000);
				} else {
					Swal.fire({
						icon: "error",
						title: "Error",
						text: res.error || "No se pudo guardar inmueble",
					});
				}
                        });
        };

        const modalEditarInmueble = document.getElementById("modal-editar-inmueble");
        const modalEditarInmuebleLoader = document.getElementById("modal-editar-inmueble-loader");
        const formEditarInmueble = document.getElementById("form-editar-inmueble");
        const botonEditarInmueble = formEditarInmueble ? formEditarInmueble.querySelector("[data-submit]") : null;
        const asesorDefault = modalEditarInmueble ? modalEditarInmueble.dataset.arrAsesor || "" : "";

        const sanitizeAmount = (value) => {
                if (typeof value === "number" && Number.isFinite(value)) {
                        return value.toFixed(2);
                }

                if (typeof value !== "string") {
                        return "";
                }

                const normalized = value.replace(/[^0-9.,-]/g, "").replace(/,/g, ".");
                if (normalized === "") {
                        return "";
                }

                const asNumber = Number(normalized);
                return Number.isFinite(asNumber) ? asNumber.toFixed(2) : "";
        };

        const fillEditarInmuebleForm = (data, fallbackPk, fallbackSk) => {
                if (!formEditarInmueble) {
                        return;
                }

                formEditarInmueble.reset();

                const pkField = formEditarInmueble.querySelector('input[name="pk"]');
                const skField = formEditarInmueble.querySelector('input[name="sk"]');
                const asesorField = formEditarInmueble.querySelector('input[name="asesor_pk"]');

                if (pkField) {
                        pkField.value = typeof data.pk === "string" && data.pk !== "" ? data.pk : fallbackPk;
                }
                if (skField) {
                        const value = typeof data.sk === "string" && data.sk !== "" ? data.sk : fallbackSk;
                        skField.value = value;
                }
                if (asesorField) {
                        asesorField.value = typeof data.asesor_pk === "string" && data.asesor_pk !== ""
                                ? data.asesor_pk
                                : asesorDefault;
                }

                const direccion = formEditarInmueble.querySelector('#edit-direccion-inmueble');
                if (direccion) {
                        direccion.value = typeof data.direccion_inmueble === "string" ? data.direccion_inmueble : "";
                }

                const tipo = formEditarInmueble.querySelector('#edit-tipo-inmueble');
                if (tipo) {
                        tipo.value = typeof data.tipo === "string" ? data.tipo : "";
                }

                const renta = formEditarInmueble.querySelector('#edit-renta');
                if (renta) {
                        renta.value = sanitizeAmount(data.renta ?? "");
                }

                const mantenimiento = formEditarInmueble.querySelector('#edit-mantenimiento');
                if (mantenimiento) {
                        const rawValue = typeof data.mantenimiento === "string"
                                ? data.mantenimiento
                                : "";
                        const normalizado = rawValue.toUpperCase().replace(/\s+/g, '_').trim();

                        let valorSelect = "No";
                        switch (normalizado) {
                                case "SI":
                                        valorSelect = "Si";
                                        break;
                                case "NO":
                                        valorSelect = "No";
                                        break;
                                case "NO_APLICA":
                                case "NA":
                                        valorSelect = "na";
                                        break;
                                default:
                                        valorSelect = "No";
                        }

                        mantenimiento.value = valorSelect;
                }

                const montoMantenimiento = formEditarInmueble.querySelector('#edit-monto-mantenimiento');
                if (montoMantenimiento) {
                        montoMantenimiento.value = sanitizeAmount(data.monto_mantenimiento ?? "");
                }

                const deposito = formEditarInmueble.querySelector('#edit-deposito');
                if (deposito) {
                        deposito.value = sanitizeAmount(data.deposito ?? "");
                }

                const estacionamiento = formEditarInmueble.querySelector('#edit-estacionamiento');
                if (estacionamiento) {
                        const value = Number.parseInt(data.estacionamiento, 10);
                        estacionamiento.value = Number.isFinite(value) ? String(value) : "";
                }

                const mascotas = formEditarInmueble.querySelector('#edit-mascotas');
                if (mascotas) {
                        const value = typeof data.mascotas === "string" ? data.mascotas.toUpperCase() : "";
                        mascotas.value = value === "SI" ? "SI" : "NO";
                }

                const comentarios = formEditarInmueble.querySelector('#edit-comentarios');
                if (comentarios) {
                        comentarios.value = typeof data.comentarios === "string" ? data.comentarios : "";
                }
        };

        const toggleEditarInmuebleLoader = (isLoading) => {
                if (!formEditarInmueble) {
                        return;
                }

                if (modalEditarInmuebleLoader) {
                        modalEditarInmuebleLoader.classList.toggle("hidden", !isLoading);
                }

                formEditarInmueble.classList.toggle("pointer-events-none", isLoading);
                formEditarInmueble.classList.toggle("opacity-50", isLoading);
        };

        window.cerrarModalInmueble = function () {
                if (!modalEditarInmueble || !formEditarInmueble) {
                        return;
                }

                modalEditarInmueble.classList.add("hidden");
                modalEditarInmueble.classList.remove("flex");
                toggleEditarInmuebleLoader(false);
                formEditarInmueble.reset();
        };

        if (modalEditarInmueble) {
                modalEditarInmueble.addEventListener("click", (event) => {
                        if (event.target === modalEditarInmueble) {
                                window.cerrarModalInmueble();
                        }
                });
        }

        window.editarInmueble = async function (pk, sk) {
                if (!modalEditarInmueble || !formEditarInmueble) {
                        return;
                }

                if (!pk || !sk) {
                        Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Identificadores del inmueble incompletos.",
                        });
                        return;
                }

                modalEditarInmueble.classList.remove("hidden");
                modalEditarInmueble.classList.add("flex");
                toggleEditarInmuebleLoader(true);

                try {
                        const url = `${BASE_URL}/inmuebles/info/${encodeURIComponent(pk)}/${encodeURIComponent(sk)}`;
                        const response = await fetch(url, { headers: { "Accept": "application/json" } });

                        if (!response.ok) {
                                throw new Error("No se pudo consultar el inmueble.");
                        }

                        const data = await response.json();
                        if (!data || typeof data !== "object" || Object.keys(data).length === 0) {
                                throw new Error("No se encontró información del inmueble.");
                        }

                        fillEditarInmuebleForm(data, pk, sk);
                } catch (error) {
                        window.cerrarModalInmueble();
                        Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: error instanceof Error ? error.message : "No se pudo cargar el inmueble.",
                        });
                        return;
                }

                toggleEditarInmuebleLoader(false);
        };

        if (formEditarInmueble) {
                formEditarInmueble.addEventListener("submit", async (event) => {
                        event.preventDefault();

                        const formData = new FormData(formEditarInmueble);

                        if (botonEditarInmueble) {
                                botonEditarInmueble.disabled = true;
                                botonEditarInmueble.classList.add("opacity-75");
                        }

                        try {
                                const response = await fetch(BASE_URL + "/inmuebles/update", {
                                        method: "POST",
                                        body: formData,
                                });

                                const result = await response.json();

                                if (!response.ok || !result.ok) {
                                        const mensaje = result?.mensaje || result?.error || "No se pudo actualizar el inmueble.";
                                        throw new Error(mensaje);
                                }

                                Swal.fire({
                                        icon: "success",
                                        title: "Inmueble actualizado",
                                        text: "Los cambios se guardaron correctamente.",
                                        background: "#1f1f2e",
                                        color: "#fde8e8ca",
                                        iconColor: "#a5b4fc",
                                        confirmButtonColor: "#4f46e5",
                                }).then(() => {
                                        window.cerrarModalInmueble();
                                        setTimeout(() => window.location.reload(), 500);
                                });
                        } catch (error) {
                                Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: error instanceof Error ? error.message : "No se pudo actualizar el inmueble.",
                                });
                        } finally {
                                if (botonEditarInmueble) {
                                        botonEditarInmueble.disabled = false;
                                        botonEditarInmueble.classList.remove("opacity-75");
                                }
                        }
                });
        }

        window.eliminarInmueble = function (sk, pk) {
                Swal.fire({
                        title: "¿Eliminar inmueble?",
			text: "Esta acción no se puede deshacer",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#de6868",
			cancelButtonColor: "#4b5563",
			confirmButtonText: "Sí, eliminar",
		}).then((result) => {
			if (result.isConfirmed) {
				fetch(BASE_URL + "/inmueble/eliminar", {
					method: "POST",
					body: new URLSearchParams({ pk, sk }),
				})
					.then((r) => r.json())
					.then((res) => {
						if (res.ok) {
							Swal.fire(
								"Eliminado",
								"El inmueble fue eliminado.",
								"success"
							);
							setTimeout(() => location.reload(), 1500);
						} else {
							Swal.fire(
								"Error",
								res.error || "No se pudo eliminar",
								"error"
							);
						}
					});
			}
		});
	};

	// =====================
	// Documentos
	// =====================
	window.eliminarDocumento = function (sk) {
		Swal.fire({
			title: "¿Eliminar documento?",
			text: "Esta acción no se puede deshacer",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#de6868",
			cancelButtonColor: "#4b5563",
			confirmButtonText: "Sí, eliminar",
		}).then((result) => {
			if (result.isConfirmed) {
				fetch(BASE_URL + "/arrendador/eliminar-archivo", {
					method: "POST",
					body: new URLSearchParams({ sk }),
				})
					.then((r) => r.json())
					.then((res) => {
						if (res.ok) {
							Swal.fire(
								"Eliminado",
								"El documento fue eliminado.",
								"success"
							);
							setTimeout(() => location.reload(), 1500);
						} else {
							Swal.fire(
								"Error",
								res.error || "No se pudo eliminar",
								"error"
							);
						}
					});
			}
		});
	};

	window.cambiarDocumento = function (
		idArrendador,
		tipo,
		currentImgUrl = ""
	) {
		Swal.fire({
			title: "Cambiar documento",
			html: `
            <div id="dropzone-cambiar" 
                 class="border-2 border-dashed border-indigo-500 rounded-lg p-4 text-indigo-200 cursor-pointer">
                Arrastra aquí una nueva imagen o haz click
            </div>
            <div class="mt-3">
                <img id="preview-cambiar" 
                     src="${currentImgUrl || ""}" 
                     class="h-40 mx-auto rounded-lg object-contain ${
							currentImgUrl ? "" : "hidden"
						}"/>
            </div>
        `,
			background: "#1f1f2e",
			color: "#fde8e8ca",
			showCancelButton: true,
			confirmButtonText: "Confirmar",
			cancelButtonText: "Cancelar",
			confirmButtonColor: "#4f46e5",
			cancelButtonColor: "#de6868",
			didOpen: () => {
				const dz = document.getElementById("dropzone-cambiar");
				const preview = document.getElementById("preview-cambiar");

				dz.addEventListener("click", () => {
					const input = document.createElement("input");
					input.type = "file";
					input.accept = "image/*";
					input.onchange = (e) => {
						const file = e.target.files[0];
						if (file && file.type.startsWith("image/")) {
							const reader = new FileReader();
							reader.onload = (ev) => {
								preview.src = ev.target.result;
								preview.classList.remove("hidden");
							};
							reader.readAsDataURL(file);

							dz.fileSeleccionado = file;
						} else {
							Swal.fire({
								icon: "error",
								title: "Error",
								text: "Solo se permiten imágenes",
								background: "#1f1f2e",
								color: "#fde8e8ca",
								iconColor: "#de6868",
								confirmButtonColor: "#de6868",
							});
						}
					};
					input.click();
				});
			},
			preConfirm: () => {
				const dz = document.getElementById("dropzone-cambiar");
				if (!dz.fileSeleccionado) {
					Swal.showValidationMessage("Debes seleccionar una imagen");
					return false;
				}

				Swal.showLoading();

				const formData = new FormData();
				formData.append("id_arrendador", idArrendador); // 🔑 numérico correcto
				formData.append("tipo", tipo); // 🔑 correcto ahora
				formData.append("archivo", dz.fileSeleccionado);

				return fetch(BASE_URL + "/arrendador/cambiar-archivo", {
					method: "POST",
					body: formData,
				})
					.then((r) => r.json())
					.then((res) => {
						if (!res.ok)
							throw new Error(
								res.error || "Error al cambiar archivo"
							);
						return res;
					})
					.catch((err) => {
						Swal.showValidationMessage(err.message);
					});
			},
			allowOutsideClick: () => !Swal.isLoading(),
		}).then((result) => {
			if (result.isConfirmed) {
				Swal.fire({
					icon: "success",
					title: "Documento actualizado",
					text: "El documento se reemplazó correctamente.",
					background: "#1f1f2e",
					color: "#fde8e8ca",
					iconColor: "#a5b4fc",
					confirmButtonColor: "#4f46e5",
				}).then(() => location.reload());
			}
		});
	};
});
function mostrarForm(tipo) {
	document.getElementById(`view-${tipo}`).classList.add("hidden");
	document.getElementById(`form-${tipo}`).classList.remove("hidden");
}

function cancelarForm(tipo) {
	// Ocultar el formulario y mostrar la vista original
	document.getElementById(`form-${tipo}`).classList.add("hidden");
	document.getElementById(`view-${tipo}`).classList.remove("hidden");

	// Limpiar el input file
	const inputFile = document.getElementById(`file-${tipo}`);
	if (inputFile) {
		inputFile.value = "";
	}

	// Resetear vista previa
	const preview = document.getElementById(`preview-${tipo}`);
	const placeholder = document.getElementById(`placeholder-${tipo}`);
	if (preview) {
		preview.src = "";
		preview.classList.add("hidden");
	}
	if (placeholder) {
		placeholder.textContent = "Vista previa";
		placeholder.classList.remove("hidden");
	}
	if (btnSubir) btnSubir.classList.add("hidden");
}

function mostrarPreview(event, tipo) {
	const file = event.target.files[0];
	if (!file) return;

	const preview = document.getElementById(`preview-${tipo}`);
	const placeholder = document.getElementById(`placeholder-${tipo}`);
	const btnSelect = document.getElementById(`btn-select-${tipo}`);
	const btnSubir = document.getElementById(`btn-subir-${tipo}`);
	const btnCancel = document.getElementById(`btn-cancel-${tipo}`);

	// Reset
	preview.classList.add("hidden");
	placeholder.classList.remove("hidden");
	placeholder.textContent = "Vista previa";

	if (file.type.startsWith("image/")) {
		const reader = new FileReader();
		reader.onload = function (e) {
			preview.src = e.target.result;
			preview.classList.remove("hidden");
			placeholder.classList.add("hidden");
		};
		reader.readAsDataURL(file);
	} else {
		let name = file.name;
		if (name.length > 20) {
			name = name.substring(0, 10) + "..." + name.slice(-7);
		}
		placeholder.textContent = name;
	}

	// UI: ocultar seleccionar, mostrar cancelar y subir
	if (btnSelect) btnSelect.classList.add("hidden");
	if (btnSubir) btnSubir.classList.remove("hidden");
	if (btnCancel) btnCancel.classList.remove("hidden");
}

function cancelarSeleccion(tipo) {
	const inputFile = document.getElementById(`file-${tipo}`);
	const preview = document.getElementById(`preview-${tipo}`);
	const placeholder = document.getElementById(`placeholder-${tipo}`);
	const btnSelect = document.getElementById(`btn-select-${tipo}`);
	const btnSubir = document.getElementById(`btn-subir-${tipo}`);
	const btnCancel = document.getElementById(`btn-cancel-${tipo}`);

	// Resetear input
	if (inputFile) inputFile.value = "";

	// Reset preview
	if (preview) {
		preview.src = "";
		preview.classList.add("hidden");
	}
	if (placeholder) {
		placeholder.textContent = "Vista previa";
		placeholder.classList.remove("hidden");
	}

	// UI: mostrar seleccionar, ocultar cancelar y subir
	if (btnSelect) btnSelect.classList.remove("hidden");
	if (btnSubir) btnSubir.classList.add("hidden");
	if (btnCancel) btnCancel.classList.add("hidden");
}
document.addEventListener("DOMContentLoaded", () => {
	document.querySelectorAll("form[data-doc-upload]").forEach((form) => {
		form.addEventListener("submit", async (e) => {
			e.preventDefault(); // 🚫 evitar submit normal

			const formData = new FormData(form);

			try {
				const res = await fetch(form.action, {
					method: "POST",
					body: formData,
				});
				const data = await res.json();

				if (data.ok) {
					Swal.fire({
						icon: "success",
						title: "Archivo actualizado",
						text: "El archivo se subió correctamente.",
						timer: 2000,
						showConfirmButton: false,
					}).then(() => {
						// Recargar la página o el bloque actual
						location.reload();
					});
				} else {
					Swal.fire({
						icon: "error",
						title: "Error",
						text: data.error || "No se pudo subir el archivo",
					});
				}
			} catch (err) {
				Swal.fire({
					icon: "error",
					title: "Error",
					text: err.message,
				});
			}
		});
	});
});
