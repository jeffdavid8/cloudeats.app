<?
if (!defined('MB_RUNNING')) exit;
/**
 * var $reorderItems array
 * var $merchant Merchant
 */
$reorderItems = $this->get('reorder_items', null);
$merchant = $this->get('merchant', null);
logger($reorderItems);
?>

<script>
  $(document).ready(function() {
    // Initialize standard storefront interactions
    window.storefront = new MerchantStorefront({
      toastIcon: 'fa-check-circle',
      toastVerb: 'basket'
    });

    <?php if (!empty($reorderItems)): ?>
      // Reorder items passed from PHP context
      var reorderItems = <?= json_encode($reorderItems) ?>;
      var merchantId = <?= intval($merchant->id) ?>;

      if (window.NHCart && Array.isArray(reorderItems) && reorderItems.length > 0) {
        reorderItems.forEach(function(item) {
          // Parse customizations JSON if present
          var customizations = null;
          if (item.meta) {
            customizations = typeof item.meta === 'string' ?
              JSON.parse(item.meta) :
              item.meta;
          }

          // Build product payload expected by ShoppingCart.addItem()
          var productData = {
            id: item.product_id,
            name: item.product_name || item.name,
            price: parseFloat(item.price_at_order || item.price),
            customizable: !!customizations,
            merchantImage: <?= json_encode($merchant->image_url) ?>,
            merchantId: merchantId,
            merchantName: <?= json_encode($merchant->business_name) ?>,
            merchantAddress: <?= json_encode($merchant->address) ?>,
            merchantLat: <?= json_encode($merchant->latitude) ?>,
            merchantLon: <?= json_encode($merchant->longitude) ?>,
            customer_notes: item.customer_notes || ''
          };

          // Build customization receipt structure if customizations exist
          var customizationReceipt = customizations ? {
            choices: customizations,
            final_price: parseFloat(item.price_at_order || item.price)
          } : null;

          // Add item to multi-tenant cart
          NHCart.addItem(
            merchantId,
            productData,
            customizationReceipt,
            parseInt(item.quantity || 1)
          );
        });

        // Open the cart side menu so the user sees their reordered items
        $('#nh-shopping-cart-sidenav').sidenav('open');

        // Toast notification
        M.toast({
          html: '<i class="fas fa-redo"></i> Previous order items loaded into your basket!',
          classes: 'notify-success notify-bottom',
          displayLength: 3500
        });
      }
    <?php endif; ?>
  });
</script>