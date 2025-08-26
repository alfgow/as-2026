<?php
// app/Views/prospectos/code.php
// $title y $headerTitle ya vienen del controlador.
// Calcula base del admin para fetch POST:
$__base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
?>

<section class="px-4 md:px-6 mt-6">
  <!-- Encabezado estilo dashboard -->
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-indigo-200"><?= htmlspecialchars($headerTitle ?? 'Emitir acceso') ?></h1>
      <p class="text-sm text-indigo-200/70">Genera un <strong>OTP</strong> y un <strong>Magic Link</strong> para que el inquilino/arrendador edite su propia ficha.</p>
    </div>
  </div>

  <!-- Grid glassy (siguiendo tu patrón) -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Card: Formulario -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 md:p-6 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)]">
      <div class="flex items-center gap-3 mb-4 text-indigo-300">
        <div class="p-2 bg-indigo-600/20 rounded-full border border-indigo-400/30">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M16 12H8m8 0a4 4 0 10-8 0m8 0a4 4 0 01-8 0m-4 8h16" />
          </svg>
        </div>
        <h2 class="text-lg font-semibold">Emitir acceso</h2>
      </div>

      <form id="issue-form" class="space-y-4">
        <div>
          <label class="block text-sm text-indigo-200/80 mb-1">Email del prospecto</label>
          <input type="email" name="email" required
       value="<?= $prefillEmail ?>"
       class="w-full rounded-xl bg-white/5 border border-white/20 text-white placeholder-indigo-200/50 px-3 py-2 focus:outline-none focus:ring-4 focus:ring-indigo-400/25 focus:border-indigo-300"
       placeholder="prospecto@dominio.com" autofocus>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-sm text-indigo-200/80 mb-1">Actor</label>
            <select name="actor"
                    class="w-full rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2 focus:outline-none focus:ring-4 focus:ring-indigo-400/25 focus:border-indigo-300">
              <option value="">Detectar automáticamente</option>
              <option value="inquilino">Inquilino</option>
              <option value="arrendador">Arrendador</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-indigo-200/80 mb-1">Vigencia (min)</label>
            <input type="number" name="ttl_minutes" min="5" value="1440"
                   class="w-full rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2 focus:outline-none focus:ring-4 focus:ring-indigo-400/25 focus:border-indigo-300">
          </div>
          <div class="flex items-end">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 font-semibold text-white"
                    style="background:#de6868;"
                    onmouseover="this.style.background='#f08f8f'"
                    onmouseout="this.style.background='#de6868'">
              Generar acceso
            </button>
          </div>
        </div>

        <div class="text-sm text-indigo-200/70" id="status"></div>
      </form>
    </div>

    <!-- Card: Resultados -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 md:p-6 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)]">
      <div class="flex items-center gap-3 mb-4 text-indigo-300">
        <div class="p-2 bg-indigo-600/20 rounded-full border border-indigo-400/30">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4" />
          </svg>
        </div>
        <h2 class="text-lg font-semibold">Resultados</h2>
        
      </div>

      <div id="result" class="grid gap-4 hidden">
        <div>
          <div class="text-xs font-medium text-indigo-200/70 mb-1">OTP (6 dígitos)</div>
          <div class="flex gap-2">
            <input id="otp" class="flex-1 rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2" readonly>
            <button data-copy="#otp"
                    class="px-3 py-2 rounded-xl text-sm text-white bg-slate-800/80 hover:bg-slate-700">
              Copiar
            </button>
          </div>
        </div>

        <div>
          <div class="text-xs font-medium text-indigo-200/70 mb-1">Magic Link</div>
          <div class="flex gap-2">
            <input id="magic_link" class="flex-1 rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2" readonly>
            <button data-copy="#magic_link"
                    class="px-3 py-2 rounded-xl text-sm text-white bg-slate-800/80 hover:bg-slate-700">
              Copiar
            </button>
          </div>
          <p class="text-xs text-indigo-200/60 mt-1">Abre la página pública <code>/auth/code</code> en el Frontend.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <div class="text-xs font-medium text-indigo-200/70 mb-1">Expira</div>
            <input id="expires_at" class="w-full rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2" readonly>
          </div>
          <div>
            <div class="text-xs font-medium text-indigo-200/70 mb-1">Actor</div>
            <input id="actor_type" class="w-full rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2" readonly>
          </div>
          <div>
            <div class="text-xs font-medium text-indigo-200/70 mb-1">ID Actor</div>
            <input id="actor_id" class="w-full rounded-xl bg-white/5 border border-white/20 text-white px-3 py-2" readonly>
          </div>
          <div id="accionesCorreo" class="mt-4 hidden">
    <button id="enviarCorreosBtn" 
        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg">
        Enviar correos
    </button>
</div>
        </div>
      </div>
    </div>


  </div>
</section>

<script>
    let ultimoResultado = null;

    // Si vino prellenado, selecciona el campo para copiar/editar rápido
    (function(){
      const input = document.querySelector('input[name="email"]');
      if (input && input.value) { input.focus(); input.select(); }
    })();

    (function(){
      const form = document.getElementById('issue-form');
      const statusEl = document.getElementById('status');
      const result = document.getElementById('result');
      const otp = document.getElementById('otp');
      const magicLink = document.getElementById('magic_link');
      const expiresAt = document.getElementById('expires_at');
      const actorType = document.getElementById('actor_type');
      const actorId = document.getElementById('actor_id');

      // Copiar al portapapeles
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-copy]');
        if (!btn) return;
        e.preventDefault();
        const sel = btn.getAttribute('data-copy');
        const input = document.querySelector(sel);
        if (!input) return;
        input.select(); document.execCommand('copy');
        const old = btn.textContent;
        btn.textContent = '¡Copiado!';
        setTimeout(()=> btn.textContent = old, 1000);
      });

      // Enviar formulario (generar acceso)
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        statusEl.textContent = 'Generando…';

        const data = {
          email: form.email.value.trim(),
          actor: form.actor.value || undefined,
          ttl_minutes: parseInt(form.ttl_minutes?.value || '15', 10)
        };

        try {
          const resp = await fetch('<?= $__base ?>/prospectos/code', {
            method: 'POST',
            headers: { 'Content-Type':'application/json' },
            body: JSON.stringify(data)
          });
          const json = await resp.json();

          if (json.ok) {
            // 🔥 Guardamos json en variable global para luego enviar correos
            ultimoResultado = json;

            otp.value        = json.otp || '';
            magicLink.value  = json.magic_link || '';
            expiresAt.value  = json.expires_at || '';
            actorType.value  = json.actor_type || '';
            actorId.value    = json.actor_id || '';

            result.classList.remove('hidden');
            document.getElementById("accionesCorreo").classList.remove("hidden");
            statusEl.textContent = 'Listo.';
          } else {
            result.classList.add('hidden');
            statusEl.textContent = json.mensaje || 'Error al generar';
            alert(json.mensaje || 'No se pudo generar el acceso.');
          }
        } catch (err) {
          result.classList.add('hidden');
          statusEl.textContent = 'Error de red';
          alert('Error de red');
        }
      });
    })();

    // Botón para enviar correos
    document.getElementById("enviarCorreosBtn").addEventListener("click", async () => {
    if (!ultimoResultado) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Primero genera el acceso antes de enviar correos.'
        });
        return;
    }

    // Loader mientras se envían los correos
    Swal.fire({
        title: 'Enviando correos...',
        text: 'Por favor espera un momento',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const resp = await fetch("<?= $baseUrl ?>/prospectos/sendEmails", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(ultimoResultado)
        });
        const data = await resp.json();

        if (data.ok) {
            Swal.fire({
                icon: 'success',
                title: '¡Enviado!',
                text: data.mensaje || 'Los correos se enviaron correctamente'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje || 'No se pudieron enviar los correos'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar con el servidor'
        });
    }
});


</script>
