$(document).ready(function () {
  var csrfToken = $('meta[name="csrf-token"]').attr("content");
  if (csrfToken) {
    mb.csrf_token = csrfToken;
  }

  // 🛡️ THE SMART SHIELD: Replace $.ajaxSetup with a Prefilter
  $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
    // 🌍 Check if the URL is leaving our domain (starts with http and isn't our hostname)
    var isExternal =
      options.url.indexOf("http") === 0 &&
      options.url.indexOf(window.location.hostname) === -1;

    if (!isExternal) {
      // 🏰 INTERNAL: Add our security tokens
      jqXHR.setRequestHeader("X-CSRF-TOKEN", mb.csrf_token);
      jqXHR.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    } else {
      // 🛰️ EXTERNAL: Do NOT add custom headers. Keep the signal pure for APIs like Weather.gov
      console.log(
        "Sovereign Bypass: Stripping security headers for external API ->",
        options.url,
      );
    }
  });
});

storage_init();

/*
 * Initialize mediabrain responders on page load
 *
$(function () {
  $(window).respond();
});
 */
function storage_init() {
  if (!localStorage.getItem("mediabrain")) {
    mb.storage = {
      apps: {},
    };
    storage_set();
  }

  storage_get();

  if (!mb.storage.apps) {
    mb.storage.apps = {};
    storage_set();
  }
}

function storage_get() {
  mb.storage = JSON.parse(localStorage.getItem("mediabrain"));
}

function storage_set() {
  //console.log(mb.storage);
  localStorage.setItem("mediabrain", JSON.stringify(mb.storage));
}

function set_backgound_image(image_name) {
  var package = {
    action: "set_background",
    data: {
      image_name: image_name,
    },
  };

  mb.ajax({
    url: "api.php",
    dataType: "json",
    data: package,
    success: function (data) {
      console.log(data);
    },
    error: function (response) {
      console.log("There was a problem with the api call");
      console.log(response);
    },
  });
}

function copyText(text) {
  var input = document.createElement("textarea");
  document.body.appendChild(input);
  input.value = text;
  input.select();
  document.execCommand("copy", false);
  input.remove();
}

function notify(message, classes) {
  if (!classes) classes = "";
  M.toast({
    html: message,
    displayLength: 2000,
    classes: classes,
  });
}

if (typeof audioContext === "undefined") {
  var audioContext = new (window.AudioContext || window.webkitAudioContext)();
}

mb.ttsAudio = null; // Global audio element for TTS playback
let browserDefaultVoiceSynth = mb.isDevelopment; // Flag to indicate if TTS is currently playing

function speak(words, attachListeners) {
  if (typeof attachListeners !== "function") {
    attachListeners = function () {};
  }
  var package = {
    action: "text_to_speech",
    data: {
      csrf_token: mb.csrf_token,
      words: words,
    },
  };
  if (mb.ttsAudio === null) {
    mb.ttsAudio = new Audio();
  }

  if (!browserDefaultVoiceSynth) {
    // Use mb.ajax() instead of fetch() to ensure CSRF protection
    $.ajax({
      url: "api.php",
      headers: {
        Authorization: "Bearer " + this.csrf_token,
        "X-CSRF-TOKEN": this.csrf_token,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
        "Access-Control-Allow-Headers":
          "Origin, X-Requested-With, Content-Type, Accept, Authorization",
        "Access-Control-Allow-Credentials": "true",
        "Access-Control-Max-Age": "86400",
      },
      method: "POST",
      data: package,
      dataType: "json",
      success: function (jsonResponse) {
        if (jsonResponse.success && jsonResponse.audioContent) {
          const audioUrl = `data:audio/mp3;base64,${jsonResponse.audioContent}`;
          mb.ttsAudio.src = audioUrl;
          mb.ttsAudio
            .play()
            .then(() => {
              // Playback started successfully
              attachListeners(mb.ttsAudio);
            })
            .catch((error) => {
              console.error("Audio playback failed:", error);
            });
        } else {
          console.error(
            "TTS API error:",
            jsonResponse.error || "Unknown error",
          );
          // Fall back to browser speech synthesis
          fallbackToWebSpeech(words, attachListeners);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        let errorMessage = "An unknown error occurred.";

        try {
          const errorData = JSON.parse(jqXHR.responseText);
          errorMessage = errorData.error || errorMessage;
        } catch (e) {
          errorMessage = `HTTP Error: ${jqXHR.status} ${errorThrown}`;
        }

        console.error("TTS API error:", errorMessage);

        // Check if it's a Google Cloud authentication error
        if (
          errorMessage.includes("authentication not configured") ||
          errorMessage.includes("Google Cloud authentication failed")
        ) {
          console.log(
            "Google Cloud TTS authentication issue, trying browser speech synthesis...",
          );
          fallbackToWebSpeech(words, attachListeners);
        } else {
          fallbackToWebSpeech(words, attachListeners);
          console.log(`Text-to-Speech failed: ${errorMessage}`);
        }
      },
    });
  } else {
    fallbackToWebSpeech(words, attachListeners);
  }
}

function fallbackToWebSpeech(words, attachListeners) {
  browserDefaultVoiceSynth = true;
  // Try browser native speech synthesis as fallback
  if ("speechSynthesis" in window) {
    let synth = window.speechSynthesis;
    if (synth.speaking || synth.pending) {
      //synth.pause();
    }
    let utterance = new SpeechSynthesisUtterance(words);
    utterance.rate = 0.8;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    synth.speak(utterance);
    attachListeners(utterance);

    console.log("Using browser speech synthesis as fallback");

    // Show a subtle notification about the fallback
    console.log(
      "📢 Using browser text-to-speech. For higher quality audio, Google Cloud TTS setup is needed.",
    );
  } else {
    alert(
      `Text-to-Speech service not available.\n\nBrowser speech synthesis is not supported on this device.`,
    );
  }
}
// Listen for the first click anywhere on the document to unlock audio
document.addEventListener(
  "click",
  function unlockAudio() {
    const audioPlayer = document.getElementById("audio-player");

    if (audioPlayer) {
      // Play a fraction of a second of silence to satisfy the browser's gesture requirement
      audioPlayer.src =
        "data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAAAAA==";
      audioPlayer
        .play()
        .then(() => {
          console.log("Audio pipeline successfully unlocked by user gesture!");
          // Remove the listener so we don't keep running this on every click
          document.removeEventListener("click", unlockAudio);
        })
        .catch((err) => {
          console.log(
            "Unlock failed, waiting for a stronger user interaction:",
            err,
          );
        });
    }
  },
  { once: true },
);
function play(audioSource) {
  if (typeof audioSource !== "string" || !audioSource) {
    console.error("The provided audio source is invalid:", audioSource);
    // You could potentially handle Blob URLs here as well if needed
    return;
  }

  const audioPlayer = document.getElementById("audio-player");
  audioPlayer.src = audioSource;
  audioPlayer.play().catch((error) => {
    // Catches the DOMException promise rejection caused by the bad source/load
    console.error("Error playing audio:", error.message, error);
  });
}
mb.play = play;

/*
 *  Loading Indicator
 */
function loading(loading, on=true) {
  if (loading && on) {
    if (loading == 1) {
      $("body").addClass("loading-bg");
    } else if (loading == 2) {
      $("body").addClass("loading");
    } else if (loading == 3) {
      $("body").addClass("loading-nav-trigger");
    } else if (loading == 4) {
      $("body").addClass("loading-progress");
    } else if (loading == 5) {
      $("body").addClass("loading-preloader");
    } else {
      $("body").addClass("loading-progress");
    }
  } else {
    if (loading == 1) {
      $("body").removeClass("loading-bg");
    } else if (loading == 2) {
      $("body").removeClass("loading");
    } else if (loading == 3) {
      $("body").removeClass("loading-nav-trigger");
    } else if (loading == 4) {
      $("body").removeClass("loading-progress");
    } else if (loading == 5) {
      $("body").removeClass("loading-preloader");
    } else {
      $("body").removeClass("loading-progress");
    }

    if (on) $("body").removeClass("loading loading-bg loading-progress loading-preloader loading-nav-trigger");
  }
}

$(window).on("load", function () {
  $("body").removeClass("loading-preloader");
  $('#cloudeats-preloader .preloader-text').html('');
});

mb.process = function (data) {
  play("audio/Star Trek - Hail.wav");
  notify("Parsing data...");
  //play('audio/star trek sounds/computerbeep_16.mp3');
  console.log(data);
};

mb.ajax = function (options, callback) {
  var isExternal =
    options.url.indexOf("http") === 0 &&
    options.url.indexOf(window.location.hostname) === -1;
  var defaults = {
    headers: {
      "User-Agent": "mediabrain.app",
      Authorization: "Bearer " + this.csrf_token,
      "X-CSRF-TOKEN": this.csrf_token,
      "X-Requested-With": "XMLHttpRequest",
      "Content-Type": "application/json",
      Accept: "application/json",
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
      "Access-Control-Allow-Headers":
        "Origin, X-Requested-With, Content-Type, Accept, Authorization",
      "Access-Control-Allow-Credentials": "true",
      "Access-Control-Max-Age": "86400",
    },
    statusCode: {
      401: function () {
        window.location.href =
          "/?p=login&return=" +
          encodeURIComponent(window.location.pathname + window.location.search);
      },
      403: function () {
        window.location.href =
          "/?p=login&return=" +
          encodeURIComponent(window.location.pathname + window.location.search);
      },
      500: function (jqxhr, textStatus, errorThrown) {
        console.log(
          "Something went wrong while trying to process your request." +
            " Please contact support with the following error information: <br/>" +
            " status: " +
            jqxhr.status +
            "<br/>" +
            " response: " +
            jqxhr.responseText +
            "<br/>" +
            " error thrown: " +
            errorThrown,
        );
      },
    },
  };

  // Extend default options
  var settings = $.extend(true, {}, defaults, options);

  // CRITICAL FIX: If contentType is explicitly false (e.g. for FormData),
  // strip out the hardcoded default application/json header so the browser handles boundaries.
  if (options.contentType === false && settings.headers) {
    delete settings.headers["Content-Type"];
  }

  if (typeof callback === "function") {
    settings.success = callback;
  }
  return $.ajax(settings);
};

// Shorthand for a mb.ajax() post request
mb.post = function (url, data, callback, type) {
  // shift arguments if data argument was omitted
  if (jQuery.isFunction(data)) {
    type = type || callback;
    callback = data;
    data = undefined;
  }

  return this.ajax({
    type: "POST",
    url: url,
    data: data,
    success: callback,
    dataType: type,
  });
};

// Shorthand for a mb.ajax() get request
mb.get = function (url, data, callback, type) {
  // shift arguments if data argument was omitted
  if (jQuery.isFunction(data)) {
    type = type || callback;
    callback = data;
    data = undefined;
  }

  return this.ajax({
    type: "GET",
    url: url,
    data: data,
    success: callback,
    dataType: type,
  });
};

mb.loadJs = function (url, callback) {
  if (typeof url === "string") {
    // Return if script is already loaded
    if ($('script[src="' + url + '"]').length) {
      if (typeof callback === "function") {
        callback();
      }
      return;
    }

    return this.ajax({
      url: url,
      dataType: "script",
      success: callback,
      async: true,
    });
  } else if (typeof url === "object") {
    var scriptComplete = 0;
    $.each(url, function (index, path) {
      console.log(path);
      if (path == "undefined.js") return;
      mb.loadJs(path, function () {
        scriptComplete++;
        if (url.length == scriptComplete) {
          if (typeof callback === "function") {
            callback();
          }
        }
      });
    });
  } else {
    console.error(
      "An unexpected url was passed to the loadJs function, url contents:",
    );
    console.error(url);
  }
};

mb.getJson = function (url, data, callback) {
  // 🛰️ We must explicitly tell mb.ajax this is a GET request
  return this.ajax({
    type: "GET", // Force GET method
    url: url,
    data: data,
    dataType: "json",
    success: callback,
  });
};
mb.getJSON = mb.getJson;

mb.loadCss = function (url) {
  if ($("link[href='" + url + "']").length < 1) {
    $("<link>").appendTo("head").attr({
      type: "text/css",
      rel: "stylesheet",
      href: url,
    });
  }
};

mb.getMeta = function (url, callback) {
  if (!url) return;

  return this.ajax({
    type: "POST",
    url: "?api=mb", // Or your specific stitch API
    data: JSON.stringify({
      action: "fetch_page_meta_data",
      url: url,
    }),
    dataType: "json",
    success: function (response) {
      if (response.success) {
        if (typeof callback === "function") callback(response.meta);
      } else {
        console.error("❌ EXTRACTION_FAILED:", response.error);
      }
    },
  });
};

mb.download = function (content, fileName, contentType) {
  var a = document.createElement("a");
  var file = new Blob([content], { type: contentType });
  a.href = URL.createObjectURL(file);
  a.download = fileName;
  a.click();
};

// Browser Tab Methods
mb.browserTab = {
  documentTitle: document.title,
  titleTimer: null,

  alert: function (message) {
    var self = this;
    var state = false;

    // Store the current title
    self.documentTitle = document.title;
    // Set the flash interval
    self.titleTimer = setInterval(flash, 1000);

    function flash() {
      // switch between old and new titles
      document.title = state ? self.documentTitle : message;
      state = !state;
    }
  },

  reset: function () {
    var self = this;
    if (self.titleTimer != null) clearInterval(self.titleTimer);
    // Restore previous title
    document.title = self.documentTitle;
  },
};

mb.userLogout = function () {
  /*
  if (confirm("Are you sure you want to logout?")) {
  } */
  // Always use the main API since authentication should be unified
  const apiUrl = "/?api=auth";
  const redirectUrl = "?p=login";

  // Use mb.ajax to properly handle CSRF tokens
  mb.ajax({
    url: apiUrl,
    type: "POST",
    data: JSON.stringify({
      action: "logout",
      return_url: redirectUrl, // Send the login page as return URL
    }),
    dataType: "json",
    success: function (data) {
      console.log("Logout response:", data);
      if (data.success) {
        // Use the redirect URL from response, or fallback to login
        const finalRedirect = data.redirect || redirectUrl;
        console.log("Redirecting to:", finalRedirect);
        window.location.href = finalRedirect;
      } else {
        console.log("Logout failed:", data.error || "Unknown error");
        //window.location.href = redirectUrl;
      }
    },
    error: function (xhr, status, error) {
      console.log("Error during logout:", error);
      // Fallback to login page
      //window.location.href = redirectUrl;
    },
  });
};

mb.newTab = function (url) {
  window.open(url, "_blank");
};

mb.loggingEnabled = false;
mb.logs = [];
mb.log = function (message, ...optionalParams) {
  if (!mb.loggingEnabled) return; // Prevent logging if disabled

  // Capture caller file and line number using Error stack
  const stack = new Error().stack;
  let callerInfo = "";
  if (stack) {
    const stackLines = stack.split("\n");
    // The third line is usually the caller (first is Error, second is this function)
    if (stackLines.length >= 3) {
      // Extract file and line info using regex
      const match = stackLines[2].match(
        /(?:at\s+.*\s+\()?([^\s]+):(\d+):(\d+)\)?/,
      );
      if (match) {
        callerInfo = ` (${match[1]}:${match[2]})`;
      }
    }
  }
  mb.logs.push(`[MediaBrain] ${message}${callerInfo}`);
  optionalParams.forEach((param) => {
    mb.logs.push(`[MediaBrain] ${JSON.stringify(param)}`);
  });
};

mb.getStardate = function () {
  const now = new Date();
  const year = now.getFullYear();
  const dayOfYear = Math.floor(
    (now - new Date(year, 0, 0)) / (1000 * 60 * 60 * 24),
  );
  // Star Trek TNG style stardate calculation
  const stardate = (year - 2323) * 1000 + dayOfYear * 2.74;
  return stardate.toFixed(1);
};

mb.audio = function (
  audioSource,
  path = "./audio/star trek sounds",
  volume = "0.5",
  callback = null,
) {
  // 🛡️ REFINED OBJECT UNPACKING
  if (typeof audioSource === "object" && audioSource !== null) {
    // Read properties from the object BEFORE we overwrite the reference
    path = audioSource.path || path;
    volume = audioSource.volume || volume;
    callback = audioSource.callback || callback;

    // Set the source last
    audioSource = audioSource.source;
  }

  if (typeof audioSource !== "string" || !audioSource) {
    console.error("The provided audio source is invalid:", audioSource);
    return;
  }

  const audioPlayer = document.getElementById("audio-player");
  audioPlayer.src = path + "/" + audioSource + ".mp3";

  // 🧹 CLEANUP: Remove old listeners so they don't stack up!
  // This is a common cause of "double-chirping"
  const newPlayer = audioPlayer.cloneNode(true);
  audioPlayer.parentNode.replaceChild(newPlayer, audioPlayer);
  newPlayer.volume = volume;

  newPlayer.play().catch((error) => {
    console.error("Error playing audio:", error.message);
  });

  newPlayer.addEventListener(
    "ended",
    function () {
      console.log("DEBUG: Executing callback for:", audioSource);
      if (typeof callback === "function") callback();
    },
    { once: true },
  ); // Use { once: true } for cleaner memory management
};

mb.getLocality = function (callback) {
  console.log("Locating_User_Node...");
  // Using ipapi.co (no-key required for basic usage)
  fetch("https://ipapi.co/json/")
    .then((response) => response.json())
    .then((data) => {
      const locality = {
        ip: data.ip,
        lat: data.latitude,
        lng: data.longitude,
        city: data.city,
      };
      console.log("Node_Located:", locality);
      if (callback) callback(locality);
    })
    .catch((err) => console.error("Locality_Fetch_Failed:", err));
};

mb.geoLocate = function (callback) {
  // Priority 1: Browser GPS (Accurate)
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const coords = {
          lat: pos.coords.latitude.toFixed(6),
          lng: pos.coords.longitude.toFixed(6),
        };
        console.log("Node_Located:", coords);
        if (callback) callback(coords);
      },
      (err) => {
        // Priority 2: IP Lookup (Fallback)
        mb.getLocality((loc) => {
          $("#stitch_lat").val(loc.lat);
          $("#stitch_lng").val(loc.lng);
          M.updateTextFields();
          $btn.find("i").removeClass("rotate-sync");
          const coords = {
            lat: loc.lat,
            lng: loc.lng,
          };
          console.log("Node_Located:", coords);
          if (callback) callback(coords);
        });
      },
    );
  } else {
    // Priority 3: IP Lookup (Fallback)
    notify("Geolocation is not supported by this browser.");
    //mb.getLocality();
  }
};

mb.reverseGeoCode = function (lat, lng, callback = {}) {
  mb.ajax({
    url: "?api=neighborhub&action=reverse_geocode_proxy",
    type: "GET",
    dataType: "json",
    data: {
      lat: lat,
      lng: lng,
    },
    success: function (data) {
      if (data && data.display_name) {
        if (typeof callback === "function") {
          callback(data);
        }
      }
    },
    error: function (xhr, status, error) {
      console.error("Proxy Endpoint AJAX Error:", error);
    },
  });
};

/*
 * jQuery Responder
 */
jQuery.fn.respond = function (options) {
  mb.respond = []; // Extend the mediabrain object

  var defaults = {
    contentSelector: "body",
    scrollingVideoContainerSelector: window,
    callbacks: {
      contentFixed: function () {},
      contentReleased: function () {},
    },
  };

  var ui = this;
  var settings = $.extend({}, defaults, options);
  var _initialized = false;
  var _pluginDir = "/js/plugins/respond";

  function learn(element) {
    loadBehaviors(element, function () {
      behave(element);
    });

    _initialized = true;
  }

  return this.each(function () {
    learn(this);
  });

  function loadBehaviors(element, callback) {
    // TODO - initialize a loader here to autoload _behaviors, based on settings or what is on the page?
    mb.respond._behaviors = [
      {
        //Video Playback
        process: function (element) {
          async function playVideo(el) {
            try {
              await el.play();
            } catch (err) {
              console.log(err);
            }
          }

          // Video scrolling playback control
          function monitorVideo() {
            $("video.pauseWhenHidden").each(function () {
              var $this = $(this);

              if (!$this[0].paused && !$this.visible(true)) {
                $this[0].pause();
              }
              /*
                     else
                     {
                        playVideo($this[0]);
                     }
                     */
            });
          }
          $(settings.scrollingVideoContainerSelector).on(
            "scroll",
            monitorVideo,
          );
          $(element).resize(monitorVideo);
        },
      },
      {
        // Inter-viewport communications
        process: function (element) {
          function receiveMessage(event) {
            trigger(element, event.data);
          }
          element.addEventListener("message", receiveMessage, false);
        },
      },
    ];

    if (typeof callback == "function") {
      callback();
    }
  }

  function behave(element) {
    $(mb.respond._behaviors).each(function (key, behavior) {
      behavior.process(element);
    });
  }

  function trigger(element, data) {
    /*
     * package = {
     *    'plugin': 'pluginName',
     *    'method': 'methodName',
     *    'payload': {}, // data passed as an argument to the method
     * }
     */

    if (typeof mb.respond._behaviors[data.plugin] === "undefined") {
      // THIS NEEDS TO BE FINISHED
      /*
         mb.loadJs(_pluginDir + '/' + data.plugin + '.js', function() {
            execute(data);
         });
         */
    } else {
      execute(data);
    }
  }

  function responsive(data) {
    return (
      typeof data.plugin === "string" &&
      typeof mb.respond._behaviors[data.plugin] !== "undefined" &&
      (typeof data.method !== "undefined"
        ? typeof data.method === "string"
        : true) &&
      typeof mb.respond._behaviors[data.plugin][data.method] === "function"
    );
  }

  function execute(data) {
    if (responsive(data)) {
      // Execute the request
      var result = {};
      if (typeof mb.respond._behaviors[data.plugin] === "function") {
        result = mb.respond._behaviors[data.plugin](data);
      } else {
        result = mb.respond._behaviors[data.plugin][data.method](data.payload);
      }

      if (typeof data.callback == "function") {
        data.callback(result);
      } else {
        return result;
      }
    } else {
      console.log(
        "The plugin is not available - mb.respond._behaviors." +
          data.plugin +
          "." +
          data.method,
      );
      handleError(data);
    }
  }

  function handleError(data) {
    console.log(data);
  }
};

/*
 * IsVisible()
 */
!(function (t) {
  var i = t(window);
  t.fn.visible = function (t, e, o) {
    if (!(this.length < 1)) {
      var r = this.length > 1 ? this.eq(0) : this,
        n = r.get(0),
        f = i.width(),
        h = i.height(),
        o = o ? o : "both",
        l = e === !0 ? n.offsetWidth * n.offsetHeight : !0;
      if ("function" == typeof n.getBoundingClientRect) {
        var g = n.getBoundingClientRect(),
          u = g.top >= 0 && g.top < h,
          s = g.bottom > 0 && g.bottom <= h,
          c = g.left >= 0 && g.left < f,
          a = g.right > 0 && g.right <= f,
          v = t ? u || s : u && s,
          b = t ? c || a : c && a;
        if ("both" === o) return l && v && b;
        if ("vertical" === o) return l && v;
        if ("horizontal" === o) return l && b;
      } else {
        var d = i.scrollTop(),
          p = d + h,
          w = i.scrollLeft(),
          m = w + f,
          y = r.offset(),
          z = y.top,
          B = z + r.height(),
          C = y.left,
          R = C + r.width(),
          j = t === !0 ? B : z,
          q = t === !0 ? z : B,
          H = t === !0 ? R : C,
          L = t === !0 ? C : R;
        if ("both" === o) return !!l && p >= q && j >= d && m >= L && H >= w;
        if ("vertical" === o) return !!l && p >= q && j >= d;
        if ("horizontal" === o) return !!l && m >= L && H >= w;
      }
    }
  };
})(jQuery);

$(document).ready(function () {
  // Your debugger info logic remains unchanged...
  $("pre.debugger-info").each(function () {
    var $this = $(this);
    var $copyButton = $(
      '<button style="position: absolute; top: 10px; right: 10px;" class="btn-small btn-copy-debug-info"><i class="material-icons">content_copy</i></button>',
    );
    $this.prepend($copyButton);
    $copyButton.on("click", function () {
      copyText($this.text());
      notify("Debug info copied to clipboard!");
    });
  });

  // 1. Initialize the Sidenav first
  var slideOut = document.getElementById("slide-out");
  var instance = M.Sidenav.init(slideOut, {
    outDuration: 200,
  });

  // 2. Custom Toggle Logic for the Button
  // Select the raw DOM element from the jQuery wrapper
  $(
    "#slide-out .sidenav-trigger, .header nav .header-sidenav-trigger, .header nav .sidenav-trigger",
  ).each(function () {
    $(this).on("click", function (e) {
      e.preventDefault();
      e.stopPropagation(); // Stops click from bubbling to document and auto-closing
      var $sidenavOverlay = $(".sidenav-overlay");

      if (instance.isOpen) {
        instance.close();
        $sidenavOverlay.css("opacity", "0").hide();
      } else {
        instance.open(); // This naturally creates and animates the overlay
        $sidenavOverlay.css("opacity", "1").show();
      }
    });
  });

  // 3. Handle Overlay Click explicitly
  $(document).on("click", ".sidenav-overlay", function (e) {
    var $sidenavOverlay = $(".sidenav-overlay");
    if (instance.isOpen) {
      instance.close();
      $sidenavOverlay.css("opacity", "0").hide();
    }
  });
});
