<?php
require_once __DIR__ . '/../controllers/TodoController.php';

$page = $_GET['page'] ?? 'index';
$ctl  = new TodoController();

switch ($page) {
    case 'index':
        $ctl->index();
        break;
    case 'create':
        $ctl->create();
        break;
    case 'update':
        $ctl->update();
        break;
    case 'delete':
        $ctl->delete();
        break;
    case 'sort':
        $ctl->sort();
        break;
    case 'detail':
        $ctl->detail();
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
}
