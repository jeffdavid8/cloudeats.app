<?php
if (!defined('MB_RUNNING')) exit;

$data = json_decode($anchor->content, true);
if (is_string($data)) $data = json_decode($data, true);

$title = $data['title'] ?? 'A Shared Memory';
$body = $data['body'] ?? '';
$author = $data['author'] ?? 'Community Member';
$audioUrl = $data['audio_url'] ?? null;
?>

<div class="stitch-wrapper card story-card" 
     data-id="<?= $anchor->id ?>" 
     data-uuid="<?= $anchor->uuid ?>"
     style="border-top: 8px solid #8d6e63; background: #fdf5e6; color: #3e2723;">
     
    <div class="card-content">
        <span style="font-size: 0.7rem; text-transform: uppercase; color: #8d6e63; font-weight: bold; letter-spacing: 1px;">
            <i class="material-icons tiny left">auto_stories</i> Oral History / Story
        </span>

        <h5 style="font-family: 'Times New Roman', serif; font-weight: bold; margin: 10px 0;">
            <?= htmlspecialchars($title) ?>
        </h5>

        <p style="font-family: 'Georgia', serif; line-height: 1.5; font-size: 1.1rem; font-style: italic;">
            "<?= nl2br(htmlspecialchars($body)) ?>"
        </p>

        <?php if ($audioUrl): ?>
            <div style="margin-top: 15px; background: #d7ccc8; padding: 10px; border-radius: 20px; display: flex; align-items: center;">
                <i class="material-icons brown-text">play_circle_filled</i>
                <span style="margin-left: 10px; font-size: 0.8rem; font-weight: bold;">Listen to Interview</span>
            </div>
        <?php endif; ?>

        <div style="margin-top: 15px; font-size: 0.8rem; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 10px; text-align: right;">
            — Captured by <?= htmlspecialchars($author) ?> on <?= date('M Y', strtotime($anchor->created_at)) ?>
        </div>
    </div>

    <div class="card-action">
        <button onclick="exploreNexus(<?= $anchor->id ?>)" class="btn-flat brown-text text-darken-3">
            <i class="material-icons left">people</i> WHO IS IN THIS STORY?
        </button>
    </div>
</div>