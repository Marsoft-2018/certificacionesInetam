<?php
// Detectar BASE_URL dinámicamente (funciona en cualquier subdirectorio)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
// scriptDir = directorio donde está index.php (ej: /certificados/public)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$dynamicBase = $scheme . '://' . $host . $scriptDir;
// Sobreescribir la constante con la URL real detectada en tiempo de ejecución
if (!defined('RUNTIME_BASE_URL')) define('RUNTIME_BASE_URL', $dynamicBase);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>INETAM – Sistema de Certificados y Diplomas</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"/>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= RUNTIME_BASE_URL ?>/css/app.css"/>
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="navbar navbar-dark">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-3">
      <div class="navbar-escudo">
        <img src="<?= RUNTIME_BASE_URL ?>/assets/img/escudo_placeholder.svg" alt="Escudo" height="48" id="navEscudo"/>
      </div>
      <div>
        <div class="navbar-brand-title">INETAM</div>
        <div class="navbar-brand-sub">Sistema de Certificados y Diplomas</div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-light text-dark small">San Martín de Loba, Bolívar</span>
      <button class="btn btn-outline-light btn-sm" id="btnNuevoEstudiante">
        <i class="bi bi-person-plus"></i> Nuevo Estudiante
      </button>
      <button class="btn btn-success btn-sm" id="btnActaGeneral" title="Generar Acta General">
        <i class="bi bi-file-earmark-pdf"></i> Acta General
      </button>
    </div>
  </div>
</nav>

<!-- ── Main Content ─────────────────────────────────────────── -->
<div class="container-fluid py-4 px-4">

  <!-- Stats Cards -->
  <div class="row g-3 mb-4" id="statsRow">
    <div class="col-sm-6 col-md-3">
      <div class="stat-card stat-total">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div class="stat-info">
          <div class="stat-num" id="statTotal">–</div>
          <div class="stat-label">Total Estudiantes</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card stat-academico">
        <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
        <div class="stat-info">
          <div class="stat-num" id="statAcademico">–</div>
          <div class="stat-label">Bachiller Académico</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card stat-tecnico">
        <div class="stat-icon"><i class="bi bi-tools"></i></div>
        <div class="stat-info">
          <div class="stat-num" id="statTecnico">–</div>
          <div class="stat-label">Bachiller Técnico</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="stat-card stat-tic">
        <div class="stat-icon"><i class="bi bi-cpu"></i></div>
        <div class="stat-info">
          <div class="stat-num" id="statTic">–</div>
          <div class="stat-label">Bachiller TIC</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Registro de Estudiantes</h5>
        <small class="text-muted">Gestión de diplomas, actas y documentos</small>
      </div>
      <div id="tableLoadingIndicator" class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Cargando…</span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="tablaEstudiantes" class="table table-hover mb-0 w-100">
          <thead>
            <tr>
              <th>#</th>
              <th>Apellidos y Nombres</th>
              <th>Documento</th>
              <th>Título</th>
              <th>Especialidad</th>
              <th>Año</th>
              <th>Libro / Folio / Diploma</th>
              <th class="text-center">Documentos PDF</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="tablaBody">
            <!-- Llenado por DataTables + Axios -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Formulario ─────────────────────────────────────── -->
<div class="modal fade" id="modalEstudiante" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalLabel"><i class="bi bi-person-badge me-2"></i>Estudiante</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="estudianteId"/>

        <!-- Sección: Datos personales -->
        <div class="section-title"><i class="bi bi-person me-2"></i>Datos Personales</div>
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label required">Primer Nombre</label>
            <input type="text" class="form-control" id="primer_nombre" placeholder="Ej: Katherin" required/>
          </div>
          <div class="col-md-3">
            <label class="form-label">Segundo Nombre <span class="text-muted">(opcional)</span></label>
            <input type="text" class="form-control" id="segundo_nombre" placeholder="Ej: Alexa"/>
          </div>
          <div class="col-md-3">
            <label class="form-label required">Primer Apellido</label>
            <input type="text" class="form-control" id="primer_apellido" placeholder="Ej: Ardila" required/>
          </div>
          <div class="col-md-3">
            <label class="form-label">Segundo Apellido <span class="text-muted">(opcional)</span></label>
            <input type="text" class="form-control" id="segundo_apellido" placeholder="Ej: Limas"/>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Número de Documento</label>
            <input type="text" class="form-control" id="numero_documento" placeholder="Ej: 1085050020" required/>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Título</label>
            <select class="form-select" id="titulo">
              <option value="">– Seleccionar –</option>
              <option value="Bachiller Académico">Bachiller Académico</option>
              <option value="Bachiller Técnico">Bachiller Técnico</option>
              <option value="Bachiller Técnico en TIC">Bachiller Técnico en TIC</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Especialidad</label>
            <select class="form-select" id="especialidad">
              <option value="Sin Especialidad">Sin Especialidad</option>
              <option value="Agropecuario">Agropecuario</option>
              <option value="Énfasis Programación">Énfasis Programación</option>
            </select>
          </div>
        </div>

        <!-- Sección: Datos del Acta -->
        <div class="section-title"><i class="bi bi-calendar-event me-2"></i>Datos del Acta de Grado</div>
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label required">Día</label>
            <input type="number" class="form-control" id="dia_acta" min="1" max="31" placeholder="1-31"/>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Mes</label>
            <select class="form-select" id="mes_acta">
              <option value="">– Mes –</option>
              <?php
              $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
              foreach ($meses as $i => $m) {
                  echo "<option value=\"".($i+1)."\">{$m}</option>\n";
              }
              ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Año</label>
            <input type="number" class="form-control" id="anio_acta" min="2000" max="2099" placeholder="Ej: 2025"/>
          </div>
        </div>

        <!-- Sección: Datos del Diploma -->
        <div class="section-title"><i class="bi bi-award me-2"></i>Datos del Diploma y Registro</div>
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label required">Libro Nº</label>
            <input type="text" class="form-control" id="libro" placeholder="Ej: 18"/>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Folio</label>
            <input type="text" class="form-control" id="folio" placeholder="Ej: 228"/>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Número de Diploma</label>
            <input type="text" class="form-control" id="numero_diploma" placeholder="Ej: 2028"/>
          </div>
        </div>

        <!-- Logo uploader -->
        <div class="section-title"><i class="bi bi-image me-2"></i>Escudo / Logo Institucional</div>
        <div class="logo-upload-area" id="logoUploadArea">
          <input type="file" id="logoFile" accept="image/png,image/jpeg,image/svg+xml" style="display:none"/>
          <div class="logo-preview" id="logoPreview">
            <i class="bi bi-shield-check text-muted" style="font-size:2rem"></i>
            <p class="mb-0 text-muted small">Haz clic o arrastra el logo/escudo institucional<br><small>PNG, JPG o SVG (opcional, se guarda en el servidor)</small></p>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="document.getElementById('logoFile').click()">
            <i class="bi bi-upload me-1"></i>Subir Logo
          </button>
        </div>
      </div>

      <div class="modal-footer justify-content-between">
        <div id="modalDocBtns" class="d-none gap-2 flex-wrap">
          <span class="text-muted small align-self-center"><i class="bi bi-file-earmark-pdf me-1"></i>Generar:</span>
          <button type="button" class="btn btn-pdf btn-sm" onclick="generarPdf('diploma')">
            <i class="bi bi-award me-1"></i>Diploma
          </button>
          <button type="button" class="btn btn-pdf btn-sm" onclick="generarPdf('acta_individual')">
            <i class="bi bi-file-text me-1"></i>Acta Individual
          </button>
          <button type="button" class="btn btn-pdf btn-sm" onclick="generarPdf('mencion')">
            <i class="bi bi-star me-1"></i>Mención de Honor
          </button>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnGuardar">
            <i class="bi bi-save me-1"></i>Guardar
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const BASE_URL = '<?= RUNTIME_BASE_URL ?>';
</script>
<script src="<?= RUNTIME_BASE_URL ?>/js/app.js"></script>
</body>
</html>
