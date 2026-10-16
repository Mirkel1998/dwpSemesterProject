<?php
// Shared movie and genre data for the home and catalogue pages.
function movie_catalog_data(PDO $pdo): array
{
    $movies = $pdo->query(
        'SELECT m.movieID, m.title, m.release_date, m.rating,
                GROUP_CONCAT(DISTINCT g.genreName ORDER BY g.genreName SEPARATOR ", ") AS genres,
                (SELECT MIN(s.starts_at) FROM showtimes s WHERE s.movieID = m.movieID AND s.starts_at > NOW()) AS next_show
         FROM movies m
         LEFT JOIN movieGenreRelation r ON r.movieID = m.movieID
         LEFT JOIN genre g ON g.genreID = r.genreID
         GROUP BY m.movieID, m.title, m.release_date, m.rating
         ORDER BY next_show IS NULL, next_show, m.rating DESC, m.title'
    )->fetchAll();

    $genres = $pdo->query(
        "SELECT genreName FROM genre
         ORDER BY CASE genreName
             WHEN 'Horror' THEN 1
             WHEN 'Action' THEN 2
             WHEN 'Sci-Fi' THEN 3
             WHEN 'Romance' THEN 4
             WHEN 'Comedy' THEN 5
             WHEN 'Drama' THEN 6
             ELSE 7
         END, genreName"
    )->fetchAll(PDO::FETCH_COLUMN);

    return [$movies, $genres];
}
