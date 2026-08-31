<?php
$mb = App::getInstance();
?>

<div data-component="runtime-errors" 
     data-errors="<?= htmlspecialchars(json_encode($mb->errors, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)); ?>"
     data-is-development="<?= json_encode(is_development()); ?>">
</div>
