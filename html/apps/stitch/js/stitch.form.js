stitch.newStitchForm = {
  forms: {
    default: {},
  },
  data: {},
  metaLoaded: false,
};

stitch.newStitchForm.init = function (type = "default") {
  console.log(`Initializing "${type}" stitch form...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].init === "function"
  ) {
    stitch.newStitchForm.forms[type].init();
  }
};

stitch.newStitchForm.getContent = function (type = "default") {
  const $form = $("#newStitchForm");
  let content = false;

  console.log(`Assembling "${type}" stitch form content data...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].getContent === "function"
  ) {
    content = stitch.newStitchForm.forms[type].getContent();
  }

  return content;
};

stitch.newStitchForm.saveDraft = function (type = "default") {
  console.log(`Saving "${type}" stitch draft...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].saveDraft === "function"
  ) {
    stitch.newStitchForm.forms[type].saveDraft();
  }
};

stitch.newStitchForm.restoreDraft = function (type = "default") {
  const draft = mb.storage.apps.stitch.currentDraft[type];

  if (typeof draft === "object" && typeof draft.content !== "undefined") {
    console.log(`Restoring "${type}" stitch draft...`);

    if (
      typeof stitch.newStitchForm.forms[type] === "object" &&
      typeof stitch.newStitchForm.forms[type].restoreDraft === "function"
    ) {
      stitch.newStitchForm.forms[type].restoreDraft();
    }
  }
};

stitch.newStitchForm.reset = function (type = "default") {
  console.log(`Resetting "${type}" stitch form...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].reset === "function"
  ) {
    stitch.newStitchForm.forms[type].reset();
  }

  mb.storage.apps.stitch.currentDraft[type] = {};
  storage_set();
};

stitch.newStitchForm.updatePreview = function (type = "default") {
  console.log(`Updating "${type}" stitch preview...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].updatePreview === "function"
  ) {
    stitch.newStitchForm.forms[type].updatePreview();
  }
};

stitch.newStitchForm.validate = function (type = "default") {
  console.log(`Validating "${type}" stitch preview...`);

  if (
    typeof stitch.newStitchForm.forms[type] === "object" &&
    typeof stitch.newStitchForm.forms[type].validate === "function"
  ) {
    return stitch.newStitchForm.forms[type].validate();
  }

  return false;
};

/**
 * Default Form Type
 */
stitch.newStitchForm.forms.default = {
  urlCheckTimer: null,
  init: function () {
    let $form = $("#newStitchForm");
    let $textArea = $("textarea#newStitchContent", $form);
    stitch.newStitchForm.data.default = {};

    stitch.newStitchForm.forms.default.restoreDraft();
    stitch.newStitchForm.forms.default.updatePreview();

    M.updateTextFields(); // Keep Materialize looking sharp
    M.textareaAutoResize($textArea[0]);

    $(document).on(
      "input",
      "#newStitchForm textarea#newStitchContent",
      function () {
        var content = $(this).val();
        window.clearTimeout(stitch.newStitchForm.forms.default.urlCheckTimer);
        stitch.newStitchForm.forms.default.urlCheckTimer = window.setTimeout(
          function () {
            clearTimeout(stitch.newStitchAutoSaveTimeout);

            stitch.newStitchForm.forms.default.updatePreview();

            stitch.newStitchAutoSaveTimeout = setTimeout(function () {
              stitch.newStitchForm.forms.default.saveDraft();
            }, 1000);
          },
          600,
        );
      },
    );
  },
  reset: function () {
    let $form = $("#newStitchForm");
    let $deck = $("#deck-container");
    let $metaPreview = $("#metaPreview");

    $("#newStitchContent", $form).val("");
    $("#metaPreview").attr("data-url", "");
    $("#metaPreview").html("");
    $("#metaPreview").hide();
    return false;
  },
  resetMetaPreview: function () {
    $("#metaPreview").attr("data-url", "");
    $("#metaPreview").html("");
    $("#metaPreview").hide();
    return false;
  },
  updatePreview: function () {
    let $form = $("#newStitchForm");
    let $deck = $("#deck-container");
    let content = $("#newStitchContent", $form).val();
    let urlRegex = /(https?:\/\/[^\s]+)/g;
    let foundUrls = content.match(urlRegex);
    let latestUrl = foundUrls ? foundUrls[0] : false;

    if (
      foundUrls &&
      latestUrl &&
      $("#metaPreview").attr("data-url") !== latestUrl
    ) {
      $("#metaPreview")
        .html(
          `
            <div id="phantom-card" class="black darken-4 crt-flicker center-align" style="border: 1px dashed #9b59b2;">
              <div class="" style="margin-top: 15px;">
                <div class="quantum-spinner" style="height: 75px; margin: 0 auto;"></div>
              </div>
              <div class="center-align purple-text" style="margin-bottom: 15px;">
                  [ SCANNING URL META DATA ]
              </div>
            </div>
          `,
        )
        .show();

      mb.getMeta(latestUrl, function (meta) {
        //console.log("🚀 META:", meta);

        stitch.newStitchForm.data.default.meta = meta;

        $("#metaPreview").attr("data-url", latestUrl);
        // "Stitch" the metadata into a preview area in your form
        $("#metaPreview")
          .html(
            `
              <div class="card row">
                  <div class="card-image col s12 m6"><img class="responsive-img" src="${meta.image}"></div>
                  <div class="card-stacked col s12 m6">
                      <div class="card-content">
                  <button class="btn-round-action right" onclick="stitch.newStitchForm.forms.default.resetMetaPreview();" title="Remove" style="display: inline-block; margin-top: 3px;"><i class="material-icons">delete</i></button>
                          <span class="card-title">${meta.title}</span>
                          <p>${meta.description}</p>
                      </div>
                  </div>
              </div>
              `,
          )
          .show();

        stitch.metaLoaded = true; // Prevents re-fetching the same link constantly
      });
    }
  },
  getContent: function () {
    let $form = $("#newStitchForm");

    return {
      body: $("#newStitchContent", $form).val(),
      date: $("#stitch_date", $form).val(),
      lat: $("#stitch_lat", $form).val(),
      lng: $("#stitch_lng", $form).val(),
      id: $("#stitch_id", $form).val(),
      meta: stitch.newStitchForm.data.default.meta,
    };
  },
  saveDraft: function () {
    const draft = {
      id: $("#stitch_id").val() || "new",
      content: stitch.newStitchForm.forms.default.getContent(),
      lat: $("#stitch_lat").val(),
      lng: $("#stitch_lng").val(),
      projected_to: $("#stitch_date").val(),
      timestamp: Date.now(),
    };
    // Save to mb.storage (which syncs to LocalStorage via your existing storage_set)
    mb.storage.apps.stitch.currentDraft.default = draft;
    storage_set();
  },
  restoreDraft: function () {
    const draft = mb.storage.apps.stitch.currentDraft.default;
    if (!draft.content) return;

    $("#stitch_id").val(draft.id);
    $("#newStitchForm textarea#newStitchContent").val(draft.content.body);
    $("#stitch_lat").val(draft.lat);
    $("#stitch_lng").val(draft.lng);
    $("#stitch_date").val(draft.projected_to);
    M.updateTextFields();
    M.textareaAutoResize($("#newStitchForm textarea#newStitchContent")[0]);
  },
  validate: function () {
    let content = stitch.newStitchForm.forms.default.getContent();
    delete content.body;
    delete content.date;
    delete content.id;
    return Object.values(content).every((value) => !!value);
  },
};
