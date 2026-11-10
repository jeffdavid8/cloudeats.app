class MerchantImagePicker {
  constructor(element, options) {
    this.element = element;
    this.options = options;
    this.select = element.querySelector("[data-image-select]");
    this.preview = element.querySelector("[data-image-preview]");
    this.copy = element.querySelector("[data-image-copy]");
    this.zone = element.querySelector("[data-image-dropzone]");
    this.input = element.querySelector("[data-image-upload]");
    this.uploading = false;
    this.zone.addEventListener("click", (event) => {
      if (event.target !== this.input) this.input.click();
    });
    this.input.addEventListener("change", () => {
      if (this.input.files[0]) this.upload(this.input.files[0]);
      this.input.value = "";
    });
    ["dragenter", "dragover"].forEach((name) => this.zone.addEventListener(name, (event) => {
      event.preventDefault();
      this.zone.classList.add("is-dragging");
    }));
    ["dragleave", "drop"].forEach((name) => this.zone.addEventListener(name, (event) => {
      event.preventDefault();
      this.zone.classList.remove("is-dragging");
    }));
    this.zone.addEventListener("drop", (event) => {
      if (event.dataTransfer.files[0]) this.upload(event.dataTransfer.files[0]);
    });
    this.select.addEventListener("change", () => {
      this.refreshPreview();
      if (this.options.onChange) this.options.onChange(this.select.value);
    });
    this.refresh();
  }

  refresh() {
    const instance = M.FormSelect.getInstance(this.select);
    if (instance) instance.destroy();
    const initialized = M.FormSelect.init(this.select);
    const items = initialized.dropdownOptions.querySelectorAll("li");
    Array.from(this.select.options).forEach((option, index) => {
      if (!option.dataset.imageUrl || !items[index]) return;
      const image = document.createElement("img");
      image.alt = "";
      image.src = option.dataset.imageUrl;
      items[index].prepend(image);
    });
    this.refreshPreview();
  }

  refreshPreview() {
    const option = this.select.selectedOptions[0];
    const url = option ? option.dataset.imageUrl : "";
    this.preview.hidden = !url;
    if (url) this.preview.src = url;
    else this.preview.removeAttribute("src");
    this.copy.textContent = url ? "Drop a replacement here or click to replace" : "Drop an image here or click to upload";
  }

  addImage(image) {
    let option = Array.from(this.select.options).find((item) => item.value === String(image.id));
    if (!option) {
      option = new Option(`Image ${image.id}`, String(image.id));
      this.select.add(option);
    }
    option.dataset.imageUrl = image.image_url;
    this.refresh();
  }

  upload(file) {
    if (this.uploading) return;
    this.uploading = true;
    this.input.disabled = true;
    this.options.onStatus("Uploading image...", false);
    const data = new FormData();
    data.append("merchant_id", this.options.merchantId);
    data.append("asset", file);
    mb.ajax({
      url: "?api=neighborhub&action=upload_storefront_asset",
      type: "POST",
      data,
      processData: false,
      contentType: false,
      success: (response) => {
        if (!response.success || !response.image || !response.image.id || !response.image.image_url) {
          this.options.onStatus(response.error || "Unable to upload image.", true);
          return;
        }
        this.options.onUpload(response.image, this);
        this.select.value = String(response.image.id);
        this.refresh();
        this.select.dispatchEvent(new Event("change", { bubbles: true }));
        this.options.onStatus("Image uploaded. Save your changes to use it.", false);
      },
      error: (xhr) => {
        const response = xhr.responseJSON || {};
        this.options.onStatus(response.error || response.message || "Unable to upload image.", true);
      },
      complete: () => {
        this.uploading = false;
        this.input.disabled = false;
      },
    });
  }
}
