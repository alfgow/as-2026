# 🏗️ Backend – Arrendamiento Seguro

## ⚙️ Tecnologías y estructura
- **Backend:** PHP con MVC, programación orientada a objetos
- **Frontend:** HTML + Tailwind CSS (tema oscuro, diseño moderno)
- **JavaScript:** validaciones, multistep, SweetAlert2, Dropzone.js
- **Base de datos:** MySQL en AWS RDS
- **Archivos:** Subidos a S3 con claves organizadas por usuario
- **Despliegue:** AWS Lambda, EC2, RDS

---

## 👥 Módulos principales

### 🧍 Inquilinos
- Formulario multistep con validaciones
- Selfie capturada desde cámara (no archivo)
- Subida de identificación (según tipo)
- Hasta 5 comprobantes de ingreso (PDF)
- Guardado de archivos en S3
- Tablas relacionadas:
  - `inquilinos_2025`
  - `inquilinos_direccion`
  - `inquilinos_trabajo`
  - `inquilinos_historial_vivienda`
  - `inquilinos_validaciones`
  - `inquilinos_archivos`
  - `inquilinos_fiador` (si aplica)
- Validación de unicidad: email, teléfono, device ID

### 🧑‍💼 Arrendadores
- Registro similar a inquilinos
- Tabla: `arrendadores`
- Archivos en: `arrendadores_archivos`
- Vista de detalle incluye:
  - Datos personales
  - Información bancaria
  - Inmuebles registrados
  - Pólizas asociadas
  - Documentos (selfie, ID)
  - Comentarios y validaciones

### 🏠 Inmuebles
- Relacionados con arrendadores
- Campos: dirección, tipo, renta, mantenimiento
- Vistas modernas tipo dashboard

### 📄 Pólizas
- Relación: arrendador, inquilino, inmueble
- Funcionalidad:
  - Registro y edición
  - Renovación automática
  - Vencimientos próximos
  - Vista de detalle estilizada
- Estatus con visualización (color, badges)

### 📊 Dashboard Administrativo
- KPIs visuales en tarjetas (`cards`)
- Gráficas (`Chart.js`)
- Vista de prospectos nuevos y vencimientos
- Navegación con sidebar e íconos

---

## 🎨 Identidad Visual (Backend)

### 🎨 Colores principales:
- Fondo base: `bg-gray-900`
- Texto principal: `text-white`
- Acentos: `text-indigo-400`, `text-indigo-300`
- Tarjetas: `bg-white/5`, `bg-white/10`
- Bordes: `border-white/20`, `border-indigo-900/20`

### 🎨 Estilo de componentes:
- **Botones:** redondeados, sombras suaves
- **Cards:** `rounded-2xl`, `shadow-xl`, `backdrop-blur-md`
- **Layout:** grid y flex responsive
- **Sidebar:** fijo, íconos Lucide, link activo dinámico

---

## 🔐 Funcionalidades clave
- Middleware de sesión centralizado
- Validaciones en frontend y backend
- Slugs amigables para vistas detalladas (`/admin/prospecto/nombre-apellido`)
- Carga de archivos con feedback visual
- Paginación y filtros con persistencia de parámetros
- Identidad visual consistente en todo el sistema

## 📚 Documentación de referencia
- [Autenticación de la API](docs/api-auth.md): guía para configurar el flujo de tokens, permisos y clientes externos.

## ✅ Verificación manual reciente
- Con `DB_CHARSET=utf8mb4_0900_ai_ci` en `admin/config/credentials.php`, la aplicación establece la conexión MySQL correctamente y ejecuta consultas sin errores.

---

## 🧠 Enfoque visual y de UX
- Mobile-first, diseño limpio y funcional
- Componentes visuales con `glassmorphism`
- Secciones separadas por tarjetas
- Experiencia clara, profesional y accesible

