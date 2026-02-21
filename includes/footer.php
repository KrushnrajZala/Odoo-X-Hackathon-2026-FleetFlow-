    </main>
    
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4><i class="fas fa-truck"></i> FleetFlow</h4>
                <p>Modern fleet management system for efficient logistics and transportation. Connecting drivers with passengers seamlessly.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>privacy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>terms.php"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
                    <li><a href="<?php echo SITE_URL; ?>faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Contact Info</h4>
                <ul>
                    <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                    <li><i class="fas fa-envelope"></i> support@fleetflow.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Business Avenue, Suite 100<br>New York, NY 10001</li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Business Hours</h4>
                <ul>
                    <li><i class="fas fa-clock"></i> Monday - Friday: 9:00 AM - 6:00 PM</li>
                    <li><i class="fas fa-clock"></i> Saturday: 10:00 AM - 4:00 PM</li>
                    <li><i class="fas fa-clock"></i> Sunday: Closed</li>
                    <li><i class="fas fa-headset"></i> 24/7 Emergency Support</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> FleetFlow. All rights reserved. | Designed with <i class="fas fa-heart text-danger"></i> for better logistics</p>
        </div>
    </footer>
    
    <!-- JavaScript Files -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
    
    <script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');
        const userDropdownToggle = document.getElementById('userDropdownToggle');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        if (navbarToggle) {
            navbarToggle.addEventListener('click', function() {
                navbarMenu.classList.toggle('active');
            });
        }
        
        // User dropdown toggle
        if (userDropdownToggle) {
            userDropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.classList.toggle('show');
            });
        }
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (userDropdownToggle && !userDropdownToggle.contains(e.target) && 
                dropdownMenu && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>