<?php
require_once __DIR__ . '/../../Helpers/url.php';

$iaHistorialUrl = admin_url('/ia/historial');
$iaChatUrl      = admin_url('/ia/chat');
?>
<!-- Chat Card -->
<div
    class="mx-auto w-full max-w-6xl sm:mt-6 flex flex-col
         bg-white/5 border border-white/10 rounded-3xl
         shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] backdrop-blur-lg overflow-hidden
         h-[80vh] md:h-[75vh]">


    <!-- Header -->
    <div class="border-b border-white/10 px-6 py-4 flex items-center gap-3 bg-gradient-to-r from-indigo-900/40 via-indigo-800/30 to-transparent">
        <div class="p-2 rounded-xl bg-white/5 border border-white/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 8h6m-3-3v6m-7 4h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="flex-1">
            <h1 class="text-sm font-semibold text-indigo-300"><?= htmlspecialchars($headerTitle) ?></h1>
        </div>
        <a href="<?= htmlspecialchars($iaHistorialUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-xs text-indigo-400 hover:underline">Historial</a>
    </div>

    <!-- Chat area -->
    <div id="chat"
        class="flex-1 overflow-y-auto overscroll-contain px-6 py-6 space-y-4"
        style="-webkit-overflow-scrolling: touch;">

        <!-- Mensaje de bienvenida -->
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center shrink-0">
                <img src="<?= $baseUrl ?>/assets/img/chat-model.svg" alt="IA" class="w-6 h-6">
            </div>

            <div class="max-w-3xl bg-white/5 border border-white/10 rounded-2xl px-4 py-3 shadow">
                <p class="text-sm text-gray-200">¡Hola! Soy PolizIA de <span class="text-indigo-300 font-medium">Arrendamiento Seguro</span>. En que puedo ayudarte hoy?.</p>
            </div>
        </div>
    </div>

    <!-- Composer -->
    <div class="border-t border-white/10 p-4 bg-gray-800/30">
        <form id="composer" class="max-w-5xl mx-auto flex items-end gap-2">
            <textarea id="prompt" rows="1" placeholder="Escribe tu mensaje..."
                class="flex-1 resize-none bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500 placeholder-gray-500"></textarea>
            <button id="sendBtn" type="submit"
                class="px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-sm font-semibold shadow transition disabled:opacity-60 disabled:cursor-not-allowed">
                Enviar
            </button>
        </form>
        <div class="max-w-5xl mx-auto mt-2 flex items-center gap-3 text-xs text-gray-400 flex-wrap">
            <span id="metaTyping" class="hidden px-2 py-1 rounded-full bg-white/5 border border-white/10">
                Pensando<span id="dots" class="inline-flex">…</span>
            </span>
        </div>
    </div>

</div>



<script>
    document.addEventListener("DOMContentLoaded", () => {
        const chat = document.getElementById("chat");
        const composer = document.getElementById("composer");
        const promptInput = document.getElementById("prompt");

        // --- Helpers de UI ---
        function scrollToBottom() {
            chat.scrollTop = chat.scrollHeight;
        }

        function bubbleUser(text) {
            const row = document.createElement("div");
            row.className = "flex items-start gap-3 justify-end";
            row.innerHTML = `
      <div class="max-w-3xl bg-indigo-600/20 border border-indigo-400/20 rounded-2xl px-4 py-3 shadow">
        <p class="text-sm text-indigo-100 whitespace-pre-wrap"></p>
      </div>
      <div class="w-8 h-8 rounded-full bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center shrink-0">
        <span class="text-xs">Tú</span>
      </div>
    `;
            row.querySelector("p").textContent = text;
            chat.appendChild(row);
            scrollToBottom();
        }

        function bubbleAIThinking() {
            const row = document.createElement("div");
            row.className = "flex items-start gap-3";
            row.innerHTML = `
      <div class="w-8 h-8 rounded-full bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center shrink-0">
        <img src="<?= $baseUrl ?>/assets/img/chat-model.svg" alt="IA" class="w-6 h-6">
      </div>
      <div class="max-w-3xl bg-white/5 border border-white/10 rounded-2xl px-4 py-3 shadow">
        <div class="flex items-center gap-2">
          <span class="inline-block w-2 h-2 rounded-full bg-gray-300 animate-bounce"></span>
          <span class="inline-block w-2 h-2 rounded-full bg-gray-300 animate-bounce [animation-delay:0.15s]"></span>
          <span class="inline-block w-2 h-2 rounded-full bg-gray-300 animate-bounce [animation-delay:0.3s]"></span>
        </div>
      </div>
    `;
            chat.appendChild(row);
            scrollToBottom();
            return row;
        }

        function replaceBubbleWithHTML(row, html) {
            const p = document.createElement("p");
            p.className = "text-sm text-gray-200 whitespace-pre-wrap";
            p.innerHTML = html; // permitir <a href=...> que viene del backend
            const container = row.querySelector(".max-w-3xl");
            container.innerHTML = "";
            container.appendChild(p);
            scrollToBottom();
        }

        // Enter para enviar (Shift+Enter = salto de línea)
        promptInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                composer.requestSubmit();
            }
        });

        composer.addEventListener("submit", async (e) => {
            e.preventDefault();
            const text = promptInput.value.trim();
            if (!text) return;

            // pinta mensaje del usuario
            bubbleUser(text);
            promptInput.value = "";

            // pinta globito "pensando"
            const typingRow = bubbleAIThinking();

            try {
                // RUTA RELATIVA: respeta tu $base del router
                const chatEndpoint = (typeof window.joinAdmin === 'function')
                    ? window.joinAdmin('/ia/chat')
                    : <?= json_encode($iaChatUrl, JSON_UNESCAPED_SLASHES) ?>;

                const res = await fetch(chatEndpoint, {
                    skipLoader: true,
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        model: "claude",
                        prompt: text
                    })
                });

                const data = await res.json();

                if (data.ok) {
                    // Si es respuesta DIRECTA (sin IA), simulamos tiempo de "pensando"
                    if (data.mode === "direct" || data.model_key === "direct") {
                        const delay = typeof data.hintDelayMs === "number" ? data.hintDelayMs : 900;
                        setTimeout(() => {
                            replaceBubbleWithHTML(typingRow, data.output || "");
                        }, delay);
                    } else {
                        // Respuesta normal del modelo: reemplazar de inmediato
                        replaceBubbleWithHTML(typingRow, data.output || "");
                    }
                } else {
                    replaceBubbleWithHTML(typingRow, "⚠️ " + (data.error || "Error desconocido"));
                }
            } catch (err) {
                replaceBubbleWithHTML(typingRow, "⚠️ Error de conexión");
            }
        });
    });
</script>