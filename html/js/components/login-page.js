/**
 * Login Page Component
 * Handles theme management, OAuth configuration and authentication flows
 */
mb.registerComponent(
  "login-page",
  function ($element, componentData) {
    //console.log("Initializing login-page component", $element);
    checkErrors();
    //updateOAuthButtons()
    // Initialize all functionality
    initializeTheme();
    //checkOAuthConfiguration();
    setupThemeToggle();
    //setupSystemThemeListener();

    function initializeTheme() {
      const savedTheme = localStorage.getItem("mediabrain_theme");
      const systemPrefersDark = window.matchMedia(
        "(prefers-color-scheme: dark)",
      ).matches;

      const goBackBtn = document.querySelector(".go-back-btn");
      if (history.length > 1) {
        goBackBtn.textContent = "← Go back";
        goBackBtn.addEventListener("click", function (e) {
          e.preventDefault();
          history.back();
        });
      } else {
        goBackBtn.textContent = "Exit";
        goBackBtn.addEventListener("click", function (e) {
          e.preventDefault();
          window.close();
        });
        // If no history, redirect to the fallback URL
      }

      if (savedTheme === "dark" || (!savedTheme && systemPrefersDark)) {
        //document.body.classList.add('nightMode');
        updateToggleIcon(true);
      } else {
        updateToggleIcon(false);
      }
    }

    function checkErrors() {
      const urlParams = new URLSearchParams(window.location.search);
      const error = urlParams.get("oauth_error");
      if (error) {
        window.play("audio/star trek sounds/computerbeep_63.mp3");
      }
    }

    function setupThemeToggle() {
      const themeToggle = document.querySelector(".theme-toggle");
      if (themeToggle) {
        themeToggle.addEventListener("click", toggleTheme);
      }
    }

    function toggleTheme() {
      const isNightMode = document.body.classList.toggle("nightMode");
      localStorage.setItem("mediabrain_theme", isNightMode ? "dark" : "light");
      updateToggleIcon(isNightMode);
    }

    function updateToggleIcon(isNightMode) {
      const icon = document.querySelector(".theme-toggle i");
      if (icon) {
        icon.textContent = isNightMode ? "light_mode" : "dark_mode";
      }
    }

    function setupSystemThemeListener() {
      // Listen for system theme changes
      if (window.matchMedia) {
        window.matchMedia("(prefers-color-scheme: dark)").addListener((e) => {
          const savedTheme = localStorage.getItem("mediabrain_theme");
          if (!savedTheme) {
            if (e.matches) {
              document.body.classList.add("nightMode");
              updateToggleIcon(true);
            } else {
              document.body.classList.remove("nightMode");
              updateToggleIcon(false);
            }
          }
        });
      }
    }

    function checkOAuthConfiguration() {
      // We'll pass the params as an object to your wrapper
      const params = {
        action: "check_oauth_config",
      };

      console.log(
        "Checking OAuth with mb.get... Signal Strength: 10,000 x's Amazing",
      );

      // Using your mb.get wrapper
      // Assuming it handles the base URL and the headers internally!
      /*
      mb.get("?api=admin", params)
        .then((data) => {
          // Your wrapper likely already did .json() for you!
          if (data && data.success) {
            updateOAuthButtons(data.providers);
            updateOAuthStatus(data.providers);
          }
        })
        .fail((error) => {
          console.log("OAuth configuration check failed:", error);
          const statusDiv = document.getElementById("oauth-status");
          if (statusDiv) statusDiv.style.display = "block";
        });
        */
    }

    function updateOAuthButtons(providers) {
      const googleBtn = document.querySelector(".google-btn");
      const appleBtn = document.querySelector(".apple-btn");
      const facebookBtn = document.querySelector(".facebook-btn");
      const linkedinBtn = document.querySelector(".linkedin-btn");
      //console.log("OAuth data:", providers);
      googleBtn.addEventListener("click", loginWithGoogle);
      facebookBtn.addEventListener("click", loginWithFacebook);
      linkedinBtn.addEventListener("click", loginWithLinkedin);
      /*
      if (providers.google && providers.google.enabled && googleBtn) {
        googleBtn.disabled = false;
        googleBtn.addEventListener("click", loginWithGoogle);
      }

      if (providers.apple && providers.apple.enabled && appleBtn) {
        appleBtn.disabled = false;
        appleBtn.addEventListener("click", loginWithApple);
      }

      if (providers.facebook && providers.facebook.enabled && facebookBtn) {
        facebookBtn.disabled = false;
        facebookBtn.addEventListener("click", loginWithFacebook);
      }

      if (providers.linkedin && providers.linkedin.enabled && linkedinBtn) {
        linkedinBtn.disabled = false;
        linkedinBtn.addEventListener("click", loginWithLinkedin);
      }
        */
    }

    function updateOAuthStatus(providers) {
      const statusDiv = document.getElementById("oauth-status");
      const hasEnabledProvider =
        (providers.google && providers.google.enabled) ||
        (providers.apple && providers.apple.enabled) ||
        (providers.facebook && providers.facebook.enabled) ||
        (providers.linkedin && providers.linkedin.enabled);

      if (!hasEnabledProvider && statusDiv) {
        statusDiv.style.display = "block";
      }
    }
  },
  ["jQuery"],
);
