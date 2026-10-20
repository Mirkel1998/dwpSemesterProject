<?php

// Read-side queries for a single movie, its cast, and movie pick-lists.
final class MovieRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(?int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT movieID, title, release_date, rating FROM movies WHERE movieID = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function cast(int $movieId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.firstName, c.lastName, r.relationToMovie
             FROM castMovieRelation r JOIN `cast` c ON c.castID = r.castID
             WHERE r.movieID = ? ORDER BY r.relationToMovie, c.lastName'
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    public function options(): array
    {
        return $this->pdo->query('SELECT movieID, title FROM movies ORDER BY title')->fetchAll();
    }
}
