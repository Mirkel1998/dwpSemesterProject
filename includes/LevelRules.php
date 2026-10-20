<?php

// Pure level, title, and discount rules; no database or session access.
final class LevelRules
{
    // Display names for levels; higher levels reuse the final title.
    private const TITLES = [
        1 => 'Noob', 2 => 'Popcorn Pawn', 3 => 'Ticket Tiger', 4 => 'Seat Sorcerer',
        5 => 'Movie Maestro', 6 => 'Binge Bitch', 7 => 'Mario Movie Man', 8 => 'Diva Director',
        9 => 'Premiere Paladin', 10 => 'Legendary Cinephile'
    ];

    public static function levelForXp(int $xp): int
    {
        // Levels rise every 100 * (level - 1)^2 XP.
        return (int) floor(sqrt(max(0, $xp) / 100)) + 1;
    }

    public static function xpForLevel(int $level): int
    {
        // Return the XP threshold at which the requested level begins.
        return 100 * ($level - 1) ** 2;
    }

    public static function title(int $level): string
    {
        // Cap title lookup at the last configured title.
        return self::TITLES[min($level, count(self::TITLES))];
    }

    // Each level above 1 gives 2% off tickets, capped at 20%.
    public static function discountPercent(int $level): int
    {
        return min($level - 1, 10) * 2;
    }
}
