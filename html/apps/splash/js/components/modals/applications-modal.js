class ApplicationsModal {
  constructor() {
    this.init();
  }

  init() {
    this.attachEventListeners();
  }

  show() {
    const modal = document.getElementById("applicationsModal");
    if (!modal) {
      return;
    }

    window.history.pushState({ modalOpen: true }, "", "#applicationsModal");

    modal.classList.add("show");
    $("body").css("overflow", "hidden");
  }

  close() {
    const modal = document.getElementById("applicationsModal");
    modal.classList.remove("show");
    $("body").css("overflow", "auto");
    if (window.location.hash === '#applicationsModal') {
      history.replaceState({}, document.title, window.location.pathname);
    }
  }

  attachEventListeners() {
    window.addEventListener("popstate", (event) => {
      this.close();
    });

    document.addEventListener("click", (e) => {
      if (e.target.id === "applicationsModal") {
        this.close();
      }
    });

    document.addEventListener("keydown", (e) => {
      if (
        document.getElementById("applicationsModal").classList.contains("show")
      ) {
        if (e.key === "Escape") {
          this.close();
        }
      }
    });

  }

  static show() {
    if (!window.applicationsModalInstance) {
      window.applicationsModalInstance = new ApplicationsModal();
    }
    window.applicationsModalInstance.show();
  }
}

window.showApplicationsModal = ApplicationsModal.show;
