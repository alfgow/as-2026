<div class="max-w-xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-indigo-200 mb-3">Editar Asesor</h1>

    <form method="POST" action="<?= $baseUrl ?>/asesores/update"
          class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-7">
        <input type="hidden" name="id" value="<?= $asesor['id'] ?>">
        <div>
            <label class="block font-bold text-indigo-300 mb-2">Nombre completo *</label>
            <input type="text" name="nombre_asesor" required value="<?= htmlspecialchars($asesor['nombre_asesor']) ?>"
                   class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition" />
        </div>
        <div>
            <label class="block font-bold text-indigo-300 mb-2">Email *</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($asesor['email']) ?>"
                   class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition" />
        </div>
        <div>
            <label class="block font-bold text-indigo-300 mb-2">Celular</label>
            <input type="text" name="celular" value="<?= htmlspecialchars($asesor['celular']) ?>"
                   class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition" />
        </div>
        <div>
            <label class="block font-bold text-indigo-300 mb-2">Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($asesor['telefono']) ?>"
                   class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition" />
        </div>
        <div class="flex justify-between gap-4">
            <a href="<?= $baseUrl ?>/asesores" class="inline-block px-6 py-3 rounded-xl bg-indigo-800 hover:bg-indigo-700 text-white font-bold shadow transition text-base">Cancelar</a>
            <button type="submit"
                    class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg transition text-base">Guardar cambios</button>
        </div>
    </form>
</div>
