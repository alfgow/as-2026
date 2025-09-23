// assets/js/login.js
"use strict";

document.addEventListener("DOMContentLoaded", () => {
	const hasSwal = typeof Swal !== "undefined";
	const ctx = window.loginContext || {};

	const form =
		document.getElementById("form-login") ||
		document.querySelector('form[action$="/login"]');
	const inputUser =
		document.getElementById("user") ||
		(form && form.querySelector('[name="user"]'));
	const inputPass =
		document.getElementById("password") ||
		(form && form.querySelector('[name="password"]'));
	const btn =
		document.getElementById("btn-login") ||
		(form &&
			form.querySelector('button[type="submit"],input[type="submit"]'));

	const toast = (title, text, icon = "info") => {
		if (hasSwal) {
			Swal.fire({
				icon,
				title,
				text,
				background: "#0f172a", // tema oscuro
				color: "#fff",
				confirmButtonColor: "#4f46e5",
			});
		} else {
			alert(`${title}\n${text}`);
		}
	};

	// ---- Mensajes que ya tenías con loginContext ----
	if (
		ctx.posted &&
		typeof ctx.error === "string" &&
		ctx.error.trim().length > 0
	) {
		toast("😖", ctx.error, "error");
		return;
	}
	if (ctx.loggedout) {
		if (hasSwal) {
			Swal.fire({
				icon: "info",
				title: "Sesión cerrada",
				text: "¡Hasta pronto, Iron Man!",
				timer: 2000,
				showConfirmButton: false,
				background: "#0f172a",
				color: "#fff",
			});
		}
	}
	if (ctx.expired) {
		if (hasSwal) {
			Swal.fire({
				icon: "warning",
				title: "Sesión expirada",
				text: "Estuviste inactivo por más de 30 minutos. Por favor inicia sesión de nuevo.",
				timer: 3500,
				showConfirmButton: false,
				background: "#0f172a",
				color: "#fff",
			});
		}
	}

	// ---- Validación/submit del formulario ----
        if (!form) {
                return;
        }
        // Si el botón no fuera submit por accidente, forzamos submit del form
        if (btn && btn.type !== "submit") {
                btn.addEventListener("click", (e) => {
                        e.preventDefault();
                        form.requestSubmit();
                });
        }

        let locked = false;
        form.addEventListener("submit", (e) => {
                const u = ((inputUser && inputUser.value) || "").trim();
                const p = ((inputPass && inputPass.value) || "").trim();

                if (!u || !p) {
			e.preventDefault();
			toast("😖", "Por favor ingresa usuario y contraseña.", "warning");
			return;
		}

		if (locked) {
			// evita doble envío
			e.preventDefault();
			return;
		}
		locked = true;

		if (btn) {
			btn.disabled = true;
			btn.setAttribute("aria-disabled", "true");
		}
	});
});
