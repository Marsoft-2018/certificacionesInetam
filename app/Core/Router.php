<?php
// app/Core/Router.php  –  Front Controller / Router robusto
namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = compact('method', 'path', 'controller', 'action');
    }

    public function dispatch(string $method, string $uri): void
    {
        // 1. Quitar query string
        $uri = strtok($uri, '?') ?: '/';

        // 2. Calcular el script base (directorio donde vive index.php)
        //    Funciona aunque el proyecto esté en /certificados/public/ o en /
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

        // 3. Eliminar ese prefijo de la URI recibida
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // 4. Normalizar: siempre empieza con /, nunca termina con /
        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        // 5. Buscar ruta
        foreach ($this->routes as $route) {
            $pattern = '@^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']) . '$@';
            if (strtoupper($method) === strtoupper($route['method'])
                && preg_match($pattern, $uri, $matches)
            ) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controllerClass = 'App\\Controllers\\' . $route['controller'];
                $ctrl = new $controllerClass();
                $ctrl->{$route['action']}($params);
                return;
            }
        }

        // 6. Ruta no encontrada  — útil para depurar temporalmente
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'    => false,
            'message'    => 'Ruta no encontrada',
            'uri_parsed' => $uri,          // ← muestra la URI que llegó al router
            'method'     => $method,
        ], JSON_UNESCAPED_UNICODE);
    }
}
