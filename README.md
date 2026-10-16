# dwpSemesterProject
1. semester DWP project PBA-WD

## Optional navigation modules

The shared navbar links to Home, Movies, My Account, News, FAQ, and Contact.
Admin is only shown to signed-in administrators. News and FAQ are standalone
pages (`news.php` and `faq.php`). Contact submissions are stored for review in
the admin-only Contact messages inbox.

Home (`index.php`) is a movie-night landing page. Movies (`movies.php`) is a
separate catalogue with genre filters, search, and sorting. To add the Sci-Fi
category and tag the matching sample films in an existing database, run:

```sh
/Applications/XAMPP/bin/php db/migrate_movie_genres.php
```

To enable contact-message storage in an existing database, run:

```sh
/Applications/XAMPP/bin/php db/migrate_contact_messages.php
```

The News and FAQ pages can be removed by deleting their page files and navbar
links. Contact is isolated in `contact.php`, `contact_messages.php`, and
`db/migrate_contact_messages.php`; remove those pieces, its navbar/admin links,
and the `contact_messages` table definition from `db/CreateMovieDB.sql` if that
module is not needed.
