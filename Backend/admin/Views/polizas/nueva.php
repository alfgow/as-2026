<?php
$siguienteNumero = $siguienteNumero ?? '';
?>
<div class="max-w-3xl mx-auto py-10">
    <?php
$hoy = date('Y-m-d');

$dt = new DateTime($hoy);
$dt->modify('+1 year -1 day');
$fin = $dt->format('Y-m-d');
?>
   <form id="form-nueva-poliza" 
      class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-8"
      method="POST" action="<?= $baseUrl ?>/polizas/store">

    <!-- Título con número de póliza -->
    <h1 class="text-3xl font-bold text-indigo-300 mb-6 flex items-center gap-3">
        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M8 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Registrando póliza Número: <span class="text-indigo-400"><?= htmlspecialchars($siguienteNumero) ?></span>
    </h1>
    <input type="hidden" name="numero_poliza" value="<?= htmlspecialchars($siguienteNumero) ?>">

    <div class="grid md:grid-cols-2 gap-6">

        <!-- Tipo de póliza -->
        <div>
            <label class="block text-indigo-300 mb-1">Tipo de Póliza</label>
            <select name="tipo_poliza" id="tipo-poliza"
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="Clásica">Clásica</option>
                <option value="Plus">Plus</option>
            </select>
        </div>


        <!-- Asesor inmobiliario -->
        <div>
            <label class="block text-indigo-300 mb-1">Asesor inmobiliario</label>
            <select name="id_asesor" id="asesor-select" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Selecciona un asesor</option>
                <?php foreach ($asesores as $as): ?>
                    <option value="<?= $as['id'] ?>"><?= htmlspecialchars($as['nombre_asesor']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Arrendador -->
        <div>
            <label class="block text-indigo-300 mb-1">Arrendador</label>
            <select name="id_arrendador" id="arrendador-select" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Selecciona un arrendador</option>
                <?php foreach ($arrendadores as $arr): ?>
                    <option value="<?= $arr['id'] ?>"><?= htmlspecialchars($arr['nombre_arrendador']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Inmueble -->
        <div>
            <label class="block text-indigo-300 mb-1">Inmueble</label>
            <select name="id_inmueble" id="inmueble-select" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">- SELECCIONA UN INMUEBLE -</option>
                <?php foreach ($inmuebles as $inm): ?>
                    <option value="<?= $inm['id'] ?>" data-monto="<?= $inm['renta'] ?>"><?= htmlspecialchars($inm['direccion_inmueble']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tipo de inmueble -->
        <div>
            <label class="block text-indigo-300 mb-1">Tipo de inmueble</label>
            <select name="tipo_inmueble" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">- SELECCIONE UNA OPCIÓN -</option>
                <?php foreach (['Departamento','Casa','Terreno','Local Comercial','Oficinas','Edificio'] as $opt): ?>
                    <option value="<?= $opt ?>"><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Monto de renta (readonly) -->
        <div>
            <label class="block text-indigo-300 mb-1">Monto de renta</label>
            <input type="text" id="monto-renta-display" readonly
                class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed">
            <input type="hidden" name="monto_renta" id="monto-renta-hidden">
        </div>


        <!-- Monto de póliza -->
        <div>
            <label class="block text-indigo-300 mb-1">Monto póliza</label>
            <input type="number" step="0.01" name="monto_poliza" id="monto-poliza"
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <!-- Fecha de inicio -->
       <div>
    <label class="block text-indigo-300 mb-1">Fecha de inicio</label>
    <input type="date" name="fecha_poliza" id="fecha-inicio"
        value="<?= $hoy ?>"
        class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
</div>

        <!-- Fecha de fin -->
        <div>
    <label class="block text-indigo-300 mb-1">Fecha de fin</label>
    <input type="date" name="fecha_fin" id="fecha-fin"
        value="<?= $fin ?>"
        class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
</div>

        <!-- Vigencia -->
        <div>
            <label class="block text-indigo-300 mb-1">Vigencia</label>
            <input type="text" name="vigencia" id="vigencia-texto"
                   class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" readonly>
        </div>

        <!-- Inquilino -->
        <div>
            <label class="block text-indigo-300 mb-1">Inquilino</label>
            <select name="id_inquilino" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">- SELECCIONA UN INQUILINO -</option>
                <?php foreach ($inquilinos as $inq): ?>
                    <option value="<?= $inq['id'] ?>" >
                        <?= htmlspecialchars(trim($inq['nombre_inquilino'] . ' ' . $inq['apellidop_inquilino'] . ' ' . $inq['apellidom_inquilino'])) ?>
                    </option>
                    <?php endforeach; ?>
            </select>
        </div>

        <!-- Obligado solidario -->
        <div>
            <label class="block text-indigo-300 mb-1">Obligado solidario</label>
            <select name="id_obligado" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Selecciona un obligado solidario</option>
                <?php foreach ($obligados as $os): ?>
                    <option value="<?= $os['id'] ?>" >
                        <?= htmlspecialchars(trim($os['nombre_inquilino'] . ' ' . $os['apellidop_inquilino'] . ' ' . $os['apellidom_inquilino'])) ?>
                    </option>
                    <?php endforeach; ?>
            </select>
        </div>

        <!-- Fiador -->
        <div>
            <label class="block text-indigo-300 mb-1">Fiador</label>
            <select name="id_fiador" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">- SELECCIONA UN FIADOR -</option>
                <?php foreach ($fiadores as $f): 
                    $idOpt   = (string)($f['id'] ?? '');
                    $idSel   = (string)($fiadorSeleccionado ?? '');
                    $nombre  = trim(
                        ($f['nombre_inquilino']    ?? '') . ' ' .
                        ($f['apellidop_inquilino'] ?? '') . ' ' .
                        ($f['apellidom_inquilino'] ?? '')
                    );
                ?>
                <option value="<?= htmlspecialchars($idOpt) ?>" >
      <?= htmlspecialchars($nombre !== '' ? $nombre : 'SIN NOMBRE') ?>
    </option>
    <?php endforeach; ?>
            </select>
        </div>
        <!-- Comisión Asesor (20% del monto) -->
        <div>
            <label class="block text-indigo-300 mb-1">Comisión del Asesor (20%)</label>
            <input type="text" id="comision-asesor" readonly
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#1c1c2a] border border-indigo-800 text-indigo-400 font-semibold cursor-not-allowed">
        </div>

    </div>

    <!-- Comentarios -->
    <div>
        <label class="block text-indigo-300 mb-1">Comentarios</label>
        <textarea name="comentarios" rows="3"
                  class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
    </div>

    <!-- Botón de guardar -->
    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow font-semibold">
            Guardar
        </button>
    </div>
</form>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
 const form = document.getElementById('form-nueva-poliza');
  if (!form) return;

  const submitBtn = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // UI: bloquear botón
    const prevText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Guardando...';
    }

    try {
      const fd = new FormData(form);
      const resp = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });

      const raw = await resp.text();
      let data;
      try { data = JSON.parse(raw); }
      catch { throw new Error(raw); }

      if (data.ok) {
        await Swal.fire({
          icon: 'success',
          title: 'Póliza registrada',
          text: `La póliza ${data.numero} se ha registrado exitosamente`
        });
        window.location = `${BASE_URL}/polizas/${data.numero}`;
      } else {
        Swal.fire('Error', data.error || 'No se pudo guardar', 'error');
      }
    } catch (err) {
      Swal.fire('Error', String(err).slice(0, 500), 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = prevText || 'Guardar';
      }
    }
  });
  // ---------- Helpers dinero ----------
  function parseMoneyToNumber(v) {
    if (typeof v === 'number') return v;
    if (!v) return 0;
    v = String(v).replace(/\s|\$/g, '');
    if (/\.\d{3},\d{2}$/.test(v)) { v = v.replace(/\./g, '').replace(',', '.'); }
    else { v = v.replace(/,/g, '.'); }
    const n = parseFloat(v);
    return isNaN(n) ? 0 : n;
  }
  function formatCurrency(n) {
    const num = typeof n === 'number' ? n : parseMoneyToNumber(n);
    return num.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
  }


  // ---------- Regla de precios ----------
  function calcularPoliza(montoRenta, tipoPoliza) {
    let precio = 0, r = parseMoneyToNumber(montoRenta);
    if (tipoPoliza === 'Clásica') {
      if (r < 10001) precio = 3700;
      else if (r < 15001) precio = 4300;
      else if (r < 20001) precio = 4500;
      else if (r < 25001) precio = 5200;
      else if (r < 30001) precio = 5500;
      else if (r < 35001) precio = 8100;
      else if (r < 40001) precio = 9300;
      else if (r < 45001) precio = 10000;
      else if (r < 50001) precio = 12000;
      else precio = r * 0.25;
    } else if (tipoPoliza === 'Plus') {
      if (r < 10001) precio = 4800;
      else if (r < 15001) precio = 5500;
      else if (r < 20001) precio = 7500;
      else if (r < 25001) precio = 8600;
      else if (r < 30001) precio = 9400;
      else if (r < 35001) precio = 11000;
      else if (r < 40001) precio = 11500;
      else if (r < 45001) precio = 13750;
      else if (r < 50001) precio = 14250;
      else precio = r * 0.30;
    }
    return Number(precio.toFixed(2));
  }

  // ---------- DOM refs ----------
  const asesorSel        = document.getElementById('asesor-select');
  const arrendadorSel    = document.getElementById('arrendador-select');
  const inmuebleSel      = document.getElementById('inmueble-select');
  const tipoInmuebleSel  = document.querySelector('select[name="tipo_inmueble"]');
  const tipoPolizaSel    = document.getElementById('tipo-poliza');
  const rentaDisplay     = document.getElementById('monto-renta-display');
  const rentaHidden      = document.getElementById('monto-renta-hidden');
  const montoPolizaInput = document.getElementById('monto-poliza');
  const comisionInput    = document.getElementById('comision-asesor');

  const fechaInicioInput = document.getElementById('fecha-inicio');
  const fechaFinInput    = document.getElementById('fecha-fin');
  const vigenciaInput    = document.getElementById('vigencia-texto');

  // ---------- Comisión ----------
  function actualizarComision() {
    const monto = parseMoneyToNumber(montoPolizaInput.value);
    comisionInput.value = monto > 0 ? formatCurrency(monto * 0.20) : '';
  }

  // ---------- Fechas / Vigencia ----------
  const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  function ymd(d){ // Date -> 'YYYY-MM-DD'
    const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), da=String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${da}`;
  }
  function fechaESLarga(ymdStr){
    if(!ymdStr) return '';
    const [y,m,d] = ymdStr.split('-'); // '2025-08-11'
    const mes = MESES[parseInt(m,10)-1];
    return `${d} de ${mes} de ${y}`;
  }
  function recalcularFechaFinDesdeInicio() {
    if (!fechaInicioInput.value) return;
    const base = new Date(fechaInicioInput.value + 'T00:00:00'); // evita desfases por zona horaria
    const fin  = new Date(base); fin.setFullYear(fin.getFullYear() + 1); fin.setDate(fin.getDate() - 1);
    fechaFinInput.value = ymd(fin);
  }
  function actualizarVigencia() {
    if (fechaInicioInput.value && fechaFinInput.value) {
      vigenciaInput.value = `del ${fechaESLarga(fechaInicioInput.value)} al ${fechaESLarga(fechaFinInput.value)}`;
    } else {
      vigenciaInput.value = '';
    }
  }

  // Listeners fechas
  fechaInicioInput.addEventListener('change', () => { recalcularFechaFinDesdeInicio(); actualizarVigencia(); });
  fechaFinInput   .addEventListener('change', actualizarVigencia);

  // ---------- Carga dependientes ----------
  asesorSel.addEventListener('change', async function () {
    const id = this.value;
    arrendadorSel.innerHTML = '<option value="">Cargando...</option>';
    inmuebleSel.innerHTML   = '<option value="">- SELECCIONA UN INMUEBLE -</option>';
    tipoInmuebleSel.value   = '';
    rentaDisplay.value = rentaHidden.value = '';
    montoPolizaInput.value  = '';
    actualizarComision();

    if (!id) { arrendadorSel.innerHTML = '<option value="">Selecciona un arrendador</option>'; return; }
    const resp = await fetch(BASE_URL + '/arrendadores/por-asesor/' + id);
    const data = await resp.json();
    let opts = '<option value="">Selecciona un arrendador</option>';
    data.forEach(a => { opts += `<option value="${a.id}">${a.nombre_arrendador}</option>`; });
    arrendadorSel.innerHTML = opts;
  });

  arrendadorSel.addEventListener('change', async function () {
    const id = this.value;
    inmuebleSel.innerHTML = '<option value="">Cargando...</option>';
    tipoInmuebleSel.value = '';
    rentaDisplay.value = rentaHidden.value = '';
    montoPolizaInput.value = '';
    actualizarComision();

    if (!id) { inmuebleSel.innerHTML = '<option value="">- SELECCIONA UN INMUEBLE -</option>'; return; }
    const resp = await fetch(BASE_URL + '/inmuebles/por-arrendador/' + id);
    const data = await resp.json();
    let opts = '<option value="">- SELECCIONA UN INMUEBLE -</option>';
    data.forEach(inm => {
      opts += `<option value="${inm.id}" data-monto="${inm.renta}">${inm.direccion_inmueble}</option>`;
    });
    inmuebleSel.innerHTML = opts;
  });

  inmuebleSel.addEventListener('change', async function () {
    const id = this.value;
    if (!id) {
      tipoInmuebleSel.value = '';
      rentaDisplay.value = rentaHidden.value = '';
      montoPolizaInput.value = '';
      actualizarComision();
      return;
    }
    const resp = await fetch(BASE_URL + '/inmuebles/info/' + id);
    const data = await resp.json();

    const renta = data?.renta ?? this.options[this.selectedIndex]?.getAttribute('data-monto') ?? '';
    const tipo  = data?.tipo  ?? '';

    tipoInmuebleSel.value = tipo || '';
    rentaHidden.value     = parseMoneyToNumber(renta);
    rentaDisplay.value    = formatCurrency(rentaHidden.value);

    const precio = calcularPoliza(rentaHidden.value, tipoPolizaSel.value);
    montoPolizaInput.value = String(precio);
    actualizarComision();
  });

  tipoPolizaSel.addEventListener('change', function () {
    const renta = parseMoneyToNumber(rentaHidden.value);
    if (renta > 0) {
      const precio = calcularPoliza(renta, tipoPolizaSel.value);
      montoPolizaInput.value = String(precio);
      actualizarComision();
    }
  });

  // Usuario edita monto manualmente
  montoPolizaInput.addEventListener('input',  actualizarComision);
  montoPolizaInput.addEventListener('change', actualizarComision);

  // ---------- Inicialización ----------
  // Con valores por defecto del servidor ($hoy / $fin) ya rellenados:
  actualizarVigencia();      // pinta la vigencia en la carga inicial
  actualizarComision();      // por si hay valor inicial en monto de póliza
});

</script>

