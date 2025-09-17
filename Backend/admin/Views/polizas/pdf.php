
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Póliza <?= $poliza['numero_poliza'] ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    @page {
      size: A4;
      margin: 40px 35px;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: white;
      color: #1f2937;
      font-size: 14px;
      line-height: 1.6;
    }

    h1, h2, h3, .title {
      color: #4b1d1d;
    }

    .section-title {
      background-color: #fde8e8ca;
      padding: 4px 10px;
      border-left: 5px solid #de6868;
      font-size: 1rem;
      margin-top: 28px;
      margin-bottom: 12px;
      font-weight: 700;
      color: #4b1d1d;
    }

    .detalle-linea {
      display: flex;
      margin-bottom: 6px;
    }

    .detalle-linea .label {
      width: 180px;
      font-weight: 600;
      color: #374151;
      flex-shrink: 0;
    }

    .detalle-linea .valor {
      flex: 1;
      color: #111827;
      word-break: break-word;
    }

    .wrapper {
      max-width: 680px;
      margin: 0 auto;
    }
  </style>
</head>
<body>

  <div class="wrapper">
    <!-- ENCABEZADO -->
    <div class="text-center mb-8">
      <img src="https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png" class="mx-auto h-16 mb-2" alt="Logo">
      <h1 class="text-2xl font-bold uppercase tracking-wide">Arrendamiento Seguro</h1>
      <p class="text-xs">GRUPO VEINTIUNO CERO CINCO DOCE S.A. DE C.V.</p>
      <p class="text-xs">Av. Ejército Nacional Mexicano 505, Chapultepec Morales, Granada, Miguel Hidalgo, 11520 Ciudad de México, CDMX</p>
      <p class="text-xs">Tel: 5587929965 | Email: polizas@arrendamientoseguro.app</p>
    </div>

    <!-- TÍTULO -->
    <div class="mb-4">
      <h2 class="text-xl font-bold title text-center mb-1">Póliza <?= htmlspecialchars($poliza['tipo_poliza']) ?> No. <?= htmlspecialchars($poliza['numero_poliza']) ?></h2>
      <p class="text-right text-xs text-gray-600">Fecha de emisión: <?= date('Y-m-d') ?></p>
    </div>

    <!-- GENERALES -->
    <div class="section-title">1. Generales de la Póliza</div>
    <div class="detalle-linea"><span class="label">Serie:</span><span class="valor"><?= date('Y') ?></span></div>
    <div class="detalle-linea"><span class="label">Asesor Inmobiliario:</span><span class="valor"><?= $poliza['nombre_asesor'] ?></span></div>
    <div class="detalle-linea"><span class="label">Fiador:</span><span class="valor"><?= $poliza['nombre_fiador_completo'] ?? 'NO APLICA' ?></span></div>
    <div class="detalle-linea"><span class="label">Obligado Solidario:</span><span class="valor"><?= $poliza['nombre_obligado_completo'] ?? 'NO APLICA' ?></span></div>

    <!-- INMUEBLE -->
    <div class="section-title">2. Inmueble Asegurado</div>
    <div class="detalle-linea"><span class="label">Tipo de inmueble:</span><span class="valor"><?= $poliza['tipo_inmueble'] ?></span></div>
    <div class="detalle-linea"><span class="label">Dirección:</span><span class="valor"><?= $poliza['direccion_inmueble'] ?></span></div>

    <!-- ARRENDADOR -->
    <div class="section-title">3. Datos del Arrendador</div>
    <div class="detalle-linea"><span class="label">Nombre:</span><span class="valor"><?= $poliza['nombre_arrendador'] ?></span></div>

    <!-- INQUILINO -->
    <div class="section-title">4. Datos del Inquilino</div>
    <div class="detalle-linea"><span class="label">Nombre:</span><span class="valor"><?= $poliza['nombre_inquilino_completo'] ?></span></div>
    <div class="detalle-linea"><span class="label">Dirección:</span><span class="valor"><?= $poliza['direccion_inmueble'] ?></span></div>

    <!-- VIGENCIA -->
    <div class="section-title">5. Vigencia y Cobertura</div>
    <div class="detalle-linea"><span class="label">Vigencia:</span><span class="valor"><?= $poliza['vigencia'] ?></span></div>
    <div class="detalle-linea"><span class="label">Monto renta:</span><span class="valor">$<?= number_format($poliza['monto_renta'], 2) ?></span></div>
    <div class="detalle-linea"><span class="label">Monto póliza:</span><span class="valor">$<?= number_format($poliza['monto_poliza'], 2) ?></span></div>

    <!-- TOTAL -->
    <div class="mt-6 text-right pr-2 pt-2 border-t border-[#de6868]">
      <p class="text-lg font-bold text-[#4b1d1d]">Total a pagar: $<?= number_format($poliza['monto_poliza'], 2) ?></p>
    </div>
  </div>

</body>
</html>
