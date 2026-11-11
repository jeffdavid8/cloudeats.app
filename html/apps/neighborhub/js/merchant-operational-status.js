(function () {
  if (window.NHMerchantOperationalStatus) return;

  const selector = "[data-merchant-id].nh-merchant-operational-status";
  const components = new Map();

  function publish(element, status, stale) {
    element.dataset.status = status.status;
    element.dataset.stale = stale ? "true" : "false";
    element.classList.remove("is-open", "is-closed", "is-opening-soon", "is-closing-soon", "is-stale");
    element.classList.add(stale ? "is-stale" : `is-${status.status}`);
    if (!stale && status.urgency) element.classList.add(`is-${status.urgency}`);
    element.querySelector("[data-status-label]").textContent = stale ? "Status unavailable" : status.label;
    element.querySelector("[data-status-message]").textContent = stale ? "Unable to update hours. Please try again." : status.message;
    document.querySelectorAll(".header-merchant-status-dot[data-merchant-id]").forEach((dot) => {
      if (dot.dataset.merchantId === element.dataset.merchantId) {
        dot.hidden = stale || status.status !== "open";
      }
    });
    element.dispatchEvent(new CustomEvent("nh:merchant-status", {
      bubbles: true,
      detail: { merchantId: element.dataset.merchantId, status: status.status, open: !stale && status.status === "open", stale },
    }));
  }

  function start(element) {
    if (components.has(element)) return;
    const component = { request: null, timer: null, retryTimer: null, retryDelay: 5000 };
    components.set(element, component);
    document.body.classList.add("nh-has-operational-status");

    function refresh() {
      if (!element.isConnected || document.hidden || component.request) return;
      if (component.retryTimer !== null) {
        window.clearTimeout(component.retryTimer);
        component.retryTimer = null;
      }
      let failed = false;
      component.request = mb.ajax({
        url: `?api=neighborhub&action=get_merchant_operational_status&merchant_id=${encodeURIComponent(element.dataset.merchantId)}`,
        method: "GET",
        dataType: "json",
        cache: false,
        timeout: 10000,
        success: function (response) {
          const value = response && response.operational_status;
          if (!response || !response.success || String(response.merchant_id) !== element.dataset.merchantId
              || !value || !["open", "closed"].includes(value.status)
              || typeof value.label !== "string" || typeof value.message !== "string"
              || !["", "opening-soon", "closing-soon"].includes(value.urgency)) {
            console.error("Invalid merchant status response");
            failed = true;
            publish(element, { status: "closed" }, true);
            return;
          }
          component.retryDelay = 5000;
          publish(element, value, false);
        },
        error: function (xhr, textStatus) {
          if (textStatus === "abort" && !element.isConnected) return;
          console.error("Merchant status refresh failed", xhr.status, textStatus);
          failed = true;
          publish(element, { status: "closed" }, true);
        },
        complete: function () {
          component.request = null;
          if (failed && element.isConnected) {
            component.retryTimer = window.setTimeout(function () {
              component.retryTimer = null;
              refresh();
            }, component.retryDelay);
            component.retryDelay = Math.min(component.retryDelay * 2, 60000);
          }
        },
      });
    }

    component.refresh = refresh;
    component.timer = window.setInterval(refresh, 60000);
    refresh();
  }

  function scan() {
    components.forEach((component, element) => {
      if (!element.isConnected) {
        window.clearInterval(component.timer);
        if (component.retryTimer !== null) window.clearTimeout(component.retryTimer);
        if (component.request) component.request.abort();
        components.delete(element);
      }
    });
    document.querySelectorAll(selector).forEach(start);
  }

  window.NHMerchantOperationalStatus = { scan };
  function init() {
    scan();
    new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
    document.addEventListener("visibilitychange", function () {
      if (!document.hidden) components.forEach((component) => component.refresh());
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
