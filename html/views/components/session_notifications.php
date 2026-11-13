<?php
if (!defined('MB_RUNNING')) exit;

$notifications = $_SESSION['notifications'] ?? [];
if (!is_array($notifications)) {
  throw new UnexpectedValueException('The session notification queue is invalid.');
}
if (isset($_SESSION['notification'])) {
  array_unshift($notifications, $_SESSION['notification']);
}
foreach ($notifications as $notification) {
  if (!is_array($notification) || !isset($notification['type'], $notification['message'])
      || !is_string($notification['type']) || !in_array($notification['type'], ['success', 'info', 'warning', 'error'], true)
      || !is_string($notification['message']) || trim($notification['message']) === '') {
    throw new UnexpectedValueException('A session notification is invalid.');
  }
}
unset($_SESSION['notifications'], $_SESSION['notification']);
?>
<?php if ($notifications): ?>
<section class="session-notifications" aria-label="Notifications">
  <?php foreach ($notifications as $notification): ?>
    <div class="session-notice session-notice-<?= htmlspecialchars($notification['type'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $notification['type'] === 'error' ? 'alert' : 'status' ?>">
      <span class="session-notice-icon" aria-hidden="true"><?= ['success' => '&#10003;', 'info' => '&#8505;', 'warning' => '!', 'error' => '&#10005;'][$notification['type']] ?></span>
      <p><?= htmlspecialchars($notification['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
      <button type="button" class="session-notice-dismiss" aria-label="Dismiss notification">&times;</button>
    </div>
  <?php endforeach; ?>
</section>
<script>
document.querySelectorAll('.session-notice-dismiss').forEach(function(button) {
  button.addEventListener('click', function() {
    const notice = button.closest('.session-notice');
    const container = notice.parentElement;
    notice.remove();
    if (!container.querySelector('.session-notice')) container.remove();
  });
});
</script>
<?php endif; ?>
