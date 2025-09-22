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

	window.editarInmueble = function (sk) {
		// Aquí muestras un form dinámico o modal
		console.log("Editar inmueble", sk);
	};

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
