<?php

// Persistence for seat bookings.
final class BookingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.title, s.starts_at, s.showtimeID, t.name, t.city, b.seat_row, b.seat_number,
                    b.price_paid, b.created_at, b.booking_ref
             FROM bookings b
             JOIN showtimes s ON s.showtimeID = b.showtimeID
             JOIN movies m ON m.movieID = s.movieID
             JOIN theaters t ON t.theaterID = s.theaterID
             WHERE b.userID = ?
             ORDER BY b.created_at DESC, b.bookingID DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Returns a lookup keyed by "row-number" for seats already booked.
    public function takenSeats(int $showtimeId): array
    {
        $stmt = $this->pdo->prepare('SELECT seat_row, seat_number FROM bookings WHERE showtimeID = ?');
        $stmt->execute([$showtimeId]);
        $taken = [];
        foreach ($stmt as $row) {
            $taken[$row['seat_row'] . '-' . $row['seat_number']] = true;
        }
        return $taken;
    }

    // Inserts one row per [row, number] seat under a shared booking reference.
    public function insertSeats(int $userId, int $showtimeId, string $bookingRef, array $seats, int $price): void
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO bookings (userID, showtimeID, booking_ref, seat_row, seat_number, price_paid)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($seats as [$row, $number]) {
            $insert->execute([$userId, $showtimeId, $bookingRef, $row, $number, $price]);
        }
    }
}
