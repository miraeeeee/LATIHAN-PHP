<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/TodoController.php';

$controller = new TodoController(new TodoModel(pdo()));

$page = $_GET['page'] ?? 'index';

switch ($page) {
    case 'index':
        $controller->index();
        break;
    case 'create':
        $controller->create();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'toggle':
        $controller->toggle();
        break;
    case 'detail':
        $controller->detail();
        break;
    case 'reorder':
        $controller->reorder();
        break;
    default:
        http_response_code(404);
        echo 'Not Found';
}
