<?php
// Puedes traer estos datos desde el controlador si deseas que sean dinámicos
$totalPolizas      = $totalPolizas ;
$polizasVigentes   = $polizasVigentes ;
$polizasConcluidas = $polizasConcluidas ;
$polizasIncumplidas = $polizasIncumplidas ;
$ultimaPoliza = $ultimaPoliza;
?>

<!-- Tarjeta: Total -->
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
    <div class="flex items-center gap-2 text-indigo-400">
        <div class="p-2 bg-indigo-600/30 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h18v18H3V3z" /></svg>
        </div>
        <p class="text-sm text-white">Total de pólizas</p>
    </div>
    <p class="text-3xl font-bold text-indigo-400"><?= $ultimaPoliza ?></p>
</div>

<!-- Tarjeta: Vigentes -->
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
    <div class="flex items-center gap-2 text-green-400">
        <div class="p-2 bg-green-600/30 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg>
        </div>
        <p class="text-sm text-white">Vigentes</p>
    </div>
    <p class="text-3xl font-bold text-green-400"><?= $polizasVigentes ?></p>
</div>

<!-- Tarjeta: Concluidas -->
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
    <div class="flex items-center gap-2 text-blue-400">
        <div class="p-2 bg-blue-600/30 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" /></svg>
        </div>
        <p class="text-sm text-white">Concluidas</p>
    </div>
    <p class="text-3xl font-bold text-blue-400"><?= $polizasConcluidas ?></p>
</div>

<!-- Tarjeta: Incumplimientos -->
<div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
    <div class="flex items-center gap-2 text-red-400">
        <div class="p-2 bg-red-600/30 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.364 5.636L5.636 18.364M5.636 5.636l12.728 12.728" /></svg>
        </div>
        <p class="text-sm text-white">Incumplimientos</p>
    </div>
    <p class="text-3xl font-bold text-red-400"><?= $polizasIncumplidas ?></p>
</div>
