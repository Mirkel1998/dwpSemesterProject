<?php

// Persistence for user accounts; contains no validation or presentation logic.
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findProfile(int $id): ?array
    {
        return $this->fetchRow(
            'SELECT userID, username, role, xp, level, login_streak FROM users WHERE userID = ?',
            [$id]
        );
    }

    public function findForEdit(int $id): ?array
    {
        // The password hash is deliberately not selected.
        return $this->fetchRow('SELECT userID, username, email, role, xp FROM users WHERE userID = ?', [$id]);
    }

    public function findCredentials(string $username): ?array
    {
        return $this->fetchRow('SELECT userID, password_hash FROM users WHERE username = ?', [$username]);
    }

    public function emailOf(int $id): string
    {
        return (string) $this->scalar('SELECT email FROM users WHERE userID = ?', [$id]);
    }

    public function passwordHashOf(int $id): string
    {
        return (string) $this->scalar('SELECT password_hash FROM users WHERE userID = ?', [$id]);
    }

    public function listAll(): array
    {
        return $this->pdo->query(
            'SELECT userID, username, email, role, xp, level, created_at FROM users ORDER BY userID'
        )->fetchAll();
    }

    public function topByXp(int $limit): array
    {
        $stmt = $this->pdo->prepare('SELECT username, xp, level FROM users ORDER BY xp DESC, userID LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $username, string $email, string $passwordHash, string $role = 'user', int $xp = 0, int $level = 1): int
    {
        $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, xp, level) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$username, $email, $passwordHash, $role, $xp, $level]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $username, string $email, string $role, int $xp, int $level): void
    {
        $this->pdo->prepare('UPDATE users SET username = ?, email = ?, role = ?, xp = ?, level = ? WHERE userID = ?')
            ->execute([$username, $email, $role, $xp, $level, $id]);
    }

    public function updateEmail(int $id, string $email): void
    {
        $this->pdo->prepare('UPDATE users SET email = ? WHERE userID = ?')->execute([$email, $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE userID = ?')->execute([$passwordHash, $id]);
    }

    public function deleteWithDependents(int $id): void
    {
        // Remove dependent records before the account itself, atomically.
        $this->pdo->beginTransaction();
        try {
            foreach (['bookings', 'xp_log', 'user_achievements'] as $table) {
                $this->pdo->prepare("DELETE FROM $table WHERE userID = ?")->execute([$id]);
            }
            $this->pdo->prepare('DELETE FROM users WHERE userID = ?')->execute([$id]);
            $this->pdo->commit();
        } catch (PDOException $ex) {
            $this->pdo->rollBack();
            throw $ex;
        }
    }

    private function fetchRow(string $sql, array $params): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    private function scalar(string $sql, array $params): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
