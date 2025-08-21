<?php
// CHRONONAV_WEBZD/templates/footer.php
?>
        </main> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php
// Close the database connection ONLY ONCE at the very end of the page rendering.
// Check if $conn exists and is an object before trying to close it.
if (isset($conn) && is_object($conn) && method_exists($conn, 'close') && $conn->ping()) {
    $conn->close();
}
?>
</body>
</html>


