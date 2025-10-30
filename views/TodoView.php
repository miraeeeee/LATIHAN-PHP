<?php
// Data tersedia: $todos (array), $filter (string), $q (string), $flash (array)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>PHP - Aplikasi Todolist</title>
  <link href="/assets/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .todo-item.dragging { opacity: .6; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Todo List</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Tambah Data</button>
  </div>

  <?php if (!empty($flash['error'])): ?>
    <div class="alert alert-danger"><?= h($flash['error']) ?></div>
  <?php endif; ?>
  <?php if (!empty($flash['success'])): ?>
    <div class="alert alert-success"><?= h($flash['success']) ?></div>
  <?php endif; ?>

  <!-- FILTER & SEARCH -->
  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="page" value="index">
    <div class="col-auto">
      <select class="form-select" name="filter" onchange="this.form.submit()">
        <option value="all"   <?= $filter==='all'?'selected':''; ?>>Semua</option>
        <option value="done"  <?= $filter==='done'?'selected':''; ?>>Selesai</option>
        <option value="undone"<?= $filter==='undone'?'selected':''; ?>>Belum selesai</option>
      </select>
    </div>
    <div class="col">
      <input type="text" class="form-control" name="q" value="<?= h($q) ?>" placeholder="Cari judul/desk...">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </div>
  </form>

  <!-- LIST -->
  <div class="card">
    <div class="card-body">
      <div id="todoList" class="list-group">
        <?php foreach ($todos as $t): ?>
          <div class="list-group-item d-flex justify-content-between align-items-start todo-item" data-id="<?= (int)$t['id'] ?>">
            <div class="ms-0 me-2">
              <div class="fw-semibold">
                <?php if ($t['is_finished'] === 't' || $t['is_finished'] === true): ?>
                  <span class="badge text-bg-success me-1">Selesai</span>
                <?php else: ?>
                  <span class="badge text-bg-warning me-1">Belum</span>
                <?php endif; ?>
                <?= h($t['title']) ?>
              </div>
              <?php if (!empty($t['description'])): ?>
                <div class="text-muted small"><?= nl2br(h($t['description'])) ?></div>
              <?php endif; ?>
              <div class="small text-secondary mt-1">
                Dibuat: <?= h($t['created_at']) ?> · Update: <?= h($t['updated_at']) ?>
              </div>
            </div>
            <div class="btn-group">
              <button class="btn btn-sm btn-info text-white" onclick='showDetail(<?= (int)$t["id"] ?>, <?= json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>Detail</button>
              <button class="btn btn-sm btn-warning" onclick='showEdit(<?= (int)$t["id"] ?>, <?= json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>Edit</button>
              <a class="btn btn-sm btn-danger" href="?page=delete&id=<?= (int)$t['id'] ?>" onclick="return confirm('Hapus todo ini?')">Hapus</a>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($todos)): ?>
          <div class="text-center text-muted py-4">Belum ada data</div>
        <?php endif; ?>
      </div>
      <div class="mt-2 text-muted small">Tip: drag item untuk mengubah urutan.</div>
    </div>
  </div>
</div>

<!-- MODAL CREATE -->
<div class="modal fade" id="modalCreate" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="?page=create">
      <div class="modal-header"><h5 class="modal-title">Tambah Todo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Judul <span class="text-danger">*</span></label>
          <input class="form-control" name="title" required maxlength="250">
        </div>
        <div class="mb-2">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-control" name="description" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="?page=update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header"><h5 class="modal-title">Edit Todo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Judul <span class="text-danger">*</span></label>
          <input class="form-control" name="title" id="edit-title" required maxlength="250">
        </div>
        <div class="mb-2">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-control" name="description" id="edit-description" rows="3"></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="edit-is-finished" name="is_finished">
          <label class="form-check-label" for="edit-is-finished">Selesai</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="modalDetail" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detail Todo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><b>Judul:</b> <span id="detail-title"></span></div>
        <div class="mb-2"><b>Status:</b> <span id="detail-status" class="badge"></span></div>
        <div class="mb-2"><b>Deskripsi:</b><br><div id="detail-description" class="text-muted"></div></div>
        <div class="text-secondary small">
          <div>Created: <span id="detail-created"></span></div>
          <div>Updated: <span id="detail-updated"></span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="/assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<!-- SortableJS (drag & drop). Pakai CDN untuk praktis -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  // Drag & drop + persist order
  const listEl = document.getElementById('todoList');
  new Sortable(listEl, {
    animation: 150,
    ghostClass: 'dragging',
    onEnd: async () => {
      const ids = [...document.querySelectorAll('.todo-item')].map(el => el.dataset.id);
      try {
        await fetch('?page=sort', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ orderedIds: ids })
        });
      } catch (e) {
        console.error(e);
      }
    }
  });

  // Edit modal
  function showEdit(id, data) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = data.title || '';
    document.getElementById('edit-description').value = data.description || '';
    document.getElementById('edit-is-finished').checked = (data.is_finished === true || data.is_finished === 't');
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
  }

  // Detail modal
  function showDetail(id, data) {
    document.getElementById('detail-title').textContent = data.title || '';
    const statusEl = document.getElementById('detail-status');
    const done = (data.is_finished === true || data.is_finished === 't');
    statusEl.textContent = done ? 'Selesai' : 'Belum';
    statusEl.className = 'badge ' + (done ? 'text-bg-success' : 'text-bg-warning');
    document.getElementById('detail-description').innerHTML = (data.description||'').replace(/\n/g,'<br>');
    document.getElementById('detail-created').textContent = data.created_at || '';
    document.getElementById('detail-updated').textContent = data.updated_at || '';
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
  }
</script>
</body>
</html>
