<!-- Views/404.php -->

<section class="flex flex-col items-center justify-center py-24 px-4 select-none">
  <!-- SVG ilustrado: Un “mapa perdido” estilo minimalista -->
  <div class="mb-8 animate-fadeIn">
    <svg width="160" height="120" viewBox="0 0 160 120" fill="none">
      <!-- Fondo circular difuminado -->
      <ellipse cx="80" cy="60" rx="75" ry="55" fill="#fde8e8ca"/>
      <!-- Camino -->
      <path d="M35 110 Q80 90 125 110" stroke="#de6868" stroke-width="3" fill="none" />
      <!-- Punto ubicación -->
      <circle cx="80" cy="82" r="7" fill="#fbbcbc" stroke="#de6868" stroke-width="2"/>
      <!-- Pin ubicación estilizado -->
      <path d="M80 58 Q92 74 80 82 Q68 74 80 58Z" fill="#de6868" opacity="0.7"/>
      <!-- Icono brújula -->
      <circle cx="130" cy="35" r="14" fill="#23243a" stroke="#de6868" stroke-width="2"/>
      <polygon points="130,27 135,43 130,39 125,43" fill="#fbbcbc"/>
      <circle cx="130" cy="35" r="3" fill="#de6868"/>
      <!-- X de "tesoro perdido" -->
      <line x1="48" y1="35" x2="62" y2="49" stroke="#de6868" stroke-width="2" />
      <line x1="62" y1="35" x2="48" y2="49" stroke="#de6868" stroke-width="2" />
    </svg>
  </div>

  <h1 class="text-6xl md:text-7xl font-extrabold text-[var(--rosa-fuerte)] drop-shadow-lg mb-2 tracking-tight animate-fadeInUp">
    404
  </h1>
  <p class="text-2xl md:text-3xl font-bold text-[var(--rosa-acento)] mb-4 animate-fadeInUp delay-100">
    ¡Uy! Te perdiste en el mapa
  </p>
  <p class="text-base md:text-lg text-gray-300 mb-8 text-center animate-fadeInUp delay-200">
    La página que buscas no existe, fue movida o nunca estuvo aquí.<br>
    Regresa al panel y sigue explorando.
  </p>
  <a href="<?= admin_url('dashboard') ?>"
    class="inline-block px-8 py-3 rounded-2xl bg-[var(--rosa-fuerte)] text-white font-semibold shadow-xl hover:bg-[var(--rosa-acento)] hover:text-[var(--texto-oscuro)] focus:outline-none focus:ring-2 focus:ring-[var(--rosa-fuerte)] focus:ring-offset-2
    transition-all duration-200 animate-bounce">
    Volver al Dashboard
  </a>
</section>

<!-- Animaciones CSS para fadeIn -->
<style>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(.98);}
  to { opacity: 1; transform: scale(1);}
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(24px);}
  to { opacity: 1; transform: translateY(0);}
}
.animate-fadeIn { animation: fadeIn .8s ease both;}
.animate-fadeInUp { animation: fadeInUp .8s cubic-bezier(.4,2,.6,1) both;}
.animate-bounce { animation: bounce 1.3s infinite alternate;}
@keyframes bounce {
  0% { transform: translateY(0);}
  100% { transform: translateY(-8px);}
}
</style>
