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

        return $this->hasAcl($path, 'can_read') ?? true;
    }

    public function canWrite(string $path): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasAcl($path, 'can_write') ?? false;
    }

    private function hasAcl(string $path, string $field): ?bool
    {
        $query = $this->pdo->prepare(
            "SELECT {$field} FROM document_acl WHERE document_path = :path AND (user_id = :user_id OR role_id = :role_id) ORDER BY user_id DESC LIMIT 1"
        );

        $query->execute([
            'path' => $path,
            'user_id' => $this->user['id'],
            'role_id' => $this->user['role_id'],
        ]);

        $result = $query->fetchColumn();

        if ($result === false) {
            return null;
        }

        return (bool) $result;
    }
}
