<?php
if (!defined('MB_RUNNING')) exit;

$merchantId = (int)($merchant_id ?? 0);
$configuration = is_array($storefront_config ?? null) ? $storefront_config : MerchantStorefront::defaultConfiguration();
$merchantGallery = is_array($merchant->gallery ?? null) ? $merchant->gallery : [];
$configuration['asset_urls'] = [];
foreach ($merchantGallery as $asset) {
  if (isset($asset['id'], $asset['image_url'])) {
    $configuration['asset_urls'][(int)$asset['id']] = (string)$asset['image_url'];
  }
}
$previewUrl = '/?app=neighborhub&view=customer&p=merchant_products&merchant_id=' . $merchantId . '&preview=1';
?>
<link rel="stylesheet" href="/apps/neighborhub/css/merchant-image-picker.css">
<style>
  .storefront-editor {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 1rem;
    min-height: calc(100vh - 8rem);
  }

  .storefront-editor-canvas {
    min-height: calc(90vh - 5rem);
    overflow: hidden;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    background: #f3f4f6;
  }

  .storefront-editor-canvas iframe {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 720px;
    border: 0;
    background: #fff;
  }

  .storefront-editor-panel {
    --storefront-editor-panel-background: #fff;
    position: sticky;
    top: 1rem;
    height: calc(90vh - 5rem);
    align-self: start;
    max-height: calc(100vh - 2rem);
    overflow: auto;
    padding: 1rem;
    padding-bottom: 0;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: var(--storefront-editor-panel-background);
    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    color: #1f2937;
  }

  .nightMode .storefront-editor-panel {
    --storefront-editor-panel-background: #1f2937;
    border-color: #374151;
  }

  .nightMode .storefront-editor-panel h2 {
    color: #f9fafb !important;
  }

  .nightMode .storefront-editor-panel {
    color: #f9fafb !important;
  }

  .storefront-editor-field {
    margin: 0 0 1rem;
  }

  .storefront-editor-field>label {
    position: static !important;
    display: block;
    width: auto;
    height: auto;
    margin: 0 0 .35rem;
    transform: none !important;
    /* color: #4b5563 !important; */
    font-size: .9rem;
  }

  .storefront-editor-field .select-wrapper {
    margin-top: 0;
  }

  .storefront-editor-panel select,
  .storefront-editor-panel input[type="color"] {
    color: #1f2937;
  }

  .storefront-editor-toolbar {
    display: flex;
    gap: .5rem;
    margin-bottom: .75rem;
  }

  .storefront-editor-toolbar button.is-active {
    background: #2563eb;
    color: #fff;
  }

  .storefront-editor-canvas.is-mobile {
    max-width: 390px;
    margin: 0 auto;
  }

  .storefront-editor-canvas.is-mobile iframe {
    min-height: 760px;
  }

  .storefront-editor-panel h2 {
    margin-top: 0;
    font-size: 1.25rem;
  }

  .storefront-editor-footer {
    position: sticky;
    bottom: 0;
    z-index: 5;
    margin: 1rem -1rem 0;
    padding: .75rem 1rem 1rem;
    border-top: 1px solid #e5e7eb;
    border-radius: 0 0 11px 11px;
    background: var(--storefront-editor-panel-background);
  }

  .nightMode .storefront-editor-footer {
    border-color: #374151;
  }

  .storefront-editor-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
  }

  .storefront-editor-status {
    min-height: 1.5rem;
    margin-top: .75rem;
  }

  .storefront-editor-blocks {
    display: grid;
    gap: .75rem;
    margin: 1rem 0;
  }

  .storefront-editor-blocks:empty::before {
    content: "No promotional blocks yet. Choose a block type and select Add block.";
    display: block;
    padding: .75rem;
    border: 1px dashed #9ca3af;
    border-radius: 8px;
    color: #6b7280;
    font-size: .9rem;
  }

  .storefront-editor-block {
    padding: .75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
  }

  .storefront-editor-block h3 {
    margin: 0 0 .5rem;
    font-size: 1rem;
    text-transform: capitalize;
  }

  .storefront-editor-block label {
    display: block;
    margin-top: .5rem;
  }

  @media (max-width: 900px) {
    .storefront-editor {
      grid-template-columns: 1fr;
    }

    .storefront-editor-panel {
      position: static;
      max-height: none;
      overflow: visible;
      order: -1;
    }

    .storefront-editor-canvas,
    .storefront-editor-canvas iframe {
      min-height: 620px;
    }
  }
</style>

<nav class="clean-nav breadcrumb-nav">
  <div class="col s12">
    <a href="?view=merchant&merchant_id=<?= $merchantId ?>&p=dashboard" class="breadcrumb">Store Dashboard</a>
    <a href="?view=merchant&merchant_id=<?= $merchantId ?>&p=storefront_editor" class="breadcrumb">Edit Storefront</a>
  </div>
</nav>

<div class="container storefront-editor" data-merchant-id="<?= $merchantId ?>">
  <div class="storefront-editor-canvas">
    <div class="storefront-editor-toolbar" aria-label="Preview size">
      <button type="button" class="btn-small storefront-editor-viewport is-active" data-viewport="desktop">Desktop</button>
      <button type="button" class="btn-small storefront-editor-viewport" data-viewport="mobile">Mobile</button>
    </div>
    <iframe id="storefront-editor-preview" title="Live storefront draft preview" src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
  </div>
  <aside class="storefront-editor-panel">
    <h2>Edit storefront</h2>
    <p class="">Changes are saved as a draft until you publish them.</p>
    <form id="storefront-editor-form">
      <label for="storefront-editor-theme">Theme</label>
      <select id="storefront-editor-theme">
        <option value="default">Default</option>
        <option value="pizza-king">Pizza King</option>
        <option value="bakery">Bakery</option>
        <option value="diner">Diner</option>
      </select>
      <label for="storefront-editor-layout">Hero layout</label>
      <select id="storefront-editor-layout">
        <option value="hero-centered">Centered hero</option>
        <option value="hero-full">Full hero</option>
        <option value="hero-split">Split hero</option>
        <option value="hero-minimal">Minimal hero</option>
      </select>
      <div class="storefront-editor-field">
        <label for="storefront-editor-primary">Primary color</label>
        <input id="storefront-editor-primary" type="color" value="#4f46e5">
      </div>
      <?php
      render('components/merchant_image_picker.php', ['imagePicker' => [
        'id' => 'storefront-editor-logo', 
        'label' => 'Promo Image', 
        'fallback_label' => 'Use merchant profile image', 
        'fallback_url' => $merchant->image_url ?? '', 'gallery' => $merchantGallery
        ]]);
      ?>
      <div class="storefront-editor-field">
        <label for="storefront-editor-logo-style">Logo style</label>
        <select id="storefront-editor-logo-style">
          <option value="default">Default (existing style)</option>
          <option value="original">Original / transparent</option>
          <option value="circle">Circle</option>
          <option value="rectangle">Rectangle</option>
        </select>
      </div>
      <?php
      $imagePicker = ['id' => 'storefront-editor-hero', 'label' => 'Hero image', 'fallback_label' => 'Use merchant profile image', 'fallback_url' => $merchant->image_url ?? '', 'gallery' => $merchantGallery];
      include __DIR__ . '/../../components/merchant_image_picker.php';
      ?>
      <p><label><input type="checkbox" class="filled-in storefront-editor-visibility" data-key="search"><span>Product search</span></label></p>
      <p><label><input type="checkbox" class="filled-in storefront-editor-visibility" data-key="contact"><span>Contact information</span></label></p>
      <p><label><input type="checkbox" class="filled-in storefront-editor-visibility" data-key="categories"><span>Menu categories</span></label></p>
      <p><label><input type="checkbox" class="filled-in storefront-editor-visibility" data-key="operational_status"><span>Open/closed status</span></label></p>
      <h3>Promotional blocks</h3>
      <div id="storefront-editor-blocks" class="storefront-editor-blocks"></div>
      <div style="display:flex; gap:.5rem; align-items:center;">
        <select id="storefront-editor-block-type">
          <option value="announcement">Announcement</option>
          <option value="featured_product">Featured product</option>
          <option value="image_text">Image and text</option>
          <option value="call_to_action">Call to action</option>
          <option value="store_information">Store information</option>
        </select>
        <button type="button" class="btn-small blue waves-effect waves-light" id="storefront-editor-add-block">Add block</button>
      </div>
      <div class="storefront-editor-footer">
        <div class="storefront-editor-actions">
          <button type="button" class="btn waves-effect waves-light" id="storefront-editor-save">Save draft</button>
          <button type="button" class="btn green waves-effect waves-light" id="storefront-editor-publish">Publish</button>
          <button type="button" class="btn-flat waves-effect" id="storefront-editor-revert">Revert</button>
          <a class="btn-flat waves-effect" href="<?= htmlspecialchars('/?app=neighborhub&view=customer&p=merchant_products&merchant_id=' . $merchantId, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">View live</a>
        </div>
        <div id="storefront-editor-status" class="storefront-editor-status" role="status"></div>
      </div>
    </form>
  </aside>
</div>

<script src="/apps/neighborhub/js/merchant-image-picker.js"></script>
<script>
  (function() {
    const merchantId = <?= json_encode($merchantId) ?>;
    const preview = document.getElementById('storefront-editor-preview');
    let configuration = <?= json_encode($configuration, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const pickers = [];

    function status(message, error) {
      const element = document.getElementById('storefront-editor-status');
      element.textContent = message;
      element.className = 'storefront-editor-status ' + (error ? 'red-text' : 'green-text');
    }

    function initMaterializeSelects() {
      if (!window.M || !window.M.FormSelect) return;
      document.querySelectorAll('.storefront-editor-panel select').forEach(function(select) {
        if (!window.M.FormSelect.getInstance(select)) {
          window.M.FormSelect.init(select);
        }
      });
    }

    function render() {
      document.getElementById('storefront-editor-theme').value = configuration.theme || 'default';
      document.getElementById('storefront-editor-layout').value = configuration.layout || 'hero-centered';
      document.getElementById('storefront-editor-logo-style').value = configuration.logo_style || 'default';
      document.getElementById('storefront-editor-primary').value = configuration.colors && configuration.colors.primary || '#4f46e5';
      document.getElementById('storefront-editor-logo').value = configuration.assets && configuration.assets.logo_id || '';
      document.getElementById('storefront-editor-hero').value = configuration.assets && configuration.assets.hero_id || '';
      document.querySelectorAll('.storefront-editor-visibility').forEach(function(input) {
        input.checked = !configuration.visibility || configuration.visibility[input.dataset.key] !== false;
      });
      renderBlocks();
      initMaterializeSelects();
      pickers.forEach(function(picker) {
        picker.refresh();
      });
    }

    function renderBlocks() {
      const wrapper = document.getElementById('storefront-editor-blocks');
      wrapper.innerHTML = '';
      (configuration.blocks || []).forEach(function(block, index) {
        const content = block.content || {};
        const card = document.createElement('div');
        card.className = 'storefront-editor-block';
        const title = document.createElement('h3');
        title.textContent = String(block.type || '').replace(/_/g, ' ');
        card.appendChild(title);

        if (block.type === 'announcement' || block.type === 'image_text') {
          appendBlockInput(card, 'Title', content.title || '', function(value) {
            block.content = Object.assign({}, block.content, {
              title: value
            });
          });
          appendBlockInput(card, 'Text', content.text || '', function(value) {
            block.content = Object.assign({}, block.content, {
              text: value
            });
          });
          if (block.type === 'image_text') {
            appendBlockInput(card, 'Gallery image ID', content.image_id || '', function(value) {
              block.content = Object.assign({}, block.content, {
                image_id: value ? Number(value) : null
              });
            });
          }
        } else if (block.type === 'featured_product') {
          appendBlockInput(card, 'Product ID', content.product_id || '', function(value) {
            block.content = Object.assign({}, block.content, {
              product_id: value ? Number(value) : null
            });
          });
        } else if (block.type === 'call_to_action') {
          appendBlockInput(card, 'Label', content.label || '', function(value) {
            block.content = Object.assign({}, block.content, {
              label: value
            });
          });
          appendBlockInput(card, 'Link', content.url || '', function(value) {
            block.content = Object.assign({}, block.content, {
              url: value
            });
          });
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn-small red waves-effect';
        remove.textContent = 'Remove';
        remove.addEventListener('click', function() {
          configuration.blocks.splice(index, 1);
          renderBlocks();
          updatePreview();
        });
        card.appendChild(remove);
        wrapper.appendChild(card);
      });
    }

    function appendBlockInput(parent, labelText, value, onChange) {
      const label = document.createElement('label');
      label.textContent = labelText;
      const input = document.createElement('input');
      input.type = 'text';
      input.value = value;
      input.addEventListener('input', function() {
        onChange(input.value);
        updatePreview();
      });
      label.appendChild(input);
      parent.appendChild(label);
    }

    function updateConfiguration() {
      configuration.theme = document.getElementById('storefront-editor-theme').value;
      configuration.layout = document.getElementById('storefront-editor-layout').value;
      configuration.logo_style = document.getElementById('storefront-editor-logo-style').value;
      configuration.colors = configuration.colors || {};
      configuration.colors.primary = document.getElementById('storefront-editor-primary').value;
      configuration.assets = configuration.assets || {};
      ['logo', 'hero'].forEach(function(slot) {
        const selectedValue = document.getElementById('storefront-editor-' + slot).value;
        if (selectedValue !== '') {
          configuration.assets[slot + '_id'] = Number(selectedValue);
        }
      });
      configuration.visibility = configuration.visibility || {};
      document.querySelectorAll('.storefront-editor-visibility').forEach(function(input) {
        configuration.visibility[input.dataset.key] = input.checked;
      });
    }

    function updatePreview() {
      if (!preview.contentWindow) return;
      preview.contentWindow.postMessage({
        type: 'merchant-storefront-draft',
        configuration: configuration
      }, window.location.origin);
    }

    function request(action, payload) {
      return new Promise(function(resolve, reject) {
        mb.ajax({
          url: '?api=neighborhub&action=' + action,
          method: 'POST',
          data: JSON.stringify(Object.assign({
            merchant_id: merchantId
          }, payload || {})),
          success: resolve,
          error: reject
        });
        updatePreview();
      });
    }

    document.getElementById('storefront-editor-save').addEventListener('click', function() {
      if (pickers.some(function(picker) {
          return picker.uploading;
        })) {
        status('Wait for the image upload to finish before saving.', true);
        return;
      }
      updateConfiguration();
      status('Saving draft...');
      request('save_merchant_storefront_draft', {
        configuration: configuration
      }).then(function(response) {
        const localConfiguration = configuration;
        const assetUrls = configuration.asset_urls || {};
        const savedConfiguration = response.configuration || {};
        configuration = Object.assign({}, localConfiguration, savedConfiguration);
        configuration.assets = Object.assign({},
          localConfiguration.assets || {},
          savedConfiguration.assets || {}
        );
        configuration.asset_urls = assetUrls;
        pickers.forEach(function(picker) {
          picker.refresh();
        });
        return Promise.resolve();
      }).then(function() {
        preview.contentWindow.location.reload();
        status('Draft saved.');
      }).catch(function(error) {
        status(error.message || 'Unable to save draft.', true);
      });
    });

    document.getElementById('storefront-editor-publish').addEventListener('click', function() {
      if (pickers.some(function(picker) {
          return picker.uploading;
        })) {
        status('Wait for the image upload to finish before publishing.', true);
        return;
      }
      if (!window.confirm('Publish this storefront draft for customers?')) return;
      request('publish_merchant_storefront').then(function() {
        status('Storefront published.');
        preview.contentWindow.location.reload();
      }).catch(function(error) {
        status(error.message || 'Unable to publish storefront.', true);
      });
    });

    document.getElementById('storefront-editor-revert').addEventListener('click', function() {
      if (!window.confirm('Revert the draft to the currently published storefront?')) return;
      request('revert_merchant_storefront').then(function() {
        status('Draft reverted.');
        window.location.reload();
      }).catch(function(error) {
        status(error.message || 'Unable to revert draft.', true);
      });
    });

    document.getElementById('storefront-editor-theme').addEventListener('change', updateConfiguration);
    document.getElementById('storefront-editor-layout').addEventListener('change', function() {
      updateConfiguration();
      updatePreview();
    });
    document.getElementById('storefront-editor-logo-style').addEventListener('change', function() {
      updateConfiguration();
      updatePreview();
    });
    document.getElementById('storefront-editor-primary').addEventListener('input', updateConfiguration);
    document.getElementById('storefront-editor-logo').addEventListener('change', function() {
      updateConfiguration();
      if (!this.value) configuration.assets.logo_id = null;
      updatePreview();
    });
    document.getElementById('storefront-editor-hero').addEventListener('change', function() {
      updateConfiguration();
      if (!this.value) configuration.assets.hero_id = null;
      updatePreview();
    });
    document.querySelectorAll('[data-image-picker]').forEach(function(element) {
      pickers.push(new MerchantImagePicker(element, {
        merchantId: merchantId,
        onStatus: status,
        onUpload: function(image) {
          configuration.asset_urls[image.id] = image.image_url;
          pickers.forEach(function(picker) {
            picker.addImage(image);
          });
        }
      }));
    });
    document.querySelectorAll('.storefront-editor-visibility').forEach(function(input) {
      input.addEventListener('change', updateConfiguration);
    });
    document.getElementById('storefront-editor-add-block').addEventListener('click', function() {
      configuration.blocks = Array.isArray(configuration.blocks) ? configuration.blocks : [];
      configuration.blocks.push({
        type: document.getElementById('storefront-editor-block-type').value,
        content: {}
      });
      renderBlocks();
      updatePreview();
      const blocks = document.getElementById('storefront-editor-blocks');
      const newestBlock = blocks.lastElementChild;
      if (newestBlock) {
        newestBlock.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
        const firstInput = newestBlock.querySelector('input');
        if (firstInput) firstInput.focus();
      }
    });
    preview.addEventListener('load', updatePreview);
    document.querySelectorAll('.storefront-editor-viewport').forEach(function(button) {
      button.addEventListener('click', function() {
        document.querySelectorAll('.storefront-editor-viewport').forEach(function(item) {
          item.classList.toggle('is-active', item === button);
        });
        document.querySelector('.storefront-editor-canvas').classList.toggle('is-mobile', button.dataset.viewport === 'mobile');
      });
    });
    render();
  }());
</script>