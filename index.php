<?php
include 'includes/config.php';

$page_title = 'Home';
include 'includes/header.php';
?>

<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 5rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 L0,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
    opacity: 0.1;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto 3rem;
    position: relative;
    z-index: 1;
}

.hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    animation: fadeInUp 0.8s ease;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.hero-subtitle {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
    animation: fadeInUp 0.8s ease 0.2s both;
}

.hero-description {
    font-size: 1.1rem;
    margin-bottom: 2rem;
    opacity: 0.8;
    animation: fadeInUp 0.8s ease 0.4s both;
}

.hero-buttons {
    animation: fadeInUp 0.8s ease 0.6s both;
}

.hero-buttons .btn {
    margin: 0 0.5rem;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: 50px;
}

.btn-outline-light {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.btn-outline-light:hover {
    background: white;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 4rem;
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.8s ease 0.8s both;
}

.stat-item {
    text-align: center;
}

.stat-item i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: rgba(255,255,255,0.9);
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.3rem;
}

.stat-label {
    opacity: 0.8;
    font-size: 0.9rem;
}

/* Features Section */
.features-section {
    padding: 5rem 2rem;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: #333;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: center;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.feature-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.feature-icon i {
    font-size: 3rem;
    color: white;
}

.feature-card h3 {
    margin-bottom: 1rem;
    color: #333;
    font-size: 1.5rem;
}

.feature-card p {
    color: #666;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.feature-list {
    list-style: none;
    padding: 0;
    text-align: left;
}

.feature-list li {
    margin-bottom: 0.8rem;
    color: #555;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feature-list li i {
    color: #27ae60;
    font-size: 1rem;
}

/* How It Works Section */
.how-it-works {
    padding: 5rem 2rem;
    background: white;
}

.steps-container {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.step-item {
    flex: 1;
    min-width: 200px;
    text-align: center;
    position: relative;
}

.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 40px;
    right: -30px;
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

@media (max-width: 768px) {
    .step-item:not(:last-child)::after {
        display: none;
    }
}

.step-number {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto 1.5rem;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.step-content h3 {
    margin-bottom: 0.5rem;
    color: #333;
    font-size: 1.2rem;
}

.step-content p {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
}

/* Testimonials Section */
.testimonials-section {
    padding: 5rem 2rem;
    background: #f8f9fa;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.testimonial-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.testimonial-content {
    margin-bottom: 1.5rem;
    position: relative;
}

.testimonial-content i {
    font-size: 2rem;
    color: #667eea;
    opacity: 0.3;
    margin-bottom: 1rem;
}

.testimonial-content p {
    color: #555;
    line-height: 1.6;
    font-style: italic;
    font-size: 1rem;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.testimonial-author img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #667eea;
}

.testimonial-author h4 {
    margin: 0;
    color: #333;
    font-size: 1rem;
}

.testimonial-author p {
    margin: 0;
    color: #666;
    font-size: 0.85rem;
}

/* CTA Section */
.cta-section {
    padding: 5rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-buttons .btn {
    margin: 0 0.5rem;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: 50px;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .hero-buttons .btn {
        display: block;
        margin: 1rem 0;
        width: 100%;
    }
    
    .cta-buttons .btn {
        display: block;
        margin: 1rem 0;
        width: 100%;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .feature-card {
        padding: 1.5rem;
    }
    
    .step-number {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="hero-content">
        <h1>Welcome to FleetFlow</h1>
        <p class="hero-subtitle">Modern Fleet Management System for Efficient Logistics</p>
        <p class="hero-description">Connect with drivers, track vehicles in real-time, and manage your fleet operations seamlessly.</p>
        
        <?php if(!isLoggedIn()): ?>
        <div class="hero-buttons">
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus"></i> Register Now
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
        <?php else: ?>
        <div class="hero-buttons">
            <a href="<?php echo $_SESSION['user_role']; ?>/dashboard.php" class="btn btn-primary btn-lg">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="hero-stats">
        <div class="stat-item">
            <i class="fas fa-users"></i>
            <span class="stat-number">500+</span>
            <span class="stat-label">Happy Clients</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-truck"></i>
            <span class="stat-number">200+</span>
            <span class="stat-label">Active Vehicles</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-road"></i>
            <span class="stat-number">50K+</span>
            <span class="stat-label">Trips Completed</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-star"></i>
            <span class="stat-number">4.8</span>
            <span class="stat-label">User Rating</span>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="features-section" id="features">
    <h2 class="section-title">Why Choose FleetFlow?</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <h3>For Admins</h3>
            <p>Complete fleet oversight, driver management, real-time analytics, and comprehensive reporting tools.</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Fleet Monitoring</li>
                <li><i class="fas fa-check-circle"></i> Driver Management</li>
                <li><i class="fas fa-check-circle"></i> Financial Reports</li>
                <li><i class="fas fa-check-circle"></i> Maintenance Tracking</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h3>For Drivers</h3>
            <p>Real-time ride requests, GPS navigation, earnings tracking, and flexible work schedule.</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Ride Requests</li>
                <li><i class="fas fa-check-circle"></i> Live Navigation</li>
                <li><i class="fas fa-check-circle"></i> Earnings Dashboard</li>
                <li><i class="fas fa-check-circle"></i> Trip History</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-user"></i>
            </div>
            <h3>For Passengers</h3>
            <p>Easy booking, live tracking, multiple payment options, and ride history management.</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Quick Booking</li>
                <li><i class="fas fa-check-circle"></i> Live Tracking</li>
                <li><i class="fas fa-check-circle"></i> Multiple Payments</li>
                <li><i class="fas fa-check-circle"></i> Ride History</li>
            </ul>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="how-it-works" id="how-it-works">
    <h2 class="section-title">How It Works</h2>
    
    <div class="steps-container">
        <div class="step-item">
            <div class="step-number">1</div>
            <div class="step-content">
                <h3>Register Account</h3>
                <p>Sign up as a passenger, driver, or administrator in just 2 minutes</p>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>Book or Accept Rides</h3>
                <p>Passengers book rides, drivers accept requests based on availability</p>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3>Track in Real-time</h3>
                <p>Live GPS tracking for complete transparency and peace of mind</p>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number">4</div>
            <div class="step-content">
                <h3>Complete & Rate</h3>
                <p>Complete trips safely and rate your experience with drivers</p>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="testimonials-section">
    <h2 class="section-title">What Our Users Say</h2>
    
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <div class="testimonial-content">
                <i class="fas fa-quote-left"></i>
                <p>"FleetFlow has revolutionized how we manage our delivery fleet. The real-time tracking and analytics are incredible! We've improved our efficiency by 40%."</p>
            </div>
            <div class="testimonial-author">
                <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="John Smith">
                <div>
                    <h4>John Smith</h4>
                    <p>Fleet Manager, XYZ Logistics</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial-card">
            <div class="testimonial-content">
                <i class="fas fa-quote-left"></i>
                <p>"As a driver, I love how easy it is to find rides and track my earnings. The app is intuitive and reliable. I've doubled my income since joining!"</p>
            </div>
            <div class="testimonial-author">
                <img src="https://randomuser.me/api/portraits/women/2.jpg" alt="Sarah Johnson">
                <div>
                    <h4>Sarah Johnson</h4>
                    <p>Professional Driver</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial-card">
            <div class="testimonial-content">
                <i class="fas fa-quote-left"></i>
                <p>"Booking rides is so simple, and I love being able to track my driver in real-time. Great service and competitive prices. Highly recommended!"</p>
            </div>
            <div class="testimonial-author">
                <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="Mike Wilson">
                <div>
                    <h4>Mike Wilson</h4>
                    <p>Regular Passenger</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <div class="cta-content">
        <h2>Ready to Get Started?</h2>
        <p>Join thousands of satisfied users and experience the future of fleet management</p>
        <?php if(!isLoggedIn()): ?>
        <div class="cta-buttons">
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
        <?php else: ?>
        <div class="cta-buttons">
            <a href="<?php echo $_SESSION['user_role']; ?>/dashboard.php" class="btn btn-primary btn-lg">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>