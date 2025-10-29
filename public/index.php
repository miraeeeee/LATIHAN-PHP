<?php
// ===== Debug sementara (hapus di produksi) =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== Path & URL dasar =====
define('BASE_PATH', dirname(__DIR__));   // .../LATIHAN-...
define('PUBLIC_PATH', __DIR__);          // .../LATIHAN-.../public
define('BASE_URL', 'https://latihan-php.raynalhaposan.fun/'); // sesuaikan jika perlu

// ===== Bootstrap: load config & kelas =====
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/controllers/TodoController.php';
require_once BASE_PATH . '/models/TodoModel.php';   // <-- WAJIB, ini yang hilang

try {
    // Siapkan dependency
    $pdo = pdo(); // fungsi dari config.php
    $controller = new TodoController(new TodoModel($pdo));

    // Routing sangat sederhana
    $allowed = ['index','create','update','delete','toggle','detail','reorder'];
    $page = $_GET['page'] ?? 'index';
    if (!in_array($page, $allowed, true)) {
        http_response_code(404);
        exit('Not Found');
    }

    switch ($page) {
        case 'index':   $controller->index();   break;
        case 'create':  $controller->create();  break;
        case 'update':  $controller->update();  break;
        case 'delete':  $controller->delete();  break;
        case 'toggle':  $controller->toggle();  break;
        case 'detail':  $controller->detail();  break;
        case 'reorder': $controller->reorder(); break;
    }
} catch (Throwable $e) {
    // Tangkep error supaya tidak blank 500
    http_response_code(500);
    echo '<pre>Server Error: ' . htmlspecialchars($e->getMessage())
       . "\n" . $e->getFile() . ':' . $e->getLine() . "</pre>";
}
