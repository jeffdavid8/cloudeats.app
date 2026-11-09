(function () {
  function init() {
    const menu = document.getElementById("merchant-slide-out");
    const trigger = document.querySelector(".merchant-sidenav-trigger");
    if (!menu || !trigger) return;

    const instance = M.Sidenav.init(menu, {
      outDuration: 200,
      draggable: false,
      onOpenStart: function () { trigger.setAttribute("aria-expanded", "true"); },
      onCloseStart: function () { trigger.setAttribute("aria-expanded", "false"); },
    });

    trigger.addEventListener("click", function (event) {
      event.preventDefault();
      if (instance.isOpen) instance.close();
      else instance.open();
    });
    menu.querySelector(".merchant-sidenav-close").addEventListener("click", function (event) {
      event.preventDefault();
      instance.close();
      trigger.focus();
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
