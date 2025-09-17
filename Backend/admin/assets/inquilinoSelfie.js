// inquilinoSelfie.js
function handleSelfieSelect(input) {
	const file = input.files[0];
	if (!file) return;

	// Leer atributos seguros desde el input
	const fileId = input.dataset.id || null;
	const idInquilino = input.dataset.inquilino || null; // 👈 aquí sacamos el id_inquilino
	const nombreInquilino = input.dataset.nombre || "";

	const preview = document.getElementById("selfie-preview");
	const actions = document.getElementById("selfie-actions");
	if (!preview || !actions) return;

	// limpiar
	preview.innerHTML = "";
	actions.innerHTML = "";

	// mostrar preview
	if (file.type.startsWith("image/")) {
		const img = document.createElement("img");
		img.src = URL.createObjectURL(file);
		img.className = "max-h-28 rounded shadow";
		preview.appendChild(img);
	} else {
		preview.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5Zm6.61 10.936a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
      </svg>`;
	}

	// botones
	const btnConfirm = document.createElement("button");
	btnConfirm.textContent = "Confirmar";
	btnConfirm.className =
		"bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-full";
	btnConfirm.onclick = () => {
		const fd = new FormData();
		fd.append("archivo", file);
		fd.append("tipo", "selfie");
		fd.append("id_inquilino", idInquilino);
		fd.append("nombre_normalizado", nombreInquilino);
		if (fileId) fd.append("archivo_id", fileId);

		Swal.fire({
			title: "Subiendo selfie...",
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
					title: "¡Selfie subida!",
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

	const btnCancel = document.createElement("button");
	btnCancel.textContent = "Descartar";
	btnCancel.className =
		"bg-gray-600 hover:bg-gray-700 text-white text-xs px-3 py-1 rounded-full";
	btnCancel.onclick = () => location.reload();

	actions.appendChild(btnConfirm);
	actions.appendChild(btnCancel);
}
