/**
 * Neighborhub Real-Time Polling System
 *
 * Provides automatic polling for customer, merchant, and courier dashboards.
 * Updates UI in real-time as order states change.
 *
 * Usage:
 *   - Customer: new NeighborhubPoller('customer', customerId);
 *   - Merchant: new NeighborhubPoller('merchant', merchantId);
 *   - Courier: new NeighborhubPoller('courier', courierId);
 */

class NeighborhubPoller {
  constructor(mode, entityId) {
    this.mode = mode; // 'customer', 'merchant', or 'courier'
    this.entityId = entityId;
    this.pollInterval = this.getPollInterval();
    this.pollTimer = null;
    this.isPolling = false;
    this.lastPolledData = null;

    this.init();
  }

  /**
   * Get polling interval based on mode
   * Customer: aggressive (2-5 sec) - users want real-time tracking
   * Merchant: moderate (3-5 sec)
   * Courier: relaxed (5-10 sec)
   */
  getPollInterval() {
    switch (this.mode) {
      case "customer":
        return 3000; // 3 seconds
      case "merchant":
        return 4000; // 4 seconds
      case "courier":
        return 5000; // 5 seconds
      default:
        return 5000;
    }
  }

  /**
   * Initialize polling on DOM ready
   */
  init() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.start());
    } else {
      this.start();
    }

    // Stop polling on page unload
    window.addEventListener("beforeunload", () => this.stop());
  }

  /**
   * Start polling timer
   */
  start() {
    if (this.isPolling) return;

    this.isPolling = true;
    console.log(
      `[Neighborhub] Starting ${this.mode} polling every ${this.pollInterval}ms`,
    );

    // Poll immediately on start
    this.poll();

    // Then set interval for subsequent polls
    this.pollTimer = setInterval(() => this.poll(), this.pollInterval);
  }

  /**
   * Stop polling timer
   */
  stop() {
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
      this.pollTimer = null;
    }
    this.isPolling = false;
    console.log(`[Neighborhub] Stopped ${this.mode} polling`);
  }

  /**
   * Main polling function - routes to appropriate handler based on mode
   */
  poll() {
    switch (this.mode) {
      case "customer":
        this.pollCustomerOrders();
        break;
      case "merchant":
        this.pollMerchantOrders();
        break;
      case "courier":
        this.pollAvailableJobs();
        break;
    }
  }

  /**
   * Customer Mode: Poll list_customer_orders
   * Updates: order status badges, courier assignment, ETA, location
   */
  pollCustomerOrders() {
    mb.ajax({
      url: "/?api=neighborhub&action=list_customer_orders",
      type: "GET",
      data: {
        customer_id: this.entityId,
        limit: 10,
      },
      success: (response) => {
        if (response.success && response.orders) {
          this.updateCustomerUI(response.orders);
        }
      },
      error: (xhr, status, error) => {
        console.error("[Neighborhub] Customer polling error:", error);
      },
    });
  }

  /**
   * Merchant Mode: Poll get_pending_orders
   * Updates: pending orders list, state transitions
   */
  pollMerchantOrders() {
    mb.ajax({
      url: "/?api=neighborhub&action=get_pending_orders",
      type: "GET",
      data: {
        merchant_id: this.entityId,
      },
      success: (response) => {
        if (response.success && response.orders) {
          this.updateMerchantUI(response.orders);
        }
      },
      error: (xhr, status, error) => {
        console.error("[Neighborhub] Merchant polling error:", error);
      },
    });
  }

  /**
   * Courier Mode: Poll get_available_jobs
   * Updates: available jobs list, removes accepted orders
   */
  pollAvailableJobs() {
    mb.ajax({
      url: "/?api=neighborhub&action=get_available_jobs",
      type: "GET",
      success: (response) => {
        if (response.success && response.jobs) {
          this.updateCourierUI(response.jobs);
        }
      },
      error: (xhr, status, error) => {
        console.error("[Neighborhub] Courier polling error:", error);
      },
    });
  }

  /**
   * Update customer dashboard UI with fresh order data
   */
  updateCustomerUI(orders) {
    if (!orders || orders.length === 0) return;

    orders.forEach((order) => {
      const orderId = order.id;
      const state = order.state;

      // Find order card/section in DOM
      const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
      if (!orderCard) return;

      // Update status badge
      const statusBadge = orderCard.querySelector("[data-status-badge]");
      if (statusBadge) {
        this.updateStatusBadge(statusBadge, state);
      }

      // Update courier info if IN_TRANSIT
      if (state === "IN_TRANSIT") {
        this.updateCourierDisplay(orderCard, order);
      }

      // Update delivery info if DELIVERED
      if (state === "DELIVERED") {
        this.updateDeliveryComplete(orderCard, order);
      }
    });
  }

  /**
   * Update merchant dashboard UI with fresh order data
   */
  updateMerchantUI(orders) {
    if (!orders) return;

    // Detect if we are on the passive lobby (TV) board
    const isLobbyMode = !!document.querySelector(".nh-lobby-board");

    // Separate orders by state
    const pendingConfirm = orders.pending
      ? orders.pending.filter((o) => o.state === "PENDING_CONFIRMATION")
      : [];
    const confirmed = orders.confirmed
      ? orders.confirmed.filter((o) => o.state === "CONFIRMED")
      : [];
    const ready = orders.ready
      ? orders.ready.filter((o) => o.state === "READY_FOR_PICKUP")
      : [];

    // Save active tracking array
    this.allActiveOrderIds = [
      ...pendingConfirm.map((o) => o.id),
      ...confirmed.map((o) => o.id),
      ...ready.map((o) => o.id),
    ];

    // Trigger New Order alerts (only for pending)
    var currentPendingOrderCount = pendingConfirm.length;
    if (
      typeof lastPendingOrderCount !== "undefined" &&
      currentPendingOrderCount > lastPendingOrderCount
    ) {
      $("section.nh-pending-queue .nh-alert").addClass("hide");
      $("section.nh-pending-queue .nh-content").removeClass("hide");

      const modal = document.getElementById("new-order-alert-modal");
      if (modal) modal.style.display = "block";

      if (typeof playPersistentKitchenAlert === "function") {
        playPersistentKitchenAlert();
      } else {
        try {
          mp3("alert24");
        } catch (err) {}
      }

      lastPendingOrderCount = currentPendingOrderCount;
    }

    // --- LAYOUT ROUTING ---
    if (isLobbyMode) {
      // Lobby mode maps directly to your explicit layout dataset values
      this.updateOrderSection("pending", pendingConfirm, true);
      this.updateOrderSection("confirmed", confirmed, true);
      this.updateOrderSection("ready-for-pickup", ready, true);
    } else {
      // Standard 3-column Expo KDS Mode (Kitchen view keys)
      this.updateOrderSection("pending", pendingConfirm, false);
      this.updateOrderSection("confirmed", confirmed, false);
      this.updateOrderSection("ready", ready, false);
    }

    this.lastPolledData = orders;
  }

  /**
   * Update order section without breaking layout configurations
   */
  updateOrderSection(sectionName, orders, isLobbyMode = false) {
    const section = document.querySelector(
      `[data-orders-section="${sectionName}"]`,
    );
    if (!section) return;

    const container = section.querySelector("[data-orders-list]");
    if (!container) return;

    let sectionCount = section.querySelector(
      "[data-" + sectionName + "-count]",
    );
    if (sectionCount) sectionCount.innerHTML = orders.length;

    // 1. Handle complete removal
    container.querySelectorAll("[data-order-id]").forEach((card) => {
      const orderIdStr = String(card.dataset.orderId);

      if (!orders.find((o) => String(o.id) === orderIdStr)) {
        const isStillActiveInSystem = this.allActiveOrderIds
          .map(String)
          .includes(orderIdStr);

        if (!isStillActiveInSystem) {
          card.remove();
        }
      }
    });

    // 2. Handle additions & lane transitions smoothly
    orders.forEach((order) => {
      const targetOrderId = String(order.id);

      let existingCard = document.body.querySelector(
        `[data-order-id="${targetOrderId}"]`,
      );

      // Determine what markup to generate
      const targetMarkup = isLobbyMode
        ? `<div class="tv-tile" data-order-id="${order.id}">#${order.order_number || "—"}</div>`
        : order.html;

      if (!existingCard) {
        // Scenario A: Brand new entry
        let inserted = false;

        // Only run card animation transitions in KDS Expo layout
        if (
          !isLobbyMode &&
          targetMarkup &&
          typeof animateOrderStateTransition === "function"
        ) {
          try {
            animateOrderStateTransition(order.id, order.state, targetMarkup);
            if (
              document.body.querySelector(`[data-order-id="${targetOrderId}"]`)
            ) {
              inserted = true;
            }
          } catch (err) {
            console.error(
              "[Polling] Animation engine failed, falling back to direct insertion:",
              err,
            );
          }
        }

        // Fallback or Lobby board direct append
        if (!inserted && targetMarkup) {
          $(container).append($(targetMarkup));
          if (!isLobbyMode) {
            this.showNewOrderNotification(order);
          }
        }

        if (typeof assignKb9000BumpIndexes === "function") {
          assignKb9000BumpIndexes();
        }
      } else {
        // Scenario B: Entry exists. Check if it transitioned lanes
        const currentLane = existingCard.closest("[data-orders-section]")
          ?.dataset.ordersSection;

        if (currentLane && currentLane !== sectionName) {
          let moved = false;

          if (
            !isLobbyMode &&
            targetMarkup &&
            typeof animateOrderStateTransition === "function"
          ) {
            try {
              animateOrderStateTransition(order.id, order.state, targetMarkup);
              moved = true;
            } catch (err) {
              console.error("[Polling] Transition engine failed:", err);
            }
          }

          if (!moved && targetMarkup) {
            existingCard.remove();
            $(container).append($(targetMarkup));
          }

          if (typeof assignKb9000BumpIndexes === "function") {
            assignKb9000BumpIndexes();
          }
        }
      }
    });
  }

  /**
   * Update courier dashboard UI with available jobs
   */
  updateCourierUI(jobs) {
    const jobsList = document.querySelector("[data-available-jobs]");
    if (jobsList && (!jobs || jobs.length === 0)) {
      this.clearAvailableJobsList();
      jobsList.className = "nh-grid";
      return;
    }

    // Update available jobs list
    if (!jobsList) return;

    jobsList.className = "nh-grid nh-grid-2";
    jobsList.innerHTML = "";

    // Recalculate distances and update
    jobs.forEach((job) => {
      let jobCard = document.querySelector(`[data-order-id="${job.id}"]`);

      if (!jobCard) {
        // New job - add to list
        jobCard = $(job.html)[0];
        jobsList.appendChild(jobCard);
      } else {
        // Update existing job card if needed
        this.updateJobCard(jobCard, job);
      }
    });

    // Remove jobs that are no longer available
    jobsList.querySelectorAll("[data-job-id]").forEach((card) => {
      const jobId = card.dataset.jobId;
      if (!orders.find((o) => o.id == jobId)) {
        card.remove();
      }
    });
  }

  /**
   * Update status badge color and text
   */
  updateStatusBadge(badge, state) {
    let colorClass = "grey";
    let stateLabel = state;

    switch (state) {
      case "PENDING_CONFIRMATION":
        colorClass = "orange";
        stateLabel = "Pending Confirmation";
        break;
      case "CONFIRMED":
        colorClass = "blue";
        stateLabel = "Confirmed";
        break;
      case "READY_FOR_PICKUP":
        colorClass = "green";
        stateLabel = "Ready for Pickup";
        break;
      case "IN_TRANSIT":
        colorClass = "purple";
        stateLabel = "On the Way";
        break;
      case "DELIVERED":
        colorClass = "teal";
        stateLabel = "Delivered";
        break;
      case "CANCELLED":
        colorClass = "grey";
        stateLabel = "Cancelled";
        break;
      case "FAILED":
        colorClass = "red";
        stateLabel = "Failed";
        break;
    }

    // Update class
    badge.className = `chip ${colorClass} white-text`;
    badge.textContent = stateLabel;
  }

  /**
   * Update courier information display
   */
  updateCourierDisplay(orderCard, order) {
    const courierSection = orderCard.querySelector("[data-courier-info]");
    if (!courierSection) return;

    // Fetch courier profile if courier_id exists
    if (order.courier_id) {
      mb.ajax({
        url: "/?api=neighborhub&action=get_courier_profile",
        type: "GET",
        data: { courier_id: order.courier_id },
        success: (response) => {
          if (response.success && response.courier) {
            const courier = response.courier;
            courierSection.innerHTML = `
              <p><strong>${courier.business_name || "Courier"}</strong></p>
              <p>${courier.vehicle_type || "vehicle"}</p>
              <p><i class="fas fa-star"></i> ${courier.rating || "N/A"}</p>
            `;
          }
        },
      });
    }
  }

  /**
   * Update delivery complete section
   */
  updateDeliveryComplete(orderCard, order) {
    const deliveredSection = orderCard.querySelector(
      "[data-delivery-complete]",
    );
    if (!deliveredSection) return;

    const deliveredTime = order.delivered_at
      ? new Date(order.delivered_at).toLocaleString()
      : "Just now";
    deliveredSection.innerHTML = `
      <p><strong>Delivered</strong></p>
      <p>${deliveredTime}</p>
      <p>${order.delivery_address}</p>
    `;
  }

  /**
   * Create order card for merchant dashboard
   */
  createOrderCard(order, section) {
    const card = document.createElement("div");
    card.className = "card";
    card.dataset.orderId = order.id;

    const timeAgo = this.getTimeAgo(new Date(order.created_at));

    card.innerHTML = `
      <div class="card-content">
        <span class="card-title">#${order.order_number}</span>
        <p><strong>Total:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
        <p><strong>Time:</strong> ${timeAgo}</p>
      </div>
      <div class="card-action">
        ${
          section === "pending"
            ? `
          <button class="btn btn-small blue" data-action="confirm-order" data-order-id="${order.id}">
            <i class="fas fa-check"></i> Confirm
          </button>
        `
            : ""
        }
        ${
          section === "confirmed"
            ? `
          <button class="btn btn-small green" data-action="mark-ready" data-order-id="${order.id}">
            <i class="fas fa-box"></i> Mark Ready
          </button>
        `
            : ""
        }
      </div>
    `;

    return card;
  }

  /**
   * Create job card for courier dashboard
   */
  createJobCard(order) {
    const card = document.createElement("div");
    card.className = "card";
    card.dataset.jobId = order.id;

    card.innerHTML = `
      <div class="card-content">
        <span class="card-title">Order #${order.order_number}</span>
        <p><strong>Payout:</strong> $${parseFloat(order.total_amount * 0.15).toFixed(2)}</p>
        <p><strong>Pickup:</strong> ${order.pickup_address}</p>
        <p><strong>Delivery:</strong> ${order.delivery_address}</p>
      </div>
      <div class="card-action">
        <button class="btn btn-small green" data-action="accept-job" data-order-id="${order.id}">
          <i class="fas fa-handshake"></i> Accept Job
        </button>
      </div>
    `;

    return card;
  }

  /**
   * Update existing job card
   */
  updateJobCard(card, order) {
    // Update payout and info if changed
    const payoutEl = card.querySelector("p strong:first-of-type");
    if (payoutEl) {
      payoutEl.nextSibling.textContent = `$${parseFloat(order.total_amount * 0.15).toFixed(2)}`;
    }
  }

  /**
   * Show new order notification
   */
  showNewOrderNotification(order) {
    M.toast({
      html: `<i class="fas fa-bell"></i> New Order #${order.order_number} received!`,
      displayLength: 4000,
    });
  }

  /**
   * Clear available jobs list (no orders)
   */
  clearAvailableJobsList() {
    const jobsList = document.querySelector("[data-available-jobs]");
    if (jobsList) {
      jobsList.innerHTML = `<div class="nh-alert nh-alert-info" style="width: 100%">
                                <div class="nh-alert-icon">ℹ</div>
                                <div class="nh-alert-content">
                                    <p class="nh-alert-message">No available delivery jobs at the moment. Check back soon!</p>
                                </div>
                            </div>`;
    }
  }

  /**
   * Get human-readable time ago string
   */
  getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);

    if (seconds < 60) return "just now";
    if (seconds < 3600) return Math.floor(seconds / 60) + "m ago";
    if (seconds < 86400) return Math.floor(seconds / 3600) + "h ago";
    return Math.floor(seconds / 86400) + "d ago";
  }

  /**
   * Pause/resume polling (useful for page visibility)
   */
  pause() {
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
      this.isPolling = false;
    }
  }

  resume() {
    if (!this.isPolling) {
      this.start();
    }
  }
}

/**
 * Page visibility API - pause polling when tab is not visible
 */
document.addEventListener("visibilitychange", function () {
  if (typeof window.neighborhubPoller !== "undefined") {
    if (document.hidden) {
      window.neighborhubPoller.pause();
    } else {
      window.neighborhubPoller.resume();
    }
  }
});
