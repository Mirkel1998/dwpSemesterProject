<?php

// Read-side queries for a user's achievements and XP history.
final class ProgressRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function achievementsFor(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.name, a.description, a.xp_reward, ua.unlocked_at
             FROM achievements a
             LEFT JOIN user_achievements ua ON ua.achievementID = a.achievementID AND ua.userID = ?
             ORDER BY a.achievementID'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function recentXp(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT amount, reason, created_at FROM xp_log WHERE userID = ? ORDER BY logID DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
