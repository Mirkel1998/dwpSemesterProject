(function () {
    "use strict";

    var grid = document.querySelector("[data-movie-grid]");
    if (!grid) {
        return;
    }

    var cards = Array.prototype.slice.call(grid.querySelectorAll(".movie-card"));
    var genreButtons = Array.prototype.slice.call(document.querySelectorAll("[data-genre]"));
    var search = document.querySelector("[data-movie-search]");
    var sort = document.querySelector("[data-movie-sort]");
    var resultCount = document.querySelector("[data-result-count]");
    var emptyResults = document.querySelector("[data-empty-results]");
    var requestedGenre = new URLSearchParams(window.location.search).get("genre");
    var selectedGenre = genreButtons.find(function (button) {
        return button.dataset.genre === requestedGenre;
    });
    var activeGenre = selectedGenre ? selectedGenre.dataset.genre : "all";

    genreButtons.forEach(function (button) {
        var isActive = button.dataset.genre === activeGenre;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", String(isActive));
    });

    function applyFilters() {
        var query = search.value.trim().toLocaleLowerCase();
        var visibleCount = 0;

        cards.forEach(function (card) {
            var genres = card.dataset.genres.split("|");
            var searchable = (card.dataset.title + " " + genres.join(" ")).toLocaleLowerCase();
            var matchesGenre = activeGenre === "all" || genres.indexOf(activeGenre) !== -1;
            var matchesSearch = query === "" || searchable.indexOf(query) !== -1;
            var visible = matchesGenre && matchesSearch;

            card.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        resultCount.textContent = visibleCount + (visibleCount === 1 ? " film" : " films");
        if (activeGenre !== "all" && visibleCount === 0) {
            emptyResults.textContent = "No " + activeGenre + " films are in the programme yet. Try another genre.";
        } else {
            emptyResults.textContent = "No films match those filters. Try another search or genre.";
        }
        emptyResults.hidden = visibleCount !== 0;
    }

    genreButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            activeGenre = button.dataset.genre;
            genreButtons.forEach(function (item) {
                var isActive = item === button;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-pressed", String(isActive));
            });
            applyFilters();
        });
    });

    search.addEventListener("input", applyFilters);
    sort.addEventListener("change", function () {
        var mode = sort.value;
        cards.sort(function (a, b) {
            if (mode === "title") {
                return a.dataset.title.localeCompare(b.dataset.title);
            }
            if (mode === "newest") {
                return Number(b.dataset.year) - Number(a.dataset.year);
            }
            if (mode === "rating") {
                return Number(b.dataset.rating) - Number(a.dataset.rating);
            }
            return Number(b.dataset.featured) - Number(a.dataset.featured);
        });
        cards.forEach(function (card) {
            grid.appendChild(card);
        });
        applyFilters();
    });

    applyFilters();
})();
