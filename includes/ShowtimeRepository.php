<?php

// Persistence for showtimes and the theaters they run in.
final class ShowtimeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findBookable(?int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.showtimeID, s.starts_at, s.price, m.title, t.name, t.city, t.seat_rows, t.seats_per_row
             FROM showtimes s
             JOIN movies m ON m.movieID = s.movieID
             JOIN theaters t ON t.theaterID = s.theaterID
             WHERE s.showtimeID = ? AND s.starts_at > NOW()'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function upcomingForMovie(int $movieId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.showtimeID, s.starts_at, s.price, t.name, t.city,
                    t.seat_rows * t.seats_per_row AS capacity,
                    (SELECT COUNT(*) FROM bookings b WHERE b.showtimeID = s.showtimeID) AS taken
             FROM showtimes s JOIN theaters t ON t.theaterID = s.theaterID
             WHERE s.movieID = ? AND s.starts_at > NOW()
             ORDER BY s.starts_at'
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    // Showtimes in [from, to) with booked-seat counts, for the admin calendar.
    public function inRange(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.showtimeID, s.movieID, s.theaterID, s.starts_at, s.price,
                    m.title, t.name AS theater_name, t.city,
                    COUNT(b.bookingID) AS booked_seats
             FROM showtimes s
             JOIN movies m ON m.movieID = s.movieID
             JOIN theaters t ON t.theaterID = s.theaterID
             LEFT JOIN bookings b ON b.showtimeID = s.showtimeID
             WHERE s.starts_at >= ? AND s.starts_at < ?
             GROUP BY s.showtimeID, s.movieID, s.theaterID, s.starts_at, s.price,
                      m.title, t.name, t.city
             ORDER BY s.starts_at, t.name, m.title'
        );
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }

    public function theaterOptions(): array
    {
        return $this->pdo->query('SELECT theaterID, name, city FROM theaters ORDER BY name')->fetchAll();
    }

    // Reports whether the movie and theater exist and the start time is in the future.
    public function checkScheduling(int $movieId, int $theaterId, string $startsAt): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM movies WHERE movieID = ?) AS movie_exists,
                EXISTS(SELECT 1 FROM theaters WHERE theaterID = ?) AS theater_exists,
                (? > NOW()) AS is_future'
        );
        $stmt->execute([$movieId, $theaterId, $startsAt]);
        return $stmt->fetch();
    }

    public function hasConflict(int $theaterId, string $startsAt): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM showtimes WHERE theaterID = ? AND starts_at = ? LIMIT 1');
        $stmt->execute([$theaterId, $startsAt]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $movieId, int $theaterId, string $startsAt, int $price): void
    {
        $this->pdo->prepare('INSERT INTO showtimes (movieID, theaterID, starts_at, price) VALUES (?, ?, ?, ?)')
            ->execute([$movieId, $theaterId, $startsAt, $price]);
    }
}
