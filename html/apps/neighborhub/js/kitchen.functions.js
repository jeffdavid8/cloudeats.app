// State tracking for the keyboard navigation focus
let activeFocusedElement = null;

function handleKitchenHotkeys(e) {
  const target = e.target;
  const targetTag = target && target.tagName ? target.tagName : "";
  if (
    targetTag === "INPUT" ||
    targetTag === "TEXTAREA" ||
    targetTag === "SELECT" ||
    target?.isContentEditable
  )
    return;

  const dropdownMenu = document.getElementById("store-status-dropdown");
  const isDropdownOpen = dropdownMenu && dropdownMenu.style.display === "block";

  if (isDropdownOpen) {
    const options = Array.from(
      dropdownMenu.querySelectorAll('li[tabindex="0"]'),
    );
    const activeEl = document.activeElement;
    const currentIdx = options.findIndex(
      (li) => li === activeEl || li.contains(activeEl),
    );

    if (currentIdx !== -1) {
      const dropdownInstance = M.Dropdown.getInstance(
        document.getElementById("store-status-toggle"),
      );

      const isUpAtTop = e.key === "ArrowUp" && currentIdx === 0;
      const isDownAtBottom =
        e.key === "ArrowDown" && currentIdx === options.length - 1;

      if (isUpAtTop || isDownAtBottom) {
        e.preventDefault();
        if (dropdownInstance) dropdownInstance.close();

        const trigger = document.getElementById("store-status-toggle");
        if (trigger) setFocus(trigger);
        return;
      }
    }

    if (
      target.closest("#store-status-dropdown") ||
      target.id === "store-status-toggle"
    ) {
      return;
    }
  }

  // 1. Aggressively block Materialize from opening the dropdown on Arrow keys
  if (
    target &&
    target.id === "store-status-toggle" &&
    (e.key === "ArrowDown" || e.key === "ArrowUp")
  ) {
    e.preventDefault();
    e.stopImmediatePropagation();

    if (e.key === "ArrowDown") {
      const pageFsBtn = document.getElementById("toggle-kds-mode");
      if (pageFsBtn) {
        setFocus(pageFsBtn);
      } else {
        const pendingToggle = document.getElementById(
          "toggle-kds-mode-pending",
        );
        if (pendingToggle) setFocus(pendingToggle);
      }
    }
    return;
  }

  // 2. Block default browser page scrolling for KDS controls
  const blockedKeys = [
    "ArrowUp",
    "ArrowDown",
    "ArrowLeft",
    "ArrowRight",
    "PageUp",
    "PageDown",
    " ",
  ];
  if (blockedKeys.includes(e.key)) {
    e.preventDefault();
  }

  const fsElement = document.fullscreenElement;
  const isLaneFs = fsElement && fsElement.hasAttribute("data-orders-section");

  const sections = ["pending", "confirmed", "ready"];

  const cards = isLaneFs
    ? Array.from(fsElement.querySelectorAll(".nh-order-card"))
    : Array.from(document.querySelectorAll(".nh-order-card"));

  const headerControls = isLaneFs
    ? [fsElement.querySelector(".toggle-kds-lane")].filter(Boolean)
    : [
        document.getElementById("store-status-toggle"),
        document.getElementById("toggle-kds-mode"),
        document.getElementById("toggle-kds-mode-pending"),
        document.getElementById("toggle-kds-mode-confirmed"),
        document.getElementById("toggle-kds-mode-ready"),
      ].filter(Boolean);

  // Unified Focus Helper
  function setFocus(element) {
    document.querySelectorAll(".kds-focused").forEach((el) => {
      el.classList.remove("kds-focused");
    });

    // Cleaned up fallback to prevent recursive stack overflow
    activeFocusedElement =
      element || document.getElementById("store-status-toggle") || cards[0];

    if (activeFocusedElement) {
      activeFocusedElement.classList.add("kds-focused");
      activeFocusedElement.focus();

      activeFocusedElement.scrollIntoView({
        behavior: "smooth",
        block: "nearest",
      });
    }
  }

  // --- ARROW UP ---
  if (e.key === "ArrowUp") {
    if (!activeFocusedElement) {
      setFocus(cards[0] || headerControls[0]);
    } else if (cards.includes(activeFocusedElement)) {
      const currentLane = activeFocusedElement.closest("[data-orders-section]");
      const laneCards = Array.from(
        currentLane.querySelectorAll(".nh-order-card"),
      );
      const idx = laneCards.indexOf(activeFocusedElement);

      if (idx > 0) {
        setFocus(laneCards[idx - 1]);
      } else {
        const laneToggle = currentLane.querySelector(".toggle-kds-lane");
        if (laneToggle) {
          setFocus(laneToggle);
        } else {
          setFocus(headerControls[0]);
        }
      }
    } else if (activeFocusedElement.classList.contains("toggle-kds-lane")) {
      if (isLaneFs) {
        if (cards.length > 0) setFocus(cards[cards.length - 1]);
      } else {
        const currentLaneName =
          activeFocusedElement.getAttribute("data-lane") ||
          (activeFocusedElement.id === "toggle-kds-mode-pending"
            ? "pending"
            : "") ||
          (activeFocusedElement.id === "toggle-kds-mode-confirmed"
            ? "confirmed"
            : "") ||
          (activeFocusedElement.id === "toggle-kds-mode-ready" ? "ready" : "");

        if (currentLaneName === "pending") {
          const pageFsBtn = document.getElementById("toggle-kds-mode");
          if (pageFsBtn) {
            setFocus(pageFsBtn);
          } else {
            const storeStatus = document.getElementById("store-status-toggle");
            if (storeStatus) setFocus(storeStatus);
          }
        } else {
          const currentLaneIdx = sections.indexOf(currentLaneName);
          if (currentLaneIdx > 0) {
            const prevLaneName = sections[currentLaneIdx - 1];
            const prevLaneElement = document.querySelector(
              `[data-orders-section="${prevLaneName}"]`,
            );

            if (prevLaneElement) {
              const prevLaneCards = Array.from(
                prevLaneElement.querySelectorAll(".nh-order-card"),
              );
              if (prevLaneCards.length > 0) {
                setFocus(prevLaneCards[prevLaneCards.length - 1]);
              } else {
                const prevLaneToggle =
                  prevLaneElement.querySelector(".toggle-kds-lane");
                if (prevLaneToggle) setFocus(prevLaneToggle);
              }
            }
          } else {
            setFocus(document.getElementById("store-status-toggle"));
          }
        }
      }
    } else if (activeFocusedElement.id === "toggle-kds-mode") {
      const storeStatus = document.getElementById("store-status-toggle");
      if (storeStatus) {
        setFocus(storeStatus);
      } else if (cards.length > 0) {
        setFocus(cards[0]);
      }
    }
    return;
  }

  // --- ARROW DOWN ---
  if (e.key === "ArrowDown") {
    if (!activeFocusedElement) {
      setFocus(cards[0] || headerControls[0]);
    } else if (activeFocusedElement.id === "store-status-toggle") {
      const pageFsBtn = document.getElementById("toggle-kds-mode");
      if (pageFsBtn) {
        setFocus(pageFsBtn);
      } else {
        const pendingToggle =
          document.getElementById("toggle-kds-mode-pending") ||
          document.querySelector('.toggle-kds-lane[data-lane="pending"]');
        if (pendingToggle) setFocus(pendingToggle);
      }
    } else if (activeFocusedElement.id === "toggle-kds-mode") {
      const pendingToggle =
        document.getElementById("toggle-kds-mode-pending") ||
        document.querySelector('.toggle-kds-lane[data-lane="pending"]') ||
        document.querySelector(
          '[data-orders-section="pending"] .toggle-kds-lane',
        );
      if (pendingToggle) {
        setFocus(pendingToggle);
      } else if (cards.length > 0) {
        setFocus(cards[0]);
      }
    } else if (activeFocusedElement.classList.contains("toggle-kds-lane")) {
      const associatedLane =
        activeFocusedElement.getAttribute("data-lane") ||
        (activeFocusedElement.id === "toggle-kds-mode-pending"
          ? "pending"
          : "") ||
        (activeFocusedElement.id === "toggle-kds-mode-confirmed"
          ? "confirmed"
          : "") ||
        (activeFocusedElement.id === "toggle-kds-mode-ready" ? "ready" : "");

      const laneElement = document.querySelector(
        `[data-orders-section="${associatedLane}"]`,
      );
      const firstCardInLane = laneElement?.querySelector(".nh-order-card");
      if (firstCardInLane) {
        setFocus(firstCardInLane);
      } else {
        const currentLaneIdx = sections.indexOf(associatedLane);
        if (currentLaneIdx < sections.length - 1) {
          const nextLaneToggle = document.getElementById(
            `toggle-kds-mode-${sections[currentLaneIdx + 1]}`,
          );
          if (nextLaneToggle) setFocus(nextLaneToggle);
        }
      }
    } else if (cards.includes(activeFocusedElement)) {
      const currentLane = activeFocusedElement.closest("[data-orders-section]");
      const laneCards = Array.from(
        currentLane.querySelectorAll(".nh-order-card"),
      );
      const idx = laneCards.indexOf(activeFocusedElement);

      if (idx < laneCards.length - 1) {
        setFocus(laneCards[idx + 1]);
      } else {
        if (isLaneFs) {
          const laneToggle = currentLane.querySelector(".toggle-kds-lane");
          if (laneToggle) setFocus(laneToggle);
        } else {
          const currentLaneName = currentLane.dataset.ordersSection;
          const currentLaneIdx = sections.indexOf(currentLaneName);

          if (currentLaneIdx < sections.length - 1) {
            const nextLaneName = sections[currentLaneIdx + 1];
            const nextLaneToggle = document.getElementById(
              `toggle-kds-mode-${nextLaneName}`,
            );
            if (nextLaneToggle) {
              setFocus(nextLaneToggle);
            } else {
              const nextLaneElement = document.querySelector(
                `[data-orders-section="${nextLaneName}"]`,
              );
              const nextLaneFirstCard =
                nextLaneElement?.querySelector(".nh-order-card");
              if (nextLaneFirstCard) setFocus(nextLaneFirstCard);
            }
          }
        }
      }
    }
    return;
  }

  // --- ARROW LEFT & RIGHT ---
  if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
    if (isLaneFs) return;

    if (cards.includes(activeFocusedElement)) {
      const currentLaneName = activeFocusedElement.closest(
        "[data-orders-section]",
      ).dataset.ordersSection;
      let targetLaneIdx = sections.indexOf(currentLaneName);

      if (e.key === "ArrowLeft" && targetLaneIdx > 0) targetLaneIdx--;
      if (e.key === "ArrowRight" && targetLaneIdx < sections.length - 1)
        targetLaneIdx++;

      const targetLane = document.querySelector(
        `[data-orders-section="${sections[targetLaneIdx]}"]`,
      );
      const targetCards = targetLane.querySelectorAll(".nh-order-card");
      if (targetCards.length > 0) {
        setFocus(targetCards[0]);
      } else {
        const laneToggle = document.getElementById(
          `toggle-kds-mode-${sections[targetLaneIdx]}`,
        );
        if (laneToggle) setFocus(laneToggle);
      }
    } else if (headerControls.includes(activeFocusedElement)) {
      const idx = headerControls.indexOf(activeFocusedElement);
      if (e.key === "ArrowLeft" && idx > 0) setFocus(headerControls[idx - 1]);
      if (e.key === "ArrowRight" && idx < headerControls.length - 1)
        setFocus(headerControls[idx + 1]);
    }
    return;
  }

  // --- PAGE UP / PAGE DOWN ---
  if (e.key === "PageUp" || e.key === "PageDown") {
    let targetContainer =
      activeFocusedElement?.closest("[data-orders-list]") ||
      document.querySelector("[data-orders-list]");
    if (targetContainer) {
      const scrollAmount = targetContainer.clientHeight * 0.8;
      targetContainer.scrollBy({
        top: e.key === "PageUp" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
    return;
  }

  // --- ENTER key logic ---
  if (e.key === "Enter") {
    if (activeFocusedElement) {
      e.preventDefault();

      // Store status trigger behavior
      if (activeFocusedElement.id === "store-status-toggle") {
        const instance = M.Dropdown.getInstance(activeFocusedElement);
        if (instance) {
          instance.isOpen ? instance.close() : instance.open();
        }
        return;
      }

      // Lane Fullscreen toggles
      if (activeFocusedElement.classList.contains("toggle-kds-lane")) {
        const laneName =
          activeFocusedElement.getAttribute("data-lane") ||
          (activeFocusedElement.id === "toggle-kds-mode-pending"
            ? "pending"
            : "") ||
          (activeFocusedElement.id === "toggle-kds-mode-confirmed"
            ? "confirmed"
            : "") ||
          (activeFocusedElement.id === "toggle-kds-mode-ready" ? "ready" : "");

        const laneContainer = document.querySelector(
          `[data-orders-section="${laneName}"]`,
        );

        if (laneContainer) {
          if (!document.fullscreenElement) {
            laneContainer.requestFullscreen?.().catch(() => {});
          } else {
            document.exitFullscreen?.().catch(() => {});
          }
        }
        return;
      }

      // Main KDS Fullscreen toggles
      if (activeFocusedElement.id === "toggle-kds-mode") {
        toggleKdsFullscreen();
        return;
      }

      // Card Bumping via Enter key
      if (cards.includes(activeFocusedElement)) {
        const orderId = activeFocusedElement.getAttribute("data-order-id");
        const isPending = activeFocusedElement.closest(
          '[data-orders-section="pending"]',
        );
        if (isPending) {
          confirmOrder(orderId, true);
        } else {
          markReadyForPickup(orderId, true);
        }
        return;
      }
    }
  }

  // --- physical KB9000 BUMP command (Unified with activeFocusedElement) ---
  if (e.key === "Control") {
    e.preventDefault();
    if (activeFocusedElement && cards.includes(activeFocusedElement)) {
      const orderId = activeFocusedElement.getAttribute("data-order-id");
      console.log(`Bumping Order ID: ${orderId}`);

      // Fallback check to make sure the custom function can handle the pure JS element
      const $activeCard = $(activeFocusedElement);
      bumpOrder(orderId, $activeCard);
      resetBuffer();
    }
    return;
  }

  // --- UNDO ACTIONS ('Z' or 'U') ---
  if (e.key.toLowerCase() === "z" || e.key.toLowerCase() === "u") {
    e.preventDefault();
    triggerKdsUndo();
    return;
  }

  // --- TAB to swap Page Fullscreen ---
  if (e.key === "Tab") {
    e.preventDefault();
    toggleKdsFullscreen();
    return;
  }

  // --- 1-9 Fast Bump Matrix ---
  if (e.key >= "1" && e.key <= "9") {
    const targetIndex = parseInt(e.key) - 1;
    const targetCard = document.querySelector(
      `.nh-order-card[data-bump-index="${targetIndex}"]`,
    );
    if (targetCard) {
      e.preventDefault();

      const isPending = targetCard.closest('[data-orders-section="pending"]');
      const orderId = targetCard.getAttribute("data-order-id");

      targetCard.style.transition = "all 100ms ease";
      targetCard.style.borderColor = "#16a34a";
      targetCard.style.transform = "scale(0.95)";

      setTimeout(() => {
        if (isPending) {
          confirmOrder(orderId, true);
        } else {
          markReadyForPickup(orderId, true);
        }
      }, 100);
    }
    return;
  }

  // --- SPACEBAR / BUMP SLOT #1 ---
  // Safely isolated from standard Enter events
  if (e.key === " ") {
    e.preventDefault();
    const oldestCard = document.querySelector(
      '.nh-order-card[data-bump-index="0"]',
    );
    if (oldestCard) {
      const orderId = oldestCard.getAttribute("data-order-id");
      const isPending = oldestCard.closest('[data-orders-section="pending"]');
      if (isPending) {
        confirmOrder(orderId, true);
      } else {
        markReadyForPickup(orderId, true);
      }
    }
    return;
  }

  // --- RECALL last bumped order ---
  if (e.key === "-" || e.key === "Minus") {
    e.preventDefault();
    console.log("Recall key pressed. Opening last bumped order...");
    recallLastOrder();
    return;
  }
}

function refreshBumpBarSlots() {
  // If the focused card was destroyed or bumped off the screen, clean up the variable
  if (activeFocusedElement && !document.body.contains(activeFocusedElement)) {
    activeFocusedElement = null;
  }

  // Get all active cards across the dashboard
  const activeCards = document.querySelectorAll(".nh-order-card");

  activeCards.forEach((card, index) => {
    // 🚨 ENFORCE TAB INDEX: Makes the div focusable in its natural DOM position
    card.setAttribute("tabindex", "0");

    // Handle bump bar hotkey slots (1-9)
    const oldMarker = card.querySelector(".kds-bump-slot");
    if (oldMarker) oldMarker.remove();

    if (index < 9) {
      card.setAttribute("data-bump-index", index);
      const slotBadge = document.createElement("div");
      slotBadge.className = "kds-bump-slot";
      slotBadge.style.cssText =
        "position: absolute; top: -10px; left: -10px; background: #e65100; color: #fff; font-weight: 900; font-size: 1.25rem; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 10; border: 2px solid #fff;";
      slotBadge.textContent = index + 1;
      card.style.position = "relative";
      card.appendChild(slotBadge);
    } else {
      card.removeAttribute("data-bump-index");
    }
  });
}

function toggleKdsFullscreen(container) {
  // If we targeted a specific lane container
  if (container) {
    const lane = container.getAttribute("data-orders-section");
    const toggleButton = document.querySelector(
      `.toggle-kds-lane[data-lane="${lane}"]`,
    );

    // Check if the current fullscreen element is this specific lane container
    if (document.fullscreenElement === container) {
      // Exit fullscreen
      document
        .exitFullscreen?.()
        .then(() => {
          if (toggleButton) toggleButton.classList.remove("active");
          document.body.classList.remove("kds-fullscreen-mode");
        })
        .catch((err) => console.error(err));
    } else {
      // Enter fullscreen
      container
        .requestFullscreen?.()
        .then(() => {
          if (toggleButton) toggleButton.classList.add("active");
          document.body.classList.add("kds-fullscreen-mode");
        })
        .catch((err) => console.error(err));
    }
    return;
  }

  // Fallback for global page-level fullscreen
  if (document.fullscreenElement) {
    document
      .exitFullscreen?.()
      .then(() => {
        document.body.classList.remove("kds-fullscreen-mode");
      })
      .catch((err) => console.error(err));
  } else {
    document.documentElement
      .requestFullscreen?.()
      .then(() => {
        document.body.classList.add("kds-fullscreen-mode");
      })
      .catch((err) => console.error(err));
  }
}

// KDS TICKING COUNTDOWN CLOCK ENGINE
function startKdsTimers() {
  setInterval(function() {
    const cards = document.querySelectorAll('.nh-order-card');
    const now = new Date();

    cards.forEach(card => {
      // --- RESTORED AUTO-CREATE LOGIC ---
      let timerBadge = card.querySelector('.kds-timer-badge');
      if (!timerBadge) {
        timerBadge = document.createElement('span');
        timerBadge.className = 'kds-timer-badge right'; // added 'right' to align nicely
        timerBadge.style.cssText = 'font-weight: bold; font-size: 1.25rem; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 8px;';
        
        const header = card.querySelector('.nh-card-header') || card.querySelector('div');
        if (header) $(header).prepend(timerBadge);
      }

      const createdStr = card.getAttribute('data-created-at');
      if (!createdStr) return;

      const createdTime = new Date(createdStr.replace(/-/g, "/"));
      if (isNaN(createdTime.getTime())) return;

      const diffMs = now - createdTime;
      const totalSecs = Math.floor(diffMs / 1000);
      if (totalSecs < 0) return;

      const mins = Math.floor(totalSecs / 60);
      const secs = totalSecs % 60;
      timerBadge.textContent = mins + ":" + (secs < 10 ? "0" : "") + secs;

      // Color code thresholds
      if (mins >= 20) {
        timerBadge.style.background = '#d32f2f'; // Red Alert
        timerBadge.style.color = '#fff';
      } else if (mins >= 12) {
        timerBadge.style.background = '#fbc02d'; // Warning Amber
        timerBadge.style.color = '#000';
      } else {
        timerBadge.style.background = '#388e3c'; // Fresh Green
        timerBadge.style.color = '#fff';
      }
    });
  }, 1000);
}


document.addEventListener("DOMContentLoaded", function () {
  // 1. Bind click event to the main, global KDS fullscreen button (if present on the view)
  const mainFsBtn = document.getElementById("toggle-kds-mode");
  if (mainFsBtn) {
    mainFsBtn.addEventListener("click", function (e) {
      e.preventDefault();
      toggleKdsFullscreen();
    });
  }

  // 2. Bind click events to ALL individual lane fullscreen buttons (querySelectorAll)
  const laneFsButtons = document.querySelectorAll(".toggle-kds-lane");
  laneFsButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const lane = e.currentTarget.getAttribute("data-lane");
      const laneContainer = document.querySelector(
        `[data-orders-section="${lane}"]`,
      );
      if (laneContainer) {
        toggleKdsFullscreen(laneContainer);
      }
    });
  });

  // 3. Sync the active focused element tracker with standard Tab-key navigation
  document.addEventListener("focusin", function (e) {
    const target = e.target;
    const isCard = target.classList.contains("nh-order-card");
    const isHeaderControl =
      target.id === "store-status-toggle" ||
      target.id === "toggle-kds-mode" ||
      target.classList.contains("toggle-kds-lane");

    if (isCard || isHeaderControl) {
      if (activeFocusedElement) {
        activeFocusedElement.classList.remove("kds-focused");
      }
      activeFocusedElement = target;
      activeFocusedElement.classList.add("kds-focused");
    }
  });

  // 4. Remove highlight if focus leaves KDS elements entirely
  document.addEventListener("focusout", function (e) {
    if (
      activeFocusedElement &&
      !activeFocusedElement.contains(document.activeElement)
    ) {
      activeFocusedElement.classList.remove("kds-focused");
      activeFocusedElement = null;
    }
  });
});
