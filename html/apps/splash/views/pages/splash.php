<?
$night_mode = (App::getInstance()->get('day_night_mode') == 'night');
?>
<link href='https://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'>

<? render('components/header/header.php'); ?>

<div id="promo-banner">
  <div class="banner-content">
    <? // echo App::getInstance('neighborhub')->render('pages/public.splash.php'); 
    ?>
  </div>
</div>


<div id="splash" data-component="splash-page">

  <div class="animated-logo-container">
    <div class="animated-logo">
      <div id="mediabrain-icon">
        <?php
        render('components/animated_logo.php');
        ?>
      </div>
    </div>
  </div>

  <div id='title'>
    <!-- CACHE_CLEAR_v2.0 -->

    <span id="site-name-span">
      Mediabrain </span>
    <br>
    <div class="version">
      App Services<br /><span class="custom-badge">Written From Scratch <br />With Modern Technology</span>
    </div>
  </div>

</div>
<!--  -->
<?php
if (get_var('starfield', false)) {
  echo '
    <div id="stars"></div>
    <div id="stars2"></div>
    <div id="stars3"></div>';
}
?>

<?
/** 
 *  Preload Star Trek Themed Audio
 * */
include_theme_audio('startrek', ['Star Trek - Hail', 'helm_engage_clean', 'computer_activate', 'processing2', 'ds9intercom']);
?>
<? /*
<!-- Professional Career Command Center Section -->
<!-- CACHE_BUST_v3.0_CAREER_SECTION_ADDED -->
<div id="career-command-center">
  <div class="starfleet-border">
    <div class="command-header">
      <h2 class="command-title">🚀 TECHNICAL OPERATIONS CENTER</h2>
      <p class="command-subtitle">Full-Stack Development Division • Engineering Excellence Protocol</p>
      <div class="stardate">Stardate: <?= date('Y.m.d'); ?> • Status: ACTIVELY SEEKING OPPORTUNITIES</div>
    </div>
    <div class="technical-showcase">
      <div class="showcase-grid">
        <div class="tech-panel">
          <div class="panel-header">
            <span class="panel-icon">⚡</span>
            <h3>FRONTEND SYSTEMS</h3>
          </div>
          <div class="tech-details">
            • Advanced JavaScript (ES6+) & TypeScript<br>
            • React.js, Vue.js, Angular Frameworks<br>
            • CSS3, SASS, Responsive Design<br>
            • Progressive Web Apps (PWA)<br>
            • Modern Build Tools (Webpack, Vite)
          </div>
        </div>
        <div class="tech-panel">
          <div class="panel-header">
            <span class="panel-icon">🛠️</span>
            <h3>BACKEND ARCHITECTURE</h3>
          </div>
          <div class="tech-details">
            • PHP 8+, Node.js, Python<br>
            • RESTful APIs & GraphQL<br>
            • MySQL, PostgreSQL, MongoDB<br>
            • Redis, Elasticsearch<br>
            • Microservices Architecture
          </div>
        </div>
        <div class="tech-panel">
          <div class="panel-header">
            <span class="panel-icon">☁️</span>
            <h3>CLOUD OPERATIONS</h3>
          </div>
          <div class="tech-details">
            • Docker Containerization<br>
            • Google Cloud Platform (GCP)<br>
            • AWS Services & Azure<br>
            • CI/CD Pipelines<br>
            • Infrastructure as Code
          </div>
        </div>
        <div class="tech-panel">
          <div class="panel-header">
            <span class="panel-icon">🔐</span>
            <h3>SECURITY & PERFORMANCE</h3>
          </div>
          <div class="tech-details">
            • OAuth 2.0 & JWT Authentication<br>
            • HTTPS/SSL Implementation<br>
            • Rate Limiting & CSRF Protection<br>
            • Performance Optimization<br>
            • Security Headers & Best Practices
          </div>
        </div>
      </div>
      <div class="mission-statement">
        <div class="mission-content">
          <h3>🎯 PRIME DIRECTIVE</h3>
          <p>To boldly build scalable, secure, and innovative web applications that push the boundaries of what's possible. This platform demonstrates advanced full-stack capabilities including containerized deployment, reverse proxy configuration, dynamic JavaScript loading, and comprehensive security implementations.</p>
          <div class="current-mission">
            <strong>CURRENT MISSION:</strong> Seeking challenging opportunities to contribute enterprise-level development expertise to forward-thinking organizations.
          </div>
          <div class="command-buttons">
            <button class="starfleet-btn secondary" onclick="showTechnicalSpecs()">
              📋 VIEW TECHNICAL SPECIFICATIONS
            </button>
            <button class="starfleet-btn secondary" onclick="play('audio/star trek sounds/computer_activate.mp3'); window.open('https://github.com/jeffdavid8', '_blank')">
              🔗 ACCESS CODE REPOSITORY
            </button>
            <button class="starfleet-btn quaternary" onclick="initiateContact()">
              📡 INITIATE CONTACT PROTOCOL
            </button>
            <button class="starfleet-btn quaternary" onclick="showServicePricing()">
              💰 VIEW SERVICE PACKAGES
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Success Stories & Testimonials Section -->
<div id="success-stories" class="testimonials-section">
  <div class="testimonials-container">
    <div class="section-header">
      <h2 class="testimonials-title">🌟 CLIENT SUCCESS MISSIONS</h2>
      <p class="section-subtitle">Real results from real projects - building the future one application at a time</p>
    </div>

    <div class="testimonials-grid">
      <div class="testimonial-card">
        <div class="testimonial-content">
          <div class="quote-icon">"</div>
          <p>"The genealogy platform Jeff built for our family has been incredible. The social login integration and permission system allow us to safely share our family history with relatives worldwide. The interface is intuitive and the security features give us complete peace of mind."</p>
          <div class="client-info">
            <strong>Sarah M.</strong>
            <span>Family History Project</span>
            <div class="project-type">Custom Genealogy Platform</div>
          </div>
        </div>
        <div class="project-highlights">
          ✅ OAuth Social Authentication<br>
          ✅ Granular Permission System<br>
          ✅ Responsive Family Interface
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-content">
          <div class="quote-icon">"</div>
          <p>"Our recipe management system has transformed how our culinary team operates. The cook mode interface and ingredient tracking features are exactly what we needed. The admin dashboard makes managing our extensive recipe database effortless."</p>
          <div class="client-info">
            <strong>Chef Antonio R.</strong>
            <span>Restaurant Management</span>
            <div class="project-type">Culinary Operations Platform</div>
          </div>
        </div>
        <div class="project-highlights">
          ✅ Interactive Cook Mode<br>
          ✅ Inventory Management<br>
          ✅ Multi-user Recipe Sharing
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-content">
          <div class="quote-icon">"</div>
          <p>"The modular architecture Jeff implemented allows us to easily add new features as our organization grows. The hook system is brilliant - each department can contribute their own admin interfaces without affecting the core system. Exceptional work!"</p>
          <div class="client-info">
            <strong>David K.</strong>
            <span>Enterprise Solutions</span>
            <div class="project-type">Modular Business Platform</div>
          </div>
        </div>
        <div class="project-highlights">
          ✅ Drupal-style Hook Architecture<br>
          ✅ Scalable Admin Framework<br>
          ✅ Department-specific Modules
        </div>
      </div>
    </div>

    <div class="portfolio-showcase">
      <h3>🚀 LIVE DEMONSTRATION PLATFORM</h3>
      <p>This application showcases advanced development capabilities:</p>
      <div class="demo-features">
        <div class="demo-feature">
          <span class="feature-icon">🛡️</span>
          <strong>Authentication System:</strong> Try the admin login with OAuth integration
        </div>
        <div class="demo-feature">
          <span class="feature-icon">🌤️</span>
          <strong>Real-time APIs:</strong> Weather data with dynamic location detection
        </div>
        <div class="demo-feature">
          <span class="feature-icon">📱</span>
          <strong>Responsive Design:</strong> Optimized experience across all devices
        </div>
        <div class="demo-feature">
          <span class="feature-icon">🔧</span>
          <strong>Modular Architecture:</strong> Hook-based system for scalable development
        </div>
      </div>

      <div class="portfolio-cta">
        <p><strong>Ready to see these technologies in action for your project?</strong></p>
        <button class="starfleet-btn secondary" onclick="showServicePricing()">
          🚀 START YOUR PROJECT TODAY
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Technical Specifications Modal -->
<? render('components/modals/tech_specs_modal.php'); ?>
*/ ?>

<!-- Contact Modal -->
<div id="contactModal" class="star-trek-modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="modal-close" onclick="window.history.back(); return false;">&times;</span>
      <h2>📡 MEDIABRAIN COMMUNICATIONS</h2>
    </div>
    <div class="modal-body">
      <div class="officer-profile">
        <h4>🖖 CHIEF DEVELOPMENT OFFICER</h4>
        <div class="officer-name">Jeff David</div>
        <div class="officer-rank">Full Stack Developer</div>
      </div>

      <p>Ready to discuss your next development project or explore collaboration opportunities?</p>

      <div class="contact-options">
        <? /*https://m.me/mediabrainllc
        <div class="contact-item">
          <strong>📧 Primary Communications:</strong>
          <a href="mailto:virtualimmortal@gmail.com" class="contact-link">virtualimmortal@gmail.com</a>
        </div>
        */ ?>
        <? /*
        */ ?>
        <div class="contact-item">
          <strong>📧 Primary Communications:</strong>
          <a target="_blank" href="https://m.me/mediabrainllc" class="contact-link">m.me/mediabrainllc</a>
        </div>
        <div class="contact-item">
          <strong>💼 Professional Network:</strong>
          <a href="https://linkedin.com/in/jeffdavid8" target="_blank" class="contact-link">linkedin.com/in/jeffdavid8</a>
        </div>
        <div class="contact-item">
          <strong>🌐 Code Repository:</strong>
          <a href="https://github.com/jeffdavid8" target="_blank" class="contact-link">github.com/jeffdavid8</a>
        </div>
        <div class="contact-item">
          <strong>🚀 Portfolio Access:</strong>
          <span class="contact-link">mediabrain.app</span> (Live Demo Platform)
        </div>
      </div>

      <div class="specialties">
        <h4>🎯 SPECIALIZED CAPABILITIES</h4>
        <div class="specialty-grid">
          <span class="specialty-tag">• Enterprise Web Applications</span>
          <span class="specialty-tag">• API Development & Integration</span>
          <span class="specialty-tag">• Cloud Infrastructure</span>
          <span class="specialty-tag">• Database Architecture</span>
          <span class="specialty-tag">• Security Implementation</span>
          <span class="specialty-tag">• Performance Optimization</span>
        </div>
      </div>

      <div class="availability">
        <strong>🟢 STATUS:</strong> Available for immediate deployment on exciting projects!
      </div>

      <div class="engagement-cta" style="margin-bottom: 2em;">
        <em>"Let's build something extraordinary together"</em>
      </div>

      <? render('components/tech_specs_section.php'); ?>

    </div>
  </div>
</div>

<? /*
<!-- LCARS Contact Console Section -->
<div id="lcars-contact-section" style="margin: 40px auto; max-width: 520px;">
  <?php render('components/lcars-contact.php'); ?>
</div>
*/ ?>

<style>
  h3 {
    font-size: 1.2em;
  }

  #career-command-center {
    background: linear-gradient(135deg, #000814 0%, #001d3d 30%, #003566 70%, #0077b6 100%);
    color: #caf0f8;
    padding: 60px 20px;
    margin-top: 50px;
    font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
    position: relative;
    overflow: hidden;
  }

  #career-command-center::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
      radial-gradient(circle at 20% 80%, rgba(0, 180, 216, 0.15) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(144, 224, 239, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 40% 40%, rgba(72, 202, 228, 0.08) 0%, transparent 60%);
    pointer-events: none;
  }

  .starfleet-border {
    border: 2px solid #48cae4;
    border-radius: 12px;
    padding: 40px;
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    background: rgba(0, 8, 20, 0.6);
    backdrop-filter: blur(10px);
    box-shadow:
      0 0 30px rgba(72, 202, 228, 0.3),
      inset 0 1px 0 rgba(144, 224, 239, 0.2);
  }

  .command-header {
    text-align: center;
    margin-bottom: 40px;
  }

  .command-title {
    font-size: 2.5em;
    color: #90e0ef;
    text-shadow:
      0 0 10px #48cae4,
      0 0 20px #0077b6,
      0 0 30px #023e8a;
    margin: 0;
    letter-spacing: 2px;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 700;
  }

  .command-subtitle {
    font-size: 1.2em;
    color: #00b4d8;
    margin: 10px 0;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 400;
  }

  .stardate {
    color: #90e0ef;
    font-size: 0.9em;
    font-family: 'Consolas', 'Monaco', monospace;
    opacity: 0.8;
  }

  .showcase-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
  }

  .tech-panel {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.12) 0%, rgba(72, 202, 228, 0.08) 100%);
    border: 1px solid #0077b6;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
  }

  .tech-panel:hover {
    cursor: pointer;
  }

  .tech-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(144, 224, 239, 0.1), transparent);
    transition: left 0.6s ease;
  }

  .tech-panel:hover::before {
    left: 100%;
  }

  .tech-panel:hover {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.2) 0%, rgba(72, 202, 228, 0.15) 100%);
    transform: translateY(-8px);
    box-shadow:
      0 8px 25px rgba(0, 119, 182, 0.4),
      0 0 20px rgba(72, 202, 228, 0.3);
    border-color: #48cae4;
  }

  .panel-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
  }

  .panel-icon {
    font-size: 1.5em;
    margin-right: 10px;
  }

  .panel-header h3 {
    color: #90e0ef;
    margin: 0;
    font-size: 1.1em;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 600;
    text-shadow: 0 0 8px rgba(144, 224, 239, 0.5);
  }

  .tech-details {
    line-height: 1.6;
    color: #caf0f8;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 400;
  }

  .mission-statement {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.15) 0%, rgba(2, 62, 138, 0.2) 100%);
    border: 1px solid #0077b6;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .mission-statement::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #48cae4, #0077b6, #023e8a, #00b4d8);
    background-size: 300% 300%;
    animation: borderShimmer 4s ease-in-out infinite;
    border-radius: 12px;
    z-index: -1;
  }

  @keyframes borderShimmer {

    0%,
    100% {
      background-position: 0% 50%;
    }

    50% {
      background-position: 100% 50%;
    }
  }

  .mission-content h3 {
    color: #90e0ef;
    margin-bottom: 15px;
    text-shadow:
      0 0 8px #48cae4,
      0 0 16px #0077b6;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 600;
  }

  .mission-content p {
    line-height: 1.7;
    margin-bottom: 20px;
    color: #caf0f8;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-weight: 400;
    font-size: 1.05em;
  }

  .current-mission {
    background: linear-gradient(135deg, rgba(144, 224, 239, 0.2) 0%, rgba(72, 202, 228, 0.15) 100%);
    border-left: 4px solid #48cae4;
    padding: 15px;
    margin: 20px 0;
    font-size: 1.1em;
    border-radius: 0 8px 8px 0;
    box-shadow: 0 4px 15px rgba(72, 202, 228, 0.2);
  }

  .command-buttons {
    display: flex;
    /* flex-wrap: wrap; */
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
  }

  .starfleet-btn {
    padding: 12px 24px;
    border: 2px solid;
    border-radius: 5px;
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-size: 0.9em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .starfleet-btn.primary {
    background: linear-gradient(135deg, rgba(0, 119, 182, 0.3) 0%, rgba(72, 202, 228, 0.2) 100%);
    border-color: #0077b6;
    color: #90e0ef;
    position: relative;
    overflow: hidden;
  }

  .starfleet-btn.primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(144, 224, 239, 0.3), transparent);
    transition: left 0.6s ease;
  }

  .starfleet-btn.primary:hover::before {
    left: 100%;
  }

  .starfleet-btn.primary:hover {
    background: linear-gradient(135deg, #0077b6 0%, #48cae4 100%);
    color: #000814;
    box-shadow:
      0 0 20px rgba(72, 202, 228, 0.6),
      0 4px 15px rgba(0, 119, 182, 0.4);
    transform: translateY(-2px);
  }

  .starfleet-btn.secondary {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.3) 0%, rgba(144, 224, 239, 0.2) 100%);
    border-color: #00b4d8;
    color: #caf0f8;
  }

  .starfleet-btn.secondary:hover {
    background: linear-gradient(135deg, #00b4d8 0%, #90e0ef 100%);
    color: #000814;
    box-shadow:
      0 0 20px rgba(144, 224, 239, 0.6),
      0 4px 15px rgba(0, 180, 216, 0.4);
    transform: translateY(-2px);
  }

  .starfleet-btn.tertiary {
    background: linear-gradient(135deg, rgba(144, 224, 239, 0.3) 0%, rgba(202, 240, 248, 0.2) 100%);
    border-color: #90e0ef;
    color: #48cae4;
  }

  .starfleet-btn.tertiary:hover {
    background: linear-gradient(135deg, #90e0ef 0%, #caf0f8 100%);
    color: #000814;
    box-shadow:
      0 0 20px rgba(202, 240, 248, 0.6),
      0 4px 15px rgba(144, 224, 239, 0.4);
    transform: translateY(-2px);
  }

  /* Contact Modal Styles */
  #contactModal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
  }

  #contactModal.show {
    display: block;
    animation: modalFadeIn 0.5s ease;
  }

  #contactModal .modal-content {
    background: linear-gradient(135deg, #000814, #001d3d, #003566);
    border: 2px solid #48cae4;
    border-radius: 12px;
    margin: 2em auto;
    width: 90%;
    max-width: 600px;
    color: #caf0f8;
    position: relative;
    box-shadow:
      0 0 40px rgba(72, 202, 228, 0.5),
      0 0 80px rgba(0, 119, 182, 0.3);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #0077b6;
  }

  .modal-header h2 {
    color: #90e0ef;
    margin: 0;
    text-shadow:
      0 0 10px #48cae4,
      0 0 20px #0077b6;
    font-size: 26px;
  }

  @media only screen and (min-width: 992px) {
    .modal-header h2 {
      font-size: 30px;
    }
  }

  .modal-close {
    font-size: 28px;
    cursor: pointer;
    transition: all 0.3s;
    position: absolute;
    top: 0px;
    width: 45px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    right: 0px;
    height: 30px;
    padding-bottom: 5px;
  }

  .modal-close:hover {
    color: #eee;
    background-color: #ff5252
  }

  @media only screen and (min-width: 768px) {
    .modal-close {
      top: -10px;
      right: -10px;
    }

    .achievement-header .modal-close {
      top: 0px;
      right: 0px;
    }
  }

  .contact-options {
    margin: 20px 0;
  }

  .contact-item {
    padding: 10px 0;
    border-bottom: 1px solid rgba(0, 136, 255, 0.3);
  }

  .availability {
    background: rgba(0, 255, 136, 0.2);
    padding: 15px;
    border-radius: 5px;
    text-align: center;
    margin-top: 20px;
    color: #00ff88;
  }

  .officer-profile {
    text-align: center;
    margin-bottom: 25px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(144, 224, 239, 0.15) 0%, rgba(72, 202, 228, 0.1) 100%);
    border: 1px solid #48cae4;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
  }

  .officer-profile::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(202, 240, 248, 0.1) 50%, transparent 70%);
    transform: translateX(-100%);
    animation: officerGlow 3s ease-in-out infinite;
  }

  @keyframes officerGlow {

    0%,
    100% {
      transform: translateX(-100%);
    }

    50% {
      transform: translateX(100%);
    }
  }

  .officer-profile h4 {
    color: #90e0ef;
    margin: 0 0 10px 0;
    text-shadow: 0 0 8px #48cae4;
    font-size: 1.6em;
  }

  /* Medium devices (landscape tablets, 768px and up) */
  @media only screen and (min-width: 768px) {
    .officer-profile h4 {
      font-size: 2em;
    }
  }

  .officer-name {
    font-size: 1.8em;
    color: #caf0f8;
    font-weight: bold;
    margin: 5px 0;
    text-shadow:
      0 0 10px #90e0ef,
      0 0 20px #48cae4;
  }

  .officer-rank {
    color: #00b4d8;
    font-size: 1.2em;
    font-style: italic;
  }

  .contact-options {
    margin: 20px 0;
  }

  .contact-item {
    padding: 12px 0;
    border-bottom: 1px solid rgba(72, 202, 228, 0.3);
  }

  .contact-link {
    color: #48cae4;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .contact-link:hover {
    color: #90e0ef;
    text-shadow: 0 0 8px #90e0ef;
  }

  .specialties {
    margin: 25px 0;
    padding: 20px;
    background: linear-gradient(135deg, rgba(0, 119, 182, 0.15) 0%, rgba(72, 202, 228, 0.1) 100%);
    border: 1px solid #0077b6;
    border-radius: 10px;
  }

  .specialties h4 {
    color: #90e0ef;
    margin: 0 0 15px 0;
    text-align: center;
    text-shadow: 0 0 8px #48cae4;
    font-size: 23px;
  }

  .specialty-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 8px;
  }

  .specialty-tag {
    color: #00d4ff;
    font-size: 0.9em;
    line-height: 1.4;
  }

  .engagement-cta {
    text-align: center;
    margin-top: 20px;
    padding: 15px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 5px;
    color: #00d4ff;
    font-size: 1.1em;
    border: 1px solid rgba(0, 212, 255, 0.3);
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    font-style: italic;
    font-weight: 300;
  }

  .scroll-indicator {
    display: none;
    /* Removed per request */
  }

  /* Testimonials Section Styles */
  .testimonials-section {
    background: linear-gradient(135deg, rgba(22, 84, 176, 0.6), #1b234c5c 30%, #2a2f4a 70%, rgba(0, 8, 20, 0.6));
    color: #e3f2fd;
    padding: 80px 20px;
    margin-top: 0;
    font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
    position: relative;
    overflow: hidden;
  }

  .dayMode .testimonials-section {
    background: linear-gradient(135deg, rgb(35, 45, 59), #141e4e 30%, #2a2f4a 70%, rgba(0, 8, 20, 1))
  }

  .dayMode .testimonials-section::before {
    background:
      radial-gradient(circle at 30% 40%, rgb(0, 0, 0) 0%, transparent 50%), radial-gradient(circle at 70% 60%, rgb(43, 50, 87) 0%, transparent 50%)
  }

  .testimonials-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
      radial-gradient(circle at 30% 40%, rgba(33, 150, 243, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 70% 60%, rgba(63, 81, 181, 0.08) 0%, transparent 50%);
    pointer-events: none;
  }

  .testimonials-container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
  }

  .section-header {
    text-align: center;
    margin-bottom: 50px;
  }

  .testimonials-title {
    font-size: 2.5em;
    color: #81c784;
    text-shadow:
      0 0 10px #4caf50,
      0 0 20px #2e7d32;
    margin: 0 0 15px 0;
    letter-spacing: 2px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
  }

  .section-subtitle {
    font-size: 1.2em;
    color: #81d4fa;
    margin: 0;
    font-style: italic;
  }

  .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
  }

  .testimonial-card {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.12) 0%, rgba(63, 81, 181, 0.08) 100%);
    border: 2px solid #1976d2;
    border-radius: 15px;
    padding: 25px;
    position: relative;
    transition: all 0.4s ease;
    overflow: hidden;
  }

  .testimonial-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(129, 212, 250, 0.1), transparent);
    transition: left 0.8s ease;
  }

  .testimonial-card:hover::before {
    left: 100%;
  }

  .testimonial-card:hover {
    transform: translateY(-8px);
    box-shadow:
      0 12px 30px rgba(33, 150, 243, 0.3),
      0 0 25px rgba(25, 118, 210, 0.2);
    border-color: #42a5f5;
  }

  .quote-icon {
    font-size: 3em;
    color: #4caf50;
    opacity: 0.3;
    line-height: 0.8;
    margin-bottom: 15px;
  }

  .testimonial-content p {
    font-size: 1.05em;
    line-height: 1.6;
    color: #e3f2fd;
    margin: 0 0 20px 0;
    font-style: italic;
  }

  .client-info {
    border-top: 1px solid rgba(25, 118, 210, 0.3);
    padding-top: 15px;
  }

  .client-info strong {
    color: #1ef4ff;
    font-size: 1.1em;
    display: block;
    margin-bottom: 5px;
  }

  .client-info span {
    color: #81d4fa;
    font-size: 0.9em;
    font-style: italic;
  }

  .project-type {
    background: rgba(76, 175, 80, 0.2);
    color: #a5d6a7;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    margin-top: 8px;
    display: inline-block;
    border: 1px solid rgba(76, 175, 80, 0.3);
  }

  .project-highlights {
    background: rgba(25, 118, 210, 0.15);
    border-left: 3px solid #1976d2;
    padding: 12px 15px;
    margin-top: 15px;
    border-radius: 0 8px 8px 0;
    font-size: 0.9em;
    line-height: 1.5;
    color: #81d4fa;
  }

  .portfolio-showcase {
    background: linear-gradient(135deg, rgba(2, 1, 2, 0.6) 0%, rgba(0, 0, 0, 0.65) 100%);
    border: 2px solid #d1abd2;
    border-radius: 15px;
    padding: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .portfolio-showcase::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #5977ea, #adadd9, #c7a0d4, #6c69d7);
    background-size: 400% 400%;
    animation: borderPulse 6s ease-in-out infinite;
    border-radius: 15px;
    z-index: -1;
  }

  @keyframes borderPulse {

    0%,
    100% {
      background-position: 0% 50%;
    }

    50% {
      background-position: 100% 50%;
    }
  }

  .portfolio-showcase h3 {
    color: #f4d34f;
    margin: 0 0 15px 0;
    text-shadow: 0 0 8px #d7b52e;
    font-family: 'Orbitron', sans-serif;
  }

  .portfolio-showcase p {
    color: #e3f2fd;
    font-size: 1.1em;
    margin: 0 0 25px 0;
  }

  .demo-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 25px 0 30px 0;
  }

  .demo-feature {
    background: rgba(0, 0, 0, 0.48);
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #1976d2;
    text-align: left;
  }

  .feature-icon {
    font-size: 1.5em;
    margin-right: 10px;
  }

  .demo-feature strong {
    color: #81d4fa;
    display: block;
    margin-bottom: 5px;
  }

  .portfolio-cta {
    margin-top: 30px;
  }

  .portfolio-cta p {
    font-size: 1.2em;
    color: #81c784;
    margin-bottom: 20px;
    font-weight: 500;
  }

  /* Responsive testimonials */
  @media (max-width: 768px) {
    .testimonials-section {
      padding: 50px 15px;
    }

    .testimonials-title {
      font-size: 1.8em;
    }

    .testimonials-grid {
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .demo-features {
      grid-template-columns: 1fr;
      gap: 12px;
    }

    .portfolio-showcase {
      padding: 25px;
    }
  }

  @keyframes modalFadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    #career-command-center {
      padding: 40px 15px;
    }

    .starfleet-border {
      padding: 20px;
    }

    .command-title {
      font-size: 1.8em;
    }

    .showcase-grid {
      grid-template-columns: 1fr;
    }

    .command-buttons {
      flex-direction: column;
      align-items: center;
    }

    .starfleet-btn {
      width: 100%;
      max-width: 300px;
    }
  }
</style>