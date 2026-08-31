const Vault = {
  // 💎 Mint Daily Provision
  mintProvision: function () {
    if (!confirm("Ready to mint the daily provision for the Town?")) return;

    mb.ajax(
      {
        url: "?api=vault",
        method: "POST",
        data: JSON.stringify({ action: "mint_provision" }), // mb.ajax handles stringifying if needed
        dataType: "json",
      },
      function (res) {
        console.log(res);
        if (res.status === "success") {
          M.toast({ html: "💎 PROVISION MINTED!", classes: "green rounded" });
          setTimeout(() => location.reload(), 1000);
        } else {
          M.toast({ html: "❌ Vault Error: " + res.message, classes: "red" });
        }
      },
    );
  },

  // 📩 Open the "Share" Window
  openTransferModal: function () {
    const elem = document.querySelector("#transferModal");
    const instance = M.Modal.init(elem);
    instance.open();
    // Materialize needs to re-init selects inside modals
    M.FormSelect.init(document.querySelectorAll("select"));
  },

  // 🤝 Send Treasure to a Resident
  sendTransfer: function () {
    const data = {
      action: "transfer",
      to_id: $("#transferRecipient").val(),
      amount: $("#transferAmount").val(),
      note: $("#transferNote").val(),
    };

    let apiUrl = "?api=vault";
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("admin_test_user")) {
      apiUrl += '&admin_test_user=' + urlParams.get("admin_test_user");
    }

    if (!data.to_id || !data.amount) {
      return M.toast({
        html: "Fill in the blanks, Vern! <3",
        classes: "amber",
      });
    }

    mb.ajax(
      {
        url: apiUrl,
        method: "POST",
        data: JSON.stringify(data),
        dataType: "json",
      },
      function (res) {
        if (res.status === "success") {
          // 1. Show a beautiful success toast
          M.toast({
            html: '<span><i class="material-icons left">check_circle</i> Treasure Shared!</span>',
            classes: "green rounded",
          });

          // 2. Wait 1.2 seconds so they can read it, then refresh
          setTimeout(() => {
            // Add a little fade out effect if you're feeling fancy
            document.body.style.opacity = "0.5";
            document.body.style.transition = "opacity 0.5s";
            location.reload();
          }, 1200);
        } else {
          M.toast({
            html: "❌ Transfer Failed: " + (res.message || "Unknown error"),
            classes: "red",
          });
        }
      },
    );
  },
};
