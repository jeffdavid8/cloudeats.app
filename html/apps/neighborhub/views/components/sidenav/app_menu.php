<?
if (!defined('MB_RUNNING')) exit;
?>

<li>
  <a href="/"><i class="material-icons">store</i>Home</a>
</li>

<?
if ($this->user->is_admin):
  $merchants = Merchant::getAllMerchants();
?>
  <li style="margin-left: 10px;">
    <select id="merchant-select" class="browser-default" onchange="window.location.href = updateQueryStringParameter('merchant_id', this.value);">
      <option value="">Select Merchant</option>
      <? foreach ($merchants as $merchant): ?>
        <option value="<?= $merchant->id ?>" <?= (get_var('merchant_id') == $merchant->id) ? 'selected' : '' ?>><?= $merchant->id . ' - ' . htmlspecialchars($merchant->business_name) ?></option>
      <? endforeach; ?>
    </select>
  </li>

  <script>
    function updateQueryStringParameter(key, value) {
      // return the current URL with only "&view&merchant_id&p" param
      const url = new URL(window.location.href);
      url.searchParams.set(key, value);
      // Remove all other query params except "view", "merchant_id", and "p"
      const allowedParams = ['app', 'view', 'merchant_id', 'p'];
      for (const param of url.searchParams.keys()) {
        if (!allowedParams.includes(param)) {
          url.searchParams.delete(param);
        }
      }
      return url.toString();
    }
  </script>
<?
endif;
if (get_var('view') === 'merchant') {
 render('components/sidenav/merchant_menu.php');
}
?>
