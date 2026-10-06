(function () {
  function initializeSignup() {
    var modalElement = document.getElementById("neighborhub-signup-modal");
    var form = document.getElementById("neighborhub-signup-form");
    var signupButton = document.getElementById("btn-header-signup");

    if (!modalElement || !form || !signupButton || !window.M) {
      return;
    }

    var modal = M.Modal.init(modalElement);
    var message = document.getElementById("neighborhub-signup-message");
    var submitButton = document.getElementById("neighborhub-signup-submit");
    var siteKey = modalElement.getAttribute("data-recaptcha-site-key");

    $(".btn-signup").on("click", function (e) {
      message.textContent = "";
      modal.open();
    });

    window.grecaptcha.ready(function () {
      recaptchaWidgetId = grecaptcha.render("recaptcha-inline-container", {
        sitekey: "6LcrhuAtAAAAALYP1aVODI5goyyfDn6Q6_K6eGwk",
        badge: "inline",
        size: "invisible",
      });
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      message.textContent = "";

      if (!form.reportValidity()) {
        return;
      }
      if (
        form.elements.password.value !==
        form.elements.password_confirmation.value
      ) {
        message.textContent = "The passwords do not match.";
        form.elements.password_confirmation.focus();
        return;
      }
      if (!siteKey || !window.grecaptcha) {
        message.textContent =
          "Signup verification is temporarily unavailable. Please try again later.";
        return;
      }
      loading(2)
      submitButton.disabled = true;
      submitButton.textContent = "Sending verification email...";
      window.grecaptcha.ready(function () {
        window.grecaptcha
          .execute(siteKey, { action: "neighborhub_signup" })
          .then(function (recaptchaToken) {
            return new Promise(function (resolve, reject) {
              mb.ajax({
                type: "POST",
                url: "/?api=neighborhub&action=register_customer",
                data: JSON.stringify({
                  name: form.elements.name.value.trim(),
                  email: form.elements.email.value.trim(),
                  password: form.elements.password.value,
                  password_confirmation:
                    form.elements.password_confirmation.value,
                  recaptcha_token: recaptchaToken,
                }),
                dataType: "json",
                statusCode: {
                  403: function () {},
                },
                success: resolve,
                error: reject,
              });
            });
          })
          .then(function (response) {
            message.textContent =
              response.message ||
              "Check your email for an account verification link.";
            form.reset();
            submitButton.disabled = false;
            submitButton.textContent = "Create account";
            loading(0);
            launchSovereignConfetti();
            M.toast({
              html:
                response.message ||
                "Check your email for an account verification link.",
              displayLength: 7000,
            });
          })
          .then(null, function (xhr) {
            var response = xhr && xhr.responseJSON;
            message.textContent =
              (response && (response.error || response.message)) ||
              "We could not complete signup. Please try again.";
            submitButton.disabled = false;
            submitButton.textContent = "Create account";
          })
          .then(null, function (xhr) {});
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeSignup);
  } else {
    initializeSignup();
  }
})();
