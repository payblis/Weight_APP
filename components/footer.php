    </main>
    
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>À propos</h4>
                <p><?php echo APP_NAME; ?> - Votre compagnon de perte de poids intelligent</p>
            </div>
            
            <div class="footer-section">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="<?php echo APP_URL; ?>/about.php">À propos</a></li>
                    <li><a href="<?php echo APP_URL; ?>/contact.php">Contact</a></li>
                    <li><a href="<?php echo APP_URL; ?>/privacy.php">Confidentialité</a></li>
                    <li><a href="<?php echo APP_URL; ?>/terms.php">Conditions d'utilisation</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Suivez-nous</h4>
                <div class="social-links">
                    <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <?php if (isLoggedIn()): ?>
    <script src="<?php echo APP_URL; ?>/assets/js/dashboard.js"></script>
    <?php endif; ?>
</body>
</html> 