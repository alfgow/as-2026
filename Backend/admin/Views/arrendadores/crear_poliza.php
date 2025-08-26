<h1 class="text-2xl font-bold mb-6 text-indigo-200">Registrar Póliza</h1>
<form method="post" action="#" class="space-y-4 max-w-xl">
    <div>
        <label class="block text-sm mb-1">Inquilino</label>
        <input type="text" name="inquilino" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
    </div>
    <div>
        <label class="block text-sm mb-1">Inmueble</label>
        <input type="text" name="inmueble" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
    </div>
    <div>
        <label class="block text-sm mb-1">Tipo de póliza</label>
        <input type="text" name="tipo_poliza" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">Vigencia</label>
            <input type="text" name="vigencia" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
        <div>
            <label class="block text-sm mb-1">Monto</label>
            <input type="text" name="monto" class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100" />
        </div>
    </div>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Generar póliza</button>
</form>
