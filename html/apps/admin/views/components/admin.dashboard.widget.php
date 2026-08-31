<?

?>
<div class="widget-item" style="margin: 15px 0;">
  <div class="card">
    <?
    echo (!empty($widget['title'])) ? '<div class="widget-title">' . $widget['icon'] . ' ' . htmlspecialchars($widget['title']) . '</div>' : '';
    ?>
    <div class="card-content">
      <?
      // Call the widget's content callback
      if (isset($widget['content_callback']) && function_exists($widget['content_callback'])) {
        try {
          echo $widget['content_callback']();
        } catch (Exception $e) {
          echo '<div class="card red lighten-4"><div class="card-content">';
          echo '<span class="card-title">Widget Error</span>';
          echo '<p>Error rendering ' . htmlspecialchars($widget['title']) . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
          echo '</div></div>';
        }
      } else {
        echo '<div class="card orange lighten-4"><div class="card-content">';
        echo '<span class="card-title">' . htmlspecialchars($widget['title']) . '</span>';
        echo '<p>Widget content callback "' . htmlspecialchars($widget['content_callback']) . '" not found.</p>';
        echo '</div></div>';
      }
      ?>
    </div>
  </div>
</div>