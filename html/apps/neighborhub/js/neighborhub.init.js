let nh = {
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
  guest: {
    phone: "",
  },
  cart: {
    activeBuilderInstance: null,
    activeCustomProductMetadata: null,
    activeMerchantIdReference: null,
  },
};

nh.setLocation = function (coords) {
  mb.geoLocate(function (coords) {
    console.log("Geolocation successful:", coords);
    nh.geo.defaultCenter = mb.storage.apps.neighborhub.preferences.map_center =
      {
        lat: coords.lat,
        lng: coords.lng,
      };
    storage_set();
  });
};

(function () {
  const rawStorage = localStorage.getItem("mediabrain");
  if (rawStorage) {
    mb.storage = JSON.parse(rawStorage);
    if (!mb.storage.apps.neighborhub) {
      mb.storage.apps.neighborhub = {
        currentDraft: [],
        chronicle: [],
        preferences: {
          mute_audio: false,
          map_center: null,
          terms_accepted: false,
          guest: {
            phone: null,
          },
        },
      };
      storage_set();
    }
    storage_get();
  }
  if (!mb.storage.apps.neighborhub.preferences.map_center) {
  }
})();

$(document).ready(function () {
  $(".mb-modal-fixed .modal-inner-overlay").on("click", function (e) {
    M.Modal.getInstance(
      document.getElementById($(this).closest(".modal").attr("id")),
    ).close();
  });

  // Check if terms are already accepted
  const isAcceptedLocally =
    mb.storage?.apps?.neighborhub?.preferences?.terms_accepted;

  if (!isAcceptedLocally) {
    $("#nh-terms-banner").show();
  }

  window.addEventListener('scroll', () => {
    const navbar = document.getElementById('navbar');

    if (window.scrollY > 40) {
      document.body.classList.add('scrolled');
    } else {
      document.body.classList.remove('scrolled');
    }
  });

  // Add .scrolled to body if document scroll position is not 0
  if (window.scrollY > 0) {
    document.body.classList.add('scrolled');
  }

  // Handle Accept Button Click
  $("#nh-accept-terms-btn").on("click", function () {
    loading(3);
    // Save to Database if user is logged in
    mb.ajax({
      type: "POST",
      url: "/?api=neighborhub&action=accept_terms",
      data: JSON.stringify({ accepted: true }),
      success: function (res) {
        loading(0);
        // Hide Banner
        $("#nh-terms-banner").slideUp(200);

        // Save locally to mb.storage
        if (mb.storage?.apps?.neighborhub?.preferences) {
          mb.storage.apps.neighborhub.preferences.terms_accepted = true;
          storage_set();
        }
      },
      error: function (err) {
        console.error("Failed to record terms acceptance on backend:", err);
      },
    });
  });
});
