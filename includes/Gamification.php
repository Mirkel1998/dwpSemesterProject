<?php

// Applies XP awards, daily-login, booking, and achievement rewards.
// Callers pass the shared PDO connection so rewards use the same database.
final class Gamification
{
    public static function award(PDO $pdo, int $userId, int $amount, string $reason): void
    {
        // Add positive XP to the account and history, then synchronize its level.
        if ($amount > 0) {
            $pdo->prepare('UPDATE users SET xp = xp + ? WHERE userID = ?')->execute([$amount, $userId]);
            $pdo->prepare('INSERT INTO xp_log (userID, amount, reason) VALUES (?, ?, ?)')
                ->execute([$userId, $amount, $reason]);
            flash('xp', "+$amount XP: $reason");
        }

        $stmt = $pdo->prepare('SELECT xp, level FROM users WHERE userID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $newLevel = LevelRules::levelForXp((int) $user['xp']);

        if ($newLevel > (int) $user['level']) {
            $pdo->prepare('UPDATE users SET level = ? WHERE userID = ?')->execute([$newLevel, $userId]);
            flash('levelup', "LEVEL UP! You are now level $newLevel: " . LevelRules::title($newLevel));
            if ($newLevel >= 5) {
                self::unlock($pdo, $userId, 'LEVEL_5');
            }
        }
    }

    public static function unlock(PDO $pdo, int $userId, string $code): void
    {
        // Insert an achievement once per user; grant its reward only on first unlock.
        $stmt = $pdo->prepare('SELECT achievementID, name, xp_reward FROM achievements WHERE code = ?');
        $stmt->execute([$code]);
        $achievement = $stmt->fetch();
        if (!$achievement) {
            return;
        }

        $insert = $pdo->prepare('INSERT IGNORE INTO user_achievements (userID, achievementID) VALUES (?, ?)');
        $insert->execute([$userId, $achievement['achievementID']]);
        if ($insert->rowCount() === 1) {
            flash('achievement', 'ACHIEVEMENT UNLOCKED: ' . $achievement['name']);
            self::award($pdo, $userId, (int) $achievement['xp_reward'], 'Achievement: ' . $achievement['name']);
        }
    }

    public static function onRegister(PDO $pdo, int $userId): void
    {
        // A new account gets a welcome award, the welcome achievement, and login rewards.
        self::award($pdo, $userId, 50, 'Welcome bonus');
        self::unlock($pdo, $userId, 'WELCOME');
        self::onLogin($pdo, $userId);
    }

    // Daily login bonus with a streak multiplier (max 7 days).
    public static function onLogin(PDO $pdo, int $userId): void
    {
        // Give at most one daily award, increasing the streak only on consecutive days.
        $stmt = $pdo->prepare('SELECT login_streak, last_login_date FROM users WHERE userID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $today = date('Y-m-d');
        if ($user['last_login_date'] === $today) {
            return;
        }
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = $user['last_login_date'] === $yesterday ? (int) $user['login_streak'] + 1 : 1;

        $pdo->prepare('UPDATE users SET login_streak = ?, last_login_date = ? WHERE userID = ?')
            ->execute([$streak, $today, $userId]);

        self::award($pdo, $userId, 10 + min($streak, 7) * 5, "Daily login (streak $streak)");
        if ($streak >= 3) {
            self::unlock($pdo, $userId, 'STREAK_3');
        }
    }

    public static function onBooking(PDO $pdo, int $userId, int $seatCount, string $startsAt): void
    {
        // Reward the booking and unlock any milestones it satisfies.
        self::award($pdo, $userId, 50 + 10 * $seatCount, "Booked $seatCount seat(s)");

        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT showtimeID) FROM bookings WHERE userID = ?');
        $stmt->execute([$userId]);
        $bookingCount = (int) $stmt->fetchColumn();

        self::unlock($pdo, $userId, 'FIRST_TICKET');
        if ($bookingCount >= 5) {
            self::unlock($pdo, $userId, 'REGULAR');
        }
        if ($seatCount >= 4) {
            self::unlock($pdo, $userId, 'SQUAD');
        }
        if (date('H', strtotime($startsAt)) === '21') {
            self::unlock($pdo, $userId, 'NIGHT_OWL');
        }
    }
}
