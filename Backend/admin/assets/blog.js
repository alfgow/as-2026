const ADMIN_BASE = (window.ADMIN_BASE || window.baseurl || '').replace(/\/$/, '');
const joinAdminUrl = (path = '') => {
        const normalizedPath = path
                ? path.startsWith('/')
                        ? path
                        : `/${path}`
                : '';

        if (!ADMIN_BASE) {
                return normalizedPath || '/';
        }

        return `${ADMIN_BASE}${normalizedPath}`;
};

// Drag & drop image preview
const dropzone = document.getElementById("dropzone");
const fileInput = document.getElementById("image");
const imagePreview = document.getElementById("image-preview");

let webpBlob = null; // Nuevo: aquí guardamos el archivo convertido

tinymce.init({
	selector: "#contenido",
	plugins: "link image lists code table",
	toolbar:
		"undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code",
	menubar: false,
	height: 380,
	branding: false,
	content_css: [
		"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css",
	],
	images_upload_url: "/ruta-a-tu-upload-en-backend", // Opcional
	automatic_uploads: true,
	images_upload_handler: function (blobInfo, success, failure) {
		failure(
			"La carga directa está deshabilitada. Usa la sección de imágenes."
		);
	},
});

dropzone.addEventListener("dragover", (e) => {
	e.preventDefault();
	dropzone.classList.add("border-indigo-400");
});
dropzone.addEventListener("dragleave", (e) => {
	e.preventDefault();
	dropzone.classList.remove("border-indigo-400");
});
dropzone.addEventListener("drop", (e) => {
	e.preventDefault();
	dropzone.classList.remove("border-indigo-400");
	const files = e.dataTransfer.files;
	if (files.length) {
		fileInput.files = files;
		handleImage(files[0]);
	}
});
fileInput.addEventListener("change", () => {
	if (fileInput.files.length) handleImage(fileInput.files[0]);
});

function handleImage(file) {
	// Si ya es WebP, solo lo usamos tal cual
	if (file.type === "image/webp") {
		webpBlob = file;
		showPreview(file);
		return;
	}

	const reader = new FileReader();
	reader.onload = (e) => {
		const img = new Image();
		img.onload = () => {
			const canvas = document.createElement("canvas");
			canvas.width = img.width;
			canvas.height = img.height;
			const ctx = canvas.getContext("2d");
			ctx.drawImage(img, 0, 0);
			canvas.toBlob(
				(blob) => {
					webpBlob = blob;
					showPreviewBlob(blob);
				},
				"image/webp",
				0.92
			); // Calidad 92%
		};
		img.src = e.target.result;
	};
	reader.readAsDataURL(file);
}

function showPreview(file) {
	const reader = new FileReader();
	reader.onload = (e) => {
		imagePreview.src = e.target.result;
		imagePreview.classList.remove("hidden");
	};
	reader.readAsDataURL(file);
}

function showPreviewBlob(blob) {
	const url = URL.createObjectURL(blob);
	imagePreview.src = url;
	imagePreview.classList.remove("hidden");
}

document.addEventListener("DOMContentLoaded", function () {
	const form = document.getElementById("blogForm");
	form.addEventListener("submit", async function (e) {
		e.preventDefault();

		// ¡Esta línea es la clave para TinyMCE!
		tinymce.triggerSave();

		const formData = new FormData(form);

		// Si hay imagen convertida, reemplaza el archivo en el FormData
		if (webpBlob) {
			formData.set("image", webpBlob, "imagen.webp");
		}

		try {
			const response = await fetch(form.action, {
				method: "POST",
				body: formData,
			});

			const result = await response.json();

			if (result.success) {
				Swal.fire({
					icon: "success",
					title: "¡Éxito!",
					text:
						result.message ||
						"Entrada de blog creada correctamente.",
					confirmButtonText: "Ir al blog",
				}).then(() => {
                                        window.location.href = joinAdminUrl('blog');
				});
			} else {
				Swal.fire({
					icon: "error",
					title: "Error",
					text:
						result.message ||
						"No se pudo crear la entrada de blog.",
				});
			}
		} catch (error) {
			Swal.fire({
				icon: "error",
				title: "Error de conexión",
				text: "No se pudo conectar al servidor.",
			});
		}
	});
});
