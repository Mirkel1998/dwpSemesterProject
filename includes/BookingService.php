<?php

// Booking use case: seat-selection rules plus the atomic save-and-reward flow.
final class BookingService
{
    public const MAX_SEATS = 8;

    public function __construct(private PDO $pdo, private BookingRepository $bookings)
    {
    }

    // Keep only distinct "row-number" seats that fit the theater layout.
    public static function parseSeats(mixed $submitted, int $rows, int $perRow): array
    {
        $seats = [];
        foreach ((array) $submitted as $raw) {
            if (is_string($raw) && preg_match('/^(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
                $r = (int) $m[1];
                $n = (int) $m[2];
                if ($r >= 1 && $r <= $rows && $n >= 1 && $n <= $perRow) {
                    $seats["$r-$n"] = [$r, $n];
                }
            }
        }
        return $seats;
    }

    // Saves every seat and awards booking rewards atomically.
    public function book(int $userId, array $show, array $seats, int $price): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->bookings->insertSeats($userId, (int) $show['showtimeID'], bin2hex(random_bytes(16)), $seats, $price);
            Gamification::onBooking($this->pdo, $userId, count($seats), $show['starts_at']);
            $this->pdo->commit();
        } catch (Throwable $ex) {
            $this->pdo->rollBack();
            // Discard flashes queued by the rolled-back transaction.
            pull_flashes();
            throw $ex;
        }
    }
}
