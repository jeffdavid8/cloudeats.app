<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Ancestry Card: The Heirloom View
 */
$data = json_decode($anchor->content, true);
if (is_string($data)) $data = json_decode($data, true);

$name = $data['body'] ?? 'Unknown Ancestor';
$events = $data['events'] ?? [];
$location = $data['location_context'] ?? 'Unknown Location';

// Extract meaningful dates
$birth = $events['BIRT']['date'] ?? $events['birth']['date'] ?? null;
$death = $events['DEAT']['date'] ?? $events['death']['date'] ?? null;
$lifespan = ($birth || $death) ? "($birth — $death)" : "";
?>

<div class="stitch-wrapper card ancestry-card"
    data-id="<?= $anchor->id ?>"
    data-uuid="<?= $anchor->uuid ?>"
    style="border-left: 5px solid #795548; background: #2d2d2d;">

    <div class="card-content white-text">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #bcaaa4; letter-spacing: 1.5px;">
                <i class="material-icons tiny left">account_tree</i> Ancestor Record
            </span>
            <i class="material-icons grey-text">history_edu</i>
        </div>

        <h5 style="margin: 10px 0 5px 0; font-family: 'Georgia', serif; font-weight: bold;">
            <?= htmlspecialchars($name) ?>
        </h5>
        <h6 style="margin: 0; color: #d7ccc8; font-size: 0.9rem; font-style: italic;">
            <?= htmlspecialchars($lifespan) ?>
        </h6>

        <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 4px;">
            <div style="display: flex; align-items: center; margin-bottom: 5px;">
                <i class="material-icons tiny amber-text">place</i>
                <span style="font-size: 0.8rem; margin-left: 5px;"><?= htmlspecialchars($location) ?></span>
            </div>
            <?php if (!empty($events)): ?>
                <div class="events-mini-list" style="font-size: 0.75rem; opacity: 0.8;">
                    <?php
                    $i = 0;
                    foreach ($events as $type => $e):
                        if ($i++ > 2) break;
                        // Check both DATE and date keys
                        $displayDate = $e['DATE'] ?? $e['date'] ?? 'n/a';
                    ?>
                        <div style="margin-top: 2px;">• <?= strtoupper($type) ?>: <?= htmlspecialchars($displayDate) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-action" style="background: rgba(0,0,0,0.1);">
        <button onclick="viewFullTree(<?= $anchor->id ?>)" class="btn-flat brown-text text-lighten-3">
            <i class="material-icons left">share</i> TREE
        </button>
        <button onclick="vouchForStitch(<?= $anchor->id ?>)" class="btn-flat green-text">
            <i class="material-icons left">verified_user</i> VOUCH
        </button>
    </div>
</div>