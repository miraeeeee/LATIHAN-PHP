<?php
// Variables: $todos, $filter, $q, $flashes
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Todo List - PostgreSQL</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <style>
    body { background:#f6f7fb; }
    .card { border:0; box-shadow:0 6px 20px rgba(0,0,0,.06); }
    .drag-handle { cursor:grab; user-select:none; font-size:1.1rem; opacity:.7; }
    .todo-title { font-weight:600; }
    .todo-item.dragging { opacity:.65; }
    .todo-actions .btn { margin-right:.25rem; margin-bottom:.25rem; }
    .badge-filter { text-transform:capitalize; }
    .truncate-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Tambah Todo</h5>
            <?php foreach ($flashes as $f): ?>
              <div class="alert alert-<?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['msg']) ?></div>
            <?php endforeach; ?>
            <form action="index.php?page=create" method="post">
              <div class="mb-3">
                <label class="form-label">Judul <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required maxlength="250" placeholder="Judul todo">
              </div>
              <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Detail pekerjaan..."></textarea>
              </div>
              <div class="d-grid">
                <button class="btn btn-primary">Simpan</button>
              </div>
            </form>
            <hr class="my-4">
            <small class="text-muted d-block">Filter aktif:
              <span class="badge bg-secondary badge-filter"><?= htmlspecialchars($filter) ?></span>
            </small>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
              <h5 class="mb-0">Daftar Todo</h5>
              <form class="d-flex gap-2" action="index.php" method="get">
                <input type="hidden" name="page" value="index">
                <select name="filter" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                  <option value="all"        <?=$filter==='all'?'selected':''?>>Semua</option>
                  <option value="finished"   <?=$filter==='finished'?'selected':''?>>Selesai</option>
                  <option value="unfinished" <?=$filter==='unfinished'?'selected':''?>>Belum Selesai</option>
                </select>
                <div class="input-group">
                  <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari judul/desk..." />
                  <button class="btn btn-outline-secondary">Cari</button>
                </div>
              </form>
            </div>

            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th style="width:40px"></th>
                    <th>Judul</th>
                    <th style="width:120px">Status</th>
                    <th style="width:260px">Aksi</th>
                  </tr>
                </thead>
                <tbody id="todo-list">
                  <?php if (!$todos): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                  <?php endif; ?>

                  <?php foreach ($todos as $t): ?>
                    <tr class="todo-item" data-id="<?= (int)$t['id'] ?>">
                      <td class="text-muted"><span class="drag-handle">☰</span></td>
                      <td>
                        <div class="todo-title"><?= htmlspecialchars($t['title']) ?></div>
                        <?php if (!empty($t['description'])): ?>
                          <div class="text-muted small truncate-2"><?= nl2br(htmlspecialchars($t['description'])) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                          dibuat: <?= htmlspecialchars($t['created_at']) ?> • update: <?= htmlspecialchars($t['updated_at']) ?>
                        </div>
                      </td>
                      <td>
                        <?= $t['is_finished']
                            ? '<span class="badge bg-success">Selesai</span>'
                            : '<span class="badge bg-warning text-dark">Belum</span>'; ?>
                      </td>
                      <td class="todo-actions">
                        <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#detailModal"
                                data-detail-id="<?= (int)$t['id'] ?>">
                          Detail
                        </button>

                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-edit-id="<?= (int)$t['id'] ?>"
                                data-edit-title="<?= htmlspecialchars($t['title']) ?>"
                                data-edit-description="<?= htmlspecialchars($t['description']) ?>"
                                data-edit-finished="<?= $t['is_finished'] ? '1' : '0' ?>">
                          Edit
                        </button>

                        <a class="btn btn-sm btn-outline-success" href="index.php?page=toggle&id=<?= (int)$t['id'] ?>">
                          Toggle Selesai
                        </a>

                        <a class="btn btn-sm btn-outline-danger"
                           href="index.php?page=delete&id=<?= (int)$t['id'] ?>"
                           onclick="return confirm('Hapus todo ini?')">
                          Hapus
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="small text-muted">Drag & drop baris untuk mengurutkan. Urutan tersimpan permanen.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Todo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="detailBody">Memuat...</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" action="index.php?page=update" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Edit Todo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" class="form-control" name="title" id="edit-title" required maxlength="250">
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" name="description" id="edit-description" rows="4"></textarea>
          </div>
          <!-- perbaikan: kirim 0 selalu, override ke 1 jika dicentang -->
          <div class="form-check">
            <input type="hidden" name="is_finished" value="0">
            <input class="form-check-input" type="checkbox" id="edit-finished" name="is_finished" value="1">
            <label class="form-check-label" for="edit-finished">Tandai selesai</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Batal</button>
          <button class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', event => {
      const btn = event.relatedTarget;
      const id = btn.getAttribute('data-detail-id');
      const body = document.getElementById('detailBody');
      body.innerHTML = 'Memuat...';
      fetch('index.php?page=detail&id=' + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => body.innerHTML = html)
        .catch(() => body.innerHTML = '<span class="text-danger">Gagal memuat</span>');
    });

    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', event => {
      const btn = event.relatedTarget;
      document.getElementById('edit-id').value = btn.getAttribute('data-edit-id');
      document.getElementById('edit-title').value = btn.getAttribute('data-edit-title');
      document.getElementById('edit-description').value = btn.getAttribute('data-edit-description') || '';
      document.getElementById('edit-finished').checked = btn.getAttribute('data-edit-finished') === '1';
    });

    const el = document.getElementById('todo-list');
    new Sortable(el, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        const ids = Array.from(document.querySelectorAll('#todo-list .todo-item'))
          .map(tr => tr.getAttribute('data-id'));
        fetch('index.php?page=reorder', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ ordered_ids: ids })
        }).then(r => {
          if (!r.ok) console.error('Gagal simpan urutan');
        }).catch(err => console.error(err));
      }
    });
  </script>
</body>
</html>
