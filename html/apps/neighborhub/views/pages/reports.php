<?php
if (!defined('MB_RUNNING')) exit;

$app = App::getInstance('neighborhub');
$reportView = $app->get('current_view', 'customer');
$userId = (int)($_SESSION['user']['id'] ?? 0);
$isAdmin = !empty($_SESSION['user']['is_admin']) || !empty($app->user->is_admin);
$merchantId = $reportView === 'merchant'
  ? (int)get_var('merchant_id', $app->get('merchant_id', $_SESSION['user']['active_merchant_id'] ?? 0))
  : 0;

if (!$userId) {
  echo '<div class="container"><div class="card-panel"><h4>Sign in to view reports</h4><a class="btn" href="/?p=login">Sign in</a></div></div>';
  return;
}

if ($reportView === 'admin' && !$isAdmin) {
  http_response_code(403);
  echo '<div class="container"><div class="card-panel red lighten-4"><h4>Access denied</h4><p>Administrative access is required to view NeighborHub reports.</p></div></div>';
  return;
}

if ($reportView === 'merchant') {
  $app->includeClass('NeighborhubReporting');
  try {
    (new NeighborhubReporting($app->db))->assertMerchantAnalyticsAccess($userId, $merchantId, $isAdmin);
  } catch (DomainException | InvalidArgumentException $e) {
    http_response_code(403);
    echo '<div class="container"><div class="card-panel red lighten-4"><h4>Access denied</h4><p>'
      . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
      . '</p></div></div>';
    return;
  }
}

$roleTitles = [
  'admin' => 'NeighborHub Reports & Analytics',
  'merchant' => 'Merchant Reports',
  'courier' => 'Courier Delivery Reports',
  'customer' => 'Your Order Reports',
];
$reportTitle = $roleTitles[$reportView] ?? 'NeighborHub Reports';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
  <div class="row valign-wrapper" style="flex-wrap: wrap;">
    <div class="col s12 m8">
      <h4><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></h4>
      <p class="grey-text">Filter your authorized order history and download a CSV or JSON report.</p>
      <?php if (in_array($reportView, ['admin', 'merchant'], true)): ?>
        <p class="grey-text">Payout values are estimates based on amounts recorded at checkout and may differ from settlement adjustments.</p>
      <?php endif; ?>
    </div>
    <div class="col s12 m4 right-align">
      <a class="btn-flat" href="/?app=neighborhub&p=dashboard&view=<?= rawurlencode($reportView) ?>">Back to dashboard</a>
    </div>
  </div>

  <div class="card">
    <div class="card-content">
      <span class="card-title">Order reports</span>
      <div class="row">
        <div class="input-field col s12 m3">
          <input id="report-start-date" type="date">
          <label class="active" for="report-start-date">Start date</label>
        </div>
        <div class="input-field col s12 m3">
          <input id="report-end-date" type="date">
          <label class="active" for="report-end-date">End date</label>
        </div>
        <div class="input-field col s12 m3">
          <select id="report-state">
            <option value="">All statuses</option>
            <option value="PENDING_CONFIRMATION">Pending confirmation</option>
            <option value="CONFIRMED">Confirmed</option>
            <option value="READY_FOR_PICKUP">Ready for pickup</option>
            <option value="IN_TRANSIT">In transit</option>
            <option value="DELIVERED">Delivered</option>
            <option value="CANCELLED">Cancelled</option>
            <option value="FAILED">Failed</option>
          </select>
          <label for="report-state">Order status</label>
        </div>
        <div class="input-field col s12 m3">
          <select id="report-type">
            <option value="orders_summary">Orders summary</option>
            <option value="line_items">Line items</option>
            <?php if (in_array($reportView, ['admin', 'merchant'], true)): ?>
              <option value="payouts">Fees & estimated payouts</option>
            <?php endif; ?>
          </select>
          <label for="report-type">Report type</label>
        </div>
        <div class="col s12">
          <button type="button" id="load-order-report" class="btn teal">View report</button>
          <button type="button" class="btn-flat export-report" data-format="csv">Download CSV</button>
          <button type="button" class="btn-flat export-report" data-format="json">Download JSON</button>
          <span id="report-status" role="status" aria-live="polite" class="grey-text"></span>
        </div>
      </div>
      <div class="">
        <table class="striped">
          <thead id="order-report-head"></thead>
          <tbody id="order-report-body">
            <tr><td>Choose a date range, then load the report.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="valign-wrapper" style="justify-content: space-between; margin-top: 1rem;">
        <button type="button" id="report-previous" class="btn-flat" disabled>Previous</button>
        <span id="report-page-status" class="grey-text"></span>
        <button type="button" id="report-next" class="btn-flat" disabled>Next</button>
      </div>
    </div>
  </div>

  <?php if (in_array($reportView, ['admin', 'merchant'], true)): ?>
    <div class="card">
      <div class="card-content">
        <span class="card-title">Business usage analytics</span>
        <p class="grey-text">
          <?php if ($reportView === 'merchant'): ?>
            Usage associated with this merchant from the most recent 90 days. Raw search text and personal contact/location data are not collected.
          <?php else: ?>
            Business events from the most recent 90 days. Raw search text and personal contact/location data are not collected.
          <?php endif; ?>
        </p>
        <div class="row">
          <div class="col s12 m6">
            <canvas id="usage-chart" aria-label="NeighborHub business usage event trends"></canvas>
          </div>
          <div class="col s12 m6">
            <h6>Event totals for selected dates</h6>
            <ul id="usage-totals" class="collection"></ul>
          </div>
          <div class="col s12 m6">
            <h6>Event-count funnel</h6>
            <p class="grey-text">Counts are events, not unique visitors.</p>
            <ul id="usage-funnel" class="collection"></ul>
          </div>
          <div class="col s12 m6">
            <h6>Order state transitions</h6>
            <ul id="usage-outcomes" class="collection"></ul>
          </div>
          <div class="col s12">
            <button type="button" id="load-analytics" class="btn teal">Refresh analytics</button>
            <button type="button" class="btn-flat export-analytics" data-report-type="analytics_summary" data-format="csv">Download trend CSV</button>
            <button type="button" class="btn-flat export-analytics" data-report-type="analytics_summary" data-format="json">Download analytics JSON</button>
            <button type="button" class="btn-flat export-analytics" data-report-type="analytics_events" data-format="csv">Download event CSV</button>
            <button type="button" class="btn-flat export-analytics" data-report-type="analytics_events" data-format="json">Download event JSON</button>
            <span id="analytics-status" role="status" aria-live="polite" class="grey-text"></span>
          </div>
          <div class="col s12">
            <h6>Recent events</h6>
            <div class="responsive-table">
              <table class="striped">
                <thead><tr><th>Time</th><th>Event</th><th>Context</th></tr></thead>
                <tbody id="usage-events"><tr><td colspan="3">Loading analytics…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (in_array($reportView, ['admin', 'merchant'], true)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script>
(() => {
  const reportView = <?= json_encode($reportView, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const merchantId = <?= $merchantId ?>;
  const pageSize = 25;
  let offset = 0;
  let orderChart = null;
  const today = new Date();
  const startDate = new Date();
  startDate.setDate(today.getDate() - 29);
  document.getElementById("report-start-date").value = startDate.toISOString().slice(0, 10);
  document.getElementById("report-end-date").value = today.toISOString().slice(0, 10);

  const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
  })[character]);

  const reportFilters = () => ({
    view: reportView,
    merchant_id: merchantId || "",
    start_date: document.getElementById("report-start-date").value,
    end_date: document.getElementById("report-end-date").value,
    state: document.getElementById("report-state").value,
    report_type: document.getElementById("report-type").value,
  });

  const setStatus = (id, text) => {
    document.getElementById(id).textContent = text;
  };

  function apiRequest(data) {
    if (!window.mb || typeof window.mb.ajax !== "function") {
      return Promise.reject(new Error("The MediaBrain API client is unavailable."));
    }
    return new Promise((resolve, reject) => {
      window.mb.ajax({
        url: "/?api=neighborhub",
        type: "GET",
        data,
        dataType: "json",
        success: resolve,
        error: (xhr) => {
          const payload = xhr.responseJSON;
          reject(new Error(payload?.error || payload?.message || "The report request failed."));
        },
      });
    });
  }

  async function loadOrders() {
    const params = {
      action: "get_report_data",
      ...reportFilters(),
      limit: pageSize,
      offset,
    };
    setStatus("report-status", "Loading…");
    try {
      const payload = await apiRequest(params);
      if (!payload.success) throw new Error(payload.error || "Unable to load report.");
      const report = payload.data;
      const rows = report.records;
      const headers = rows.length ? Object.keys(rows[0]) : [];
      document.getElementById("order-report-head").innerHTML = headers.length
        ? `<tr>${headers.map((name) => `<th>${escapeHtml(name.replaceAll("_", " "))}</th>`).join("")}</tr>`
        : "";
      document.getElementById("order-report-body").innerHTML = rows.length
        ? rows.map((row) => `<tr>${headers.map((name) => `<td>${escapeHtml(row[name])}</td>`).join("")}</tr>`).join("")
        : `<tr><td colspan="8">No matching records found.</td></tr>`;
      const first = report.total ? offset + 1 : 0;
      const last = Math.min(offset + rows.length, report.total);
      document.getElementById("report-page-status").textContent =
        `${first}–${last} of ${report.total} records`;
      document.getElementById("report-previous").disabled = offset === 0;
      document.getElementById("report-next").disabled = offset + pageSize >= report.total;
      setStatus("report-status", "");
    } catch (error) {
      setStatus("report-status", error.message);
      document.getElementById("order-report-body").innerHTML =
        `<tr><td colspan="8">${escapeHtml(error.message)}</td></tr>`;
    }
  }

  function csrfHeaders() {
    const token = (window.mb && window.mb.csrf_token) ||
      document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) throw new Error("Export could not be authenticated: CSRF token unavailable.");
    return {
      Authorization: "Bearer " + token,
      "X-CSRF-TOKEN": token,
      "X-Requested-With": "XMLHttpRequest",
    };
  }

  async function download(reportType, format) {
    const filters = reportFilters();
    filters.report_type = reportType;
    filters.format = format;
    const params = new URLSearchParams({ api: "neighborhub", action: "export_data", ...filters });
    const response = await fetch(`/?${params.toString()}`, {
      credentials: "same-origin",
      headers: csrfHeaders(),
    });
    const blob = await response.blob();
    if (!response.ok) {
      let message = `Export failed (${response.status}).`;
      try {
        const payload = JSON.parse(await blob.text());
        message = payload.error || message;
      } catch (error) {
        console.error("Could not parse export error response.", error);
      }
      throw new Error(message);
    }
    const disposition = response.headers.get("Content-Disposition") || "";
    const filename = disposition.match(/filename="([^"]+)"/)?.[1] ||
      `neighborhub_${reportType}.${format}`;
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  document.getElementById("load-order-report").addEventListener("click", () => {
    offset = 0;
    loadOrders();
  });
  document.getElementById("report-previous").addEventListener("click", () => {
    offset = Math.max(0, offset - pageSize);
    loadOrders();
  });
  document.getElementById("report-next").addEventListener("click", () => {
    offset += pageSize;
    loadOrders();
  });
  document.querySelectorAll(".export-report").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        await download(reportFilters().report_type, button.dataset.format);
      } catch (error) {
        setStatus("report-status", error.message);
      }
    });
  });

  <?php if (in_array($reportView, ['admin', 'merchant'], true)): ?>
  async function loadAnalytics() {
    const params = {
      action: "get_report_data",
      ...reportFilters(),
      report_type: "analytics_summary",
    };
    setStatus("analytics-status", "Loading…");
    try {
      const payload = await apiRequest(params);
      if (!payload.success) throw new Error(payload.error || "Unable to load analytics.");
      const analytics = payload.data;
      const types = [...new Set(analytics.trend.map((row) => row.event_type))];
      const labels = [...new Set(analytics.trend.map((row) => row.event_date))];
      const colors = ["#00897b", "#3949ab", "#fb8c00", "#8e24aa", "#039be5", "#e53935", "#7cb342", "#6d4c41"];
      const datasets = types.map((type, index) => ({
        label: type.replaceAll("_", " "),
        data: labels.map((date) => {
          const row = analytics.trend.find((entry) => entry.event_date === date && entry.event_type === type);
          return row ? Number(row.event_count) : 0;
        }),
        borderColor: colors[index % colors.length],
        backgroundColor: colors[index % colors.length],
        tension: 0.25,
      }));
      if (orderChart) orderChart.destroy();
      orderChart = new Chart(document.getElementById("usage-chart"), {
        type: "line",
        data: { labels, datasets },
        options: { responsive: true, interaction: { intersect: false, mode: "index" } },
      });
      const totals = Object.entries(analytics.totals);
      document.getElementById("usage-totals").innerHTML = totals.length
        ? totals.map(([name, count]) => `<li class="collection-item">${escapeHtml(name.replaceAll("_", " "))}<span class="secondary-content">${escapeHtml(count)}</span></li>`).join("")
        : '<li class="collection-item">No events recorded for this period.</li>';
      document.getElementById("usage-funnel").innerHTML = Object.entries(analytics.funnel).length
        ? Object.entries(analytics.funnel).map(([name, count]) => `<li class="collection-item">${escapeHtml(name.replaceAll("_", " "))}<span class="secondary-content">${escapeHtml(count)}</span></li>`).join("")
        : '<li class="collection-item">No funnel events recorded for this period.</li>';
      document.getElementById("usage-outcomes").innerHTML = analytics.outcomes.length
        ? analytics.outcomes.map((outcome) => `<li class="collection-item">${escapeHtml(outcome.order_state || "Unknown")}<span class="secondary-content">${escapeHtml(outcome.event_count)}</span></li>`).join("")
        : '<li class="collection-item">No order transitions recorded for this period.</li>';
      document.getElementById("usage-events").innerHTML = analytics.events.length
        ? analytics.events.map((event) => `<tr><td>${escapeHtml(event.created_at)}</td><td>${escapeHtml(event.event_type)}</td><td>${escapeHtml(event.actor_role)}</td></tr>`).join("")
        : '<tr><td colspan="3">No events recorded for this period.</td></tr>';
      setStatus("analytics-status", "");
    } catch (error) {
      setStatus("analytics-status", error.message);
    }
  }
  document.getElementById("load-analytics").addEventListener("click", loadAnalytics);
  document.querySelectorAll(".export-analytics").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        await download(button.dataset.reportType, button.dataset.format);
      } catch (error) {
        setStatus("analytics-status", error.message);
      }
    });
  });
  loadAnalytics();
  <?php endif; ?>
  loadOrders();
})();
</script>
