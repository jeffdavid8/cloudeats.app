
<?
if (!defined('MB_RUNNING')) exit;
/**
 * Shopping Cart Sidenav
 * @var Object $customer
 * @var String $classList
 */
$merchant = $this->get('merchant', false);
?>
<script>
  nh.merchant = <?= json_encode($merchant) ?>;
  console.log('nh.merchant', nh.merchant);
  const NHCart = new ShoppingCart(nh.merchant);
</script>