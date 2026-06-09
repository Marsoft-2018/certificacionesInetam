<?php
// app/Core/Controller.php  –  Controlador base
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $file = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("Vista no encontrada: $file");
        }
        require $file;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
