<h1 class="text-2xl font-bold mb-6 text-indigo-200">Editar Arrendador</h1>
<form method="post" action="<?= $baseUrl ?>/arrendador/update" class="space-y-4 max-w-xl">
    <input type="hidden" name="id" value="<?= $arrendador['id'] ?>">
    <div>
        <label class="block text-sm mb-1">Nombre completo</label>
        <input type="text" name="nombre_arrendador" value="<?= htmlspecialchars($arrendador['nombre_arrendador']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
    </div>
    <div>
        <label class="block text-sm mb-1">Correo</label>
        <input type="email" name="email" value="<?= htmlspecialchars($arrendador['email']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">Celular</label>
            <input type="text" name="celular" value="<?= htmlspecialchars($arrendador['celular']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
        </div>
        <div>
            <label class="block text-sm mb-1">Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($arrendador['telefono']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1">RFC</label>
        <input type="text" name="rfc" value="<?= htmlspecialchars($arrendador['rfc']) ?>" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100">
    </div>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Guardar cambios</button>
</form>
