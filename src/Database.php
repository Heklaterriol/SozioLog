<?php
namespace Logbuch;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database — PDO-Singleton
 *
 * Verwendung:
 *   $db = Database::getInstance();
 *   $rows = $db->fetchAll('SELECT * FROM circles WHERE status = ?', ['active']);
 */
class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct(array $cfg)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Keine Zugangsdaten in der Exception-Message nach außen
            throw new RuntimeException('Datenbankverbindung fehlgeschlagen.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $cfg = require dirname(__DIR__) . '/config/config.php';
            // config.local.php überschreibt config.php (optional)
            $local = dirname(__DIR__) . '/config/config.local.php';
            if (file_exists($local)) {
                $localCfg = require $local;
                $cfg = array_replace_recursive($cfg, $localCfg);
            }
            self::$instance = new self($cfg['db']);
        }
        return self::$instance;
    }

    // ----------------------------------------------------------
    //  Query-Helfer
    // ----------------------------------------------------------

    /** Alle Zeilen als Array zurückgeben */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Erste Zeile oder null */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Einen einzelnen Wert (erste Spalte, erste Zeile) */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** INSERT / UPDATE / DELETE — gibt Anzahl betroffener Zeilen zurück */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT — gibt die neue ID zurück */
    public function insert(string $sql, array $params = []): int
    {
        $this->execute($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /** Rohes PDO für Sonderfälle */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    // ----------------------------------------------------------
    //  Transaktionen
    // ----------------------------------------------------------

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Transaktion mit Callable — bei Exception automatisch rollback.
     *
     *   $db->transaction(function(Database $db) {
     *       $db->execute(...);
     *       $db->execute(...);
     *   });
     */
    public function transaction(callable $fn): mixed
    {
        $this->beginTransaction();
        try {
            $result = $fn($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
