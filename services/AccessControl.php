<?php

class AccessControl
{
    private PDO $pdo;
    private array $user;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function isAdmin(): bool
    {
        return (int) ($this->user['role_id'] ?? 0) === 1;
    }

    public function canRead(string $path): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $rule = $this->resolveRule($path);

        if ($rule === null) {
            return true;
        }

        return (bool) $rule['can_read'];
    }

    public function canWrite(string $path): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $rule = $this->resolveRule($path);

        if ($rule === null) {
            return false;
        }

        return (bool) $rule['can_write'];
    }

    public function isHidden(string $path): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $rule = $this->resolveRule($path);

        if ($rule === null) {
            return false;
        }

        return !(bool) $rule['can_read'];
    }

    private function resolveRule(string $path): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT can_read, can_write FROM document_acl '
            . 'WHERE (:path = document_path OR :path LIKE CONCAT(document_path, "/%")) '
            . 'AND (user_id = :user_id OR role_id = :role_id) '
            . 'ORDER BY LENGTH(document_path) DESC, user_id DESC LIMIT 1'
        );

        $query->execute([
            'path' => trim($path, '/'),
            'user_id' => $this->user['id'],
            'role_id' => $this->user['role_id'],
        ]);

        $result = $query->fetch();

        return $result ?: null;
    }
}
