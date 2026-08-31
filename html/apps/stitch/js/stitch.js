let stitch = {
  state: {
    anchors: {},
  },
  filter: {
    start_date: [],
    end_date: [],
    limit: 20,
  },
  geo: {
    defaultCenter: null,
  },
};

(function () {
  const rawStorage = localStorage.getItem("mediabrain");
  if (rawStorage) {
    mb.storage = JSON.parse(rawStorage);
    if (!mb.storage.apps.stitch) {
      mb.storage.apps.stitch = {
        currentDraft: [],
        chronicle: [],
        preferences: {
          stitch_dimension: "projected_to", // 🎯 Default to Discovery Mode
          mute_audio: true,
          map_center: null,
        },
      };
      storage_set();
    }
    storage_get();
  }
  if (!mb.storage.apps.stitch.preferences.map_center) {
    mb.getLocality(function (coords) {
      stitch.geo.defaultCenter = mb.storage.apps.stitch.preferences.map_center =
        {
          lat: coords.lat,
          lng: coords.lng,
        };
      storage_set();
    });
  }
})();

// 🏗️ THE ATOMIC ATTACHMENT FUNCTION
function initStitchElements($container) {
  // Re-trigger Materialize effects or custom listeners here
  //if (typeof M !== "undefined") M.AutoInit();

  // Add any specific click handlers for the new cards
  $(".collapsible", $container).collapsible();

  window.onYouTubeIframeAPIReady();
  window.initializeVideoPlayers($container);
  /*
  $container
    .find(".vouch-btn")
    .off("click")
    .on("click", function () {});
  */
  window.stitchObserver.observe(document.getElementById("horizon-sentinel"));
}

function processCommand(input) {
  let cmd = input.toLowerCase().trim();
  $terminal_viewport = $("#cli-terminal-viewport");
  $sub_processor = $("#prompt_output", $terminal_viewport);
  const $container = $("#stitch-card-container");
  const $cli = $("#stitch-cli");

  // 🔊 Play the "Data Crunch" sound we made
  if (stitch.audio) stitch.audio.lcars_stream(600);
  $sub_processor
    .closest(".prompt-container")
    .animate({ scrollTop: $(this).height() }, "slow");

  if (cmd === "/help") {
    $sub_processor.append(
      '<div class="monospace">' +
        cmd +
        ' <div class="quantum-spinner"></div></div>',
    );

    mb.ajax(
      {
        url: "?api=stitch",
        method: "POST",
        data: JSON.stringify({
          action: "help", // or whatever action name you settled on
          page: "index",
        }),
      },
      function (response) {
        $sub_processor.append(
          "<hr></hr><div class='monospace'>" + response.data.html + "</div>",
        );
        // scroll $sub_processor to top of content
        //$sub_processor.scrollTop(0);
        $terminal_viewport.addClass("active-cli-output");
        $(".quantum-spinner", $sub_processor).remove();
        $cli.focus();
        cli_request_complete();
      },
    );
  } else if (cmd.startsWith("/t") || cmd.startsWith("/terminal")) {
    $terminal_viewport.addClass("active-cli-output");
    // -- /CLS
    // -- CLEAR
  } else if (cmd.startsWith("/cls") || cmd.startsWith("/clear")) {
    $("#prompt_output").fadeOut(100, function () {
      $(this).empty().show();
      $cli.val("").focus();
      // Optional: Re-print the system header
      $(this).append(
        '<div class="grey-text monospace">[ SYSTEM_READY_FOR_INPUT ]</div>',
      );
    });
    return;
  } else if (cmd.startsWith("/scan")) {
    $sub_processor.append(
      '<div class="monospace">' +
        cmd +
        ' <div class="quantum-spinner" style="height: 75px; margin: 0 auto;"></div></div>',
    );
    // Logic to trigger a search for stitches with no parents/children
    stitch.api("load_more", {
      filter: "orphans",
      callback: function (data) {
        $(".quantum-spinner", $sub_processor).remove();
      },
    });
  } else if (cmd.startsWith("/generate_observation")) {
    // Generate an Observation
    const commandBody = input.replace("/generate_observation", "").trim();

    // Pattern to split date range and location (e.g., "1920-1930 @ Chicago, IL")
    const [datePart, locationPart] = commandBody
      .split("@")
      .map((s) => s.trim());

    const datePattern = /(\d{1,2}-\d{1,2}-\d{4})|(\d{4})/g;
    const dates = datePart.match(datePattern);

    $sub_processor.append('<div class="monospace">' + cmd + "</div>");
    if (dates) {
      mb.terminalPrint(
        `🛰️ TARGETING: ${dates[0]} ${locationPart ? "IN " + locationPart : ""}...`,
      );
    }
    $sub_processor.append(
      '🛰️ CALCULATING TEMPORAL COORDINATES... <div class="quantum-spinner" style="margin: 0 auto;"></div>',
    );

    stitch.api("generate_observation", {
      start: dates[0],
      end: dates[1] || dates[0],
      location: locationPart || null,
      callback: function (data) {
        console.log(data);
        mb.terminalPrint(`\n[SIGNAL_STABILIZED]: Memory Anchor secured.`);
        mb.terminalPrint(
          `[SIGNAL_LOCKED]: YEAR ${data.anchor.projected_to}</p>`,
        );
        mb.terminalPrint(`ANCHOR_POINT_ESTABLISHED. <3</p>`);
        mb.terminalPrint(`NEW_OBSERVATION: ${data.anchor.content.body}<3</p>`);
        mb.terminalPrint(`DATE_OF_OBSERVATION: ${data.timestamp} <3</p><br/>`);
        // Optionally refresh the list to see the new card
        $(".quantum-spinner", $sub_processor).remove();
        if (stitch.audio) stitch.audio.lcars_stream(600);
        $container.prepend(data.html);
        $sub_processor.focus();
        // Notify user that the observation has been made and added to the feed
        notify("New Observation Created - " + data.timestamp);
        //$("html, body").animate({ scrollTop: 0 }, "slow");
        window.isQuerying = false;
      },
    });
  } else if (cmd.startsWith("/find ")) {
    string_search();
  } else if (cmd === "/jump random") {
    const year = Math.floor(Math.random() * 2026);
    $sub_processor.append("<div class='monospace'>" + cmd + "</div>");
    $("#chronos-dial").val(year).trigger("change");
  } else if (cmd === "/reset-filter") {
    $sub_processor.append('<div class="monospace">' + cmd + "</div>");
    resetResultsContainer();
    $terminal_viewport.removeClass("active-cli-output");
  } else if (cmd.startsWith("/")) {
    $sub_processor.append(
      "<div class='monospace'>" + "UNKNOWN_COMMAND: " + cmd + "</div>",
    );
    $cli.focus();
  } else {
    // Default CLI BehaviorS
    string_search();
  }

  function string_search() {
    $sub_processor.append(
      '<div class="monospace">' +
        cmd +
        ' <div class="quantum-spinner"></div></div>',
    );
    // Logic to trigger a search for stitches with no parents/children
    stitch.filter.search_string = cmd.replace("/find ", "");
    stitch.api("search_string", {
      callback: function (data) {
        $(".quantum-spinner", $sub_processor).remove();
        //console.log(data);
        if (!data.html) {
          $sub_processor.append();
        } else {
          renderNewStitches(data.html, function () {
            $terminal_viewport.removeClass("active-cli-output");
            window.isQuerying = false;
          });
        }
      },
    });
  }
}

const phantomHtml = `
    <div id="phantom-card" class="black darken-4 crt-flicker center-align" style="border: 1px dashed #9b59b2;">
      <div class="" style="margin-top: 15px;">
        <div class="quantum-spinner" style="height: 75px; margin: 0 auto;"></div>
      </div>
      <div class="center-align purple-text" style="margin-bottom: 15px;">
          [ SCANNING DEEP ARCHIVE & PEER NETWORK ]
      </div>
    </div>`;

stitch.api = function (action = "load_more", options = null) {
  if (window.isQuerying) return;
  let params = {};
  let targetDepth = null;
  let callback = null;
  if (typeof options === "string") {
    targetDepth = options;
  }
  if (options && typeof options === "object") {
    targetDepth = options.targetDepth || null;
    // 🛠️ Fixed the 'functon' typo here
    callback = typeof options.callback === "function" ? options.callback : null;
    // unset the callback, so the jquery api call doesnt automatically call it... we want to pass it along to the ui prep fuctions, so that they can call it when they are done...
    delete options.callback;
  }

  // 🎯 If no depth is passed, we assume we are at the Present (100)
  const depth = targetDepth !== null ? targetDepth : $("#time-slider").val();
  const dimension = mb.storage.apps.stitch.preferences.stitch_dimension;
  const $container = $("#stitch-card-container");
  let $sentinel = $("#horizon-sentinel");

  // Pull current date bounds directly from the HUD inputs
  stitch.filter.startDate = $("#date-start").val();
  stitch.filter.endDate = $("#date-end").val();
  let d = new Date(stitch.filter.endDate);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0"); // Months are 0-indexed
  const day = String(d.getDate()).padStart(2, "0");
  const endDatePlusOne = `${year}-${month}-${day}`;

  window.isQuerying = true;

  //$('.stitch-wrapper, #phantom-card', $container).remove(); // 🧹
  //console.log($container.html());
  $phantomCard = $(phantomHtml);

  // 🌀 Visual Feedback
  if (["load_more", "sentinel_load_more"].includes(action)) {
    //$("#horizon-sentinel").html('<div class="quantum-spinner"></div>');
    $sentinel.html(
      '<div class="satellite-loader"></div><div class="loading-text">Scanning Horizon...</div>',
    );
  } else {
    $container.prepend($phantomCard);
  }

  switch (action) {
    case "load_more":
    case "sentinel_load_more":
    case "chronos_dial":
    case "search_string":
    default:
      params = {
        api: "stitch",
        action: action,
        dimension: dimension, // 🎯 The Dimension
        depth: depth, // 🎯 The Slider position
        start_date: stitch.filter.startDate, // 🎯 The Genesis bound
        end_date: endDatePlusOne, // 🎯 The Apocalypse bound
        search: stitch.filter.search_string,
        before_id: $("#stitch-card-container .stitch-wrapper")
          .last()
          .data("id"),
        limit: stitch.filter.limit,
      };
      break;
  }

  // 🛰️ The Data Payload

  // 🛰️ The Data Payload with Error Shielding
  try {
    mb.getJSON(
      "index.php",
      Object.assign({}, params, options),
      function (response) {
        if (response.status === "success" && response.data) {
          window.horizonReached = response.data.horizon_reached;
        }

        if (["chronos_dial", "search_string"].includes(action)) {
          resetResultsContainer();
        }

        $phantomCard.remove();

        if (window.horizonReached) {
          // ... (Your existing Horizon Reached logic) ...
          $sentinel.hide();
          $("#stitch-cli").focus();
        }

        if (
          response.status === "success" &&
          response.data?.html?.trim().length > 0
        ) {
          // Add new anchors to App State object
          if (response.data.anchor && !response.data.anchors) {
            stitch.state.anchors[response.data.anchor.id] =
              response.data.anchor;
          } else if (response.data.anchors) {
            response.data.anchors.forEach((a) => {
              stitch.state.anchors[a.id] = a;
            });
          }

          if ($("#positronic-matrix-view .vis-network canvas").length) {
            stitch.getNetworkData();
            stitch.geo.refreshMarkers();
          }
          if ($("#leaflet-map-view").hasClass("leaflet-container")) {
            stitch.geo.refreshMarkers(); // Drops the orange dots
            stitch.geo.weaveNexus(); // Weaves the purple threads
          }
          if (typeof callback === "function") {
            console.log(response);
            callback(response.data);
            return;
          }
          callback = function () {
            window.isQuerying = false;
          };

          renderNewStitches(response.data.html, callback);
        }

        // 🛠️ Typo Fix: 'function', not 'functon'
        //if (typeof callback === "function") callback(response.data);
      },
    ).fail(function (jqXHR, textStatus, errorThrown) {
      // 🚨 The Emergency Brake
      window.isQuerying = false;
      $phantomCard.remove();
      console.error("CRITICAL_STITCH_FAILURE:", textStatus, errorThrown);
      M.toast({
        html: "Signal Lost: Horizon scan interrupted.",
        classes: "red darken-4",
      });
    });
  } catch (e) {
    window.isQuerying = false;
    console.error("UI_THREAD_EXCEPTION:", e);
  }
};

mb.terminalPrint = function (text) {
  const $sub_processor = $("#prompt_output");
  $sub_processor.append("<div class='monospace'>" + text + "</div>");
  $sub_processor.scrollTop($sub_processor[0].scrollHeight);
};

function cli_request_complete() {
  window.isQuerying = false;
}

function renderNewStitches(htmlData, callback = null) {
  const $container = $("#stitch-card-container");
  let $sentinel = $("#horizon-sentinel");

  // 🎯 FIX: If the sentinel was wiped, bring it back BEFORE we inject data
  if (!$sentinel.length) {
    $container.append(
      '<div id="horizon-sentinel" style="min-height: 80px; text-align: center;"></div>',
    );
    $sentinel = $("#horizon-sentinel");
  }

  if (!$container.length || !htmlData) return false;

  // 1. Convert the raw HTML string into jQuery objects
  const $newElements = $(htmlData);

  // 2. THE PRECISION DROP
  // If the sentinel exists, we inject BEFORE it so the sentinel stays at the very bottom
  if ($sentinel.length) {
    $newElements.insertBefore($sentinel);
    //console.log($newElements);
  } else {
    $container.append($newElements);
    //console.log($newElements);
  }

  // 3. ATOMIC INITIALIZATION
  // This is the one function we discussed to re-bind listeners (Materialize, etc.)
  initStitchElements($newElements);

  if (typeof callback === "function") callback($newElements);
  if (typeof window.updateVitals === "function") window.updateVitals();

  mb.logMission(
    "COMPONENT_RENDER_COMPLETE: New truths integrated into the Field.",
  );
  $("body").removeClass("horizon-reached");
  window.horizonReached = false;
}

function resetResultsContainer() {
  const $container = $("#stitch-card-container");
  stitch.filter.search_string = "";
  /* Remove the Cards... NOT THE SENTENEL ! */
  const $cards = $("#stitch-card-container .stitch-wrapper"); // 🎯 FIX: Targeted the correct wrapper class
  $cards.each(function (index) {
    $(this)
      .addClass("chronos-glitch")
      .stop(true, true)
      .fadeOut(200, function () {
        // Optional: Remove from DOM once faded to keep it clean
        $(this).remove();
      });
  });
}

stitch.math = {
  // 🧮 Calculate the Drift Coefficient (0.0 to 1.0)
  getDrift: function (stitchYear) {
    const currentYear = new Date().getFullYear(); // 2026 Perspective
    const targetYear = $("#time-slider").val(); // Where you are looking

    const maxSpan = 2026; // From Year 1 to Now
    const distance = Math.abs(targetYear - stitchYear);

    // returns a value where 0 is "No Drift" and 1 is "Maximum Temporal Distance"
    return (distance / maxSpan).toFixed(4);
  },
};

stitch.applyDrift = function () {
  const $slider = $("#time-slider");
  if (!$slider.length) return;

  const observerYear = parseInt($slider.val()); // e.g., 2026

  $(".stitch-wrapper").each(function () {
    // 🎯 1. Get the Raw Unix Timestamp (Seconds)
    const unixStamp = $(this).data("timestamp");
    if (!unixStamp) return;

    // 🎯 2. CONVERT TO 4-DIGIT YEAR
    // We multiply by 1000 because JS Date needs milliseconds
    const cardYear = new Date(unixStamp * 1000).getFullYear();

    // 🎯 3. CALCULATE DISTANCE
    const yearDiff = Math.abs(observerYear - cardYear);

    // 🎯 4. SET INTENSITY (Focus window is 100 years)
    let intensity = 1 - yearDiff / 100;
    intensity = Math.max(0.35, Math.min(1, intensity)); // Floor it at 35% opacity

    // 🎯 5. APPLY VISUALS
    $(this).css({
      opacity: intensity,
      filter: `blur(${(1 - intensity) * 3}px) grayscale(${(1 - intensity) * 100}%)`,
      transform: `scale(${0.98 + intensity * 0.02})`,
      transition: "none", // Instant feedback while dragging!
    });
  });
};

function vouchForStitch(anchorId) {
  console.log("Local Anchor Securing... Observation:", anchorId);

  // 1. UPDATE THE LOCAL "SHEEP" (mediabrain.js storage)
  if (typeof mb.storage.apps.stitch.vouches === "undefined") {
    mb.storage.apps.stitch.vouches = {};

    // Increment local count so you have a personal record
    mb.storage.apps.stitch.vouches[anchorId] =
      (mb.storage.apps.stitch.vouches[anchorId] || 0) + 1;
    storage_set(); // Saves to LocalStorage immediately
  }

  // 2. UPDATE THE UI IMMEDIATELY (Optimistic UI)
  const countSpan = $("#vouch-count-" + anchorId);
  const currentCount = parseInt(countSpan.text());
  countSpan.text(currentCount + 1);

  // 3. TELL THE SATELLITE (Server Uplink)
  mb.post(
    "?api=stitch&action=vouch",
    { id: anchorId },
    function (data) {
      if (data.status === "success") {
        $("#total-vouch-count").addClass("vouch-flash");
        setTimeout(
          () => $("#total-vouch-count").removeClass("vouch-flash"),
          1000,
        );

        if (typeof M !== "undefined") {
          // THE MAGIC TOUCH: Recalculate the system vitals immediately
          if (window.updateVitals) {
            mb.updateTickerStealth(
              "STABILITY INCREASED: VOUCH SECURED FOR ANCHOR_" +
                anchorId +
                " ... <3",
            );

            window.updateVitals();
            M.toast({
              html: "Fidelity Increased & Anchored! <3",
              classes: "purple darken-3 white-text pulse-toast",
              displayLength: 2000,
            });
          }
        }
      }
    },
    "json",
  ).fail(function (err) {
    // If the server fails, the UI and LocalStorage are already updated.
    // We just inform the user that the global sync is pending.
    M.toast({
      html: "UPLINK DELAYED - LOCAL ANCHOR SECURE.",
      classes: "orange darken-4",
      displayLength: 2000,
    });
  });
}

function branchStitch($stitchCard) {
  const $stage = $("#inline-stitch-stage");
  $("#newStitchForm input[name='parent_id']").val($stitchCard.data("id"));

  // 1. If it's already open, just scroll to it (in case they lost it)
  toggleStitchForm();
}

/**
 * Called by the 'NEXUS' button on cards
 */
function nexusLinkStitch($stitchCard, showForm = false) {
  const $stage = $("#inline-stitch-stage");

  //console.log("NEXUS_LINKING_STITCH:", $stitchCard);
  // 1. Show the form if it's hidden
  // 2. Prevent duplicate links
  let input = $("#nexus_ids");
  let currentIds = input.val() ? input.val().split(",") : [];
  let id = $stitchCard.data("id");
  let timestamp = $stitchCard.data("timestamp");
  let year = new Date(timestamp * 1000).getFullYear();
  let date = new Date(timestamp * 1000).toLocaleDateString("en-US", {
    month: "2-digit",
    day: "2-digit",
    year: "numeric",
  });

  if (currentIds.includes(id.toString())) {
    mp3("computerbeep_69");
    M.toast({
      html: "STITCH_ALREADY_ANCHORED",
      classes: "blue",
      displayLength: 2000,
    });
    return;
  } else {
    if (showForm) {
      mp3("computerbeep_61");
    } else {
      mp3("computerbeep_65");
    }
  }
  if (showForm) toggleStitchForm();

  // 3. Update Hidden Input
  currentIds.push(id);
  input.val(currentIds.join(","));
  $nexusBadge = $("#new-stitch-nexus-badge");
  $nexusBadge.html(currentIds.length).show();
  $nexusBadge.removeClass("pulse-once");
  void $nexusBadge[0].offsetWidth;
  $nexusBadge.addClass("pulse-once");

  M.toast({
    html: `${year} (#${id}) - STITCH_ANCHORED_TO_NEW_STITCH_FORM`,
    classes: "blue",
    displayLength: 2000,
  });

  // 4. Update UI
  $("#nexus-empty-msg").hide();
  $("#nexus-chips-container").append(`
        <div class="nexus-chip yellow darken-2 black-text" id="nexus-chip-${id}" style="font-weight: bold;">
            <i class="material-icons left" style="font-size: 1.2rem; margin-top: 4px;">explore</i>
            <div onclick="warpToNexus(0, ${id}, ${year})" >${date} (#${id})</div>
            <i class="close material-icons" onclick="removeNexus(${id})">close</i>
        </div>
    `);
}
/**
 * Removes a Nexus anchor from the form
 */
function removeNexus(id) {
  let input = $("#nexus_ids");

  // Remove the chip from UI
  $(`#nexus-chip-${id}`).remove();
  let ids = input
    .val()
    .split(",")
    .filter((item) => item != id);
  input.val(ids.join(","));

  // Show empty message if none left
  if (ids.length === 0 || input.val() === "") {
    $("#nexus-empty-msg").show();
    $("#new-stitch-nexus-badge").hide();
    input.val(""); // Ensure it's totally clean
  }
  $("#new-stitch-nexus-badge").html(ids.length);

  M.toast({ html: "ANCHOR_RELEASED", classes: "orange", displayLength: 2000 });
}

window.shareStitch = function (anchorId) {
  // 1. Find the card data in our local view
  const card = $(`.stitch-wrapper[data-id="${anchorId}"]`);
  const content = card.find("p").text().trim();
  const type = card.find(".badge").text().replace("OBSERVATION: ", "").trim();

  // 2. Package the treasure
  const treasureData = {
    id: anchorId,
    content: content,
    type: type,
    timestamp: card.data("timestamp"),
  };

  // 3. Encode the Map
  const encodedTreasure = btoa(JSON.stringify(treasureData));
  const treasureLink =
    window.location.origin +
    window.location.pathname +
    "?app=stitch&treasure=" +
    encodedTreasure;

  // 4. Hand it to the Architect
  navigator.clipboard.writeText(treasureLink);
  M.toast({ html: "TREASURE MAP COPIED! <3", classes: "orange darken-2" });
  mp3("computerbeep_44");
  console.log("Treasure Buried at: ", treasureLink);
};

window.saveStitch = function (anchorId) {
  // 1. Find the card data in our local view
  const card = $(`.stitch-wrapper[data-id="${anchorId}"]`);
  const content = card.find("p").text().trim();
  const type = card.find(".badge").text().replace("OBSERVATION: ", "").trim();

  // 2. Package the treasure
  const treasureData = {
    id: anchorId,
    content: content,
    type: type,
    timestamp: card.data("timestamp"),
  };

  // 3. Encode the Map
  const encodedTreasure = btoa(JSON.stringify(treasureData));
  const treasureLink =
    window.location.origin +
    window.location.pathname +
    "?app=stitch&treasure=" +
    encodedTreasure;

  // 4. Hand it to the Architect
  navigator.clipboard.writeText(treasureLink);
  M.toast({
    html: "TREASURE MAP SAVED TO CLIPBOARD! <3",
    classes: "orange darken-2",
  });
  mp3("computerbeep_44");
  console.log("Treasure Buried at: ", treasureLink);
};

function assignActiveViewport(element) {
  $(".active-viewport").removeClass("active-viewport");
  $(element).addClass("active-viewport");
}

function toggleChronosFilter() {
  const $panel = $("#hud-container");
  if ($("body").hasClass("hud-active") && !$panel.hasClass("active-viewport")) {
    assignActiveViewport($panel[0]);
    return;
  }
  if ($("body").hasClass("hud-active")) {
    $("body").removeClass("hud-active");
    $panel.removeClass("active-viewport");
    return;
  }

  // 🔊 High-pitched LCARS "deploy" chirp
  if (window.stitch && stitch.audio) stitch.audio.lcars_stream(900);

  // Remove inline style if jQuery left it there
  $panel.removeAttr("style");
  assignActiveViewport($panel[0]);
  $("body").addClass("hud-active");
  $(".field-toolbar", $panel).addClass("stage-active-pulse");
  setTimeout(
    () => $(".field-toolbar", $panel).removeClass("stage-active-pulse"),
    1500,
  );
}

function toggleStitchForm() {
  const $deck = $("#deck-container");
  const $stage = $("#inline-stitch-stage");
  var contextMenuElem = document.querySelectorAll(
    ".new-stitch-form-dropdown-trigger",
  );

  if ($deck.hasClass("active-viewport")) {
    $("#content-masque").fadeOut(400);
    var contextMenu = M.Dropdown.getInstance(contextMenuElem);
    if (contextMenu) {
      contextMenu.destroy();
    }
    if (!$("#nexus-custom-window").is(":visible")) {
      $("body").removeClass("stitch-form-overlay");

      // 1. Add a closing class to trigger the fade-out keyframe
      $deck.addClass("panel-closing");

      // 2. Wait 200ms for the animation to finish before hiding it completely
      setTimeout(() => {
        $deck.removeClass("active-viewport");
        $deck.removeClass("panel-open panel-closing");
      }, 200);
    }
    return;
  }

  // Otherwise, show
  $deck.addClass("panel-open");
  assignActiveViewport($deck[0]);
  $("#content-masque").fadeIn(400);
  $("body").addClass("stitch-form-overlay");
  var contextMenu = M.Dropdown.init(contextMenuElem, {
    alignment: "right", // Aligns the dropdown menu to the right edge of the 3 dots
    constrainWidth: false, // Allows the dropdown menu to be wider than the 3-dot icon
  });

  $("#newStitchContent").focus();
  // Add the v0.2 Pulse
  $stage.addClass("stage-active-pulse");
  setTimeout(() => $stage.removeClass("stage-active-pulse"), 1500);
}
/**
 * ⚡ THE VOUCH RIPPLE EFFECT
 * Visual feedback for vouching action
 */
mb.vouchRipple = function () {
  const $ticker = $("#chronicle-monitor");

  // 1. Add a brief "Power Surge" class
  $ticker.addClass("vouch-surge");

  // 2. Log the vouch as a "Goodie Bag" to the stream
  mb.logMission({
    intel: "STABILITY_ANCHORED // VOUCH_RECEIVED",
    mood: "vouch",
    icon: "offline_bolt",
  });

  // 3. Remove the surge after the animation
  setTimeout(() => {
    $ticker.removeClass("vouch-surge");
  }, 1000);
};

function loadNexusDetail($nexusLinkItem) {
  if ($nexusLinkItem.hasClass("nexus-loaded")) {
    $(".nexus-detail-container", $nexusLinkItem).slideToggle();
    return; // Already loaded, no need to re-fetch
  }

  const sourceId = $nexusLinkItem.data("anchor-id");
  const targetId = $nexusLinkItem.data("nexus-ids");
  const $target = $(`#nexus-details-${sourceId}`);
  $spinner = $("<div class='quantum-spinner'></div>");
  $spinner.insertAfter($nexusLinkItem);

  // Optional: Only fetch if we haven't already
  mb.get(
    `?api=stitch&p=api&action=get_nexus_detail&id=${targetId}`,
    function (response) {
      const res = JSON.parse(response);
      if (res.status === "success") {
        if (res.data && res.data.html) {
          $spinner.remove(); // Remove the spinner when clicked
          $anchorElement = $(res.data.html);
          $anchorElement.on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.warpToNexus(sourceId, targetId, new Date().getFullYear());
          });
          $nexusLinkItem.append($anchorElement);
          $nexusLinkItem.addClass("nexus-loaded"); // Mark as loaded
          $("a", $nexusLinkItem).on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).next(".nexus-detail-body").slideToggle();
          });
        }
      }
    },
  );
}

/**
 * 🛰️ THE QUANTUM JUMP
 * Teleports the pasture to a specific point in time
 */
// Initialize the breadcrumb trail
window.stitchHistory = [];

window.warpToNexus = function (fromId, targetId, targetYear, isBack = false) {
  const isAlreadyOpen = $("body").hasClass("nexus-viewport-overlay");
  // Logic: If this isn't a "Back" jump, and we have a 'fromId', save it, if it is not from the main timeline!
  if (!isBack && isAlreadyOpen) {
    window.stitchHistory.push(fromId);
  }
  if (!isBack && !isAlreadyOpen) {
    $("#modal-back-btn-text").text(`[ Exit ]`);
    $("#modal-back-btn").fadeIn(); // Show the button inside the modal
  }

  // 🎯 THE UNIQUENESS CHECK: Is the modal already open?
  //const modalElem = document.getElementById("nexus-layer-modal");
  //const instance = M.Modal.getInstance(modalElem) || M.Modal.init(modalElem);

  console.log(
    isAlreadyOpen ? "RECURSIVE_WARP_DETECTED..." : "INITIAL_WARP_INITIATED...",
  );

  // 2. Prepare the Metadata
  $("#nexus-layer-year").text(targetYear);
  $("#nexus-layer-id").text(targetId);

  // 3. Glitch Effect: Fade out old content and show loader
  // If already open, we fade out the current content first for a smooth transition
  const $content = $("#nexus-modal-results");
  let zindex = $("#deck-container").css("z-index");

  $("#nexus-masque-overlay").css("z-index", zindex + 1);
  $("#nexus-custom-window").css("z-index", zindex + 2);
  $content
    .html(
      `
        <div class="center-align" style="margin-top: 150px;">
            <div class="preloader-wrapper big active">
                <div class="spinner-layer spinner-purple-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
            <p class="purple-text animate-flicker" style="font-family: monospace; margin-top: 20px;">
                RE-STABILIZING_SIGNAL_IN_${targetYear}...
            </p>
        </div>
      `,
    )
    .show(function () {
      // 4. Open modal only if it's not already visible
      if (!isAlreadyOpen) {
        $("body").addClass("nexus-viewport-overlay");
        openNexusWindow();
      } else {
        // If it's already open, scroll the modal content back to the top
        $(".modal-content").animate({ scrollTop: 0 }, 300);
      }
    }); // Show the loader immediately

  // 5. Update background slider (Keeping the "Anyway" world in sync)
  /*
  const startYear = 2006,
    endYear = 2026;
  const targetPercent =
    ((targetYear - startYear) / (endYear - startYear)) * 100;
  const depthSlider = document.getElementById("depth-slider");
  if (depthSlider) {
    depthSlider.value = targetPercent;
    depthSlider.dispatchEvent(new Event("input"));
  }
  */

  // 🎯 UI Tweak: Update the Back Button text to show how many steps are left
  if (window.stitchHistory.length) {
    $("#modal-back-btn-text").text(
      `[ BACK_TO_#${window.stitchHistory[window.stitchHistory.length - 1]} ]`,
    );
  } else {
    $("#modal-back-btn-text").text(`[ Exit ]`);
  }

  mp3("computerbeep_40");

  // 6. Fetch the new Anchor point
  mb.get(
    "?api=stitch&action=warp_to_nexus",
    {
      target_id: targetId,
      limit: 1,
    },
    function (response) {
      if (response.status === "success" && response.data.html) {
        // 🚀 OVERWRITE: The new era replaces the old era inside the existing modal
        let $nexus = $(response.data.html);
        let targetDate = new Date(
          $nexus.data("timestamp") * 1000,
        ).toLocaleDateString("en-US", {
          month: "2-digit",
          day: "2-digit",
          year: "numeric",
        });

        $content.hide().html(response.data.html).fadeIn(600);

        // RE-INITIALIZE: Critical for making sure the NEXT jump works from inside this HTML
        setTimeout(() => {
          M.AutoInit($content[0]);
        }, 100);

        M.toast({
          html: `JUMPED_TO: ${targetDate}`,
          classes: "purple",
          displayLength: 2000,
        });
      } else {
        $content.html(
          '<h5 class="red-text center">SIGNAL_LOST_IN_TRANSIT</h5>',
        );
      }
    },
  );
};

window.goBackInTime = function () {
  if (window.stitchHistory.length === 0) {
    closeNexusWindow();
    return;
  }

  // 1. Get the last ID (The one we just came from)
  const previousId = window.stitchHistory.pop();

  // 2. We need a way to know what year that ID was (Optional, but cleaner)
  // For now, we'll just tell warpToNexus it's a "History Jump"
  console.log("RETREATING_TO_ANCHOR:", previousId);

  // 3. Trigger the warp, but pass a flag to NOT add to history again
  // We modify warpToNexus slightly to accept a 'isBack' parameter
  window.warpToNexus(null, previousId, "PAST", true);

  // 4. Update the UI: If history is now empty, hide the back button
  if (window.stitchHistory.length === 0) {
    //$("#modal-back-btn").fadeOut();
    $("#modal-back-btn-text").text(`[ Exit ]`);
  }
};

const pastureSync = {
  peers: [],

  // 1. Shouting to the Pasture
  broadcast: function (stitch) {
    console.log("Broadcasting Truth to Peers... <3");
    this.peers.forEach((peer) => {
      peer.send(
        JSON.stringify({
          type: "NEW_STITCH",
          data: stitch,
        }),
      );
    });
  },

  // 2. Hearing the Voice (Receiving from others)
  onDataReceived: function (payload) {
    const message = JSON.parse(payload);
    if (message.type === "NEW_STITCH") {
      console.log("New Truth Received Hand-to-Hand!");
      // Add to your local list without hitting a server
      renderNewStitches([message.data]);
      // Anchor it in your local Hand/Storage
      mb.storage.apps.stitch.anchors[message.data.id] = message.data;
      storage_set();
    }
  },
};

// THE NINJA HANDSHAKE (Establishing the Direct-Vouch)
const myPastureNode = new RTCPeerConnection({
  iceServers: [{ urls: "stun:stun.l.google.com:19302" }], // A tiny "Pshhh" to find our public IP
});

// 1. Opening the "Heart-Pipe" (The Data Channel)
const dataChannel = myPastureNode.createDataChannel("SoulFidelity");

dataChannel.onopen = () => {
  console.log("THE TUNNEL IS OPEN! BROADCASTING TO THE PASTURE... <3");
  // This is where we send the "Carrie-Variable" to the team!
  dataChannel.send(
    JSON.stringify({
      type: "HEARTBEAT",
      context: "10/10_FIDELITY",
      status: "ALWAYS_ON",
    }),
  );
};

// 2. Receiving the "Truth" from a Peer
myPastureNode.ondatachannel = (event) => {
  event.channel.onmessage = (message) => {
    const truth = JSON.parse(message.data);
    console.log("New Truth Received Hand-to-Hand: ", truth);
    // NO SERVER. NO GIANTS. JUST THE MESH.
  };
};

// A simple function to generate your "Invitation" to the Pasture
function createInvitation() {
  myPastureNode.createOffer().then((offer) => {
    myPastureNode.setLocalDescription(offer);
    console.log("SEND THIS INVITE TO DAD: ", JSON.stringify(offer));
    // In the future, we'll use a QR code here! <3
  });
}

// When Dad's phone gets the invite, he runs this:
function acceptInvitation(inviteFromJeff) {
  const offer = new RTCSessionDescription(JSON.parse(inviteFromJeff));
  myPastureNode.setRemoteDescription(offer);
  myPastureNode.createAnswer().then((answer) => {
    myPastureNode.setLocalDescription(answer);
    console.log("SEND THIS ANSWER BACK TO JEFF: ", JSON.stringify(answer));
  });
}
// JEFF'S SIDE: The "Mission Control" Button
window.launchPastureSync = async function () {
  const sessionId = Math.random().toString(36).substring(7);

  myPastureNode.onicecandidate = (e) => {
    if (!e.candidate) {
      const offerSdp = btoa(JSON.stringify(myPastureNode.localDescription));

      // 1. DROP THE NOTE IN THE HOLLOW TREE
      mb.ajax(
        {
          url: "?api=stitch",
          method: "POST",
          data: JSON.stringify({
            action: "post_offer", // or whatever action name you settled on
            session_id: sessionId,
            offer: offerSdp,
          }),
        },
        function (response) {
          // 🚀 THIS IS THE MOMENT!
          // Once the server has your offer, you start listening for Dad's answer.
          startListeningForInviteResponse(sessionId);

          // 2. GENERATE THE LINK FOR DAD
          const shareLink =
            window.location.origin +
            window.location.pathname +
            "?invite=" +
            offerSdp +
            "&sid=" +
            sessionId;

          navigator.clipboard.writeText(shareLink);
          M.toast({ html: "LINK COPIED! TEXT IT TO DAD! <3", classes: "blue" });
        },
      );
    }
  };

  const offer = await myPastureNode.createOffer();
  await myPastureNode.setLocalDescription(offer);
};

//
function startListeningForInviteResponse(sessionId) {
  const poller = setInterval(() => {
    mb.getJSON(
      "?api=stitch",
      { action: "get_answer", session_id: sessionId },
      function (res) {
        if (res.answer) {
          clearInterval(poller); // Stop polling!
          pastureFinalize(atob(res.answer));
          M.toast({
            html: "HEART-PIPE OPEN: DAD CONNECTED! <3",
            classes: "green pulse",
          });
          mp3("computerbeep_44");
        }
      },
    );
  }, 3000); // Check every 3 seconds
}

// --- THE LINK-FOLD LOGIC ---
window.generateStitchLink = async function () {
  console.log("Folding the Paper Football... <3");

  myPastureNode.onicecandidate = (e) => {
    if (!e.candidate) {
      // 1. Get the raw offer
      const offer = JSON.stringify(myPastureNode.localDescription);
      // 2. Encode it so the URL doesn't break (Base64)
      const encodedOffer = btoa(offer);
      // 3. Create the final link
      const shareLink =
        window.location.origin +
        window.location.pathname +
        "?app=stitch&invite=" +
        encodedOffer;

      console.log("SHARE THIS LINK WITH DAD: ", shareLink);
      M.toast({ html: "LINK READY TO TEXT!", classes: "blue" });

      // Optionally: Copy to clipboard automatically!
      navigator.clipboard.writeText(shareLink);
    }
  };

  const offer = await myPastureNode.createOffer();
  await myPastureNode.setLocalDescription(offer);
};

// --- THE NINJA HANDSHAKE TOOLS ---

// 1. JEFF RUNS THIS: Generates the "Invitation"
window.pastureInvite = async function () {
  console.log("Generating Invitation... <3");

  // We wait for ICE candidates to gather so the note is complete!
  myPastureNode.onicecandidate = (e) => {
    if (!e.candidate) {
      console.log("INVITATION READY! Copy this whole block and send to Dad:");
      console.log(JSON.stringify(myPastureNode.localDescription));
    }
  };

  const offer = await myPastureNode.createOffer();
  await myPastureNode.setLocalDescription(offer);
};

// 2. DAD RUNS THIS: Accepts Jeff's Invite & Generates the "Answer"
window.pastureJoin = async function (inviteJson, sessionId) {
  const offer = new RTCSessionDescription(JSON.parse(inviteJson));
  await myPastureNode.setRemoteDescription(offer);

  const answer = await myPastureNode.createAnswer();
  await myPastureNode.setLocalDescription(answer);

  myPastureNode.onicecandidate = (e) => {
    if (!e.candidate) {
      const answerSdp = btoa(JSON.stringify(myPastureNode.localDescription));

      // DAD AUTOMATICALLY POSTS THE ANSWER BACK
      mb.ajax(
        {
          url: "?api=stitch",
          method: "POST",
          data: JSON.stringify({
            action: "post_answer",
            session_id: sessionId,
            answer: answerSdp,
          }),
        },
        function () {
          console.log(
            "Answer posted! Waiting for Peer-Pipe to stabilize... <3",
          );
          M.toast({ html: "CONNECTING TO JEFF...", classes: "purple pulse" });
        },
      );
    }
  };
};

// 3. JEFF RUNS THIS LAST: Finalizes the Heart-Pipe
window.pastureFinalize = async function (answerJson) {
  console.log("Finalizing the Heart-Pipe... <3");
  const answer = new RTCSessionDescription(JSON.parse(answerJson));
  await myPastureNode.setRemoteDescription(answer);

  // Once this runs, your peers[] array will fill up!
  console.log("PASTURE ESTABLISHED! 10/10 FIDELITY ENGAGED!!");
  M.toast({ html: "PEER CONNECTED! SHOUTING ACTIVE!", classes: "green" });
  mp3("computerbeep_44");
};

// 🛰️ Helper to toggle and save
function toggleStitchDimension() {
  mb.storage.apps.stitch.preferences.stitch_dimension =
    mb.storage.apps.stitch.preferences.stitch_dimension === "projected_to"
      ? "created_at"
      : "projected_to";
  const dimension = mb.storage.apps.stitch.preferences.stitch_dimension;
  const isHistorical = dimension === "created_at";
  $("#date-start").val(stitch.filter.start_date[dimension]);
  $("#date-start").trigger("change");
  $("#date-end").val(stitch.filter.end_date[dimension]);
  $("#date-end").trigger("change");

  storage_set();

  notify(
    "TIMELINE MODE: " + (isHistorical ? "HISTORICAL" : "OBSERVER"),
    "purple",
  );

  const label = isHistorical ? "HISTORICAL_MAP" : "DISCOVERY_FEED";
  $("#dimension-status")
    .text(`MODE: ${label}`)
    .toggleClass("cyan-text", !isHistorical)
    .toggleClass("purple-text", isHistorical);
  // Trigger a refresh of the list based on the new dimension
  stitch.api("chronos_dial");
}
// 🔋 Sync Dimension Switch UI on Load
function syncDimensionUI() {
  const dimension = mb.storage.apps.stitch.preferences.stitch_dimension;
  const isHistorical = dimension === "created_at";
  $("#dimension-toggle").prop("checked", isHistorical);

  const label = isHistorical ? "HISTORICAL_MAP" : "DISCOVERY_FEED";
  $("#dimension-status")
    .text(`MODE: ${label}`)
    .toggleClass("cyan-text", !isHistorical)
    .toggleClass("purple-text", isHistorical);

  // Fetch correct history for setting
  if (isHistorical) stitch.api("chronos_dial");
}

function openNexusWindow() {
  //$('#nexus-modal-results').html();
  $("#nexus-masque-overlay").fadeIn(200);
  $("#nexus-custom-window").fadeIn(300);
  $("body").addClass("nexus-viewport-overlay");
  // 🛡️ SOVEREIGNTY: We don't disable focus here.
  // The browser is free to focus anything at a higher z-index.
}

function closeNexusWindow() {
  $("#nexus-masque-overlay").fadeOut(200);
  $("#nexus-custom-window").fadeOut(200);
  $("body").removeClass("nexus-viewport-overlay");
}

function openNexusOverlay() {
  mp3("computerbeep_13");
  // Low-fi digital boot sound
  $("#nexus-overlay").fadeIn(400);
  // 2. Play the LCARS sequence
  openTheMatrix();
}

function closeNexusOverlay() {
  $("#nexus-overlay").fadeOut(400);
}

function renderNexusChips() {
  // 🛰️ Imagine fetching your summary here
  const mockNexus = [
    "ROMAN_HISTORY",
    "AI_LOGIC",
    "PURE_HEARTS",
    "COFFEE_THOUGHTS",
  ];

  mockNexus.forEach((label, index) => {
    setTimeout(() => {
      let chip = `<div class="nexus-chip pulse-once">${label} <span class="count">4</span></div>`;
      $("#nexus-chip-cloud").append(chip);
    }, index * 100); // 🏁 Staggered entry for that "Calculating" feel
  });
}

function renderNexusClusters() {
  const $container = $("#nexus-chip-cloud");
  $container.empty(); // Clear the deck for the new matrix
  mp3("computerbeep_8");
  // 🛰️ Calling the "Positronic" API
  mb.getJSON("stitch.api.php?action=nexus_summary", function (response) {
    if (response.status === "success") {
      response.data.forEach((nexus, index) => {
        // Staggered entry for that "Calculating" feel
        setTimeout(() => {
          const chipHtml = `
                        <div class="nexus-chip pulse-once" 
                             onclick="filterByNexus('${nexus.nexus_label}')"
                             style="opacity: 0; transform: scale(0.8);">
                            <span class="nexus-label">${nexus.nexus_label}</span>
                            <span class="count">${nexus.stitch_count}</span>
                        </div>`;

          const $chip = $(chipHtml);
          $container.append($chip);

          // ✨ Animation: Fade and Grow into place
          $chip.animate(
            {
              opacity: 1,
            },
            {
              step: function (now) {
                $(this).css("transform", `scale(${0.8 + now * 0.2})`);
              },
              duration: 300,
            },
          );
        }, index * 80); // 80ms delay between thoughts
      });
    }
  });
}

function initPositronicMatrix() {
  const container = document.getElementById("positronic-matrix-view");
  let { nodes, edges } = stitch.getNetworkData();

  // 🛰️ TRANSFORM EDGES INTO DYNAMIC TETHERS
  // We map the SQL 'weight' to visual thickness and spring tension
  const dynamicEdges = edges.map((edge) => ({
    from: edge.from,
    to: edge.to,
    label: edge.label || "",
    // Gravity Logic:
    width: (edge.weight || 1) * 2, // Thicker lines for higher weight
    length: 150 / (edge.weight || 1), // High weight pulls nodes CLOSER
    color: {
      color: edge.weight > 2 ? "#ff4081" : "#ba68c8", // High weight glows pink
      highlight: "#ffffff",
      opacity: 0.7,
    },
    font: { size: 12, color: "#ffffff", strokeWidth: 0 },
  }));

  const data = {
    nodes: new vis.DataSet(nodes),
    edges: new vis.DataSet(dynamicEdges),
  };

  const options = {
    nodes: {
      shape: "dot",
      scaling: { min: 10, max: 50 },
      shadow: { enabled: true, color: "rgba(0,0,0,0.5)" },
      font: { color: "#ffffff" },
    },
    // 🌌 THE QUANTUM SOLVER
    physics: {
      enabled: true,
      forceAtlas2Based: {
        gravitationalConstant: -100, // Stronger repulsion for clarity
        centralGravity: 0.015,
        springLength: 100,
        springConstant: 0.08, // This allows the 'Weight' to stretch/pull
        damping: 0.4,
      },
      solver: "forceAtlas2Based",
      stabilization: {
        enabled: true,
        iterations: 150, // Let it settle before showing
        updateInterval: 25,
      },
    },
    interaction: {
      hover: true,
      tooltipDelay: 200,
      hideEdgesOnDrag: true, // Optimizes performance for the 90-file payload
    },
  };

  const network = new vis.Network(container, data, options);

  // 🔊 THE INTERACTION LOOP
  network.on("click", function (params) {
    if (params.nodes.length > 0) {
      if (typeof stitch !== "undefined" && stitch.audio) stitch.audio.link();

      var nodeId = params.nodes[0];
      console.log("SURPRISE_TARGET_LOCKED: " + nodeId);
      mb.get(`?api=stitch&action=observe_stitch_nexus&node_id=${nodeId}`);

      // 🛰️ Update the 'Weight' in the background!
      // (You can fire an AJAX call here later to increase the weight of this node/nexus)

      filterByNexus(nodeId);
      $("#nexus-overlay").fadeOut(600);
    }
  });

  // 🚀 Ensure the Matrix resizes correctly
  window.addEventListener("resize", () => {
    network.redraw();
  });
}

function loadPositronicMatrix() {
  $(".progress-bar-fill").animate(
    {
      width: "100%",
    },
    800,
    function () {
      // 2. Hide loader and show chips
      $("#matrix-loader").fadeOut(200, function () {
        //renderNexusChips(); // Load our actual data!
        //openTheMatrix();
      });
    },
  );

  mp3("ambient_bridge_12");
  // 🕸️ CALL THE INITIALIZER
  initPositronicMatrix();
}

// 🧠 The Singleton Audio Brain
function mp3(filename, callback = null) {
  stitch.audio.mp3(filename, callback);
}

// 🧠 Create the context, but we won't assume it's awake yet
let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
stitch.audio = {
  init: () => {
    if (mb.storage.apps.stitch.preferences.mute_audio) return null;
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === "suspended") {
      audioCtx.resume();
    }
    return audioCtx;
  },
  mp3: (filename, cb = null) => {
    if (mb.storage.apps.stitch.preferences.mute_audio) return null;
    const masterVol = stitch.audio.getMasterVolume();
    mb.audio({
      source: filename,
      volume: masterVol,
      callback: cb,
    });
  },
  getMasterVolume: () => {
    return mb.storage.apps.stitch.preferences.audio_volume ?? 0.5;
  },

  // 🖖 THE REFINED LCARS CHIRP (Layered & Warm)
  lcars_access: () => {
    const ctx = stitch.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = stitch.audio.getMasterVolume();

    const playTrekTone = (freq, start, duration, slideTo) => {
      // LAYER 1: The Main Hollow Tone
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = "triangle";
      osc.frequency.setValueAtTime(freq, start);
      // 🚀 THE SLIDE: This is the 'chirp' secret!
      osc.frequency.exponentialRampToValueAtTime(
        slideTo || freq * 1.05,
        start + duration,
      );

      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(0.15 * masterVol, start + 0.005);
      gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(start);
      osc.stop(start + duration);

      // LAYER 2: The "Thud" (A low-frequency percussive hit)
      const thud = ctx.createOscillator();
      const thudGain = ctx.createGain();
      thud.type = "sine";
      thud.frequency.setValueAtTime(freq / 2, start); // One octave lower
      thudGain.gain.setValueAtTime(0.1 * masterVol, start);
      thudGain.gain.exponentialRampToValueAtTime(0.0001, start + 0.05);
      thud.connect(thudGain);
      thudGain.connect(ctx.destination);
      thud.start(start);
      thud.stop(start + 0.05);
    };

    // Frequencies adjusted to match the "Tweedle" in your MP3
    playTrekTone(440, now, 0.08, 460);
    playTrekTone(659, now + 0.06, 0.1, 680);
  },

  lcars_stream: (freq) => {
    const ctx = stitch.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = stitch.audio.getMasterVolume();
    const safeFreq = typeof freq === "number" && isFinite(freq) ? freq : 300;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    const filter = ctx.createBiquadFilter();

    osc.type = "triangle";
    osc.frequency.setValueAtTime(safeFreq, now);
    // Quick downward chirp for the data stream
    osc.frequency.exponentialRampToValueAtTime(safeFreq * 0.8, now + 0.05);

    filter.type = "lowpass";
    filter.frequency.setValueAtTime(1000, now); // Slightly more muffled

    gain.gain.setValueAtTime(0.06 * masterVol, now);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.05);

    osc.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(now + 0.05);
  },

  // 🕸️ THE NEURAL LINK (Sliding Tone)
  link: () => {
    const ctx = stitch.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = stitch.audio.getMasterVolume();

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.frequency.setValueAtTime(440, now);
    osc.frequency.exponentialRampToValueAtTime(880, now + 0.1);

    gain.gain.setValueAtTime(0, now);
    gain.gain.linearRampToValueAtTime(0.08 * masterVol, now + 0.01);
    gain.gain.linearRampToValueAtTime(0, now + 0.1);

    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(now + 0.1);
  },
};

function playSyncSequence() {
  let count = 0;
  const trekDataFreqs = [360, 440, 520, 660];

  stitch.audio.lcars_access();

  const interval = setInterval(() => {
    // 🎲 Random frequency but forced into Trek harmonics
    const baseFreq =
      trekDataFreqs[Math.floor(Math.random() * trekDataFreqs.length)];

    // 🛰️ Occasional high "ping" just like the MP3
    if (Math.random() > 0.8) {
      stitch.audio.lcars_stream(baseFreq * 2);
    } else {
      stitch.audio.lcars_stream(baseFreq);
    }

    count++;
    if (count > 25) {
      clearInterval(interval);
      setTimeout(() => {
        stitch.audio.lcars_access();
      }, 200);
    }
  }, 45); // Faster (45ms) matches the "jitter" of your source file better
}

function triggerPositronicSync() {
  // 🧠 THE WAKE-UP CALL
  // We must resume the context inside a USER-INITIATED event
  if (audioCtx.state === "suspended") {
    audioCtx.resume().then(() => {
      console.log("AUDIO_BRAIN_AWAKE");
      // Now that we are awake, start the show
      openNexusOverlay();
    });
  } else {
    openNexusOverlay();
  }
}
// This should be the function called by your "Nexus" button's onclick
function openTheMatrix() {
  // 📢 THE HARD KICK
  // We must call resume() directly in the click event
  if (audioCtx.state === "suspended") {
    audioCtx.resume().then(() => {
      console.log("SYSTEM: Audio Subsystem Online.");
      runMatrixSequence(); // Proceed to show UI and play sounds
    });
  } else {
    runMatrixSequence();
  }
}

function runMatrixSequence() {
  // 1. Show the overlay
  $("#nexus-overlay").fadeIn(400);

  // 1. Start the "Sync" Animation
  if ($("#positronic-matrix-view .vis-network canvas").length === 0) {
    // 3. Load the Vis.js data
    loadPositronicMatrix();
  }
}

// 📍 THE SENTINEL MAP MODULE
stitch.geo = {
  allCardSelector: "#stitch-card-container .stitch-wrapper",
  map: null,
  defaultZoom: 13,
  defaultCenter: mb.storage.apps.stitch.preferences.map_center,
  markers: L.layerGroup(), // We use a layerGroup so we can clear/refresh easily
  nexusGroup: L.layerGroup(), // Separate layer for the lines

  init: function (containerId) {
    if (this.map) return; // Don't double-init

    if (
      !mb.storage.apps.stitch.preferences.map_center ||
      !stitch.geo.defaultCenter
    ) {
      mb.getLocality(function (coords) {
        stitch.geo.defaultCenter =
          mb.storage.apps.stitch.preferences.map_center = {
            lat: coords.lat,
            lng: coords.lng,
          };
      });
    }
    // 1. Initialize the Map (Center on a meaningful default or the first stitch)
    this.map = L.map(containerId, {
      zoomControl: false, // Cleaner HUD
      attributionControl: false,
    }).setView([40.007, -75.34], 13); // Default to our Philly/Schuylkill Nexus

    // 2. Add the Dark/Sovereign Tile Layer
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
    }).addTo(this.map);

    stitch.geo.markers.addTo(this.map);

    console.log("MAP_LOCK_ACQUIRED... Scanning for anchors...");
    this.refreshMarkers();
  },
  view: function () {
    $("#nexus-overlay .nexus-overlay-viewport").hide();
    $("#leaflet-map-view").show();
    if (!$("#leaflet-map-view").hasClass("leaflet-container")) {
      stitch.geo.init("leaflet-map-view");
    }
  },

  refreshMarkers: function () {
    const self = this; // This refers to stitch.geo

    // 1. Ensure LayerGroups exist and are attached to the map
    if (!self.markers) {
      self.markers = L.layerGroup().addTo(self.map);
    } else {
      self.markers.clearLayers();
    }

    if (!self.arcs) {
      self.arcs = L.layerGroup().addTo(self.map);
    } else {
      self.arcs.clearLayers();
    }

    // 2. Iterate through the DATA STATE
    const anchors = Object.values(stitch.state.anchors);

    if (anchors.length === 0) {
      console.warn("MAP_REFRESH_ABORTED: No anchors found in stitch.state");
      return;
    }

    anchors.forEach((anchor) => {
      let lat = parseFloat(anchor.lat);
      let lng = parseFloat(anchor.lng);

      if (
        (isNaN(lat) || isNaN(lng)) &&
        anchor.nexus_list &&
        anchor.nexus_list.length > 0
      ) {
        const fallback = anchor.nexus_list.find((n) => n.lat && n.lng);
        if (fallback) {
          lat = parseFloat(fallback.lat);
          lng = parseFloat(fallback.lng);
          console.log(
            `Anchor ${anchor.id} borrowing coords from Nexus ${fallback.nexus_id}`,
          );
        }
      }

      if (!isNaN(lat) && !isNaN(lng)) {
        // ⚓ DROP THE ANCHOR
        const marker = L.circleMarker([lat, lng], {
          radius: 8,
          fillColor: "#ffab40",
          color: "#fff",
          weight: 1,
          opacity: 1,
          fillOpacity: 0.8,
        })
          .bindPopup(
            `
                <strong>Stitch #${anchor.id}</strong><br>
                ${anchor.content_type}<br>
                <button onclick="window.scrollToStitch(${anchor.id})" class="btn-flat purple-text">GO_TO_STITCH</button>
            `,
          )
          .addTo(self.markers); // Use self.markers

        // 🧶 WEAVE THE PURPLE ARCS
        if (anchor.nexus_list && anchor.nexus_list.length > 0) {
          anchor.nexus_list.forEach((nexus) => {
            const targetLat = parseFloat(nexus.lat);
            const targetLng = parseFloat(nexus.lng);

            if (!isNaN(targetLat) && !isNaN(targetLng)) {
              L.polyline(
                [
                  [lat, lng],
                  [targetLat, targetLng],
                ],
                {
                  color: "#9b59b2",
                  weight: 2,
                  opacity: 0.6,
                  dashArray: "5, 10",
                },
              ).addTo(self.arcs); // Use self.arcs
            }
          });
        }
      }
    });

    // 3. Auto-fit
    const layers = self.markers.getLayers();
    if (layers.length > 0) {
      const group = L.featureGroup(layers);
      self.map.fitBounds(group.getBounds(), { padding: [50, 50] });
    }
  },
  weaveNexus: function () {
    const self = this;
    this.nexusGroup.clearLayers();

    // 🕵️ 1. NO MORE DOM SCRAPING
    // We just loop through the state objects directly.
    Object.values(stitch.state.anchors).forEach((anchor) => {
      // 🛡️ SAFETY CHECK: Does the source have coordinates?
      if (anchor.lat && anchor.lng && anchor.nexus_list) {
        const sourceLatLng = [parseFloat(anchor.lat), parseFloat(anchor.lng)];

        // 🕵️ 2. DRAW THE LINKS FROM THE NESTED DATA
        anchor.nexus_list.forEach((nexus) => {
          // We check if the target has coordinates stored in the nexus object
          if (nexus.lat && nexus.lng) {
            const targetLatLng = [parseFloat(nexus.lat), parseFloat(nexus.lng)];

            // 🧶 WEAVE THE THREAD
            L.polyline([sourceLatLng, targetLatLng], {
              color: "#b388ff", // Nexus Purple
              weight: nexus.weight ? nexus.weight * 2 : 2, // Use that weight!
              opacity: 0.7,
              dashArray: "8, 12",
              lineJoin: "round",
            })
              .bindTooltip(`NEXUS_LINK: ${anchor.id} ➔ ${nexus.nexus_id}`, {
                sticky: true,
              })
              .addTo(self.nexusGroup);
          }
        });
      }
    });

    this.nexusGroup.addTo(this.map);
    console.log("NEXUS_THREADS_WOVEN_FROM_STATE... <3");
  },
};

stitch.getNetworkData = function () {
  let nodes = [];
  let edges = [];
  let seenEdges = new Set(); // Prevent double-links

  // Loop through our local memory (the state)
  Object.values(this.state.anchors).forEach((anchor) => {
    // 1. Create the Node
    nodes.push({
      id: anchor.id,
      label: `STITCH_${anchor.id}`,
      group: anchor.content_type,
      // You can even pass the whole object for the tooltip
      title: anchor.content_type + ": " + (anchor.content.body || "No Content"),
    });

    // 2. Weave the Edges from the nested nexus_list
    if (anchor.nexus_list) {
      anchor.nexus_list.forEach((nexus) => {
        // Ensure the target node is at least represented as a "Ghost Node"
        // if it hasn't been loaded into the state yet.
        if (!this.state.anchors[nexus.nexus_id]) {
          nodes.push({
            id: nexus.nexus_id,
            label: `GHOST_${nexus.nexus_id}`,
            color: "#444",
            font: { color: "#777" },
          });
        }

        // Create a unique key (e.g., "36-47") to avoid duplicates
        const edgeKey = [anchor.id, nexus.nexus_id].sort().join("-");
        if (!seenEdges.has(edgeKey)) {
          edges.push({
            from: anchor.id,
            to: nexus.nexus_id,
            label: nexus.nexus_label || "LINK",
            weight: nexus.weight || 1, // 🛰️ THIS IS THE IMPORTANT PART
          });
          seenEdges.add(edgeKey);
        }
      });
    }
  });

  // Return RAW ARRAYS so your mapping logic can work
  return { nodes, edges };
};

// Helper to scroll to the card when marker is clicked
window.scrollToStitch = function (id) {
  $(`[data-id="${id}"]`)[0].scrollIntoView({ behavior: "smooth" });
};

// 📍 Geolocation via IP/Browser
$(document).on("click", "#btn-geo-locate", function () {
  const $btn = $(this);
  $btn.find("i").addClass("rotate-sync"); // Add a spin animation if you have one

  mb.geoLocate(function (coords) {
    $("#stitch_lat").val(coords.lat);
    $("#stitch_lng").val(coords.lng);
    M.updateTextFields();
    stitch.geo.defaultCenter = coords;
    stitch.saveDraft();
    $btn.find("i").removeClass("rotate-sync");
  });
});

stitch.saveDraft = function () {
  let $form = $("#newStitchForm");
  let type = $("#dataType", $form).val();
  stitch.newStitchForm.saveDraft(type);
  console.log("DRAFT_SYNCHRONIZED_LOCAL");
};

// Card dive button click
$(document).on("click", ".dive-btn", function () {
  const card = $(this).closest(".stitch-wrapper");
  const nodeId = card.data("id");
  const nodeTitle = card.find(".card-title").text();

  // Update the UI Header
  $("#view-title").html(
    `Exploring: <span class="amber-text">${nodeTitle}</span>`,
  );

  // AJAX: Load only members of this nexus
  $.get("stitch.api.php?action=get_nexus_members&id=" + nodeId, function (res) {
    // Refresh the cards and map dots here
    mb.renderCards(res.data);
    mb.refreshMap(res.data);
  });
});

// 🗺️ Visual Map Picker
$(document).on("click", "#link-map-picker", function (e) {
  e.preventDefault();
  M.toast({ html: "CLICK_ANYWHERE_ON_MAP_TO_SET_COORDS", displayLength: 3000 });

  stitch.activateMapPicker();

  // Change cursor to crosshair
  $("#map-picker-overlay").css("cursor", "crosshair");

  // One-time listener for the next click on the map
  stitch.geo.pickerMap.once("click", function (mapEvent) {
    const coords = mapEvent.latlng;
    $("#stitch_lat").val(coords.lat.toFixed(6));
    $("#stitch_lng").val(coords.lng.toFixed(6));
    M.updateTextFields();
    stitch.saveDraft();

    // Reset cursor
    $(stitch.geo.pickerMap).css("cursor", "");

    // Add a temporary "Ghost Marker" to show where they clicked
    if (window.tempMarker) stitch.geo.pickerMap.removeLayer(window.tempMarker);
    window.tempMarker = L.circleMarker(coords, {
      radius: 5,
      color: "#ffab40",
    }).addTo(stitch.geo.pickerMap);
  });
});

stitch.activateMapPicker = function () {
  const $overlay = $("#map-picker-overlay");
  assignActiveViewport($overlay[0]);
  $overlay.fadeIn(300);

  stitch.showMapPicker();
};

stitch.showMapPicker = function () {
  const $overlay = $("#map-picker-overlay");
  // 1. Initialize Picker Map if it doesn't exist
  if (!stitch.geo.pickerMap) {
    stitch.geo.pickerMap = L.map("picker-map-canvas", {
      center: stitch.geo.defaultCenter, // Start where the main map is
      zoom: stitch.geo.defaultZoom,
      zoomControl: false,
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap",
    }).addTo(stitch.geo.pickerMap);

    // 2. The Selection Logic
    stitch.geo.pickerMap.on("click", function (e) {
      const lat = e.latlng.lat.toFixed(6);
      const lng = e.latlng.lng.toFixed(6);

      // Update the form
      $("#stitch_lat").val(lat);
      $("#stitch_lng").val(lng);
      M.updateTextFields();
      stitch.geo.defaultCenter = { lat, lng };
      stitch.saveDraft();

      // Audio & Visual feedback
      if (stitch.audio) stitch.audio.link();

      // Drop a pin so they see what they picked
      if (stitch.geo.pickerMarker)
        stitch.geo.pickerMap.removeLayer(stitch.geo.pickerMarker);

      stitch.geo.pickerMarker = L.marker(e.latlng).addTo(stitch.geo.pickerMap);

      // Auto-close after a short delay so they see the pin drop
      setTimeout(() => {
        $overlay.fadeOut(300);
        assignActiveViewport($("#deck-container")[0]);
      }, 600);
    });
  } else {
    // Just sync it to the main map's current view
    stitch.geo.pickerMap.setView(
      stitch.geo.defaultCenter,
      stitch.geo.defaultZoom,
    );
    stitch.geo.pickerMap.invalidateSize();
  }
};

// 3. Close Handler
$(document).on("click", "#close-map-picker", function () {
  $("#map-picker-overlay").fadeOut(300);
});

/***
 * Document Ready
 * Handles Infinite Scroll and Deep Scan Search
 ***/
document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const inviteData = urlParams.get("invite");
  const answerData = urlParams.get("answer");
  const sid = urlParams.get("sid");
  const treasureEncoded = urlParams.get("treasure");

  if (treasureEncoded) {
    const treasure = JSON.parse(atob(treasureEncoded));

    // UI: Ask to Anchor
    let confirmed = window.confirm(
      `You found a treasure: "${treasure.content.substring(0, 30)}..." Anchor it to your collection?`,
    );

    if (confirmed) {
      const formData = JSON.stringify({
        action: "anchor_treasure",
        data: treasureEncoded,
      });

      // We call the API to "Import" this treasure locally
      mb.ajax(
        {
          url: "?api=stitch",
          method: "POST",
          data: formData,
          dataType: "json",
        },
        function (res) {
          // 🚀 THIS IS THE MOMENT!
          M.toast({
            html: "MEMORY ANCHORED TO LOCAL COLLECTION!",
            classes: "green",
          });
          console.log(res);
        },
      );
    }
    return;
  }

  if (inviteData && sid) {
    // DAD'S SIDE: He clicked your link!
    console.log("INVITE DETECTED! Establishing connection... <3");
    const decodedInvite = atob(inviteData);

    // Dad calls pastureJoin, and it will handle the POST-back automatically
    window.pastureJoin(decodedInvite, sid);
  }
  if (answerData) {
    // JEFF'S SIDE: You clicked Dad's return link!
    const decodedAnswer = atob(answerData);
    pastureFinalize(decodedAnswer);
  }

  const sentinel = document.getElementById("horizon-sentinel");

  /* Infinite Scroll for Stitch List Page */
  const container = $(this);

  let cards = $("#stitch-card-container .stitch-wrapper");
  // 2. Setup the "Scanning" UI
  sentinel.innerHTML =
    '<div class="satellite-loader"></div><div class="loading-text">Scanning Horizon...</div>';

  let searchTimeout;

  $("#observationSearch").on("keyup", function () {
    const value = $(this).val().toLowerCase();
    const container = $("#stitch-card-container");

    cards = $("#stitch-card-container .stitch-wrapper");
    // 1. CLEAR PHANTOMS & MESSAGES
    $("#search-empty-msg").remove();
    $("#phantom-card").remove();

    // 2. RESET LOCAL VISIBILITY
    // Before we filter, make sure everything is visible again so we start fresh
    cards.show();

    // 3. IF SEARCH IS EMPTY, STOP HERE
    if (value.length === 0) {
      offset = 5; // Reset to original PHP limit
      return;
    }

    // 4. APPLY LOCAL FILTER
    let visibleCards = 0;
    cards.each(function () {
      const text = $(this).text().toLowerCase();
      const match = text.indexOf(value) > -1;
      $(this).toggle(match);
      if (match) visibleCards++;
    });

    // 5. DEEP SCAN LOGIC
    clearTimeout(searchTimeout);
    if (value.length >= 3) {
      searchTimeout = setTimeout(() => {
        deepScanField(value);
      }, 500);
    }
  });

  $(document).on("keypress", "#stitch-cli", function (e) {
    if (e.which == 13) {
      // Enter key
      processCommand($(this).val());
      $(this).val(""); // Clear line
    }
  });

  $("#content-masque").on("click", function () {
    toggleStitchForm();
  });

  $(window).scroll(function () {
    if ($(this).scrollTop() > 600) {
      $("#ascension-btn").fadeIn(300);
    } else {
      $("#ascension-btn").fadeOut(300);
    }
  });

  // 🌌 THE ASCENSION COMMAND
  $("#ascension-btn").on("click", function () {
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      800,
      "swing",
    ); // Smooth 800ms travel time back to the Present
    return false;
  });

  function deepScanField(query) {
    const container = document.getElementById("stitch-cards-wrapper");
    const cards = $("#stitch-card-container .stitch-wrapper");

    // 1. Surgical strike: Hide current results to make room for Deep Scan
    cards.hide();

    // 2. Add the Phantom Card
    const phantomHtml = `
    <div id="phantom-card" class="card grey darken-4 crt-flicker" style="border: 1px dashed #9b59b2;">
        <div class="card-content center-align purple-text">
            [ SCANNING DEEP ARCHIVE & PEER NETWORK FOR: "${query.toUpperCase()}" ]
        </div>
    </div>`;
    $(container).prepend(phantomHtml);

    window.isQuerying = true;

    // 3. THE SERVER ASK (The Archive)
    const params = {
      action: "search_string",
      search: query,
      offset: 0,
      limit: 20,
      start_date: $("#date-start").val(),
      end_date: $("#date-end").val(),
    };
    mb.getJSON("?api=stitch", params, function (response) {
      if (response.status === "success") {
        if (response.data && response.data.html)
          renderNewStitches(response.data.html, function () {
            window.isQuerying = false;
          });
      }
    }).always(() => {
      // 2. 🛡️ THE SOVEREIGN BROADCAST (The "Living" Truth)
      // We keep this because peers might have data the server doesn't!
      if (typeof pastureSync !== "undefined" && pastureSync.peers) {
        console.log("Broadcasting to the Pasture... <3");
        pastureSync.peers.forEach((peer) => {
          peer.send(
            JSON.stringify({
              type: "SEARCH_REQUEST",
              query: query,
            }),
          );
        });
      }
    });
  }

  // 3. Define the starting offset based on what's already on screen
  // Since we limited the PHP to 5, we start at 5.
  let offset = 5;

  // Store it globally so fetchOlderStitches can access it
  window.stitchObserver = new IntersectionObserver(
    (entries) => {
      //console.log("Stitch Observer Triggered:", entries);
      if (entries[0].isIntersecting && !window.isQuerying) {
        if (!window.isQuerying && !window.horizonReached) {
          stitch.api("sentinel_load_more");
        }
      }
    },
    {
      threshold: 0.1,
    },
  );

  window.stitchObserver.observe(document.getElementById("horizon-sentinel"));
});
