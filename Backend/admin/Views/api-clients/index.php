<?php
/** @var array<int, array<string, mixed>> $clients */
/** @var array<string, string> $supportedScopes */
/** @var array<string, mixed>|null $flashCredentials */
/** @var string|null $errorMessage */
/** @var string $expectedAudience */

$clients          = $clients ?? [];
$supportedScopes  = $supportedScopes ?? [];
$flashCredentials = $flashCredentials ?? null;
$errorMessage     = $errorMessage ?? null;
$expectedAudience = $expectedAudience ?? 'n8n-integrations';

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'active'    => 'bg-emerald-500/20 text-emerald-200 border border-emerald-400/30',
            'suspended' => 'bg-amber-500/20 text-amber-200 border border-amber-400/30',
            'revoked'   => 'bg-red-500/20 text-red-200 border border-red-400/40',
            default     => 'bg-slate-500/20 text-slate-200 border border-slate-400/30',
        };
    }
}
?>

<div class="space-y-8">
    <section class="bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl text-slate-100">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">Centro de credenciales API</h1>
                <p class="text-sm text-slate-300 mt-1">Registra clientes, entrega secretos y controla qué scopes pueden usar tus integraciones.</p>
            </div>
            <div class="text-right">
                <span class="text-xs uppercase tracking-wide text-slate-400">Audience esperada</span>
                <p class="text-lg font-semibold text-emerald-300"><?= htmlspecialchars($expectedAudience, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </section>

    <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-500/20 border border-red-500/40 text-red-100 rounded-xl p-4">
            <p class="font-semibold">Algo salió mal</p>
            <p class="text-sm mt-1"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashCredentials) && is_array($flashCredentials)): ?>
        <div class="bg-emerald-500/10 border border-emerald-400/40 rounded-2xl p-5 text-emerald-50 shadow-lg">
            <h2 class="text-xl font-semibold flex items-center gap-2">
                <span>🔐 <?= htmlspecialchars($flashCredentials['title'] ?? 'Credenciales generadas', ENT_QUOTES, 'UTF-8') ?></span>
            </h2>
            <p class="text-sm text-emerald-100 mt-1">Guarda estos datos en tu orquestador seguro. El secreto sólo aparece una vez.</p>
            <div class="mt-4 grid md:grid-cols-2 gap-4">
                <div class="bg-black/20 rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-emerald-200">Client ID</p>
                    <div class="flex items-center gap-2 mt-1">
                        <code class="text-sm break-all"><?= htmlspecialchars($flashCredentials['client_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
                        <button type="button" class="text-xs px-2 py-1 bg-emerald-400/20 rounded-lg border border-emerald-300/30 hover:bg-emerald-400/40"
                            data-copy="<?= htmlspecialchars($flashCredentials['client_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            Copiar
                        </button>
                    </div>
                </div>
                <div class="bg-black/20 rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-emerald-200">Client secret</p>
                    <div class="flex items-center gap-2 mt-1">
                        <code class="text-sm break-all"><?= htmlspecialchars($flashCredentials['client_secret'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
                        <button type="button" class="text-xs px-2 py-1 bg-emerald-400/20 rounded-lg border border-emerald-300/30 hover:bg-emerald-400/40"
                            data-copy="<?= htmlspecialchars($flashCredentials['client_secret'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            Copiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">✨ Nuevo cliente API</h3>
            <form action="<?= admin_base_url('integrations/clients') ?>" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm mb-1 text-slate-200" for="client-name">Nombre visible</label>
                    <input type="text" id="client-name" name="name" class="w-full rounded-xl bg-black/30 border border-white/10 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="n8n Producción" required>
                </div>
                <div>
                    <label class="block text-sm mb-1 text-slate-200" for="rate-limit">Límite por minuto</label>
                    <input type="number" min="1" id="rate-limit" name="rate_limit" value="60" class="w-full rounded-xl bg-black/30 border border-white/10 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <p class="block text-sm mb-2 text-slate-200">Scopes permitidos</p>
                    <div class="grid sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                        <?php foreach ($supportedScopes as $scope => $description): ?>
                            <label class="flex items-start gap-2 bg-black/20 rounded-xl p-3 border border-white/5 hover:border-indigo-400/60 cursor-pointer">
                                <input type="checkbox" name="scopes[]" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 text-indigo-500 focus:ring-indigo-400">
                                <span>
                                    <span class="text-sm font-semibold text-slate-100"><?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="block text-xs text-slate-400 leading-tight"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-400 transition text-white font-semibold rounded-xl py-3 shadow-lg shadow-indigo-500/30">Crear cliente</button>
            </form>
        </div>
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 space-y-3 text-slate-200">
            <h3 class="text-lg font-semibold flex items-center gap-2">📋 Tips rápidos</h3>
            <ul class="space-y-3 text-sm text-slate-300">
                <li class="flex gap-2">
                    <span>•</span>
                    <span>Comparte el <strong>client_id</strong>, <strong>client_secret</strong> y el audience <code><?= htmlspecialchars($expectedAudience, ENT_QUOTES, 'UTF-8') ?></code> para el endpoint <code>/api/auth/login</code>.</span>
                </li>
                <li class="flex gap-2">
                    <span>•</span>
                    <span>Los scopes limitan lo que cada automatización puede consultar. Selecciona sólo lo que necesita.</span>
                </li>
                <li class="flex gap-2">
                    <span>•</span>
                    <span>Si se compromete un secreto, usa el botón “Rotar secreto” en la tabla para invalidar el anterior.</span>
                </li>
                <li class="flex gap-2">
                    <span>•</span>
                    <span>Cuando un cliente no se usa más, suspéndelo directamente desde la base de datos o revoca sus tokens.</span>
                </li>
            </ul>
        </div>
    </div>

    <section class="bg-white/5 border border-white/10 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <h3 class="text-lg font-semibold text-slate-100">Clientes registrados</h3>
            <p class="text-sm text-slate-400"><?= count($clients) ?> clientes activos en total.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-slate-200">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-3">Cliente</th>
                        <th class="text-left py-2 pr-3">Scopes</th>
                        <th class="text-left py-2 pr-3">Límite</th>
                        <th class="text-left py-2 pr-3">Último uso</th>
                        <th class="text-right py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-6 text-slate-400">Aún no hay clientes registrados.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($clients as $client): ?>
                        <tr class="border-t border-white/5">
                            <td class="py-4 pr-3 align-top">
                                <p class="font-semibold text-white flex items-center gap-2">
                                    <?= htmlspecialchars($client['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= status_badge_class($client['status'] ?? '') ?>">
                                        <?= htmlspecialchars($client['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </p>
                                <p class="text-xs text-slate-400 break-all mt-1">ID: <?= htmlspecialchars($client['client_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="py-4 pr-3 align-top">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach (($client['allowed_scopes'] ?? []) as $scope): ?>
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-500/20 border border-indigo-400/30">
                                            <?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="py-4 pr-3 align-top">
                                <?= isset($client['rate_limit_per_minute']) ? (int)$client['rate_limit_per_minute'] : 0 ?> rpm
                            </td>
                            <td class="py-4 pr-3 align-top text-slate-400">
                                <?php if (!empty($client['last_used_at'])): ?>
                                    <?= htmlspecialchars(date('d M Y H:i', strtotime((string)$client['last_used_at'])), ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="py-4 text-right align-top">
                                <form action="<?= admin_base_url('integrations/clients/rotate-secret') ?>" method="POST" class="inline-flex flex-col items-end gap-2">
                                    <input type="hidden" name="client_id" value="<?= (int)($client['id'] ?? 0) ?>">
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-full border border-amber-400/50 text-amber-200 hover:bg-amber-400/20">Rotar secreto</button>
                                    <button type="button" class="text-xs text-slate-300 underline" data-copy="<?= htmlspecialchars($client['client_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Copiar ID</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy');
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = 'Copiado';
                setTimeout(() => {
                    button.textContent = 'Copiar';
                }, 1500);
            } catch (error) {
                console.error('No se pudo copiar', error);
            }
        });
    });

</script>
