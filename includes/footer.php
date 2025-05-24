<!-- footer.php -->
</main>

<footer>
    <div class="footer-container">
        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul class="footer-links">

            <?php if (isset($_SESSION['member_id'])): ?>
                        <li><a href="../dashboard.php"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="../login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-chevron-right"></i> Register</a></li>
                        <li><a href="../donate_book.php"><i class="fas fa-chevron-right"></i> Donate Books</a></li>
                    <?php endif; ?>

            </ul>
        </div>

        <div class="footer-column">
            <h3>Library Hours</h3>
            <ul class="footer-links">
                <li><i class="fas fa-clock"></i> Monday - Friday: 8:00 AM - 8:00 PM</li>
                <li><i class="fas fa-clock"></i> Saturday: 10:00 AM - 6:00 PM</li>
                <li><i class="fas fa-clock"></i> Sunday: 12:00 PM - 5:00 PM</li>
                <li><i class="fas fa-info-circle"></i> Closed on Public Holidays</li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <ul class="footer-contact">
                <li>
                    <i class="fas fa-map-marker-alt"></i>
                    <div>123 Library Street<br>Book City, BC 12345</div>
                </li>
                <li>
                    <i class="fas fa-phone"></i>
                    <div>(123) 456-7890</div>
                </li>
                <li>
                    <i class="fas fa-envelope"></i>
                    <div>contact@librarysystem.com</div>
                </li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Library Management System. All Rights Reserved.</p>
        <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
    </div>
</footer>

<script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('mainNav').classList.toggle('active');
    });
</script>
</body>

</html>