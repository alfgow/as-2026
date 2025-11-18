

<aside id="sidebar"
  class="print:hidden fixed top-0 left-0 z-50 w-64 h-full  shadow-lg  transition-transform duration-300 flex flex-col bg-transparent">

  <!-- admin/Views/layouts/_sidebar.php -->
<div class="h-full flex flex-col justify-between
    bg-white/5 backdrop-blur-md border border-white/0 rounded-2xl p-5 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] m-3 
    px-4 py-7 relative ring-1 ring-white/15">

  <!-- LOGO y título -->
  <div>
    <div class="flex flex-col items-center mb-9 mt-2">
        <button id="closeSidebar"
  class="absolute top-4 right-3 xl:hidden bg-red-600 hover:bg-red-700 text-white rounded-full p-2 shadow-lg transition duration-150 ease-in-out focus:outline-none"
  aria-label="Cerrar menú">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
    <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
  </svg>
</button>


      <div class="w-14 h-14 rounded-full shadow-lg ring-2 ring-indigo-300 bg-white flex items-center justify-center mb-2">
        <img src="https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png" alt="Logo" class="w-10 h-10" />
      </div>
      <span class="text-indigo-100 font-bold text-base text-center tracking-tight drop-shadow">Arrendamiento Seguro</span>
    </div>
<nav class="flex flex-col gap-1 text-base mt-8 overflow-y-auto max-h-[80vh] scrollbar-none">
        <!-- nav: IA -->
        <a href="<?= $baseUrl ?>/ia" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="dashboard">
            <!-- Lucide Dashboard -->
            <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/>
            </svg>
            IA
        </a>
        <hr class="my-3 border-indigo-900/30" />
        <!-- nav: Dashboard -->
      <a href="<?= $baseUrl ?>/dashboard" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="dashboard">
        <!-- Lucide Dashboard -->
        <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="9" rx="2" />
          <rect x="14" y="3" width="7" height="5" rx="2" />
          <rect x="14" y="12" width="7" height="9" rx="2" />
          <rect x="3" y="16" width="7" height="5" rx="2" />
        </svg>
        Dashboard
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Proximos Vencimientos -->
      <a href="<?= $baseUrl ?>/vencimientos" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="vencimientos">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
        </svg>
        Próximos vencimientos
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Pólizas -->
      <a href="<?= $baseUrl ?>/polizas" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="polizas">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
        Pólizas
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Inquilinos -->
       <a href="<?= $baseUrl ?>/inquilino" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="inquilinos">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>

        Inquilinos
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Arrendadores -->
      <a href="<?= $baseUrl ?>/arrendadores" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="arrendadores">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        Arrendadores
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Inmuebles -->
      <a href="<?= $baseUrl ?>/inmuebles" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="inmuebles">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10z" />
            <path d="M9 22V12h6v10" />
        </svg>
        Inmuebles
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Blog -->
      <a href="<?= $baseUrl ?>/blog" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="blog">
        <!-- Lucide: Newspaper -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="5" width="18" height="14" rx="2" />
          <path d="M7 15h.01" />
          <path d="M7 9h8" />
          <path d="M7 13h8" />
        </svg>
        Blog
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: API Tokens -->
      <a href="<?= $baseUrl ?>/integrations/clients" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl" data-path="integrations/clients">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 1 1 2.122 5.122l-7.99 7.99a4.5 4.5 0 0 1-6.364-6.364l7.99-7.99A3 3 0 0 1 15.75 5.25Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 15.75h6" />
        </svg>
        API Tokens
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Asesores -->
      <a href="<?= $baseUrl ?>/asesores" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="asesores">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        Asesores Inmobiliarios
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Usuarios -->
      <a href="<?= $baseUrl ?>/usuarios" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="usuarios">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
        </svg>
        Usuarios
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- nav: Financieros -->
      <a href="<?= $baseUrl ?>/financieros" class=" sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="financieros">
        <!-- Lucide: Newspaper -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
            Reportes Financieros
      </a>
      <hr class="my-3 border-indigo-900/30" />
      <!-- ...agrega más links aquí... -->
       <!-- nav: Cerrar sesión -->
<a href="<?= $baseUrl ?>/logout" class="text-red-400 hover:text-red-300 font-bold sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl " data-path="logout">
    <!-- Icono: Logout -->
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1" />
    </svg>
    Cerrar sesión
</a>
    </nav>
  </div>

  <div class="pt-8 text-xs text-indigo-300 opacity-80 text-center select-none">
    &copy; <?= date('Y') ?> Arrendamiento Seguro<br>v1.0
  </div>
</div>


</aside>
