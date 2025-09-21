// admin/assets/main.js
document.addEventListener("DOMContentLoaded", function () {
	tailwind.config = {
		theme: {
			extend: {
				colors: {
					primary: "#4f46e5",
					"primary-dark": "#4338ca",
					secondary: "#818cf8",
					dark: "#0f172a",
					light: "#f1f5f9",
					success: "#10b981",
					warning: "#f59e0b",
					danger: "#ef4444",
					glass: "rgba(255, 255, 255, 0.08)",
					"glass-border": "rgba(255, 255, 255, 0.1)",
				},
				boxShadow: {
					glass: "0 20px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(129, 140, 248, 0.1)",
					btn: "0 8px 25px rgba(79, 70, 229, 0.4)",
					"btn-hover": "0 12px 30px rgba(79, 70, 229, 0.6)",
				},
				animation: {
					float: "float 8s infinite ease-in-out",
					rotate: "rotate 20s linear infinite",
				},
				keyframes: {
					float: {
						"0%": { transform: "translateY(0px)" },
						"50%": { transform: "translateY(-20px)" },
						"100%": { transform: "translateY(0px)" },
					},
					rotate: {
						from: { transform: "rotate(0deg)" },
						to: { transform: "rotate(360deg)" },
					},
				},
			},
		},
	};
	const BASE_URL = "<?php echo rtrim(getBaseUrl(), '/'); ?>";

	const sidebar = document.getElementById("sidebar");
	const overlay = document.getElementById("sidebar-backdrop");
	const menuBtn = document.getElementById("menu-btn");
	const closeBtn = document.getElementById("closeSidebar");

	// Abrir sidebar
	if (menuBtn) {
		menuBtn.addEventListener("click", function () {
			sidebar.classList.remove("-translate-x-full");
			overlay.classList.remove("hidden");
		});
	}

	// Cerrar sidebar
	function closeSidebar() {
		sidebar.classList.add("-translate-x-full");
		overlay.classList.add("hidden");
	}

	if (closeBtn) {
		closeBtn.addEventListener("click", closeSidebar);
	}

	if (overlay) {
		overlay.addEventListener("click", closeSidebar);
	}

	// Ajustar sidebar al cambiar tamaño
	function handleResize() {
		const width = window.innerWidth;

		if (width >= 1280) {
			sidebar.classList.remove("-translate-x-full");
			overlay.classList.add("hidden");
		} else {
			if (sidebar.classList.contains("-translate-x-full")) {
				overlay.classList.add("hidden");
			} else {
				overlay.classList.remove("hidden");
			}
		}
	}

	window.addEventListener("resize", handleResize);
	handleResize();
});

document.addEventListener("DOMContentLoaded", function () {
	const links = document.querySelectorAll(".sidebar-link");
	const currentPath = window.location.pathname;

	links.forEach((link) => {
		const linkPath = new URL(link.href).pathname;

		if (
			currentPath === linkPath ||
			currentPath.startsWith(linkPath + "/")
		) {
			link.classList.add(
				"bg-indigo-600/90",
				"text-white",
				"shadow-md",
				"font-semibold",
				"ring-2",
				"ring-indigo-400/40"
			);
		} else {
			link.classList.add(
				"hover:bg-indigo-500/40",
				"hover:text-white",
				"transition",
				"text-indigo-100"
			);
		}
	});
	function showLoader(msg = "Procesando información...") {
		const loader = document.getElementById("global-loader");
		if (!loader) return;
		const msgEl = loader.querySelector("p");
		if (msgEl) msgEl.textContent = msg;
		loader.classList.remove("hidden");
	}

	function hideLoader() {
		const loader = document.getElementById("global-loader");
		if (!loader) return;
		loader.classList.add("hidden");
	}
	// Guardamos el fetch original
	const _fetch = window.fetch;

	// Sobreescribimos fetch para mostrar/ocultar loader automáticamente
        window.fetch = async function (resource, options) {
                let fetchOptions = options;
                let shouldSkipLoader = false;

                if (options && typeof options === "object") {
                        shouldSkipLoader = options.skipLoader === true;

                        if ("skipLoader" in options) {
                                const { skipLoader, ...restOptions } = options;
                                fetchOptions = restOptions;
                        }
                }

                try {
                        // Mostrar loader al iniciar cualquier petición (a menos que se omita explícitamente)
                        if (!shouldSkipLoader) {
                                showLoader("Procesando información...");
                        }

                        // Ejecutar la petición real
                        const response = await _fetch(resource, fetchOptions);

                        // Ocultar loader al terminar
                        if (!shouldSkipLoader) {
                                hideLoader();
                        }

                        return response;
                } catch (err) {
                        // Aseguramos ocultar el loader también en caso de error
                        if (!shouldSkipLoader) {
                                hideLoader();
                        }
                        throw err;
                }
        };
});
document.addEventListener("DOMContentLoaded", () => {
	// Selecciona todos los forms de cambiar archivo
	document.querySelectorAll("form[id^='form-cambiar-']").forEach((form) => {
		form.addEventListener("submit", async function (e) {
			e.preventDefault(); // 🚫 Evita submit normal

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
	});
});
