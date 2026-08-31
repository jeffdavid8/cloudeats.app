$(document).ready(function () 
{
  $("#applicationsModal a.application-card").on("click", function (e) {
    e.preventDefault();
    //play("audio/star trek sounds/computerbeep_18.mp3");
    loading(1);
    setTimeout(
      function () {
        window.location.href = this.getAttribute("href");
      }.bind(this),
      400
    );
    e.stopPropagation();
  });

  var items = $(".sidenav a").filter(function () {
    const href = $(this).attr("href");
    return href && href.trim().length > 2;
  });
  items.each(function (index, element) {
    $(element).attr("target", "_self");
  });
  $(".portfolio-links a").on("click", function () {
    const announcementTitle = $(this).data("announcement-title");
    speak(`Opening new ${announcementTitle}`);
    play("audio/star trek sounds/processing2.mp3");
  });

  // Create global instance
  window.starTrekAchievements = new StarTrekAchievements();

  // Add achievement trigger button to splash page
  const contactTrigger = document.createElement("button");
  contactTrigger.innerHTML = "Contact 🖖";
  contactTrigger.className = "star-trek-button floating-achievement-btn";
  contactTrigger.style.cssText = `
        position: fixed;
        bottom: 48px;
        right: 30px;
        z-index: 100;
        background: linear-gradient(135deg, #0066cc, #0088ff);
        border: 2px solid #00ccff;
        border-radius: 25px;
        color: #ffffff;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 136, 255, 0.4);
        transition: all 0.3s;
        font-family: 'Orbitron', 'Courier New', monospace;
        animation: achievementPulse 3s ease-in-out infinite;
    `;

  // Add pulse animation
  const style = document.createElement("style");
  style.textContent = `
        @keyframes achievementPulse {
            0%, 100% { 
                box-shadow: 0 4px 20px rgba(0, 136, 255, 0.4); 
                border-color: #00ccff;
            }
            50% { 
                box-shadow: 0 8px 30px rgba(0, 255, 136, 0.6); 
                border-color: #00ff88;
            }
        }
    `;
  document.head.appendChild(style);

  contactTrigger.onmouseover = () => {
    contactTrigger.style.transform = "translateY(-3px)";
    contactTrigger.style.boxShadow = "0 8px 25px rgba(0, 136, 255, 0.6)";
  };

  contactTrigger.onmouseout = () => {
    contactTrigger.style.transform = "translateY(0)";
    contactTrigger.style.boxShadow = "0 4px 20px rgba(0, 136, 255, 0.4)";
  };

  contactTrigger.onclick = () => {
    window.initiateContact();
  };

  document.body.appendChild(contactTrigger);
});

// Technical Operations Center JavaScript Functions
function showApplicationsModal() {
  // Create and show technical specifications modal
  const $applicationsModal = $("#applicationsModal");

  play("audio/star trek sounds/processing2.mp3");

  $("body").css("overflow", "hidden");

  // Show modal with animation
  $applicationsModal.addClass("show");

  window.history.pushState({ modalOpen: true }, "", "#applicationsModal");
  window.addEventListener("popstate", function (event) {
    // Close your modal element
    if (typeof event.srcElement.starTrekAchievements !== "undefined") {
      closeApplicationsModal();
    }
    event.stopPropagation();
    // Optionally, you might want to remove the hash from the URL
    // if it was added for the modal.
    if (window.location.hash === "#applicationsModal") {
      history.replaceState({}, document.title, window.location.pathname);
    }
  });

  // Add click outside to close
  $applicationsModal.click(function (e) {
    if (e.target === this) {
      window.history.back();
      return false;
    }
  });
}

function closeApplicationsModal() {
    $("#applicationsModal").removeClass("show");
    restoreBodyScroll();
  
}

// Technical Operations Center JavaScript Functions
function showTechnicalSpecs() {
  // Create and show technical specifications modal
  const $techSpecModal = $("#techSpecModal");

  play("audio/star trek sounds/processing2.mp3");

  $("body").css("overflow", "hidden");
console.log($techSpecModal);
  // Show modal with animation
  $techSpecModal.fadeIn(function () {
    $(this).addClass("show");
  });

  window.history.pushState({ modalOpen: true }, "", "#techSpecModal");
  window.addEventListener("popstate", function (event) {
    // Close your modal element
    event.srcElement.closeTechSpecModal();
    event.stopPropagation();
    // Optionally, you might want to remove the hash from the URL
    // if it was added for the modal.
    if (window.location.hash === "#techSpecModal") {
      history.replaceState({}, document.title, window.location.pathname);
    }
  });

  // Add click outside to close
  $techSpecModal.click(function (e) {
    if (e.target === this) {
      window.history.back();
      return false;
    }
  });
}

function closeTechSpecModal() {
  $("#techSpecModal").fadeOut(function () {
    $(this).removeClass("show");
    restoreBodyScroll();
  });
  setTimeout(() => {
    $("#techSpecModal").hide();
  }, 500);
}

function initiateContact() {
  const contactModal = document.getElementById("contactModal");
  play("audio/star trek sounds/ds9intercom.mp3");
  $(contactModal).fadeIn();
  $("body").css("overflow", "hidden");

  window.history.pushState({ modalOpen: true }, "", "#contactModal");
  window.addEventListener("popstate", function (event) {
    // Close your modal element
    event.srcElement.closeContactModal();
    // Optionally, you might want to remove the hash from the URL
    // if it was added for the modal.
    if (window.location.hash === "#contactModal") {
      history.replaceState({}, document.title, window.location.pathname);

      return false;
    }
  });

  // Add click outside to close
  contactModal.addEventListener("click", function (e) {
    if (e.target === contactModal) {
      window.history.back();
      return false;
    }
  });

  // Track engagement
  console.log(
    "Contact protocol initiated - potential client engagement detected"
  );
}

function closeContactModal() {
  const contactModal = document.getElementById("contactModal");
  $(contactModal).fadeOut(function () {
    $(this).removeClass("show");
  });
  restoreBodyScroll();
}

function showServicePricing() {
  const pricingModal =
    `
    <div id="pricingModal" class="star-trek-modal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-close" onclick="window.history.back(); return false;">&times;</div>
          <h2>💰 MEDIABRAIN DEVELOPMENT SERVICES</h2>
        </div>
        <div class="modal-body">
          <div class="pricing-intro">
            <p>🚀 <strong>Enterprise-grade development services</strong> with transparent pricing and rapid delivery. All projects include security implementation, responsive design, and deployment assistance.</p>
          </div>
          
          <div class="pricing-grid">
            <div class="pricing-package">
              <div class="package-header essential">
                <h4>🛡️ ESSENTIAL PACKAGE</h4>
                <div class="price">$2,500 - $5,000</div>
                <div class="duration">2-4 weeks delivery</div>
              </div>
              <div class="package-features">
                <ul>
                  <li>✅ Custom Web Application</li>
                  <li>✅ Responsive Design (Mobile/Desktop)</li>
                  <li>✅ User Authentication System</li>
                  <li>✅ Database Design & Implementation</li>
                  <li>✅ Basic Admin Panel</li>
                  <li>✅ Security Best Practices</li>
                  <li>✅ Deployment & Documentation</li>
                </ul>
                <div class="ideal-for">Perfect for: Small businesses, startups, MVP development</div>
              </div>
            </div>
            
            <div class="pricing-package featured">
              <div class="package-header professional">
                <h4>🚀 PROFESSIONAL PACKAGE</h4>
                <div class="price">$5,000 - $12,000</div>
                <div class="duration">4-8 weeks delivery</div>
                <div class="popular-badge">MOST POPULAR</div>
              </div>
              <div class="package-features">
                <ul>
                  <li>✅ Everything in Essential Package</li>
                  <li>✅ Advanced User Management</li>
                  <li>✅ API Development & Integration</li>
                  <li>✅ Real-time Features (WebSockets)</li>
                  <li>✅ Payment Integration (Stripe/PayPal)</li>
                  <li>✅ Cloud Deployment (AWS/GCP)</li>
                  <li>✅ Performance Optimization</li>
                  <li>✅ 30 Days Post-Launch Support</li>
                </ul>
                <div class="ideal-for">Perfect for: Growing businesses, SaaS platforms, e-commerce</div>
              </div>
            </div>
            
            <div class="pricing-package">
              <div class="package-header enterprise">
                <h4>🌟 ENTERPRISE PACKAGE</h4>
                <div class="price">$12,000+</div>
                <div class="duration">8+ weeks delivery</div>
              </div>
              <div class="package-features">
                <ul>
                  <li>✅ Everything in Professional Package</li>
                  <li>✅ Microservices Architecture</li>
                  <li>✅ Advanced Security & Compliance</li>
                  <li>✅ Multi-tenant SaaS Features</li>
                  <li>✅ Custom Integrations (CRM, ERP)</li>
                  <li>✅ Load Balancing & Auto-scaling</li>
                  <li>✅ DevOps & CI/CD Pipeline</li>
                  <li>✅ 90 Days Premium Support</li>
                </ul>
                <div class="ideal-for">Perfect for: Large enterprises, complex platforms, high-traffic apps</div>
              </div>
            </div>
          </div>
          
          <div class="hourly-section">
            <div class="hourly-header">
              <h4>⚡ FLEXIBLE HOURLY SERVICES</h4>
            </div>
            <div class="hourly-grid">
              <div class="hourly-item">
                <strong>Development:</strong> $75-125/hour
                <div class="hourly-desc">Full-stack development, bug fixes, feature additions</div>
              </div>
              <div class="hourly-item">
                <strong>Consultation:</strong> $100/hour
                <div class="hourly-desc">Architecture planning, code reviews, technical guidance</div>
              </div>
              <div class="hourly-item">
                <strong>Emergency Support:</strong> $150/hour
                <div class="hourly-desc">Critical bug fixes, server issues, urgent deployments</div>
              </div>
            </div>
          </div>
          
          <div class="value-proposition">
            <h4>🎯 WHY CHOOSE MEDIABRAIN DEVELOPMENT?</h4>
            <div class="value-grid">
              <div class="value-item">
                <strong>🚀 Rapid Delivery:</strong> Agile development with weekly progress updates
              </div>
              <div class="value-item">
                <strong>🛡️ Security First:</strong> Built-in security features and best practices
              </div>
              <div class="value-item">
                <strong>📱 Modern Stack:</strong> Latest technologies and frameworks
              </div>
              <div class="value-item">
                <strong>☁️ Cloud Ready:</strong> Scalable deployment and infrastructure
              </div>
              <div class="value-item">
                <strong>🔧 Ongoing Support:</strong> Post-launch maintenance and enhancements
              </div>
              <div class="value-item">
                <strong>💰 Transparent Pricing:</strong> No hidden costs or surprise fees
              </div>
            </div>
          </div>
          
          <div class="cta-section">
            <h3>🚨 READY TO START YOUR PROJECT?</h3>
            <p>Get a detailed quote within 24 hours. Free consultation to discuss your requirements.</p>
            <div class="cta-buttons">
              <button class="starfleet-btn primary" onclick="initiateContact();">
                📡 REQUEST FREE CONSULTATION
              </button>` +
    /*
              <button class="starfleet-btn secondary" onclick="window.open('mailto:virtualimmortal@gmail.com?subject=Project Quote Request&body=Hi Jeff,%0A%0AI am interested in discussing a development project.%0A%0AProject Type: [Please describe]%0ATimeline: [When do you need this completed?]%0ABudget Range: [What is your budget range?]%0A%0APlease contact me to schedule a consultation.%0A%0AThanks!', '_blank');">
                📧 REQUEST INSTANT QUOTE
              </button>
              */
    `</div>
          </div>
          
          <div class="guarantee">
            <strong>🛡️ SATISFACTION GUARANTEE:</strong> 
            If you're not completely satisfied with the delivered work, 
            we'll continue refining until you are - at no additional cost.
          </div>
        </div>
      </div>
    </div>
  `;

  // Remove existing modal if present
  $("#pricingModal").hide();

  // Add modal to body
  $("body").append(pricingModal);
  $("body").css("overflow", "hidden");
  play("audio/star trek sounds/helm_engage_clean.mp3");

  // Show modal with animation
  $("#pricingModal").fadeIn(function () {
    $(this).addClass("show");
  });

  window.history.pushState({ modalOpen: true }, "", "#pricingModal");
  window.addEventListener("popstate", function (event) {
    // Close your modal element
    event.srcElement.closePricingModal();
    event.stopPropagation();
    // Optionally, you might want to remove the hash from the URL
    // if it was added for the modal.
    if (window.location.hash === "#pricingModal") {
      history.replaceState({}, document.title, window.location.pathname);
    }
  });

  // Add click outside to close
  $("#pricingModal").click(function (e) {
    if (e.target === this) {
      window.history.back();
      return false;
    }
  });

  // Track pricing view
  trackBusinessLead("pricing_viewed");
}

function closePricingModal() {
  $("#pricingModal").fadeOut(function () {
    $(this).removeClass("show");
    restoreBodyScroll();
  });
  setTimeout(() => {
    $("#pricingModal").remove();
  }, 500);
}

function restoreBodyScroll() {
  let $modals = $(".star-trek-modal.show");
  if ($modals.length === 0) {
    $("body").css("overflow", "auto");
  } else {
    $("body").css("overflow", "hidden");
  }
}

// Add keyboard navigation
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closeContactModal();
    closeTechSpecModal();
    closePricingModal();
    closeApplicationsModal();
  }
});

// Add smooth scrolling to command center
function scrollToCommandCenter() {
  document.getElementById("career-command-center").scrollIntoView({
    behavior: "smooth",
  });
}

// Add tracking for business leads
function trackBusinessLead(action) {
  console.log(`Business Lead Action: ${action} at ${new Date().toISOString()}`);

  // You could extend this to send to analytics service
  // Example: gtag('event', 'business_lead', { action: action });
}

// Initialize business tracking
$(document).ready(function () {
  // Track page visits to command center
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        trackBusinessLead("command_center_viewed");
      }
    });
  });

  const commandCenter = document.getElementById("career-command-center");
  if (commandCenter) {
    observer.observe(commandCenter);
  }

  // Track button interactions
  $(".starfleet-btn").click(function () {
    //play("audio/star trek sounds/computer_activate.mp3");
    const buttonText = $(this).text().trim();
    trackBusinessLead(
      `button_clicked_${buttonText.replace(/\s+/g, "_").toLowerCase()}`
    );
  });

  // Track contact link clicks
  $(".contact-link").click(function () {
    const linkText = $(this).text().trim();
    trackBusinessLead(
      `contact_link_clicked_${linkText.replace(/\s+/g, "_").toLowerCase()}`
    );
  });

  // Get the current URL hash, including the '#'
  const currentHash = window.location.hash;

  // Check if the hash matches the desired modal ID
  switch (currentHash) {
    case "#contactModal":
      initiateContact();
      break;
    case "#applicationsModal":
      showApplicationsModal();
      break;
    case "#techSpecModal":
      showTechnicalSpecs();
      break;
    case "#pricingModal":
      showServicePricing();
      break;
  }
});
