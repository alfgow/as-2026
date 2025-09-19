<?php

/** @var array<int, array<string, mixed>> $asesores */

?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Asesores Inmobiliarios</h1>
            <p class="text-sm text-indigo-200/80">Administra la cartera de asesores en un solo lugar.</p>
        </div>
        <button
            id="btn-open-create"
            type="button"
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-lg transition">
            + Nuevo Asesor
        </button>
    </div>

    <div
        id="asesores-empty"
        class="<?php echo empty($asesores) ? '' : 'hidden'; ?> rounded-2xl border border-indigo-700/40 bg-indigo-900/30 text-indigo-100 text-center py-12">
        <p class="text-lg font-semibold">Aún no hay asesores registrados.</p>
        <p class="text-sm mt-1 text-indigo-100/70">Utiliza el botón "+ Nuevo Asesor" para comenzar.</p>
    </div>

    <div id="asesores-list" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"></div>
</div>

<div
    id="asesor-modal"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-40 px-4"
    aria-hidden="true">
    <div class="bg-[#1b1f34] w-full max-w-xl rounded-2xl shadow-2xl border border-indigo-900/40 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 bg-indigo-900/40 border-b border-indigo-800/40">
            <h2 id="asesor-modal-title" class="text-lg font-semibold text-white">Nuevo Asesor</h2>
            <button id="asesor-modal-close" type="button" class="text-indigo-200 hover:text-white">
                <span class="sr-only">Cerrar</span>
                ✕
            </button>
        </div>
        <form id="asesor-form" class="px-6 py-6 space-y-5">
            <input type="hidden" name="id" id="asesor-id" />
            <div>
                <label for="asesor-nombre" class="block text-sm font-semibold text-indigo-200 mb-1">Nombre completo *</label>
                <input
                    id="asesor-nombre"
                    type="text"
                    name="nombre_asesor"
                    required
                    class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
            </div>
            <div>
                <label for="asesor-email" class="block text-sm font-semibold text-indigo-200 mb-1">Email *</label>
                <input
                    id="asesor-email"
                    type="email"
                    name="email"
                    required
                    class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
            </div>
            <div>
                <label for="asesor-celular" class="block text-sm font-semibold text-indigo-200 mb-1">Celular</label>
                <input
                    id="asesor-celular"
                    type="text"
                    name="celular"
                    class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 focus:ring-2 focus:ring-indigo-600 focus:outline-none" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button
                    type="button"
                    id="asesor-cancelar"
                    class="px-5 py-2 rounded-lg border border-indigo-600 text-indigo-200 hover:bg-indigo-600/20 transition">
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-lg transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const ASESORES_STATE = <?php echo json_encode(array_values($asesores), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function escapeHtml(value) {
        if (typeof value !== 'string') {
            value = value == null ? '' : String(value);
        }
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 👇 Nueva función para formatear nombres
    function toTitleCase(str) {
        if (!str) return '';
        return str
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    function formatAssignment(count, singular, plural) {
        const total = Number.isFinite(Number(count)) ? Number(count) : 0;
        const label = total === 1 ? singular : plural;
        return `${total} ${label}`;
    }

    function renderAsesores() {
        const container = document.getElementById('asesores-list');
        const emptyState = document.getElementById('asesores-empty');

        if (!container || !emptyState) return;

        if (!ASESORES_STATE.length) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        container.innerHTML = ASESORES_STATE.map((asesor) => {
            const rawName = asesor.nombre_asesor || 'Sin nombre';
            const name = escapeHtml(toTitleCase(rawName)); // 👈 formateo aplicado
            const email = escapeHtml(asesor.email || '');
            const celular = escapeHtml(asesor.celular || '—');
            const totalInquilinos = Number.isFinite(Number(asesor.inquilinos_total)) ?
                Number(asesor.inquilinos_total) :
                (Array.isArray(asesor.inquilinos_id) ? asesor.inquilinos_id.length : 0);
            const totalArrendadores = Number.isFinite(Number(asesor.arrendadores_total)) ?
                Number(asesor.arrendadores_total) :
                0;

            return `
                <article class="bg-[#1f2340] rounded-2xl border border-indigo-900/40 shadow-lg p-6 flex flex-col gap-4" data-id="${asesor.id}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1">${name}</h3>
                            <p class="text-sm text-indigo-200">${email}</p>
                            <p class="text-xs text-indigo-200/70 mt-1">Celular: ${celular}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <button
                                type="button"
                                class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition"
                                data-action="edit"
                                data-id="${asesor.id}"
                            >Editar</button>
                            <button
                                type="button"
                                class="px-3 py-1 rounded-lg bg-red-600/80 hover:bg-red-600 text-white text-sm font-semibold transition"
                                data-action="delete"
                                data-id="${asesor.id}"
                            >Eliminar</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs text-indigo-200/70 border-t border-indigo-900/60 pt-3">
                        <div>
                            <p class="font-semibold text-indigo-200/80 uppercase tracking-wide text-[11px]">Inquilinos</p>
                            <p>${formatAssignment(totalInquilinos, 'inquilino asignado', 'inquilinos asignados')}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-indigo-200/80 uppercase tracking-wide text-[11px]">Arrendadores</p>
                            <p>${formatAssignment(totalArrendadores, 'arrendador asignado', 'arrendadores asignados')}</p>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function openModal(mode, asesor = null) {
        const modal = document.getElementById('asesor-modal');
        const title = document.getElementById('asesor-modal-title');
        const idField = document.getElementById('asesor-id');
        const nombre = document.getElementById('asesor-nombre');
        const email = document.getElementById('asesor-email');
        const celular = document.getElementById('asesor-celular');
        if (!modal || !title || !idField || !nombre || !email || !celular) {
            return;
        }

        modal.dataset.mode = mode;
        if (mode === 'edit' && asesor) {
            title.textContent = 'Editar Asesor';
            idField.value = asesor.id ?? '';
            nombre.value = asesor.nombre_asesor ?? '';
            email.value = asesor.email ?? '';
            celular.value = asesor.celular ?? '';
        } else {
            title.textContent = 'Nuevo Asesor';
            idField.value = '';
            nombre.value = '';
            email.value = '';
            celular.value = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        nombre.focus();
    }

    function closeModal() {
        const modal = document.getElementById('asesor-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.removeAttribute('data-mode');
    }

    async function request(url, formData) {
        showLoader();
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json().catch(() => null);
            if (!result) {
                throw new Error('No fue posible interpretar la respuesta del servidor.');
            }

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'La operación no se pudo completar.');
            }

            return result;
        } finally {
            hideLoader();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderAsesores();

        const btnCreate = document.getElementById('btn-open-create');
        const modalClose = document.getElementById('asesor-modal-close');
        const modalCancel = document.getElementById('asesor-cancelar');
        const modal = document.getElementById('asesor-modal');
        const form = document.getElementById('asesor-form');

        btnCreate?.addEventListener('click', () => openModal('create'));
        modalClose?.addEventListener('click', closeModal);
        modalCancel?.addEventListener('click', closeModal);

        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const currentMode = modal?.dataset.mode === 'edit' ? 'edit' : 'create';
            const url = window.joinAdmin(currentMode === 'edit' ? '/asesores/update' : '/asesores/store');
            const data = new FormData(form);

            try {
                const result = await request(url, data);
                const asesor = result.asesor;
                if (!asesor) {
                    throw new Error('El servidor no regresó la información del asesor.');
                }

                if (currentMode === 'edit') {
                    const index = ASESORES_STATE.findIndex((item) => Number(item.id) === Number(asesor.id));
                    if (index !== -1) {
                        ASESORES_STATE[index] = asesor;
                    }
                } else {
                    ASESORES_STATE.push(asesor);
                }

                closeModal();
                renderAsesores();
                Swal.fire({
                    icon: 'success',
                    title: 'Listo',
                    text: result.message || 'Operación realizada correctamente.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: error instanceof Error ? error.message : 'Ocurrió un error inesperado.',
                });
            }
        });

        document.getElementById('asesores-list')?.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const action = target.dataset.action;
            const id = target.dataset.id;
            if (!action || !id) {
                return;
            }

            const asesor = ASESORES_STATE.find((item) => Number(item.id) === Number(id));
            if (!asesor) {
                Swal.fire({
                    icon: 'error',
                    title: 'No encontrado',
                    text: 'No fue posible localizar la información del asesor.',
                });
                return;
            }

            if (action === 'edit') {
                openModal('edit', asesor);
                return;
            }

            if (action === 'delete') {
                const confirm = await Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar asesor?',
                    text: 'Esta acción no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444',
                    reverseButtons: true,
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                const data = new FormData();
                data.append('id', String(asesor.id));

                try {
                    const result = await request(window.joinAdmin('/asesores/delete'), data);
                    const index = ASESORES_STATE.findIndex((item) => Number(item.id) === Number(result.id));
                    if (index !== -1) {
                        ASESORES_STATE.splice(index, 1);
                    }

                    renderAsesores();
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: result.message || 'El asesor se eliminó correctamente.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar',
                        text: error instanceof Error ? error.message : 'Ocurrió un error inesperado.',
                    });
                }
            }
        });
    });
</script>