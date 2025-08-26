<h1 class="text-2xl font-bold mb-6 text-indigo-200">Agregar Inmueble</h1>
<form method="post" action="#" class="space-y-4 max-w-xl">
    <div>
        <label class="block text-sm mb-1">Calle</label>
        <input type="text" name="calle" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">Número</label>
            <input type="text" name="numero" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
        <div>
            <label class="block text-sm mb-1">Colonia</label>
            <input type="text" name="colonia" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
    </div>
    <div>
        <label class="block text-sm mb-1">Municipio</label>
        <input type="text" name="municipio" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">CP</label>
            <input type="text" name="cp" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
        <div>
            <label class="block text-sm mb-1">Tipo de propiedad</label>
            <input type="text" name="tipo" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
    </div>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Guardar</button>
</form>
