// inquilinoImgValidacionIngresos.js
function handleValidacionIngresosSelect(input) {
	const file = input.files[0];
	if (!file) return;

	const fileId = input.dataset.id || null;
	const idInquilino = input.dataset.inquilino || null;
	const nombreInquilino = input.dataset.nombre || "";

	const preview = document.getElementById("validacion-ingresos-preview");
	const actions = document.getElementById("validacion-ingresos-actions");
	if (!preview || !actions) return;

	preview.innerHTML = "";
	actions.innerHTML = "";

	// Solo imágenes
	if (file.type.startsWith("image/")) {
		const img = document.createElement("img");
		img.src = URL.createObjectURL(file);
		img.className = "max-h-28 rounded shadow";
		preview.appendChild(img);
	} else {
		preview.innerHTML = `<span class="text-xs text-red-500">Archivo no válido (solo imágenes).</span>`;
		return;
	}

	// Botón confirmar
	const btnConfirm = document.createElement("button");
	btnConfirm.textContent = "Confirmar";
	btnConfirm.className =
		"bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-full";
	btnConfirm.onclick = () => {
		const fd = new FormData();
		fd.append("archivo", file);
		fd.append("tipo", "validacion_ingresos");
		fd.append("id_inquilino", idInquilino);
		fd.append("nombre_normalizado", nombreInquilino);
		if (fileId) fd.append("archivo_id", fileId);

		Swal.fire({
			title: "Subiendo Validación Ingresos...",
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading(),
			background: "#18181b",
			color: "#fff",
		});

		fetch(
			(window.ADMIN_BASE || window.BASE_URL || "") +
				(fileId
					? "/inquilino/reemplazar_archivo"
					: "/inquilino/subir-archivo"),
			{
				method: "POST",
				body: fd,
			}
		)
			.then((r) => r.json())
			.then((j) => {
				Swal.close();
				if (!j.ok) throw new Error(j.mensaje || j.error || "Error");
				Swal.fire({
					icon: "success",
					title: "¡Validación de Ingresos subida!",
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
					text: err.message || "No se pudo subir",
					background: "#18181b",
					color: "#fff",
				});
			});
	};

	// Botón descartar
	const btnCancel = document.createElement("button");
	btnCancel.textContent = "Descartar";
	btnCancel.className =
		"bg-gray-600 hover:bg-gray-700 text-white text-xs px-3 py-1 rounded-full";
	btnCancel.onclick = () => location.reload();

	actions.appendChild(btnConfirm);
	actions.appendChild(btnCancel);
}
