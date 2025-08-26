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
});
