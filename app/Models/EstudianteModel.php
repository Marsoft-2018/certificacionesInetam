<?php
// app/Models/EstudianteModel.php  –  CRUD de estudiantes
namespace App\Models;

use App\Core\Database;

class EstudianteModel
{
    private Database $db;

    private array $meses = [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',
        5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',
        9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Helpers ───────────────────────────────────────────────
    public function nombreMes(int $mes): string
    {
        return $this->meses[$mes] ?? '';
    }

    public function nombreCompleto(array $e): string
    {
        $partes = [$e['primer_apellido']];
        if (!empty($e['segundo_apellido'])) $partes[] = $e['segundo_apellido'];
        $partes[] = $e['primer_nombre'];
        if (!empty($e['segundo_nombre'])) $partes[] = $e['segundo_nombre'];
        return implode(' ', $partes);
    }

    // ── READ ──────────────────────────────────────────────────
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM estudiantes ORDER BY primer_apellido, primer_nombre'
        )->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM estudiantes WHERE id = ?', [$id]
        )->fetch();
        return $row ?: null;
    }

    public function getByDocumento(string $doc): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM estudiantes WHERE numero_documento = ?', [$doc]
        )->fetch();
        return $row ?: null;
    }

    // ── CREATE ────────────────────────────────────────────────
    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO estudiantes
               (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                numero_documento, titulo, especialidad,
                dia_acta, mes_acta, anio_acta,
                libro, folio, numero_diploma)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['primer_nombre'],
                $data['segundo_nombre']  ?? null,
                $data['primer_apellido'],
                $data['segundo_apellido'] ?? null,
                $data['numero_documento'],
                $data['titulo'],
                $data['especialidad'],
                (int)$data['dia_acta'],
                (int)$data['mes_acta'],
                (int)$data['anio_acta'],
                $data['libro'],
                $data['folio'],
                $data['numero_diploma'],
            ]
        );
        return (int) Database::getInstance()->getPdo()->lastInsertId();
    }

    // ── UPDATE ────────────────────────────────────────────────
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->query(
            'UPDATE estudiantes SET
               primer_nombre    = ?,
               segundo_nombre   = ?,
               primer_apellido  = ?,
               segundo_apellido = ?,
               numero_documento = ?,
               titulo           = ?,
               especialidad     = ?,
               dia_acta         = ?,
               mes_acta         = ?,
               anio_acta        = ?,
               libro            = ?,
               folio            = ?,
               numero_diploma   = ?
             WHERE id = ?',
            [
                $data['primer_nombre'],
                $data['segundo_nombre']   ?? null,
                $data['primer_apellido'],
                $data['segundo_apellido'] ?? null,
                $data['numero_documento'],
                $data['titulo'],
                $data['especialidad'],
                (int)$data['dia_acta'],
                (int)$data['mes_acta'],
                (int)$data['anio_acta'],
                $data['libro'],
                $data['folio'],
                $data['numero_diploma'],
                $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ────────────────────────────────────────────────
    public function delete(int $id): bool
    {
        $stmt = $this->db->query(
            'DELETE FROM estudiantes WHERE id = ?', [$id]
        );
        return $stmt->rowCount() > 0;
    }

    // ── Configuración institucional ───────────────────────────
    public function getConfig(): array
    {
        $rows = $this->db->query('SELECT clave, valor FROM configuracion')->fetchAll();
        $cfg = [];
        foreach ($rows as $r) $cfg[$r['clave']] = $r['valor'];
        return $cfg;
    }

    // ── Validación ────────────────────────────────────────────
    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty(trim($data['primer_nombre'] ?? '')))
            $errors[] = 'El primer nombre es obligatorio.';
        if (empty(trim($data['primer_apellido'] ?? '')))
            $errors[] = 'El primer apellido es obligatorio.';
        if (empty(trim($data['numero_documento'] ?? '')))
            $errors[] = 'El número de documento es obligatorio.';

        // Unicidad de documento
        if (!empty($data['numero_documento'])) {
            $existing = $this->getByDocumento($data['numero_documento']);
            if ($existing && $existing['id'] != $excludeId) {
                $errors[] = 'Ya existe un estudiante con ese número de documento.';
            }
        }

        $titulos = ['Bachiller Académico','Bachiller Técnico','Bachiller Técnico en TIC'];
        if (!in_array($data['titulo'] ?? '', $titulos))
            $errors[] = 'Título no válido.';

        $especialidades = ['Sin Especialidad','Agropecuario','Énfasis Programación'];
        if (!in_array($data['especialidad'] ?? '', $especialidades))
            $errors[] = 'Especialidad no válida.';

        foreach (['dia_acta','mes_acta','anio_acta','libro','folio','numero_diploma'] as $campo) {
            if (empty(trim((string)($data[$campo] ?? ''))))
                $errors[] = "El campo $campo es obligatorio.";
        }

        return $errors;
    }
}
