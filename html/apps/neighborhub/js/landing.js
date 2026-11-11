$(document).ready(function () {
  "use strict";

  const input = document.getElementById("ce-address");
  const form = document.getElementById("ce-search-form");
  const suggestions = document.getElementById("ce-suggestions");
  const grid = document.getElementById("ce-merchant-grid");
  const note = document.getElementById("ce-location-note");
  const count = document.getElementById("ce-result-count");
  const locateButton = document.getElementById("ce-locate");
  const categoryButtons = Array.from(
    document.querySelectorAll(".ce-categories button"),
  );
  const storageKey = "cloudEats.deliveryLocation";

  let location = null;
  let category = "";
  let suggestionTimer = null;
  let suggestionRequest = 0;
  let searchRequest = 0;

  function setNote(message, isError) {
    note.textContent = message;
    note.classList.toggle("is-error", Boolean(isError));
  }

  function hideSuggestions() {
    suggestions.hidden = true;
    suggestions.replaceChildren();
  }

  function saveLocation(address, lat, lng) {
    location = { address, lat: Number(lat), lng: Number(lng) };
    input.value = address;
    localStorage.setItem(storageKey, JSON.stringify(location));
    hideSuggestions();
    setNote("Showing places near " + address, false);

    const resultsElement = document.getElementById("merchant-grid-section");
    if (resultsElement) {
      const headerOffset = 70;
      const elementPosition = resultsElement.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.scrollY - headerOffset;
      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth",
      });
    }
    searchMerchants();
  }

  function requestSuggestions(query) {
    const requestId = ++suggestionRequest;
    loading(4);
    mb.ajax({
      url: "?api=neighborhub&action=geocode_proxy",
      method: "GET",
      dataType: "json",
      data: { q: query },
      success: function (results) {
        if (requestId !== suggestionRequest || !Array.isArray(results)) return;
        suggestions.replaceChildren();

        results.forEach(function (result, index) {
          if (!result.display_name || !result.lat || !result.lon) return;
          const item = document.createElement("li");
          const button = document.createElement("button");
          button.type = "button";
          button.role = "option";
          button.setAttribute("aria-selected", "false");
          button.textContent = result.display_name;
          button.addEventListener("click", function () {
            saveLocation(result.display_name, result.lat, result.lon);
          });
          item.appendChild(button);
          suggestions.appendChild(item);
          if (index === 0) button.dataset.first = "true";
        });

        suggestions.hidden = suggestions.childElementCount === 0;
      },
      error: function () {
        if (requestId === suggestionRequest) hideSuggestions();
      },
      complete: function () {
        loading(0);
      },
    });
  }

  function showLoading() {
    count.textContent = "Finding nearby places";
    grid.replaceChildren();
    for (let index = 0; index < 6; index += 1) {
      const skeleton = document.createElement("div");
      skeleton.className = "ce-skeleton";
      skeleton.setAttribute("aria-hidden", "true");
      skeleton.innerHTML = "<span></span><div><i></i><i></i><i></i></div>";
      grid.appendChild(skeleton);
    }
  }

  function showMessage(title, message, isError) {
    count.textContent = "";
    grid.replaceChildren();
    const empty = document.createElement("div");
    empty.className = "ce-empty-state" + (isError ? " is-error" : "");
    const icon = document.createElement("span");
    icon.className = "ce-empty-icon";
    icon.innerHTML = isError
      ? '<i class="fas fa-exclamation-circle" aria-hidden="true"></i>'
      : '<i class="fas fa-store" aria-hidden="true"></i>';
    const heading = document.createElement("h3");
    heading.textContent = title;
    const description = document.createElement("p");
    description.textContent = message;
    empty.append(icon, heading, description);

    if (!isError) {
      const join = document.createElement("a");
      join.href = "/?p=login";
      join.className = "ce-join-link";
      join.textContent = "Bring your business to Cloud Eats";
      empty.appendChild(join);
    }
    grid.appendChild(empty);
  }

  function operatingStatus(merchant) {
    const status = merchant.operational_status;
    if (!status || !["open", "closed"].includes(status.status)
        || typeof status.label !== "string" || typeof status.message !== "string") {
      console.error("Invalid merchant operational status", merchant.id);
      return { label: "Status unavailable", message: "", open: null };
    }
    return {
      label: status.message === "Hours not listed" ? status.message : status.label,
      message: status.message === "Hours not listed" ? "" : status.message,
      open: status.status === "open",
    };
  }

  function merchantCard(merchant) {
    const card = document.createElement("a");
    const status = operatingStatus(merchant);
    card.className = "ce-merchant-card";
    // set onclick

    card.addEventListener("click", function (event) {
      event.preventDefault();
      loading(4);
      window.location.href =
        "/?app=neighborhub&view=customer&p=merchant_products&merchant_id=" +
        encodeURIComponent(merchant.id);
    });
    
    card.href =
      "/?app=neighborhub&view=customer&p=merchant_products&merchant_id=" +
      encodeURIComponent(merchant.id);
    card.setAttribute(
      "aria-label",
      "Browse " + (merchant.business_name || "local store") + ". " +
        status.label + (status.message ? ". " + status.message : ""),
    );

    const imageWrap = document.createElement("div");
    imageWrap.className = "ce-card-image";
    if (merchant.image_url) {
      const image = document.createElement("img");
      image.src = merchant.image_url;
      image.alt = "";
      image.loading = "lazy";
      image.addEventListener(
        "error",
        function () {
          image.remove();
          imageWrap.classList.add("is-placeholder");
          imageWrap.prepend(document.createTextNode("CE"));
        },
        { once: true },
      );
      imageWrap.appendChild(image);
    } else {
      imageWrap.classList.add("is-placeholder");
      imageWrap.textContent = "CE";
    }
    if (status.open === true) {
      const imageStatus = document.createElement("span");
      imageStatus.className = "ce-card-image-status-dot";
      imageStatus.setAttribute("aria-hidden", "true");
      imageWrap.appendChild(imageStatus);
    }

    const body = document.createElement("div");
    body.className = "ce-card-body";
    const title = document.createElement("h3");
    title.textContent = merchant.business_name || "Local store";
    const address = document.createElement("p");
    address.className = "ce-card-address";
    address.textContent = merchant.full_address || merchant.address || "Your neighborhood";
    const details = document.createElement("div");
    details.className = "ce-card-details";
    const distance = document.createElement("span");
    distance.textContent = Number(merchant.distance_miles).toFixed(1) + " mi";
    const fee = document.createElement("span");
    fee.textContent =
      "$" + Number(merchant.delivery_fee || 0).toFixed(2) + " delivery";
    details.append(distance, fee);

    const statusLine = document.createElement("p");
    statusLine.className =
      "ce-card-status" +
      (status.open === true
        ? " is-open"
        : status.open === false
          ? " is-closed"
          : "");
    statusLine.append(document.createTextNode(status.label));
    if (status.message) {
      const statusDot = document.createElement("span");
      statusDot.className = "ce-card-status-separator";
      statusDot.setAttribute("aria-hidden", "true");
      statusLine.append(statusDot, document.createTextNode(status.message));
    }

    const browse = document.createElement("span");
    browse.className = "ce-card-browse";
    browse.innerHTML =
      'View menu <i class="fas fa-arrow-right" aria-hidden="true"></i>';
    body.append(title, address, details, statusLine, browse);
    card.append(imageWrap, body);
    return card;
  }

  function renderMerchants(merchants) {
    count.textContent = merchants.length
      ? merchants.length + (merchants.length === 1 ? " place" : " places")
      : "";
    if (!merchants.length) {
      $('body').removeClass('places-located');
      showMessage(
        "No local stores currently online near that location",
        "Try a nearby address or another category, or try back again a little later. Local businesses are always welcome to join.",
        false,
      );
      return;
    }
    $('body').addClass('places-located');
    grid.replaceChildren(...merchants.map(merchantCard));
  }

  function searchMerchants() {
    if (
      !location ||
      !Number.isFinite(location.lat) ||
      !Number.isFinite(location.lng)
    )
      return;
    const requestId = ++searchRequest;
    showLoading();
    loading(4);
    mb.ajax({
      url: "?api=neighborhub&action=search_merchants",
      method: "POST",
      dataType: "json",
      data: JSON.stringify({
        lat: location.lat,
        lng: location.lng,
        q: category,
      }),
      success: function (response) {
        if (requestId !== searchRequest) return;
        if (response && response.success && Array.isArray(response.merchants)) {
          renderMerchants(response.merchants);
        } else {
          showMessage(
            "We couldn't find a match",
            response && response.error
              ? response.error
              : "Try a different category or address.",
            true,
          );
        }
      },
      error: function () {
        if (requestId === searchRequest)
          showMessage(
            "Search is taking a break",
            "We couldn't reach the local store directory. Please try again.",
            true,
          );
      },
      complete: function () {
        loading(0);
      },
    });
  }
  
  const heroBg = document.getElementById("heroBgParallax");

  if (!heroBg) {
    console.warn("Parallax element #heroBgParallax not found in DOM.");
    return;
  }

  function updateParallax() {
    const currentScroll = window.scrollY || window.pageYOffset;
    // Apply transform if hero is still in view
    if (currentScroll <= 800) {
      const translateY = currentScroll * 0.3; // 30% scroll speed
      heroBg.style.transform = "translate3d(0, " + translateY + "px, 0)";
    }
  }

  window.addEventListener(
    "scroll",
    function () {
      window.requestAnimationFrame(updateParallax);
    },
    { passive: true },
  );

  input.addEventListener("input", function () {
    const query = input.value.trim();
    if (location && query !== location.address) location = null;
    window.clearTimeout(suggestionTimer);
    if (query.length < 3) {
      ++suggestionRequest;
      hideSuggestions();
      return;
    }
    suggestionTimer = window.setTimeout(function () {
      requestSuggestions(query);
    }, 1000);
  });

  input.addEventListener("keydown", function (event) {
    if (event.key === "Escape") hideSuggestions();
    if (event.key === "Enter" && !suggestions.hidden) {
      const first = suggestions.querySelector("button[data-first='true']");
      if (first) {
        event.preventDefault();
        first.click();
      }
    }
  });

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    const first = suggestions.querySelector("button[data-first='true']");
    if (first) return first.click();
    if (location) {
      const resultsElement = document.getElementById("merchant-grid-section");
      if (resultsElement) {
        const headerOffset = 70;
        const elementPosition = resultsElement.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.scrollY - headerOffset;
        window.scrollTo({
          top: offsetPosition,
          behavior: "smooth",
        });
      }

      return searchMerchants();
    }
    setNote("Choose an address from the suggestions to search nearby.", true);
    input.focus();
  });

  document.addEventListener("click", function (event) {
    //if (!form.contains(event.target)) hideSuggestions();
  });

  locateButton.addEventListener("click", function () {
    if (!navigator.geolocation) {
      setNote("Location services are not available in this browser.", true);
      return;
    }
    locateButton.disabled = true;
    locateButton.classList.add("is-loading");
    setNote("Finding your location…", false);
    navigator.geolocation.getCurrentPosition(
      function (position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        loading(4);
        mb.ajax({
          url: "?api=neighborhub&action=reverse_geocode_proxy",
          method: "GET",
          dataType: "json",
          data: { lat: lat, lng: lng },
          success: function (result) {
            saveLocation(
              result && result.display_name
                ? result.display_name
                : "Current location",
              lat,
              lng,
            );
            locateButton.disabled = false;
            locateButton.classList.remove("is-loading");
          },
          error: function () {
            saveLocation("Current location", lat, lng);
            locateButton.disabled = false;
            locateButton.classList.remove("is-loading");
          },
          complete: function () {
            loading(0);
          },
        });
      },
      function (error) {
        locateButton.disabled = false;
        locateButton.classList.remove("is-loading");
        setNote(
          error.code === error.PERMISSION_DENIED
            ? "Allow location access, or enter an address instead."
            : "Couldn't determine your location. Enter an address to continue.",
          true,
        );
      },
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
    );
  });

  categoryButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      category = button.dataset.category || "";
      categoryButtons.forEach((item) =>
        item.classList.toggle("is-active", item === button),
      );
      if (location) searchMerchants();
    });
  });

  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || "null");
    if (
      saved &&
      saved.address &&
      Number.isFinite(Number(saved.lat)) &&
      Number.isFinite(Number(saved.lng))
    ) {
      location = {
        address: saved.address,
        lat: Number(saved.lat),
        lng: Number(saved.lng),
      };
      input.value = location.address;
      setNote("Showing places near " + location.address, false);
      searchMerchants();
    }
  } catch (error) {
    localStorage.removeItem(storageKey);
  }
});
