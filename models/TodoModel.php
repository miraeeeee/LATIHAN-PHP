<?php
require_once __DIR__ . '/../config.php';

class TodoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = db_connect();
    }

    public function getTodos($filter = 'all', $q = '', $orderBy = 'sort_order')
    {
        $where = [];
        $params = [];
        $i = 1;

        if ($filter === 'done') {
            $where[] = 'is_finished = TRUE';
        } elseif ($filter === 'undone') {
            $where[] = 'is_finished = FALSE';
        }

        if ($q !== '') {
            $where[] = '(LOWER(title) LIKE $' . $i . ' OR LOWER(description) LIKE $' . $i . ')';
            $params[] = '%' . strtolower($q) . '%';
            $i++;
        }

        $sql = 'SELECT id, title, description, is_finished, sort_order, created_at, updated_at
                FROM todo';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // sort by sort_order ASC by default, updated_at DESC as tiebreaker
        $sql .= ' ORDER BY ' . ($orderBy === 'updated_at' ? 'updated_at DESC, sort_order ASC' : 'sort_order ASC, updated_at DESC');

        $result = pg_query_params($this->conn, $sql, $params);
        return $result ? pg_fetch_all($result) ?: [] : [];
    }

    public function getById($id)
    {
        $sql = 'SELECT id, title, description, is_finished, sort_order, created_at, updated_at
                FROM todo WHERE id = $1';
        $res = pg_query_params($this->conn, $sql, [$id]);
        return $res ? pg_fetch_assoc($res) : null;
    }

    public function isTitleExists($title, $excludeId = null)
    {
        if ($excludeId) {
            $sql = 'SELECT 1 FROM todo WHERE LOWER(title)=LOWER($1) AND id <> $2 LIMIT 1';
            $res = pg_query_params($this->conn, $sql, [$title, $excludeId]);
        } else {
            $sql = 'SELECT 1 FROM todo WHERE LOWER(title)=LOWER($1) LIMIT 1';
            $res = pg_query_params($this->conn, $sql, [$title]);
        }
        return $res && pg_fetch_assoc($res) ? true : false;
    }

    public function create($title, $description)
    {
        // tentukan sort_order paling akhir
        $resMax = pg_query($this->conn, 'SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM todo');
        $next = $resMax ? (int)pg_fetch_result($resMax, 0, 'next_order') : 0;

        $sql = 'INSERT INTO todo (title, description, is_finished, sort_order)
                VALUES ($1, $2, FALSE, $3)';
        $res = pg_query_params($this->conn, $sql, [$title, $description, $next]);
        return $res !== false;
    }

    public function update($id, $title, $description, $is_finished)
    {
        $sql = 'UPDATE todo
                SET title=$1, description=$2, is_finished=$3
                WHERE id=$4';
        $res = pg_query_params($this->conn, $sql, [$title, $description, $is_finished ? 't' : 'f', $id]);
        return $res !== false;
    }

    public function delete($id)
    {
        $sql = 'DELETE FROM todo WHERE id=$1';
        $res = pg_query_params($this->conn, $sql, [$id]);
        return $res !== false;
    }

    public function updateOrders($orderedIds)
    {
        // $orderedIds = [id1, id2, ...] urutan dari atas ke bawah
        pg_query($this->conn, 'BEGIN');
        try {
            $order = 0;
            foreach ($orderedIds as $id) {
                pg_query_params($this->conn, 'UPDATE todo SET sort_order=$1 WHERE id=$2', [$order, (int)$id]);
                $order++;
            }
            pg_query($this->conn, 'COMMIT');
            return true;
        } catch (\Throwable $e) {
            pg_query($this->conn, 'ROLLBACK');
            return false;
        }
    }
}
