-- Demo schedule for the next 14 days, equivalent to db/seed_showtimes.php.
-- Run this in phpMyAdmin (or any MySQL client) against the target database.
-- Requires movies and theaters to already exist.
-- Idempotent: existing movie/theater/time combinations are skipped.

INSERT INTO `showtimes` (movieID, theaterID, starts_at, price)
SELECT DISTINCT m.movieID, t.theaterID, slot.starts_at, 90
FROM (
    SELECT movieID,
           MOD(movieID, (SELECT COUNT(*) FROM `theaters`)) AS startIdx
    FROM `movies`
) m
JOIN (
    -- 0-based position of each theater when ordered by theaterID
    SELECT t1.theaterID,
           (SELECT COUNT(*) FROM `theaters` t2 WHERE t2.theaterID < t1.theaterID) AS idx
    FROM `theaters` t1
) t ON t.idx IN (m.startIdx, MOD(m.startIdx + 1, (SELECT COUNT(*) FROM `theaters`)))
CROSS JOIN (
    SELECT TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL d.n DAY), s.t) AS starts_at
    FROM (
        SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
        UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
        UNION ALL SELECT 13 UNION ALL SELECT 14
    ) d
    CROSS JOIN (
        SELECT '18:00:00' AS t UNION ALL SELECT '21:00:00'
    ) s
) slot
WHERE NOT EXISTS (
    SELECT 1 FROM `showtimes` x
    WHERE x.movieID = m.movieID
      AND x.theaterID = t.theaterID
      AND x.starts_at = slot.starts_at
);
