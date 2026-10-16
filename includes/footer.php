<!-- Shared page closing markup, paired with the opening tags in header.php. -->
<?php if ($mascotCount > 0): ?>
<aside class="mascot-trail mascot-trail--<?= e($mascotPage) ?>" aria-hidden="true">
    <?php for ($mascotIndex = 0; $mascotIndex < $mascotCount; $mascotIndex++): ?>
    <img src="assets/mascot.svg" alt="" loading="lazy" decoding="async">
    <?php endfor; ?>
</aside>
<?php endif; ?>
</main>
<footer class="footer">PIXEL CINEMA &bull; YOUR NEIGHBORHOOD CINEMA, IN PIXELS</footer>
</body>
</html>
