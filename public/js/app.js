/* ============================================================
   INETAM – app.js
   Axios + DataTables + SweetAlert2
   ============================================================ */

'use strict';

// ── Estado global ─────────────────────────────────────────────
let dt = null;
let currentEstudianteId = null;
let estudiantesData = [];

// ── Axios base config ─────────────────────────────────────────
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

// ── Utilidades ────────────────────────────────────────────────
const API = (path) => `${BASE_URL}/api${path}`;
const PDF = (id, tipo) => `${BASE_URL}/pdf/${id}/${tipo}`;

function tituloBadge(titulo) {
  if (titulo.includes('TIC'))       return `<span class="badge-titulo titulo-tic">${titulo}</span>`;
  if (titulo.includes('Técnico'))   return `<span class="badge-titulo titulo-tecnico">${titulo}</span>`;
  return `<span class="badge-titulo titulo-academico">${titulo}</span>`;
}

function botonesDocumentos(id) {
  return `
    <div class="btn-group-docs d-flex flex-wrap gap-1 justify-content-center">
      <a href="${PDF(id,'diploma')}" target="_blank" class="btn btn-pdf btn-sm" title="Ver Diploma">
        <i class="bi bi-award"></i> Diploma
      </a>
      <a href="${PDF(id,'acta_individual')}" target="_blank" class="btn btn-pdf btn-sm" title="Acta Individual">
        <i class="bi bi-file-text"></i> Acta
      </a>
      <a href="${PDF(id,'mencion')}" target="_blank" class="btn btn-pdf btn-sm" title="Mención de Honor">
        <i class="bi bi-star"></i> Mención
      </a>
    </div>`;
}

function botonesAcciones(id) {
  return `
    <div class="d-flex gap-1 justify-content-center">
      <button class="btn btn-outline-primary btn-sm btn-edit" data-id="${id}" title="Editar">
        <i class="bi bi-pencil"></i>
      </button>
      <button class="btn btn-outline-danger btn-sm btn-delete" data-id="${id}" title="Eliminar">
        <i class="bi bi-trash"></i>
      </button>
    </div>`;
}

// ── Carga inicial con Loading ─────────────────────────────────
function mostrarLoading() {
  let overlay = document.getElementById('loadingOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = `
      <div class="load-logo">🏫</div>
      <h5 class="mt-3 text-primary fw-bold">INETAM</h5>
      <p class="text-muted small mb-3">Cargando estudiantes registrados…</p>
      <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>`;
    document.body.appendChild(overlay);
  }
  overlay.style.display = 'flex';
}

function ocultarLoading() {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) {
    overlay.style.opacity = '0';
    setTimeout(() => overlay.remove(), 300);
  }
  document.getElementById('tableLoadingIndicator').style.display = 'none';
}

// ── DataTable init ────────────────────────────────────────────
function initDataTable(data) {
  estudiantesData = data;

  // Actualizar stats
  document.getElementById('statTotal').textContent = data.length;
  document.getElementById('statAcademico').textContent = data.filter(e => e.titulo === 'Bachiller Académico').length;
  document.getElementById('statTecnico').textContent   = data.filter(e => e.titulo === 'Bachiller Técnico').length;
  document.getElementById('statTic').textContent       = data.filter(e => e.titulo === 'Bachiller Técnico en TIC').length;

  if (dt) { dt.destroy(); }

  dt = $('#tablaEstudiantes').DataTable({
    data: data,
    destroy: true,
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
    },
    pageLength: 15,
    lengthMenu: [10, 15, 25, 50, 100],
    columns: [
      { data: null, render: (_, __, ___, meta) => meta.row + 1, orderable: false },
      { data: 'nombre_completo' },
      { data: 'numero_documento' },
      { data: 'titulo', render: t => tituloBadge(t) },
      { data: 'especialidad' },
      { data: 'anio_acta' },
      { data: null, render: e => `${e.libro} / ${e.folio} / ${e.numero_diploma}` },
      { data: null, render: e => botonesDocumentos(e.id), orderable: false },
      { data: null, render: e => botonesAcciones(e.id), orderable: false },
    ],
    order: [[1, 'asc']],
    responsive: true,
  });
}

// ── Cargar datos ──────────────────────────────────────────────
async function cargarEstudiantes() {
  mostrarLoading();
  try {
    const res = await axios.get(API('/estudiantes'));
    if (res.data.success) {
      initDataTable(res.data.data);
    } else {
      Swal.fire('Error', 'No se pudieron cargar los datos.', 'error');
    }
  } catch (err) {
    Swal.fire('Error de conexión', err.message, 'error');
  } finally {
    ocultarLoading();
  }
}

// ── Modal: Abrir en modo Nuevo ────────────────────────────────
function abrirModalNuevo() {
  currentEstudianteId = null;
  limpiarFormulario();
  document.getElementById('modalLabel').innerHTML = '<i class="bi bi-person-plus me-2"></i>Nuevo Estudiante';
  document.getElementById('modalDocBtns').classList.add('d-none');
  const modal = new bootstrap.Modal(document.getElementById('modalEstudiante'));
  modal.show();
}

// ── Modal: Abrir en modo Editar ───────────────────────────────
async function abrirModalEditar(id) {
  currentEstudianteId = id;
  try {
    const res = await axios.get(API(`/estudiantes/${id}`));
    if (!res.data.success) throw new Error('No encontrado');
    const e = res.data.data;
    document.getElementById('estudianteId').value   = e.id;
    document.getElementById('primer_nombre').value  = e.primer_nombre;
    document.getElementById('segundo_nombre').value = e.segundo_nombre || '';
    document.getElementById('primer_apellido').value  = e.primer_apellido;
    document.getElementById('segundo_apellido').value = e.segundo_apellido || '';
    document.getElementById('numero_documento').value = e.numero_documento;
    document.getElementById('titulo').value       = e.titulo;
    document.getElementById('especialidad').value = e.especialidad;
    document.getElementById('dia_acta').value    = e.dia_acta;
    document.getElementById('mes_acta').value    = e.mes_acta;
    document.getElementById('anio_acta').value   = e.anio_acta;
    document.getElementById('libro').value         = e.libro;
    document.getElementById('folio').value         = e.folio;
    document.getElementById('numero_diploma').value = e.numero_diploma;
    document.getElementById('modalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Editar Estudiante';
    document.getElementById('modalDocBtns').classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('modalEstudiante')).show();
  } catch (err) {
    Swal.fire('Error', 'No se pudo cargar el estudiante.', 'error');
  }
}

// ── Limpiar formulario ────────────────────────────────────────
function limpiarFormulario() {
  ['estudianteId','primer_nombre','segundo_nombre','primer_apellido','segundo_apellido',
   'numero_documento','dia_acta','anio_acta','libro','folio','numero_diploma'
  ].forEach(id => document.getElementById(id).value = '');
  document.getElementById('titulo').value       = '';
  document.getElementById('especialidad').value = 'Sin Especialidad';
  document.getElementById('mes_acta').value     = '';
  document.getElementById('logoPreview').innerHTML = `
    <i class="bi bi-shield-check text-muted" style="font-size:2rem"></i>
    <p class="mb-0 text-muted small">Haz clic o arrastra el logo/escudo institucional<br><small>PNG, JPG o SVG (opcional)</small></p>`;
}

// ── Recopilar datos del formulario ────────────────────────────
function getDatosFormulario() {
  return {
    primer_nombre:    document.getElementById('primer_nombre').value.trim(),
    segundo_nombre:   document.getElementById('segundo_nombre').value.trim() || null,
    primer_apellido:  document.getElementById('primer_apellido').value.trim(),
    segundo_apellido: document.getElementById('segundo_apellido').value.trim() || null,
    numero_documento: document.getElementById('numero_documento').value.trim(),
    titulo:           document.getElementById('titulo').value,
    especialidad:     document.getElementById('especialidad').value,
    dia_acta:         document.getElementById('dia_acta').value,
    mes_acta:         document.getElementById('mes_acta').value,
    anio_acta:        document.getElementById('anio_acta').value,
    libro:            document.getElementById('libro').value.trim(),
    folio:            document.getElementById('folio').value.trim(),
    numero_diploma:   document.getElementById('numero_diploma').value.trim(),
  };
}

// ── Guardar (Crear o Actualizar) ──────────────────────────────
async function guardarEstudiante() {
  const data = getDatosFormulario();
  const btnGuardar = document.getElementById('btnGuardar');
  btnGuardar.disabled = true;
  btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

  try {
    let res;
    if (currentEstudianteId) {
      res = await axios.put(API(`/estudiantes/${currentEstudianteId}`), data);
    } else {
      res = await axios.post(API('/estudiantes'), data);
      // Si hay logo pendiente, subirlo
      await subirLogoSiHay(res.data.data?.id);
    }

    if (res.data.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalEstudiante')).hide();
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: res.data.message,
        timer: 1800,
        showConfirmButton: false
      });
      cargarEstudiantes();
    } else {
      const msgs = res.data.errors?.join('\n') || res.data.message;
      Swal.fire('Validación', msgs, 'warning');
    }
  } catch (err) {
    const msgs = err.response?.data?.errors?.join('\n') || err.response?.data?.message || err.message;
    Swal.fire('Error', msgs, 'error');
  } finally {
    btnGuardar.disabled = false;
    btnGuardar.innerHTML = '<i class="bi bi-save me-1"></i>Guardar';
  }
}

// ── Eliminar ──────────────────────────────────────────────────
async function eliminarEstudiante(id) {
  const e = estudiantesData.find(x => x.id == id);
  const nombre = e ? e.nombre_completo : 'este estudiante';
  const { isConfirmed } = await Swal.fire({
    title: '¿Eliminar estudiante?',
    html: `Se eliminará el registro de <strong>${nombre}</strong>.<br>Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonText: 'Cancelar',
    confirmButtonText: 'Sí, eliminar',
  });
  if (!isConfirmed) return;
  try {
    const res = await axios.delete(API(`/estudiantes/${id}`));
    if (res.data.success) {
      Swal.fire({ icon:'success', title:'Eliminado', text:res.data.message, timer:1500, showConfirmButton:false });
      cargarEstudiantes();
    }
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || err.message, 'error');
  }
}

// ── Generar PDF desde modal ───────────────────────────────────
function generarPdf(tipo) {
  if (!currentEstudianteId) return;
  window.open(PDF(currentEstudianteId, tipo), '_blank');
}

// ── Subir logo ────────────────────────────────────────────────
let pendingLogoFile = null;

async function subirLogoSiHay(id) {
  if (!pendingLogoFile || !id) return;
  const formData = new FormData();
  formData.append('logo', pendingLogoFile);
  try {
    await axios.post(API(`/upload-logo`), formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    pendingLogoFile = null;
  } catch (_) { /* silencioso */ }
}

// ── Acta General ──────────────────────────────────────────────
function generarActaGeneral() {
  window.open(`${BASE_URL}/pdf/acta_general`, '_blank');
}

// ── Event Listeners ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  cargarEstudiantes();

  document.getElementById('btnNuevoEstudiante').addEventListener('click', abrirModalNuevo);
  document.getElementById('btnGuardar').addEventListener('click', guardarEstudiante);
  document.getElementById('btnActaGeneral').addEventListener('click', generarActaGeneral);

  // Delegación de eventos en la tabla
  document.getElementById('tablaEstudiantes').addEventListener('click', (e) => {
    const editBtn   = e.target.closest('.btn-edit');
    const deleteBtn = e.target.closest('.btn-delete');
    if (editBtn)   abrirModalEditar(+editBtn.dataset.id);
    if (deleteBtn) eliminarEstudiante(+deleteBtn.dataset.id);
  });

  // Logo preview
  document.getElementById('logoFile').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    pendingLogoFile = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
      document.getElementById('logoPreview').innerHTML =
        `<img src="${ev.target.result}" alt="Logo" style="max-height:80px;max-width:160px;border-radius:.5rem"/>`;
    };
    reader.readAsDataURL(file);
  });

  // Drag & drop logo
  const area = document.getElementById('logoUploadArea');
  area.addEventListener('dragover', e => { e.preventDefault(); area.style.borderColor='var(--primary)'; });
  area.addEventListener('dragleave', () => { area.style.borderColor='var(--border)'; });
  area.addEventListener('drop', e => {
    e.preventDefault();
    area.style.borderColor='var(--border)';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      document.getElementById('logoFile').files = e.dataTransfer.files;
      document.getElementById('logoFile').dispatchEvent(new Event('change'));
    }
  });
});
