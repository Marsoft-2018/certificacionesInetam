<?php
// app/Controllers/EstudianteController.php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\EstudianteModel;

class EstudianteController extends Controller
{
    private EstudianteModel $model;

    public function __construct()
    {
        $this->model = new EstudianteModel();
    }

    // ── Vista principal SPA ───────────────────────────────────
    public function index(array $params = []): void
    {
        $this->view('layouts.main');
    }

    // ── Ruta de diagnóstico ───────────────────────────────────
    public function debug(array $params = []): void
    {
        $this->json([
            'SCRIPT_NAME'  => $_SERVER['SCRIPT_NAME']  ?? null,
            'REQUEST_URI'  => $_SERVER['REQUEST_URI']  ?? null,
            'PHP_SELF'     => $_SERVER['PHP_SELF']     ?? null,
            'BASE_URL'     => BASE_URL,
            'BASE_PATH'    => BASE_PATH,
            'vendor_ok'    => file_exists(VENDOR_PATH . '/autoload.php'),
        ]);
    }

    // ── API: Listar todos ─────────────────────────────────────
    public function apiList(array $params = []): void
    {
        $estudiantes = $this->model->getAll();
        foreach ($estudiantes as &$e) {
            $e['nombre_completo'] = $this->model->nombreCompleto($e);
            $e['mes_nombre']      = $this->model->nombreMes((int)$e['mes_acta']);
        }
        $this->json(['success' => true, 'data' => $estudiantes]);
    }

    // ── API: Obtener uno ──────────────────────────────────────
    public function apiGet(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $estudiante = $this->model->getById($id);
        if (!$estudiante) {
            $this->json(['success' => false, 'message' => 'Estudiante no encontrado'], 404);
        }
        $this->json(['success' => true, 'data' => $estudiante]);
    }

    // ── API: Crear ────────────────────────────────────────────
    public function apiCreate(array $params = []): void
    {
        $data = $this->getJsonInput();
        $errors = $this->model->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }
        try {
            $id = $this->model->create($data);
            $estudiante = $this->model->getById($id);
            $estudiante['nombre_completo'] = $this->model->nombreCompleto($estudiante);
            $this->json(['success' => true, 'message' => 'Estudiante creado exitosamente.', 'data' => $estudiante], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al crear: ' . $e->getMessage()], 500);
        }
    }

    // ── API: Actualizar ───────────────────────────────────────
    public function apiUpdate(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!$this->model->getById($id)) {
            $this->json(['success' => false, 'message' => 'Estudiante no encontrado'], 404);
        }
        $data = $this->getJsonInput();
        $errors = $this->model->validate($data, $id);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }
        try {
            $this->model->update($id, $data);
            $estudiante = $this->model->getById($id);
            $estudiante['nombre_completo'] = $this->model->nombreCompleto($estudiante);
            $this->json(['success' => true, 'message' => 'Estudiante actualizado.', 'data' => $estudiante]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    // ── API: Eliminar ─────────────────────────────────────────
    public function apiDelete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!$this->model->getById($id)) {
            $this->json(['success' => false, 'message' => 'Estudiante no encontrado'], 404);
        }
        try {
            $this->model->delete($id);
            $this->json(['success' => true, 'message' => 'Estudiante eliminado.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    // ── Helper: leer JSON o form-data ─────────────────────────
    private function getJsonInput(): array
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if ($data === null) {
            $data = $_POST;
        }
        return $data ?? [];
    }
}
