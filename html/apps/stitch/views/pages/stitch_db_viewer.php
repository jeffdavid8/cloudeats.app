<div class="container-fluid" style="padding: 20px; background: #000; min-height: 100vh; color: #0f0; font-family: monospace;">
  <h2 style="border-bottom: 2px solid #0f0; padding-bottom: 10px;">🛰️ STITCH_OBSERVER_DECK</h2>

  <div class="row">
    <?php foreach ($vitals as $label => $val): ?>
      <div class="col s4">
        <div class="card-panel grey darken-4" style="border: 1px solid #0f0;">
          <span class="white-text"><?= strtoupper($label) ?></span>
          <h3 class="green-text"><?= number_format($val) ?></h3>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card grey darken-4" style="border: 1px solid #333;">
    <div class="card-content">
      <span class="card-title green-text">LATEST_MEMORY_DUMP</span>
      <table class="highlight grey-text text-lighten-1">
        <thead>
          <tr style="color: #0f0;">
            <th>ID</th>
            <th>TYPE</th>
            <th>CONTENT_SNIPPET</th>
            <th>NEXUS_LINKS</th>
            <th>TIMESTAMP</th>
          </tr>
        </thead>
        <tbody id="viewer-tbody">
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Load the raw rows
    $.get('?api=stitch&action=list&type=data&limit=50&csrf_token='+mb.csrf_token, function(res) {
      if (res.status !== 'success') {
        M.toast({
          html: 'ERROR LOADING DATA',
          classes: 'red'
        });
        return;
      } else if (res.data.anchors.length) {
        M.toast({
          html: 'DATA LOADED',
          classes: 'green'
        });
        // Map the JSON to table rows here
        const tbody = $('#viewer-tbody');
        res.data.anchors.forEach(function(item) {
          const row = `<tr>
                  <td>${item.id}</td>
                  <td>${item.content_type}</td>
                  <td>${item.content_snippet}</td>
                  <td>${item.nexus_links}</td>
                  <td>${item.created_at}</td>
              </tr>`;
          tbody.append(row);
          return;
        });
      } else {
        console.log('No data found');
        M.toast({
          html: 'NO DATA FOUND',
          classes: 'orange'
        });
      }
    });
  });
</script>