<?php

final class Gamification
{
    private const TITLES = [
        1 => 'Rookie', 2 => 'Popcorn Pawn', 3 => 'Ticket Tinkerer', 4 => 'Seat Scout',
        5 => 'Reel Ranger', 6 => 'Cinema Knight', 7 => 'Matinee Mage', 8 => 'Premiere Paladin',
        9 => 'Box Office Boss', 10 => 'Legendary Cinephile',
    ];

    public static function levelForXp(int $xp): int
    {
        return (int) floor(sqrt(max(0, $xp) / 100)) + 1;
    }

    public static function xpForLevel(int $level): int
    {
        return 100 * ($level - 1) ** 2;
    }

    public static function title(int $level): string
    {
        return self::TITLES[min($level, count(self::TITLES))];
    }

    // Each level above 1 gives 2% off tickets, capped at 20%.
    public static function discountPercent(int $level): int
    {
        return min($level - 1, 10) * 2;
    }

    public static function award(PDO $pdo, int $userId, int $amount, string $reason): void
    {
        if ($amount > 0) {
            $pdo->prepare('UPDATE users SET xp = xp + ? WHERE userID = ?')->execute([$amount, $userId]);
            $pdo->prepare('INSERT INTO xp_log (userID, amount, reason) VALUES (?, ?, ?)')
                ->execute([$userId, $amount, $reason]);
            flash('xp', "+$amount XP: $reason");
        }

        $stmt = $pdo->prepare('SELECT xp, level FROM users WHERE userID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $newLevel = self::levelForXp((int) $user['xp']);

        if ($newLevel > (int) $user['level']) {
            $pdo->prepare('UPDATE users SET level = ? WHERE userID = ?')->execute([$newLevel, $userId]);
            flash('levelup', "LEVEL UP! You are now level $newLevel: " . self::title($newLevel));
            if ($newLevel >= 5) {
                self::unlock($pdo, $userId, 'LEVEL_5');
            }
        }
    }

    public static function unlock(PDO $pdo, int $userId, string $code): void
    {
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
        self::award($pdo, $userId, 50, 'Welcome bonus');
        self::unlock($pdo, $userId, 'WELCOME');
        self::onLogin($pdo, $userId);
    }

    // Daily login bonus with a streak multiplier (max 7 days).
    public static function onLogin(PDO $pdo, int $userId): void
    {
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
