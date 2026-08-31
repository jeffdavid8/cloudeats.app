    <div id="techSpecModal" class="star-trek-modal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-close" onclick="window.history.back(); return false;">&times;</div>
          <h2>📋 TECHNICAL SPECIFICATIONS</h2>
        </div>
        <div class="modal-body">
          <div class="spec-section">
            <h3>🚀 CURRENT PLATFORM ARCHITECTURE</h3>
            <div class="spec-details">
              <strong>Frontend:</strong> Vanilla JavaScript, jQuery, CSS3 Grid/Flexbox<br>
              <strong>Backend:</strong> PHP 8+, Custom MVC Framework<br>
              <strong>Database:</strong> MYSQL/MariaDB with Cloud Integration<br>
              <strong>Deployment:</strong> Docker Containers, Apache Web Server<br>
              <strong>Security:</strong> OAuth 2.0, Rate Limiting, CSRF Protection
            </div>
          </div>
          
          <div class="spec-section">
            <h3>⚡ DEMONSTRATED CAPABILITIES</h3>
            <ul class="capabilities-list">
              <li>✅ Modular Application Architecture (Hook System)</li>
              <li>✅ Real-time Weather API Integration</li>
              <li>✅ OAuth Authentication (Google/Facebook/Apple)</li>
              <li>✅ Genealogy Data Management</li>
              <li>✅ Recipe Management with Cook Mode</li>
              <li>✅ Dynamic Permission System</li>
              <li>✅ Unit Testing with PHPUnit</li>
              <li>✅ Progressive Web App Features</li>
            </ul>
          </div>
          
          <div class="spec-section">
            <h3>🔧 DEVELOPMENT HIGHLIGHTS</h3>
            <div class="highlight-item">
              <strong>Component-Based Architecture:</strong> Converted 30+ legacy views to modular JavaScript components
            </div>
            <div class="highlight-item">
              <strong>Security Implementation:</strong> Waterfall permissions system with admin override capabilities
            </div>
            <div class="highlight-item">
              <strong>OAuth Integration:</strong> Universal login system supporting multiple apps with role-based access
            </div>
            <div class="highlight-item">
              <strong>Performance Optimization:</strong> Lazy loading, caching strategies, and optimized asset delivery
            </div>
          </div>
          <? render('components/tech_specs_section.php'); ?>
        </div>
      </div>
    </div>
