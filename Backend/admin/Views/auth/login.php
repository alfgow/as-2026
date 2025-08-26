<section class="min-h-screen w-full flex items-center justify-center bg-gradient-to-br from-[#0f0f1c] via-indigo-900 to-[#1c1c2b] text-white px-4">
    <div class="w-full max-w-md p-8 rounded-3xl backdrop-blur-lg border border-white/10 bg-white/5 shadow-[0_8px_32px_rgba(31,38,135,0.37)] animate-fade-in">
        <!-- Logo -->
        <div class="flex flex-col items-center mb-6">
            <img src="<?= asset_url('logo.png') ?>" alt="Logo" class="w-20 h-20 rounded-full shadow-md ring-2 ring-indigo-400 bg-white p-1" />
            <h1 class="text-2xl mt-3 font-bold text-indigo-100 tracking-tight drop-shadow">Iniciar sesión</h1>
        </div>

        <!-- Formulario -->
        <form action="<?= admin_url('/login') ?>" method="POST" class="space-y-5" novalidate>
            <div>
                <label for="user" class="block mb-1 text-sm font-medium text-indigo-100">Usuario</label>
                <div class="flex items-center gap-2 bg-gray-900 px-3 py-2 rounded-full focus-within:ring-2 focus-within:ring-indigo-500">
                    <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM3 21a9 9 0 1118 0H3z" />
                    </svg>
                    <input
                        type="text"
                        id="user"
                        name="user"
                        class="bg-transparent w-full text-white focus:outline-none"
                        placeholder="Tu usuario"
                        autocomplete="username"
                        required
                        autofocus
                    />
                </div>
            </div>

            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-indigo-100">Contraseña</label>
                <div class="rounded-full flex items-center gap-2 bg-gray-900 px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                    <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0 0h.01M17 13V9a5 5 0 00-10 0v4a5 5 0 00-2 4v3h14v-3a5 5 0 00-2-4z" />
                    </svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="bg-transparent w-full text-white focus:outline-none"
                        placeholder="Tu contraseña"
                        autocomplete="current-password"
                        required
                    />
                </div>
            </div>

            <button type="submit" class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 transition-all shadow-md font-semibold text-white text-lg flex items-center justify-center gap-2">
                🚀 <span>Ingresar</span>
            </button>
        </form>
    </div>
</section>

<!-- Animación de entrada -->
<style>
@keyframes fade-in {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.6s ease-out forwards; }
</style>