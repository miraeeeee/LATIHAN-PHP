<?php
require_once __DIR__ . '/../models/TodoModel.php';

class TodoController {
    private TodoModel $model;

    public function __construct(TodoModel $model) {
        $this->model = $model;
        $this->model->migrate();
    }

    public function index(): void {
        $filter = in_array($_GET['filter'] ?? null, ['all','finished','unfinished']) ? $_GET['filter'] : 'all';
        $q = trim((string)($_GET['q'] ?? ''));
        $todos = $this->model->getTodos($filter, $q);
        $flashes = $this->flashes();
        $this->render('TodoView', compact('todos','filter','q','flashes'));
    }

    public function create(): void {
        $this->require_post();
        $title = trim((string)($_POST['title'] ?? ''));
        $desc  = trim((string)($_POST['description'] ?? ''));
        // jika kamu juga punya checkbox di create, pakai pola boolean yang sama:
        // $fin   = (string)($_POST['is_finished'] ?? '0') === '1';

        if ($title === '') {
            $this->flash('danger', 'Judul wajib diisi.');
            $this->redirect('page=index');
        }
        if ($this->model->titleExists($title, null)) {
            $this->flash('danger', 'Judul todo sudah digunakan (harus unik).');
            $this->redirect('page=index');
        }

        $this->model->create($title, $desc);
        $this->flash('success', 'Todo berhasil ditambahkan.');
        $this->redirect('page=index');
    }

    public function update(): void {
        $this->require_post();
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $desc  = trim((string)($_POST['description'] ?? ''));
        // normalisasi boolean dari form (dengan hidden 0 + checkbox 1)
        $fin   = (string)($_POST['is_finished'] ?? '0') === '1';

        if ($title === '') {
            $this->flash('danger', 'Judul wajib diisi.');
            $this->redirect('page=index');
        }
        if ($this->model->titleExists($title, $id)) {
            $this->flash('danger', 'Judul todo sudah digunakan (harus unik).');
            $this->redirect('page=index');
        }
        if (!$this->model->find($id)) {
            $this->flash('danger', 'Todo tidak ditemukan.');
            $this->redirect('page=index');
        }

        $this->model->update($id, $title, $desc, $fin);
        $this->flash('success', 'Todo berhasil diubah.');
        $this->redirect('page=index');
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->model->delete($id);
            $this->flash('success', 'Todo dihapus.');
        }
        $this->redirect('page=index');
    }

    public function toggle(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->model->toggle($id);
            $this->flash('success', 'Status todo diperbarui.');
        }
        $this->redirect('page=index');
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        $todo = $this->model->find($id);
        if (!$todo) {
            http_response_code(404);
            echo '<div class="text-danger">Data tidak ditemukan</div>';
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<div class="mb-2"><strong>Judul:</strong> '.htmlspecialchars($todo['title']).'</div>';
        echo '<div class="mb-2"><strong>Deskripsi:</strong><br>'.nl2br(htmlspecialchars($todo['description'] ?? '')).'</div>';
        echo '<div class="mb-2"><strong>Status:</strong> '.($todo['is_finished'] ? '<span class="badge bg-success">Selesai</span>' : '<span class="badge bg-warning text-dark">Belum</span>').'</div>';
        echo '<div class="mb-2"><strong>Dibuat:</strong> '.htmlspecialchars($todo['created_at']).'</div>';
        echo '<div class="mb-2"><strong>Diperbarui:</strong> '.htmlspecialchars($todo['updated_at']).'</div>';
    }

    public function reorder(): void {
        $this->require_post();
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['ordered_ids']) || !is_array($data['ordered_ids'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Bad payload']);
            return;
        }
        try {
            $this->model->reorder(array_map('intval', $data['ordered_ids']));
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Server error']);
        }
    }

    /* ------- helpers ------- */
    private function render(string $view, array $data = []): void {
        extract($data);
        require __DIR__ . '/../views/'.$view.'.php';
    }
    private function flash(string $type, string $msg): void {
        $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
    }
    private function flashes(): array {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }
    private function redirect(string $params = ''): void {
        header('Location: index.php'.($params ? '?'.$params : ''));
        exit;
    }
    private function require_post(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }
    }
}
