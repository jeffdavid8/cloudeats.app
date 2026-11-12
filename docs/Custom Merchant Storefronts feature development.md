
## Storefront Builder Plan

### Phase 1: Shared storefront architecture

Create a shared storefront shell (html\apps\neighborhub\views\pages\customer\merchant_products.php) replacing duplicated `merchant_*` templates:

```text
html/apps/neighborhub/views/storefront/
  shell.php
  components/
    header.php
    hero.php
    operational_status.php
    search.php
    category_navigation.php
    product_grid.php
    merchant_contact.php
    cart.php
  themes/
    default.php
```

Existing storefronts will migrate gradually, starting with `merchant_8/merchant_products.php`.

The shared storefront entry point is now
`html/apps/neighborhub/views/storefront/shell.php`. Merchants without a
merchant-specific override render through the default theme at
`html/apps/neighborhub/views/storefront/themes/default.php`; existing
`merchant_*` overrides remain compatible during migration.

The default theme now delegates the merchant contact panel, category
navigation, and product catalog rendering to reusable components under
`html/apps/neighborhub/views/storefront/components/`.
The shared custom-order builder modal is also rendered from that component
directory so theme templates retain the existing JavaScript hooks.
Category dropdown navigation is rendered from the same component directory,
preserving the existing `category-menu-item` selectors and anchors.
The default theme also includes a reusable merchant identity hero component,
providing a deliberate fallback presentation for merchants without a custom
theme override.
Promotional blocks are rendered server-side by
`components/promotional_blocks.php` using the validated published
configuration.
The `pizza-king`, `bakery`, and `diner` themes are now selectable shared-theme
wrappers around the default components, allowing merchants to use distinct
visual presets without duplicating catalog behavior.
Shared themes define separate day and night palettes. The configured
storefront background is used in day mode; night mode uses the selected
theme's dark background, including in the draft preview. Legacy
merchant-specific CSS loads only when its matching customer storefront
override is selected.
When a merchant has a legacy `merchant_*` storefront override, the `default`
theme continues to render that override for compatibility. Selecting a named
shared theme such as `bakery`, `diner`, or `pizza-king` routes the storefront
through the shared theme renderer instead.
Theme assets use the active merchant's configured image rather than
merchant-specific hardcoded URLs.
The shared hero renders the saved centered, full, split, or minimal layout on both
draft and published storefronts. The editor previews layout changes immediately;
the full hero spans the viewport and is at least 75vh tall, using the selected
hero image or the merchant profile image when none is selected. The split
hero background moves subtly with scrolling, with a static background for
visitors who prefer reduced motion. The split layout uses a smaller logo on
narrow screens. Legacy merchant-specific overrides
remain independent of the shared hero layouts.
The storefront logo style can retain the existing default, preserve the
original image and transparency without a frame, or use a cropped circular
or rectangular frame. Logo style is saved with the storefront configuration
and updates immediately in the visual editor.
The editor keeps its draft actions and status together at the bottom of the
scrolling settings panel; on smaller screens they remain visible while scrolling
through the settings and leave view with the panel.
The visual editor and merchant settings share an image picker with gallery
thumbnails, image preview, and drag-and-drop or file upload. Uploads add an
image to the merchant gallery immediately; choosing it for a storefront or
profile takes effect only after saving the relevant form. Changing the profile
image also changes the fallback used by storefronts without a selected image.

When a merchant image is present, verified staff for that merchant and
platform admins can open the shared merchant management menu by clicking the
merchant image on the right side of the header, immediately before the basket
when shown, including in customer storefront view. The merchant menu opens from
the right. Other visitors retain the storefront image's existing behavior. Management pages
and APIs continue to enforce their own access checks.

### Phase 2: Storefront data model

Create a dedicated `neighborhub_merchant_storefronts` table rather than storing
the builder configuration in `neighborhub_merchants.meta`. The merchant table
will remain focused on merchant profile data, while this table owns storefront
drafts, published configuration, and publishing metadata.

Recommended schema:

```sql
CREATE TABLE neighborhub_merchant_storefronts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id INT NOT NULL UNIQUE,
  draft_config JSON NOT NULL,
  published_config JSON NOT NULL,
  draft_updated_at DATETIME NULL,
  published_at DATETIME NULL,
  updated_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

The initial row should be created lazily when a merchant first opens the
Storefront Designer, or during merchant provisioning. Both configurations
should start from the validated default storefront configuration.

Add the table through a new versioned Yii-style Neighborhub migration under
`html/apps/neighborhub/migrations/`, following the repository's migration
contract:

- Return a `tables` list including `neighborhub_merchant_storefronts`
- Define an `up(PDO $db)` callback with the table creation SQL
- Define a `verify(PDO $db)` callback using `DatabaseMigrationRunner::assertSchema()`
- Include the merchant and user foreign-key relationships in the migration
- Make the migration safe to apply to existing environments without modifying
  the historical baseline migration

The legacy `neighborhub_install_db()` installer and the baseline migration
should not be rewritten for this feature. The migration runner is the source
of truth for deploying this new table to existing databases.

The model layer should provide methods to:

- Load the draft configuration
- Load the published configuration
- Create or update a draft
- Validate and publish a draft atomically
- Revert the draft to the currently published configuration
- Return the default configuration when no row exists

Create a dedicated `MerchantStorefront` model at
`html/apps/neighborhub/includes/models/merchantstorefront.model.php`. It should
follow the existing Neighborhub model conventions and be loaded through
`$app->includeModel('merchantstorefront')`. The model should:

- Define the default configuration and supported theme/block allowlists
- Load storefront records by merchant ID
- Return draft and published configurations independently
- Create the initial record when a merchant has no storefront configuration
- Validate and normalize colors, layouts, visibility flags, and predefined blocks
- Validate that referenced products belong to the merchant
- Validate that referenced gallery assets belong to the merchant
- Save drafts for authorized merchant owners/staff
- Publish drafts atomically for authorized merchant owners/staff
- Restrict custom CSS fields to platform administrators
- Revert drafts to the published configuration
- Return safe defaults when no configuration has been saved

The model must use prepared statements, explicit transaction boundaries for
publish/revert operations, and clear success/error result arrays consistent
with the existing `Merchant::update()` pattern. It should not place storefront
configuration in `Merchant::$meta`; `meta` remains available for unrelated
merchant metadata.

### Phase 3: Storefront configuration

Define and validate the JSON `storefront_config` structure in the dedicated
storefront table:

- Theme and layout
- Logo and hero image selections
- Colors and typography
- Hero layout
- Visible/hidden sections
- Promotional content blocks
- Operational-status presentation
- Custom CSS, restricted to administrators

Predefined block types should be validated against an allowlist:

- `announcement`
- `featured_product`
- `image_text`
- `call_to_action`
- `store_information`

Block payloads must be normalized before storage, with product IDs checked
against the merchant and gallery asset IDs checked for ownership.

### Phase 4: Merchant admin interface

Add a **Storefront Designer** section to merchant management with:

- Theme preset selector
- Logo and hero image picker using existing gallery assets
- Color controls
- Typography controls
- Hero layout choices
- Section visibility toggles
- Promotional block editor
- Live preview panel
- Save Draft and Publish buttons
- Reset to default option

The implementation will evolve from the initial tab into a dedicated in-place
editor route at `view=merchant&p=storefront_editor`. The editor keeps the
existing tab as a fallback, renders the authorized draft storefront beside a
properties panel, and uses the existing draft/save/publish/revert APIs. Public
storefronts continue to load published configuration only.

The editor should use safe controls rather than arbitrary merchant-entered HTML or CSS.

### Phase 5: Preview and publishing

Implement:

- Draft configuration
- Published configuration
- Preview URL or preview mode
- Publish confirmation
- Revert to previous published version
- Validation before publishing

Customers should only receive the published configuration.
Preview requests may read the draft configuration, but must require an
authorized merchant owner/staff member or platform administrator and must not
change the customer-facing published storefront.

### Phase 6: Shared frontend behavior

Move common functionality into shared JavaScript/CSS:

- Operational status and closing/opening warnings
- Header/logo scroll behavior
- Cart interactions
- Product search
- Accessibility states
- Responsive layout
- Theme variables

This avoids repeating fixes across every merchant storefront.

### Phase 7: Migration

Migrate incrementally:

1. Add and test the `neighborhub_merchant_storefronts` table and migration.
2. Build the configuration model and default configuration.
3. Build the shared shell.
4. Reproduce `merchant_8` visually.
5. Compare behavior and screenshots.
6. Migrate remaining merchant themes. Merchant 4 already uses the shared
   fallback; merchant-specific branded overrides remain until visual parity
   is validated.
7. Preserve compatibility with existing URLs and merchant data.
8. Retire duplicated templates after validation.

### Phase 8: Testing

Add coverage for:

- Table creation, foreign keys, uniqueness, and cascade behavior
- Default configuration creation
- Draft/published configuration handling
- Atomic publish and revert behavior
- Permission enforcement
- Invalid configuration rejection
- Block ownership validation
- Theme rendering
- Store hours/status integration
- Preview isolation
- Mobile/desktop layouts
- Existing cart and checkout behavior

The header basket shows the active merchant's item subtotal while it contains
items. It uses the same cart calculation as the checkout drawer; taxes, tips,
delivery, and processing fees are not included. The amount updates with cart
changes and is hidden when the active merchant's basket is empty.

The merchant header search opens below the header and filters menu items as
the customer types. The search stays open when focus moves elsewhere; Clear
restores all items, while Cancel or Escape also closes the search. Where a
storefront has an additional menu search field, both fields share the filter.
For an active search, the header shows the number of matching items out of the
menu total, including zero matches.
While search is open, the floating category button moves below the header if
needed and returns to its normal position when search closes.

## Important security boundary

Because custom CSS is administrator-only, merchant users should configure appearance through validated fields and predefined options. Custom CSS must be sanitized, scoped to the storefront, and never allowed to affect the broader application.

The only remaining major product decision is whether promotional blocks should be simple predefined block types—recommended—or fully freeform content. I recommend predefined blocks such as:

- Announcement
- Featured product
- Image and text
- Call to action
- Store information

This keeps the builder powerful while maintaining consistency, accessibility, and security.
