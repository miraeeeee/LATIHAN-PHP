<?php
require_once __DIR__ . '/../models/TodoModel.php';

class TodoController
{
    public function index()
    {
        $m = new TodoModel();

        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';     // all|done|undone
        $q      = isset($_GET['q']) ? trim($_GET['q']) : '';
        $todos  = $m->getTodos($filter, $q);

        $flash = [
            'error' => isset($_GET['err']) ? $_GET['err'] : '',
            'success' => isset($_GET['ok']) ? $_GET['ok'] : '',
        ];

        include __DIR__ . '/../views/TodoView.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $m = new TodoModel();
            if ($title === '') {
                header('Location: index.php?err=Judul wajib diisi');
                return;
            }
            if ($m->isTitleExists($title)) {
                header('Location: index.php?err=Judul sudah dipakai, gunakan judul lain');
                return;
            }
            $m->create($title, $description);
            header('Location: index.php?ok=Todo berhasil ditambahkan');
            return;
        }
        header('Location: index.php');
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_finished = isset($_POST['is_finished']) && $_POST['is_finished'] == '1';

            $m = new TodoModel();
            if ($title === '') {
                header('Location: index.php?err=Judul wajib diisi');
                return;
            }
            if ($m->isTitleExists($title, $id)) {
                header('Location: index.php?err=Judul sudah dipakai, gunakan judul lain');
                return;
            }
            $m->update($id, $title, $description, $is_finished);
            header('Location: index.php?ok=Perubahan tersimpan');
            return;
        }
        header('Location: index.php');
    }

    public function delete()
    {
        if (isset($_GET['id'])) {
            $m = new TodoModel();
            $m->delete((int)$_GET['id']);
            header('Location: index.php?ok=Todo dihapus');
            return;
        }
        header('Location: index.php');
    }

    // Endpoint AJAX untuk drag & drop
    public function sort()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = json_decode(file_get_contents('php://input'), true);
            $ids = $payload['orderedIds'] ?? [];
            $m = new TodoModel();
            $ok = $m->updateOrders($ids);
            header('Content-Type: application/json');
            echo json_encode(['success' => $ok]);
            return;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
    }

    // Endpoint AJAX untuk detail by id (opsional, kita pakai data row langsung di view)
    public function detail()
    {
        if (isset($_GET['id'])) {
            $m = new TodoModel();
            $todo = $m->getById((int)$_GET['id']);
            header('Content-Type: application/json');
            echo json_encode($todo ?: []);
            return;
        }
        http_response_code(404);
        echo '{}';
    }
}
