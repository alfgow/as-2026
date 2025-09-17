// Manejo de subida de "otros" archivos
function handleOtroSelect(input) {
	const file = input.files[0];
	if (!file) return;

	const idInquilino = input.dataset.inquilino || null;
	const nombreInquilino = input.dataset.nombre || "";

	const fd = new FormData();
	fd.append("archivo", file);
	fd.append("tipo", "otro");
	fd.append("id_inquilino", idInquilino);
	fd.append("nombre_normalizado", nombreInquilino);

	Swal.fire({
		title: "Subiendo archivo...",
		allowOutsideClick: false,
		didOpen: () => Swal.showLoading(),
		background: "#18181b",
		color: "#fff",
	});

	fetch(
		(window.ADMIN_BASE || window.BASE_URL || "") +
			"/inquilino/subir-archivo",
		{
			method: "POST",
			body: fd,
		}
	)
		.then((r) => r.json())
		.then((j) => {
			Swal.close();
			if (!j.ok) throw new Error(j.error || j.mensaje || "Error");
			Swal.fire({
				icon: "success",
				title: "¡Archivo agregado!",
				timer: 1500,
				showConfirmButton: false,
				background: "#18181b",
				color: "#fff",
			}).then(() => location.reload());
		})
		.catch((err) => {
			Swal.close();
			Swal.fire({
				icon: "error",
				title: "Error",
				text: err.message || "No se pudo subir archivo",
				background: "#18181b",
				color: "#fff",
			});
		});
}

// Manejo de eliminación
function eliminarOtroArchivo(idArchivo) {
	Swal.fire({
		title: "¿Eliminar archivo?",
		text: "Esta acción no se puede deshacer.",
		icon: "warning",
		showCancelButton: true,
		confirmButtonText: "Sí, eliminar",
		cancelButtonText: "Cancelar",
		background: "#18181b",
		color: "#fff",
		confirmButtonColor: "#de6868",
	}).then((res) => {
		if (!res.isConfirmed) return;

		const fd = new FormData();
		fd.append("archivo_id", idArchivo);

		fetch(
			(window.ADMIN_BASE || window.BASE_URL || "") +
				"/inquilino/eliminar-archivo",
			{
				method: "POST",
				body: fd,
			}
		)
			.then((r) => r.json())
			.then((j) => {
				if (!j.ok) throw new Error(j.error || "Error al eliminar");
				Swal.fire({
					icon: "success",
					title: "Eliminado",
					timer: 1200,
					showConfirmButton: false,
					background: "#18181b",
					color: "#fff",
				}).then(() => location.reload());
			})
			.catch((err) => {
				Swal.fire({
					icon: "error",
					title: "Error",
					text: err.message || "No se pudo eliminar",
					background: "#18181b",
					color: "#fff",
				});
			});
	});
}
