<?php
if (!defined('MB_RUNNING')) exit;

?>
<ul class="collapsible collapsible-accordion">
  <li>
    <a class="collapsible-header waves-effect waves-light">Database Ops<i class="fas fa-rocket"></i></a>
    <div class="collapsible-body">
      <ul>
        <li>
          <a target="_blank" href="?app=admin&p=init_db">Initialize Database</a>
        </li>
        <li>
          <a target="_blank" href="?app=admin&p=init_users">Initialize Users</a>
        </li>
        <li>
          <a href="?app=admin&p=download_db&type=json">Download JSON DB</a>
        </li>
      </ul>
    </div>
  </li>
</ul>