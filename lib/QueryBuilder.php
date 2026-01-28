<?php
/**
 * Query Builder Helper
 *
 * Semplifica la costruzione di query comuni senza duplicare codice.
 */

require_once __DIR__ . '/../config/database_schema.php';

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $select = ['*'];

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Imposta i campi da selezionare
     */
    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Aggiunge una condizione WHERE
     */
    public function where(string $column, $value, string $operator = '='): self
    {
        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Aggiunge una condizione WHERE IN
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Aggiunge una condizione WHERE NULL
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    /**
     * Aggiunge una condizione WHERE NOT NULL
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "$column IS NOT NULL";
        return $this;
    }

    /**
     * Aggiunge un JOIN
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    /**
     * Aggiunge un LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Imposta l'ordinamento
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * Imposta il limite
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Imposta l'offset
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Costruisce ed esegue la query SELECT
     */
    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera il primo risultato
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Conta i risultati
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        $this->select = $originalSelect;
        return (int)($result['count'] ?? 0);
    }

    /**
     * Verifica se esistono risultati
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * INSERT
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * UPDATE
     */
    public function update(array $data): int
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $values[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
            $values = array_merge($values, $this->bindings);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }

    /**
     * DELETE
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Costruisce la query SELECT
     */
    private function buildSelectSql(): string
    {
        $select = implode(', ', $this->select);
        $sql = "SELECT $select FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}

/**
 * Helper per creare un Query Builder
 */
if (!function_exists('table')) {
    function table(PDO $pdo, string $table): QueryBuilder
    {
        return new QueryBuilder($pdo, $table);
    }
}

/**
 * Esempi di utilizzo:
 *
 * // SELECT * FROM Utenti WHERE ruolo = 'admin'
 * $admins = table($pdo, TABLE_UTENTI)
 *     ->where(COL_UTENTI_RUOLO, RUOLO_ADMIN)
 *     ->get();
 *
 * // SELECT * FROM Eventi WHERE Data >= CURDATE() ORDER BY Data LIMIT 10
 * $events = table($pdo, TABLE_EVENTI)
 *     ->where(COL_EVENTI_DATA, date('Y-m-d'), '>=')
 *     ->orderBy(COL_EVENTI_DATA, 'ASC')
 *     ->limit(10)
 *     ->get();
 *
 * // UPDATE Utenti SET verificato = 1 WHERE id = 5
 * table($pdo, TABLE_UTENTI)
 *     ->where(COL_UTENTI_ID, 5)
 *     ->update([COL_UTENTI_VERIFICATO => 1]);
 *
 * // INSERT INTO Biglietti (...)
 * $id = table($pdo, TABLE_BIGLIETTI)->insert([
 *     COL_BIGLIETTI_ID_EVENTO => 1,
 *     COL_BIGLIETTI_ID_UTENTE => 5,
 *     COL_BIGLIETTI_STATO => STATO_BIGLIETTO_CARRELLO
 * ]);
 */
