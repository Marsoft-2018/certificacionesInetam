<?php
// app/Controllers/UploadController.php
namespace App\Controllers;

use App\Core\Controller;

class UploadController extends Controller
{
    public function logo(array $params = []): void
    {
        if (empty($_FILES['logo'])) {
            $this->json(['success' => false, 'message' => 'No se recibió ningún archivo.'], 400);
        }

        $file = $_FILES['logo'];
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg'];
        $mime = mime_content_type($file['tmp_name']);

        if (!array_key_exists($mime, $allowed)) {
            $this->json(['success' => false, 'message' => 'Tipo de archivo no permitido. Use PNG, JPG o SVG.'], 422);
        }

        $ext     = $allowed[$mime];
        $destDir = ASSETS_PATH . '/img/';
        $dest    = $destDir . 'escudo.' . $ext;

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Si es PNG, guardar también como escudo.png canónico
            if ($ext !== 'png') {
                // Intentar convertir con GD si está disponible
                if ($ext === 'jpg' && function_exists('imagecreatefromjpeg')) {
                    $img = imagecreatefromjpeg($dest);
                    imagepng($img, $destDir . 'escudo.png');
                    imagedestroy($img);
                }
            }
            $this->json(['success' => true, 'message' => 'Logo subido correctamente.', 'path' => $dest]);
        } else {
            $this->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
        }
    }
}
