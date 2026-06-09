<?php
// app/Controllers/PdfController.php  –  Generación de PDFs con DOMPDF
namespace App\Controllers;

use App\Core\Controller;
use App\Models\EstudianteModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController extends Controller
{
    private EstudianteModel $model;

    public function __construct()
    {
        $this->model = new EstudianteModel();
    }

    // ── Dispatcher ────────────────────────────────────────────
    public function generar(array $params = []): void
    {
        $id   = (int)($params['id']   ?? 0);
        $tipo = strtolower($params['tipo'] ?? '');

        $estudiante = $this->model->getById($id);
        if (!$estudiante) {
            http_response_code(404);
            exit('Estudiante no encontrado');
        }

        $config = $this->model->getConfig();

        switch ($tipo) {
            case 'diploma':        $this->diploma($estudiante, $config);     break;
            case 'acta_individual':$this->actaIndividual($estudiante,$config);break;
            case 'acta_general':   $this->actaGeneral($config);              break;
            case 'mencion':        $this->mencion($estudiante, $config);     break;
            default:
                http_response_code(400);
                exit('Tipo de documento no válido');
        }
    }

    // ── PDF Helper ────────────────────────────────────────────
    private function renderPdf(string $html, string $filename, string $orient = 'portrait'): void
    {
        // Ruta absoluta real del proyecto
        $rootPath = realpath(BASE_PATH);

        // ── Directorio de caché de fuentes ────────────────────
        // Usar storage/ dentro del proyecto (permisos controlados)
        $storageDir = $rootPath . '/storage/fonts';
        if (!is_dir($storageDir)) {
            // Crear con @ para suprimir warning si falla,
            // en ese caso caemos al fontDir de vendor
            @mkdir($storageDir, 0755, true);
        }

        // Buscar el fontDir real de DOMPDF en vendor
        $vendorFontDirs = [
            $rootPath . '/vendor/dompdf/dompdf/lib/fonts',
            $rootPath . '/vendor/dompdf/dompdf/resources/fonts',
        ];
        $vendorFontDir = '';
        foreach ($vendorFontDirs as $dir) {
            if (is_dir($dir)) {
                $vendorFontDir = $dir;
                break;
            }
        }

        // fontDir = donde están los TTF (vendor)
        // fontCache = donde DOMPDF escribe su caché (storage, con permisos de escritura)
        $fontDir   = $vendorFontDir ?: (is_dir($storageDir) ? $storageDir : '');
        $fontCache = is_dir($storageDir) ? $storageDir : $vendorFontDir;

        // ── Opciones DOMPDF ───────────────────────────────────
        $options = new Options();
        $options->set('defaultFont',             'Courier');   // Fuente 100% nativa PDF (nunca falla)
        $options->set('isRemoteEnabled',         false);
        $options->set('isHtml5ParserEnabled',    true);
        $options->set('isFontSubsettingEnabled', false);       // Evita BinaryStream error
        $options->set('chroot',                  $rootPath);
        if ($fontDir)   $options->set('fontDir',   $fontDir);
        if ($fontCache) $options->set('fontCache', $fontCache);

        // ── Renderizar ────────────────────────────────────────
        // Suprimir display_errors durante el render para que ningún
        // warning de PHP contamine el output binario del PDF
        $prevDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', $orient);
        $dompdf->render();
        $output = $dompdf->output();

        ini_set('display_errors', $prevDisplayErrors);

        // ── Limpiar buffers DESPUÉS de renderizar ─────────────
        // (antes no, porque el warning de mkdir ya sería output)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // ── Enviar headers y PDF ──────────────────────────────
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: '  . strlen($output));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $output;
        exit;
    }

    private function escudoBase64(): string
    {
        // 1. Buscar PNG subido por el usuario
        $png = ASSETS_PATH . '/img/escudo.png';
        if (file_exists($png) && filesize($png) > 0) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($png));
        }
        // 2. Buscar JPG
        $jpg = ASSETS_PATH . '/img/escudo.jpg';
        if (file_exists($jpg) && filesize($jpg) > 0) {
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($jpg));
        }
        // 3. Fallback: SVG embebido hardcodeado (no depende de archivos externos)
        // DOMPDF maneja mejor SVG inlineados como data URI
        $svgFile = ASSETS_PATH . '/img/escudo_placeholder.svg';
        if (file_exists($svgFile) && filesize($svgFile) > 0) {
            return 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($svgFile));
        }
        // 4. SVG mínimo hardcodeado (último recurso — nunca falla)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" width="100" height="120">'
             . '<rect x="5" y="5" width="90" height="110" rx="8" fill="#1e5c0e" stroke="#c9a227" stroke-width="3"/>'
             . '<text x="50" y="45" text-anchor="middle" fill="#c9a227" font-size="28">🏫</text>'
             . '<text x="50" y="70" text-anchor="middle" fill="#fff" font-size="8" font-family="sans-serif">INETAM</text>'
             . '<text x="50" y="85" text-anchor="middle" fill="#c9a227" font-size="6" font-family="sans-serif">SAN MARTÍN</text>'
             . '<text x="50" y="98" text-anchor="middle" fill="#c9a227" font-size="6" font-family="sans-serif">DE LOBA</text>'
             . '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function nombreCompleto(array $e): string
    {
        return $this->model->nombreCompleto($e);
    }

    private function tituloCompleto(array $e): string
    {
        $titulo = $e['titulo'];
        if ($e['especialidad'] !== 'Sin Especialidad') {
            $titulo .= ' ' . $e['especialidad'];
        }
        return $titulo;
    }

    private function fechaTexto(array $e): string
    {
        $dias = [
            1=>'uno',2=>'dos',3=>'tres',4=>'cuatro',5=>'cinco',6=>'seis',7=>'siete',
            8=>'ocho',9=>'nueve',10=>'diez',11=>'once',12=>'doce',13=>'trece',
            14=>'catorce',15=>'quince',16=>'dieciséis',17=>'diecisiete',18=>'dieciocho',
            19=>'diecinueve',20=>'veinte',21=>'veintiuno',22=>'veintidós',23=>'veintitrés',
            24=>'veinticuatro',25=>'veinticinco',26=>'veintiséis',27=>'veintisiete',
            28=>'veintiocho',29=>'veintinueve',30=>'treinta',31=>'treinta y uno'
        ];
        $anios = [
            2023=>'dos mil veintitrés',2024=>'dos mil veinticuatro',
            2025=>'dos mil veinticinco',2026=>'dos mil veintiséis',2027=>'dos mil veintisiete'
        ];
        $meses = [
            1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
            7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
        ];
        $diaNum  = (int)$e['dia_acta'];
        $mesNum  = (int)$e['mes_acta'];
        $anioNum = (int)$e['anio_acta'];
        $diaT  = $dias[$diaNum]  ?? $diaNum;
        $mesT  = $meses[$mesNum] ?? $mesNum;
        $anioT = $anios[$anioNum] ?? $anioNum;
        return "({$diaNum}) días del mes de {$mesT} de {$anioT} ({$anioNum})";
    }

    // ─────────────────────────────────────────────────────────
    // 1. DIPLOMA
    // ─────────────────────────────────────────────────────────
    private function diploma(array $e, array $cfg): void
    {
        $nombre  = $this->nombreCompleto($e);
        $titulo  = $this->tituloCompleto($e);
        $escudo  = $this->escudoBase64();
        $fecha   = "({$e['dia_acta']}) de {$this->model->nombreMes((int)$e['mes_acta'])} de {$e['anio_acta']}";
        $muni    = $cfg['municipio'] ?? 'San Martín de Loba, Bolívar';

        $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<style>
  /* Fuentes del sistema compatibles con DOMPDF */
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    width:215.9mm; height:279.4mm;
    background: #f9f7f0;
    font-family: 'Times New Roman', serif;
    position: relative;
    overflow: hidden;
  }
  .border-outer {
    position:absolute; top:6mm; left:6mm; right:6mm; bottom:6mm;
    border: 4px solid #2d6a1f;
    border-radius: 3mm;
  }
  .border-inner {
    position:absolute; top:9mm; left:9mm; right:9mm; bottom:9mm;
    border: 1.5px solid #2d6a1f;
    border-radius: 2mm;
  }
  .corner-ornament {
    position:absolute; width:18mm; height:18mm;
    font-size:28px; color:#2d6a1f; line-height:1;
  }
  .corner-tl { top:4mm; left:4mm; }
  .corner-tr { top:4mm; right:4mm; transform:scaleX(-1); }
  .corner-bl { bottom:4mm; left:4mm; transform:scaleY(-1); }
  .corner-br { bottom:4mm; right:4mm; transform:scale(-1); }
  .flag-stripe {
    position:absolute; right:0; top:0; bottom:0; width:8mm;
    display:flex; flex-direction:column;
  }
  .stripe-y { flex:2; background:#f4c400; }
  .stripe-b { flex:1; background:#003087; }
  .stripe-r { flex:1; background:#ce1126; }
  .content {
    position:absolute; top:14mm; left:14mm; right:20mm; bottom:14mm;
    display:flex; flex-direction:column; align-items:center;
    text-align:center;
  }
  .escudo { width:28mm; margin-bottom:2mm; }
  .y-en-su { font-size:10pt; color:#333; margin-bottom:3mm; }
  .inst-name {
    font-size:13pt; font-style:italic; color:#1a1a1a;
    font-weight:bold; line-height:1.4; margin-bottom:2mm;
  }
  .resolucion { font-size:7.5pt; font-style:italic; color:#444; margin-bottom:6mm; line-height:1.5; }
  .confiere { font-size:15pt; font-style:italic; color:#1a1a1a; margin-bottom:3mm; }
  .nombre-estudiante {
    font-size:22pt; font-family:'Times New Roman',serif;
    color:#1a1a1a; margin-bottom:2mm; border-bottom:1px solid #2d6a1f;
    padding-bottom:2mm; width:100%;
  }
  .documento { font-size:11pt; color:#333; margin-bottom:5mm; }
  .titulo-label { font-size:14pt; font-weight:bold; font-style:italic; margin-bottom:1mm; }
  .titulo-valor { font-size:16pt; font-weight:bold; color:#1a1a1a; margin-bottom:4mm; }
  .por-haber { font-size:9.5pt; font-style:italic; color:#444; margin-bottom:5mm; line-height:1.5; }
  .registro { font-size:9pt; font-style:italic; color:#444; margin-bottom:2mm; }
  .fecha { font-size:9pt; font-style:italic; color:#444; margin-bottom:6mm; }
  .sello-area { display:flex; justify-content:flex-end; width:100%; }
  .sello { width:22mm; height:22mm; }
  .bg-pattern {
    position:absolute; top:0; left:0; right:0; bottom:0;
    opacity:0.04; font-size:80px; display:flex;
    align-items:center; justify-content:center; color:#2d6a1f;
    font-family:'Times New Roman',serif;
  }
</style></head><body>
  <div class="bg-pattern">✦</div>
  <div class="border-outer"></div>
  <div class="border-inner"></div>
  <div class="corner-ornament corner-tl">❧</div>
  <div class="corner-ornament corner-tr">❧</div>
  <div class="corner-ornament corner-bl">❧</div>
  <div class="corner-ornament corner-br">❧</div>
  <div class="flag-stripe">
    <div class="stripe-y"></div>
    <div class="stripe-b"></div>
    <div class="stripe-r"></div>
  </div>
  <div class="content">
    <img src="{$escudo}" class="escudo" alt="Escudo"/>
    <div class="y-en-su">y en su nombre</div>
    <div class="inst-name">La Institución Educativa Técnica Agropecuaria<br>y Minera de San Martín de Loba, Bolívar</div>
    <div class="resolucion">Aprobada por Resolución 341 del 4 de diciembre de 2003<br>Emanada de la Secretaría de Educación Departamental de Bolívar.</div>
    <div class="confiere">Confiere a:</div>
    <div class="nombre-estudiante">{$nombre}</div>
    <div class="documento">D.I {$e['numero_documento']}</div>
    <div class="titulo-label">Título:</div>
    <div class="titulo-valor">{$titulo}</div>
    <div class="por-haber">Por haber cursado los estudios correspondientes al nivel de<br>Educación Media, según los planes y programas vigentes.</div>
    <div class="registro">Registrado en el Libro Nº. {$e['libro']}, folio {$e['folio']}, Diploma Nº. {$e['numero_diploma']}</div>
    <div class="fecha">{$muni}, ({$e['dia_acta']}) de {$this->model->nombreMes((int)$e['mes_acta'])} de {$e['anio_acta']}</div>
    <div class="sello-area">
      <img src="{$escudo}" class="sello" alt="Sello"/>
    </div>
  </div>
</body></html>
HTML;
        $slug = preg_replace('/\s+/','-', strtolower($nombre));
        $this->renderPdf($html, "diploma_{$slug}.pdf", 'portrait');
    }

    // ─────────────────────────────────────────────────────────
    // 2. ACTA INDIVIDUAL
    // ─────────────────────────────────────────────────────────
    private function actaIndividual(array $e, array $cfg): void
    {
        $nombre   = $this->nombreCompleto($e);
        $titulo   = $this->tituloCompleto($e);
        $escudo   = $this->escudoBase64();
        $fecha    = $this->fechaTexto($e);
        $numActa  = $cfg['num_acta_general'] ?? '44';
        $rector   = $cfg['rector'] ?? '';
        $secretaria = $cfg['secretaria'] ?? '';
        $ccRector   = $cfg['cc_rector'] ?? '';
        $ccSecr     = $cfg['cc_secretaria'] ?? '';
        $muni       = $cfg['municipio'] ?? 'San Martín de Loba, Bolívar';

        $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<style>
  body { font-family:'Times New Roman',serif; font-size:11pt; color:#111;
         margin:0; padding: 20mm 25mm 20mm 25mm; }
  .header { text-align:center; margin-bottom:8mm; }
  .header img { width:22mm; }
  .header h3 { font-size:11pt; font-weight:bold; text-transform:uppercase; margin:2mm 0 1mm; }
  .header p { font-size:8.5pt; margin:1mm 0; }
  .titulo-acta { text-align:center; font-size:13pt; font-weight:bold;
                 text-transform:uppercase; margin:6mm 0 4mm; letter-spacing:1px; }
  .body-text { text-align:justify; line-height:1.8; margin-bottom:4mm; }
  .nombre-est { font-weight:bold; font-size:13pt; display:block; text-align:center; margin:3mm 0; }
  .doc-est { display:block; text-align:center; margin-bottom:2mm; }
  .titulo-est { font-weight:bold; font-size:12pt; display:block; text-align:center; margin:2mm 0; }
  .registro-est { font-weight:bold; display:block; text-align:center; margin:2mm 0; }
  .fiel-copia { margin:4mm 0; font-style:italic; }
  .firmas { display:flex; justify-content:space-between; margin-top:16mm; }
  .firma-col { width:45%; text-align:center; }
  .firma-nombre { font-weight:bold; font-size:10pt; display:block; margin-bottom:1mm; }
  .firma-cc { font-size:8.5pt; }
  .firma-cargo { font-size:9pt; font-style:italic; }
  .fecha-pie { text-align:center; margin-top:4mm; font-style:italic; font-size:9.5pt; }
</style></head><body>
<div class="header">
  <img src="{$escudo}" alt="Escudo"/><br>
  <strong>REPÚBLICA DE COLOMBIA</strong><br>
  <p>Ministerio de Educación Nacional</p>
  <h3>Institución Educativa Técnica Agropecuaria y Minera<br>de San Martín de Loba, Bolívar (INETAM)</h3>
  <p>Resolución de aprobación Nº. 341 del 4 de diciembre de 2003</p>
  <p>NIT: 806.012.943-6 &nbsp;|&nbsp; DANE: 113667000016 &nbsp;|&nbsp; ICFES: JD: 043042 &nbsp;|&nbsp; JN: 104505</p>
</div>

<div class="titulo-acta">Acta Individual de Grado</div>

<div class="body-text">
  En {$muni}, a los {$fecha}, se reunieron los suscritos Rector y Secretaria de la Institución Educativa,
  con el fin de formalizar la graduación de los estudiantes del último grado de la educación media.
  Comprobada la situación legal y académica de cada uno de los estudiantes, se procedió otorgar a:
</div>

<span class="nombre-est">{$nombre}</span>
<span class="doc-est">DI. Nº. {$e['numero_documento']}</span>
<span class="titulo-est">TÍTULO DE {$titulo}</span>
<span class="registro-est">Libro de registro de Diplomas Nº. {$e['libro']}, Folio {$e['folio']}, Diploma Nº. {$e['numero_diploma']}</span>

<div class="body-text fiel-copia">
  Es fiel copia, tomada del ACTA GENERAL DE GRADO Nº: {$numActa}
</div>

<div class="firmas">
  <div class="firma-col">
    <span class="firma-nombre">{$secretaria}</span>
    <span class="firma-cc">C.C. {$ccSecr}</span><br>
    <span class="firma-cargo">Secretaria</span>
  </div>
  <div class="firma-col">
    <span class="firma-nombre">{$rector}</span>
    <span class="firma-cc">C.C. {$ccRector}</span><br>
    <span class="firma-cargo">Rector</span>
  </div>
</div>

<div class="fecha-pie">{$muni}, {$e['dia_acta']} de {$this->model->nombreMes((int)$e['mes_acta'])} de {$e['anio_acta']}</div>
</body></html>
HTML;
        $slug = preg_replace('/\s+/','-', strtolower($nombre));
        $this->renderPdf($html, "acta_individual_{$slug}.pdf");
    }

    // ─────────────────────────────────────────────────────────
    // 3. ACTA GENERAL
    // ─────────────────────────────────────────────────────────
    private function actaGeneral(array $cfg): void
    {
        $todos   = $this->model->getAll();
        $escudo  = $this->escudoBase64();
        $rector  = $cfg['rector'] ?? '';
        $secretaria = $cfg['secretaria'] ?? '';
        $ccRector   = $cfg['cc_rector'] ?? '';
        $ccSecr     = $cfg['cc_secretaria'] ?? '';
        $muni    = $cfg['municipio'] ?? 'San Martín de Loba, Bolívar';
        $numActa = $cfg['num_acta_general'] ?? '44';

        // Agrupar por año del acta (usar el del primer registro como referencia)
        $anio = !empty($todos) ? $todos[0]['anio_acta'] : date('Y');
        $mes  = !empty($todos) ? $this->model->nombreMes((int)$todos[0]['mes_acta']) : '';
        $dia  = !empty($todos) ? $todos[0]['dia_acta'] : date('d');

        $filas = '';
        foreach ($todos as $i => $e) {
            $nombre  = $this->model->nombreCompleto($e);
            $titulo  = $this->tituloCompleto($e);
            $filas .= "<tr>
                <td>".($i+1)."</td>
                <td>{$nombre}</td>
                <td>{$e['numero_documento']}</td>
                <td>{$titulo}</td>
                <td>{$e['especialidad']}</td>
                <td>{$e['libro']}</td>
                <td>{$e['folio']}</td>
                <td>{$e['numero_diploma']}</td>
              </tr>";
        }
        $total = count($todos);

        $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<style>
  body { font-family:Helvetica,Arial,sans-serif; font-size:9pt; color:#111;
         margin:0; padding:15mm 18mm 15mm 18mm; }
  .header { text-align:center; margin-bottom:5mm; }
  .header img { width:18mm; }
  .header h3 { font-size:10pt; font-weight:bold; text-transform:uppercase; margin:2mm 0 1mm; }
  .header p { font-size:7.5pt; margin:0.5mm 0; }
  .titulo-acta { text-align:center; font-size:12pt; font-weight:bold;
                 text-transform:uppercase; margin:5mm 0 3mm; }
  .intro { text-align:justify; line-height:1.6; margin-bottom:4mm; font-size:8.5pt; }
  table { width:100%; border-collapse:collapse; margin:4mm 0; font-size:7.5pt; }
  th { background:#1a5276; color:#fff; padding:3mm 2mm; text-align:center; border:0.5px solid #aaa; }
  td { padding:2mm; border:0.5px solid #ccc; text-align:center; }
  tr:nth-child(even) td { background:#f0f4f8; }
  .footer-text { margin-top:4mm; font-size:8.5pt; font-style:italic; text-align:justify; }
  .firmas { display:flex; justify-content:space-between; margin-top:14mm; }
  .firma-col { width:45%; text-align:center; }
  .firma-nombre { font-weight:bold; font-size:9pt; display:block; }
  .firma-cc { font-size:8pt; }
  .firma-cargo { font-size:8.5pt; font-style:italic; }
</style></head><body>
<div class="header">
  <img src="{$escudo}" alt="Escudo"/><br>
  <h3>Institución Educativa Técnica Agropecuaria y Minera<br>San Martín de Loba, Bolívar</h3>
  <p>Aprobada por Resolución Nº. 341 del 4/12/2003 &nbsp;|&nbsp; NIT: 806.012.943-6 &nbsp;|&nbsp; DANE: 113667000016 &nbsp;|&nbsp; ICFES: JD: 043042 &nbsp;|&nbsp; JN: 104505</p>
</div>
<div class="titulo-acta">Acta General de Graduación Nº. {$numActa}<br>({$dia} de {$mes} de {$anio})</div>
<div class="intro">
  En la cabecera municipal de {$muni}, a los {$dia} días del mes de {$mes} de {$anio},
  se reunieron con el fin de formalizar la graduación de los estudiantes del grado UNDÉCIMO (11)
  jornada Matinal y CLEI VI jornada Nocturna, los suscritos Rector y Secretaria de la Institución Educativa
  Técnica Agropecuaria y Minera de Sn Martín de Loba Bolívar, Institución aprobada por Resolución Nº. 341
  del 4 de diciembre de 2003, emanada de la Secretaría de Educación Departamental de Bolívar.
  <br><br>
  Comprobada la situación legal y académica de los estudiantes que cursaron y aprobaron los estudios
  correspondientes al nivel de Educación Media, se procedió a otorgar los respectivos títulos de
  <strong>BACHILLERES</strong> a los graduandos, cuyos nombres, apellidos y números de documento
  de identidad se relacionan a continuación:
</div>
<table>
  <thead>
    <tr>
      <th>Ord.</th><th>Apellidos y Nombres</th><th>Doc.</th><th>Título</th>
      <th>Especialidad</th><th>Libro</th><th>Folio</th><th>Nº Diplo.</th>
    </tr>
  </thead>
  <tbody>{$filas}</tbody>
</table>
<div class="footer-text">
  Esta Acta consta de <strong>{$total}</strong> graduando(s).
</div>
<div class="firmas">
  <div class="firma-col">
    <span class="firma-nombre">{$secretaria}</span>
    <span class="firma-cc">C.C. {$ccSecr}</span><br>
    <span class="firma-cargo">Secretaria</span>
  </div>
  <div class="firma-col">
    <span class="firma-nombre">{$rector}</span>
    <span class="firma-cc">C.C. {$ccRector}</span><br>
    <span class="firma-cargo">Rector</span>
  </div>
</div>
</body></html>
HTML;
        $this->renderPdf($html, "acta_general_{$anio}.pdf", 'landscape');
    }

    // ─────────────────────────────────────────────────────────
    // 4. MENCIÓN DE HONOR
    // ─────────────────────────────────────────────────────────
    private function mencion(array $e, array $cfg): void
    {
        $nombre  = $this->model->nombreCompleto($e);
        $escudo  = $this->escudoBase64();
        $muni    = $cfg['municipio'] ?? 'San Martín de Loba, Bolívar';
        $rector  = $cfg['rector'] ?? '';
        $coord   = 'Coordinador(a)';
        $dia     = $e['dia_acta'];
        $mes     = $this->model->nombreMes((int)$e['mes_acta']);
        $anio    = $e['anio_acta'];

        $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { width:215.9mm; height:279.4mm;
         background: linear-gradient(145deg, #fdfbf5 0%, #f5f0e8 100%);
         font-family:'Times New Roman',serif; position:relative; overflow:hidden; }
  .gold-border-outer {
    position:absolute; top:7mm; left:7mm; right:7mm; bottom:7mm;
    border:4px double #c9a227; border-radius:4mm;
  }
  .gold-border-inner {
    position:absolute; top:11mm; left:11mm; right:11mm; bottom:11mm;
    border:1px solid #c9a227; border-radius:2mm;
  }
  .ribbon-top {
    position:absolute; top:0; left:0; right:0; height:12mm;
    background:linear-gradient(to right,#1a3a5c,#2d6a1f,#1a3a5c);
  }
  .ribbon-bottom {
    position:absolute; bottom:0; left:0; right:0; height:12mm;
    background:linear-gradient(to right,#1a3a5c,#2d6a1f,#1a3a5c);
  }
  .content {
    position:absolute; top:18mm; left:18mm; right:18mm; bottom:18mm;
    display:flex; flex-direction:column; align-items:center; text-align:center;
  }
  .inst-header { font-size:10pt; font-weight:bold; text-transform:uppercase;
                 color:#1a3a5c; line-height:1.5; margin-bottom:2mm; }
  .sede { font-size:9pt; color:#555; margin-bottom:3mm; }
  .medallion { font-size:48pt; color:#c9a227; margin:3mm 0; line-height:1; }
  .mencion-title {
    font-size:26pt; font-weight:bold; color:#c9a227; letter-spacing:3px;
    text-transform:uppercase; border-bottom:2px solid #c9a227;
    border-top:2px solid #c9a227; padding:2mm 8mm; margin:3mm 0;
  }
  .reconoce { font-size:10pt; color:#333; margin:3mm 0; font-style:italic; }
  .se-distingue { font-size:10pt; color:#333; margin-bottom:2mm; }
  .nombre-est { font-size:20pt; color:#1a3a5c; font-weight:bold; margin:3mm 0; }
  .puesto { font-size:10pt; color:#444; margin:2mm 0; }
  .desempeño { font-size:9.5pt; color:#444; font-style:italic; margin:2mm 0; }
  .fecha-pie { font-size:9pt; color:#555; margin-top:5mm; }
  .firmas { display:flex; justify-content:space-around; width:100%; margin-top:8mm; }
  .firma-col { text-align:center; width:45%; }
  .firma-nombre { font-weight:bold; font-size:9pt; }
  .firma-cargo { font-size:8.5pt; font-style:italic; color:#555; }
  .stars { color:#c9a227; font-size:14pt; letter-spacing:3px; margin:2mm 0; }
</style></head><body>
<div class="ribbon-top"></div>
<div class="ribbon-bottom"></div>
<div class="gold-border-outer"></div>
<div class="gold-border-inner"></div>
<div class="content">
  <div class="inst-header">Institución Educativa Técnica Agropecuaria<br>y Minera de San Martín de Loba, Bolívar</div>
  <div class="sede">SEDE: PRINCIPAL</div>
  <div class="stars">★ ★ ★</div>
  <div class="mencion-title">Mención de Honor</div>
  <div class="stars">★ ★ ★</div>
  <div class="se-distingue">Se le distingue con</div>
  <div class="reconoce">Hace reconocimiento por su Desempeño Académico, primer puesto a</div>
  <div class="nombre-est">{$nombre}</div>
  <div class="puesto">D.I. {$e['numero_documento']}</div>
  <div class="desempeño">Por su excelente desempeño académico y dedicación durante el año lectivo {$anio}</div>
  <div class="fecha-pie">{$muni}, {$dia} de {$mes} de {$anio}</div>
  <div class="firmas">
    <div class="firma-col">
      <span class="firma-nombre">{$rector}</span><br>
      <span class="firma-cargo">Rector(a)</span>
    </div>
    <div class="firma-col">
      <span class="firma-nombre">Coordinador(a)</span><br>
      <span class="firma-cargo">Coordinador(a) Académico</span>
    </div>
  </div>
</div>
</body></html>
HTML;
        $slug = preg_replace('/\s+/','-', strtolower($nombre));
        $this->renderPdf($html, "mencion_{$slug}.pdf");
    }

    // ─────────────────────────────────────────────────────────
    // Public dispatcher for acta general (no student id needed)
    // ─────────────────────────────────────────────────────────
    public function generarActaGeneral(array $params = []): void
    {
        $config = $this->model->getConfig();
        $this->actaGeneral($config);
    }
}
