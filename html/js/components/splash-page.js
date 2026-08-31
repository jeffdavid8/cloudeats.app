/**
 * Splash Page Component
 * Handles splash page functionality including logo interactions, achievement system integration,
 * cache busting, contact modals, and Star Trek sound effects
 */

mb.registerComponent('splash-page', function($element, data) {
  // All dependencies (jQuery) are guaranteed to be ready
  
  console.log('=== SPLASH PAGE COMPONENT INITIALIZED ===');
  
  // Logo Click Handler and Achievement System Integration
  function initializeLogoClick() {
    console.log('=== INITIALIZING LOGO CLICK HANDLER ===');
    console.log('Current window globals:', {
      achievementSystemLoaded: window.achievementSystemLoaded,
      StarTrekAchievements: typeof window.StarTrekAchievements,
      showStarTrekAchievements: typeof window.showStarTrekAchievements,
      starTrekAchievementsInstance: typeof window.starTrekAchievementsInstance
    });
    
    // Try multiple ways to attach the click handler
    const logoElement = document.getElementById('mediabrain-icon');
    console.log('Logo element found:', logoElement);
    
    if (logoElement) {
      // Add direct event listener
      logoElement.addEventListener('click', function(e) {
        //console.log('Logo clicked directly');
        e.preventDefault();
        $('body').css('overflow', 'hidden');
        //play('audio/star trek sounds/computerbeep_55.mp3');
        window.showApplicationsModal();
      });
      
      // Add visual feedback
      logoElement.style.cursor = 'pointer';
      logoElement.style.transition = 'transform 0.3s ease';
      
      logoElement.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
      });
      
      logoElement.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
      });
      
      console.log('Direct click handler attached to logo');
      
      // Listen for achievement system ready event
      document.addEventListener('achievementSystemReady', function() {
        console.log('Achievement system is now ready!');
      });
    } else {
      console.log('Logo element not found, retrying in 1 second...');
      setTimeout(initializeLogoClick, 1000);
    }
  }


  // Initialize logo click functionality
  initializeLogoClick();

  // Cache Busting
  function initializeCacheBusting() {
    // Add cache-busting meta tags if not present
    if (!document.querySelector('meta[http-equiv="Cache-Control"]')) {
      const metaCache = document.createElement('meta');
      metaCache.setAttribute('http-equiv', 'Cache-Control');
      metaCache.setAttribute('content', 'no-cache, no-store, must-revalidate');
      document.head.appendChild(metaCache);
      
      const metaPragma = document.createElement('meta');
      metaPragma.setAttribute('http-equiv', 'Pragma');
      metaPragma.setAttribute('content', 'no-cache');
      document.head.appendChild(metaPragma);
      
      const metaExpires = document.createElement('meta');
      metaExpires.setAttribute('http-equiv', 'Expires');
      metaExpires.setAttribute('content', '0');
      document.head.appendChild(metaExpires);
    }

    // Cache busting - force refresh if page hasn't been updated recently
    const lastUpdate = localStorage.getItem('mediabrain_last_update');
    const currentTime = Date.now();
    const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds
    
    if (!lastUpdate || (currentTime - parseInt(lastUpdate)) > oneHour) {
      localStorage.setItem('mediabrain_last_update', currentTime.toString());
      // Don't auto-refresh to avoid infinite loops, just update timestamp
    }
  }

  $('.technical-showcase .tech-panel').each(function(index, element) {
    $(element).on('click', function(e) {
        e.preventDefault();
        $('body').css('overflow', 'hidden');
        window.showStarTrekAchievements();
    });
  });

  // Splash Link Interactions with Star Trek Sound Effects
  $('#splash a').on('click', function(e) {
    e.preventDefault();
    var element = $(this);

    // Play Star Trek hail sound
    if (typeof play === 'function') {
      play('audio/Star Trek - Hail.wav');
    }
    
    element.addClass('flash');

    // Remove the class after the animation completes
    setTimeout(function() {
      element.removeClass('flash');
    }, 1000); // Match the animation duration (0.5s * 2 iterations = 1s)
  });

  // Achievement System Monitoring
  function monitorAchievementSystem() {
    console.log('=== DOM LOADED - CHECKING ACHIEVEMENT SYSTEM ===');
    
    // Check every 500ms for the achievement system
    let attempts = 0;
    const maxAttempts = 20; // 10 seconds max
    
    const checkSystem = setInterval(function() {
      attempts++;
      console.log(`Attempt ${attempts}: Checking for achievement system...`);
      console.log('Available globals:', {
        achievementSystemLoaded: window.achievementSystemLoaded,
        StarTrekAchievements: typeof window.StarTrekAchievements,
        showStarTrekAchievements: typeof window.showStarTrekAchievements,
        starTrekAchievementsInstance: typeof window.starTrekAchievementsInstance
      });
      
      if (window.achievementSystemLoaded || attempts >= maxAttempts) {
        clearInterval(checkSystem);
        if (window.achievementSystemLoaded) {
          console.log('✅ Achievement system loaded successfully!');
        } else {
          console.log('❌ Achievement system failed to load after 10 seconds');
        }
      }
    }, 500);
  }

  // Site Name Span Debug
  setTimeout(() => {
    const siteNameSpan = document.getElementById('site-name-span');
    if (siteNameSpan) {
      console.log('Site name span content:', siteNameSpan.textContent);
      console.log('Site name span innerHTML:', siteNameSpan.innerHTML);
    } else {
      console.log('Site name span not found');
    }
  }, 1000);

  // Initialize all functionality
  //initializeCacheBusting();
  monitorAchievementSystem();

  console.log('=== SPLASH PAGE COMPONENT COMPLETE ===');

}, ['jquery']); // Dependencies: jQuery only