<?php
class TodoModel {
    private PDO $db;

    public function __construct(PDO $db) { $this->db = $db; }

    public function migrate(): void {
        // 1) Buat tabel jika belum ada (tanpa asumsi kolom lengkap)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS todo (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Helper: cek apakah kolom ada
        $colExists = function (string $name): bool {
            $st = $this->db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_name='todo' AND column_name=:c
                LIMIT 1
            ");
            $st->execute([':c' => $name]);
            return (bool)$st->fetchColumn();
        };

        // 2) Tambahkan kolom baru jika belum ada
        if (!$colExists('title')) {
            $this->db->exec("ALTER TABLE todo ADD COLUMN title VARCHAR(250)");
        }
        if (!$colExists('description')) {
            $this->db->exec("ALTER TABLE todo ADD COLUMN description TEXT NOT NULL DEFAULT ''");
        }
        if (!$colExists('is_finished')) {
            $this->db->exec("ALTER TABLE todo ADD COLUMN is_finished BOOLEAN NOT NULL DEFAULT FALSE");
        }
        if (!$colExists('position')) {
            $this->db->exec("ALTER TABLE todo ADD COLUMN position INTEGER NOT NULL DEFAULT 0");
        }

        // 3) Migrasi dari skema lama (activity/status) bila masih ada
        if ($colExists('activity')) {
            // Isi title dari activity hanya yang masih NULL/empty
            $this->db->exec("UPDATE todo SET title = activity WHERE (title IS NULL OR title = '')");
        }
        if ($colExists('status')) {
            // Map status (0/1) → boolean
            $this->db->exec("UPDATE todo SET is_finished = (status = 1)");
        }

        // 4) Pastikan title NOT NULL; jika masih NULL, isi placeholder dulu biar constraint aman
        $this->db->exec("
            UPDATE todo
               SET title = CONCAT('todo-', id)
             WHERE title IS NULL OR title = '';
        ");
        $this->db->exec("ALTER TABLE todo ALTER COLUMN title SET NOT NULL");

        // 5) Hapus kolom lama setelah migrasi
        if ($colExists('activity')) {
            $this->db->exec("ALTER TABLE todo DROP COLUMN activity");
        }
        if ($colExists('status')) {
            $this->db->exec("ALTER TABLE todo DROP COLUMN status");
        }

        // 6) UNIQUE(title) — rapikan duplikat dulu (auto-suffix)
        $this->db->exec("
            DO $$
            DECLARE
              r RECORD;
              n INTEGER;
            BEGIN
              FOR r IN
                SELECT id, title,
                       ROW_NUMBER() OVER (PARTITION BY title ORDER BY id) AS rn
                FROM todo
              LOOP
                IF r.rn > 1 THEN
                  n := r.rn - 1;
                  UPDATE todo
                     SET title = r.title || ' (copy-' || n || ')'
                   WHERE id = r.id;
                END IF;
              END LOOP;
            END$$;
        ");
        $this->db->exec("
            DO $$
            BEGIN
              IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_todo_title') THEN
                ALTER TABLE todo ADD CONSTRAINT uq_todo_title UNIQUE (title);
              END IF;
            END$$;
        ");

        // 7) Trigger updated_at (idempotent)
        $this->db->exec("
            CREATE OR REPLACE FUNCTION update_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
               NEW.updated_at = CURRENT_TIMESTAMP;
               RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DO $$
            BEGIN
              IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_todo_timestamp') THEN
                CREATE TRIGGER update_todo_timestamp
                BEFORE UPDATE ON todo
                FOR EACH ROW
                EXECUTE FUNCTION update_timestamp();
              END IF;
            END$$;
        ");

        // 8) Inisialisasi/rapikan posisi supaya urut mulai 1
        $this->db->exec("
            WITH ordered AS (
              SELECT id, ROW_NUMBER() OVER (ORDER BY position ASC, created_at ASC, id ASC) AS rn
              FROM todo
            )
            UPDATE todo t
               SET position = o.rn
              FROM ordered o
             WHERE t.id = o.id;
        ");

        // 9) Index bantu
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_todo_is_finished ON todo(is_finished)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_todo_position  ON todo(position)");
    }

    public function getTodos(string $filter='all', ?string $q=null): array {
        $where = [];
        $params = [];
        if ($filter === 'finished')   $where[] = "is_finished = TRUE";
        if ($filter === 'unfinished') $where[] = "is_finished = FALSE";
        if ($q !== null && $q !== '') {
            $where[] = "(title ILIKE :q OR description ILIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        $sql = "SELECT * FROM todo";
        if ($where) $sql .= " WHERE ".implode(" AND ", $where);
        $sql .= " ORDER BY position ASC, created_at DESC, id DESC";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function find(int $id): ?array {
        $st = $this->db->prepare("SELECT * FROM todo WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        return $row ?: null;
    }

    public function titleExists(string $title, ?int $excludeId=null): bool {
        if ($excludeId) {
            $st = $this->db->prepare("SELECT 1 FROM todo WHERE title = :t AND id <> :id LIMIT 1");
            $st->bindValue(':t',  $title, PDO::PARAM_STR);
            $st->bindValue(':id', $excludeId, PDO::PARAM_INT);
            $st->execute();
        } else {
            $st = $this->db->prepare("SELECT 1 FROM todo WHERE title = :t LIMIT 1");
            $st->bindValue(':t', $title, PDO::PARAM_STR);
            $st->execute();
        }
        return (bool)$st->fetchColumn();
    }

    public function create(string $title, string $description): void {
        $pos = (int)$this->db->query("SELECT COALESCE(MAX(position),0) + 1 FROM todo")->fetchColumn();
        $st = $this->db->prepare("INSERT INTO todo (title, description, position) VALUES (:t, :d, :p)");
        $st->bindValue(':t', $title, PDO::PARAM_STR);
        $st->bindValue(':d', $description, PDO::PARAM_STR);
        $st->bindValue(':p', $pos, PDO::PARAM_INT);
        $st->execute();
    }

    public function update(int $id, string $title, string $description, bool $isFinished): void {
        $st = $this->db->prepare("
            UPDATE todo
               SET title = :t, description = :d, is_finished = :f
             WHERE id = :id
        ");
        $st->bindValue(':t',  $title,       PDO::PARAM_STR);
        $st->bindValue(':d',  $description, PDO::PARAM_STR);
        $st->bindValue(':f',  $isFinished,  PDO::PARAM_BOOL);
        $st->bindValue(':id', $id,          PDO::PARAM_INT);
        $st->execute();
    }

    public function delete(int $id): void {
        $st = $this->db->prepare("DELETE FROM todo WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $this->repackPositions();
    }

    public function toggle(int $id): void {
        $st = $this->db->prepare("UPDATE todo SET is_finished = NOT is_finished WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    public function reorder(array $orderedIds): void {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("UPDATE todo SET position = :p WHERE id = :id");
            $pos = 1;
            foreach ($orderedIds as $id) {
                $st->bindValue(':p',  $pos++,        PDO::PARAM_INT);
                $st->bindValue(':id', (int)$id,      PDO::PARAM_INT);
                $st->execute();
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function repackPositions(): void {
        $ids = $this->db->query("SELECT id FROM todo ORDER BY position ASC, created_at ASC, id ASC")
                        ->fetchAll(PDO::FETCH_COLUMN);
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("UPDATE todo SET position = :p WHERE id = :id");
            $p = 1;
            foreach ($ids as $id) {
                $st->bindValue(':p',  $p++,    PDO::PARAM_INT);
                $st->bindValue(':id', (int)$id, PDO::PARAM_INT);
                $st->execute();
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
