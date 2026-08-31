<?
if (!defined('MB_RUNNING')) exit;

use Ramsey\Uuid\Uuid;

/***
 * Stitch Card Component
 *  @var string $class
 *  @var object $anchor
 */
//error_log(print_r($anchor->nexus_list, true));
$displayBody = $anchor->content;
$displayLat = $anchor->lat;
$displayLng = $anchor->lng;
$displayContext = '';

// 🕵️ LAYER 1: Is the content a JSON object?
$content = $anchor->content;
$meta = $content['meta'];
$content['nexus_ids'] = (is_string($content['nexus_ids'])) ? explode(',', $content['nexus_ids']) : $content['nexus_ids'];
$content['nexus_ids'] = (empty($content['nexus_ids'])) ? [] : $content['nexus_ids'];


if (is_array($content)) {
  // 💎 Handle the Sentinel's specific Packet structure
  // We look for 'observation' (AI) or 'body' (Manual)
  $displayBody = Stitch::format_urls_with_ellipsis($content['body']);
  $displayLat  = $content['lat'] ?? $anchor->lat;
  $displayLng  = $content['lng'] ?? $anchor->lng;
  $displayContext = $content['context'] ?? $content['location_context'] ?? '';
}
?>

<div class="stitch-wrapper card <?= $class ?> <?= $anchor->content_type ?>"
  style="margin-bottom: 1.5rem;"
  data-timestamp="<?= strtotime($anchor->created_at) ?>"
  data-projected-to="<?= strtotime($anchor->projected_to) ?>"
  data-id="<?= $anchor->id ?>"
  data-parent-id="<?= $anchor->parent_id ?? '' ?>"
  data-lat="<?= $displayLat ?>"
  data-lng="<?= $displayLng ?>">

  <div class="card-content">

    <span class="badge blue darken-4 white-text" style="position: absolute; right: 25px; top: 5px;">
      OBSERVATION: <?= htmlspecialchars($anchor->content_type ?? 'unknown') ?>
    </span>

    <p style="font-size: 1.3rem; line-height: 1.6; margin-top: 0.5rem;">
      <?= $displayBody ?>
    </p>

    <? if ($meta):
      $domain = parse_url($meta['og:url'], PHP_URL_HOST);
      switch ($meta['og:type']) {
        case 'website':
          ?>
          <a class="meta-data" href="<?= $meta['og:url'] ?>" target="_blank" style="">
            <div class="card vertical">
              <div class="card-image">
                <img class="responsive-img" src="<?= $meta['og:image'] ?>" />
              </div>
              <div class="card-content">  
                <p class="card-sitename" style="font-size: 0.8rem;"><?= $domain; ?></p>
                <p class="card-title" style="font-size: 1.2rem;"><?= htmlspecialchars($meta['og:title'])  ?></p>
                <p class="card-description" style=""><?= htmlspecialchars($meta['og:description'])  ?></p>
              </div>
            </div>
          <?
          break;
        case 'article':
          ?>
          <a class="meta-data" href="<?= $meta['og:url'] ?>" target="_blank" style="">
            <div class="row card">
              <div class="col s12 m6 card-image">
                <img class="responsive-img" src="<?= $meta['og:image'] ?>">
              </div>
              <div class="col s12 m6 card-stacked">
                <div class="card-content">
                  <p class="card-sitename grey-text" style="font-size: 0.8rem;"><?= $domain; ?></p>
                  <span class="card-title"><?= htmlspecialchars($meta['title'])  ?></span>
                  <p><?= htmlspecialchars($meta['description']) ?></p>
                </div>
              </div>
            </div>
          </a>
          <? break;
        case 'video.other':
          // if it is a youtube video, then embed the video
          if (strpos($meta['og:video:url'], 'youtube.com')) {
            $urlParts = parse_url($meta['og:url'] ?? $meta['og:video:url']);
            parse_str($urlParts['query'] ?? '', $query);

            $videoId = $query['v'] ?? '';
            $listId  = $query['list'] ?? '';
            $indexId = $query['index'] ?? '';

            // 🏗️ Build the "Lush" Embed URL
            $listParam  = !empty($listId) ? '&list=' . $listId : '';
            $indexParam = !empty($indexId) ? '&index=' . $indexId : '';

            $embedUrl = 'https://www.youtube.com/embed/' . $videoId . '?rel=0&loop=1&enablejsapi=1';
            $width = $meta['og:video:width'] ?? '100%';
            $height = $meta['og:video:height'] ?? '450';
            $uuid4 = Uuid::uuid4();
            //echo $embedUrl;
          ?><div class="video-container" style="width: 100%;">
              <iframe id="video-<?= $uuid4->toString() ?>" class="stitch-video" width="<?= $width ?>" height="<?= $height ?>" src="<?= $embedUrl ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
              </iframe>
            </div>
          <?
          }
          break;

        default:
          ?>
          <div class="card horizontal">
            <div class="card-image">
              <img src="<?= $meta['og:image'] ?>" />
            </div>
          </div>
      <? break;
      }

      ?>
      <? //echo '<pre>' . print_r($meta, true) . '</pre>'; 
      ?>
    <? endif; ?>


    <?php if ($displayLat && $displayLng): ?>
      <div class="sherlock-meta" style="margin-top: 1.5rem; padding: 12px; background: rgba(0,0,0,0.3); border-left: 4px solid #ffab40; border-radius: 0 4px 4px 0; display: flex; align-items: center;">

        <div style="margin-right: 15px; flex-shrink: 0;">
          <a href="https://maps.google.com/?q=<?= $displayLat ?>,<?= $displayLng ?>" target="_blank">
            <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?= $displayLat ?>,<?= $displayLng ?>&zoom=12&size=100x100&maptype=satellite&key=AIzaSyBczAa8MQ_-C1OYS7_0oZPSGFS1KhMeTwg"
              style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #ffab40; box-shadow: 0 0 10px rgba(255,171,64,0.3); object-fit: cover;"
              alt="Map Anchor">
          </a>
        </div>

        <div style="flex-grow: 1;">
          <div style="color: #ffab40; font-size: 0.85rem; font-family: monospace; letter-spacing: 1px;">
            <i class="material-icons tiny" style="vertical-align: middle;">place</i>
            LOCATION_FIXED: <?= $displayLat ?>, <?= $displayLng ?>
          </div>

          <?php if ($displayContext): ?>
            <div style="color: #90a4ae; font-style: italic; font-size: 0.95rem; margin-top: 8px; line-height: 1.4;">
              "<?= htmlspecialchars($displayContext) ?>"
            </div>
          <?php endif; ?>
        </div>

      </div>
    <?php endif; ?>

    <div style="margin-top: 1.5rem; color: #555; font-family: monospace; font-size: 0.85rem;">
      [ ANCHOR_ID: <span class="copy-target" style="cursor: pointer; color: #9b59b2;" onclick="copyText('<?= $anchor->id ?>'); M.toast({html: 'ID COPIED', classes: 'blue'});">
        <?= $anchor->id ?>
      </span> ] <br /> [ TIMESTAMP: <?= date('m/d/Y H:i:s', strtotime($anchor->created_at)) ?> ] <br /> [ PROJECTION_DATE: <?= date('m/d/Y H:i:s', strtotime($anchor->projected_to)) ?> ]
    </div>
  </div>

  <?php
  if (!count($content['nexus_ids']) >= 1): ?>
    <ul class="collapsible" style="border: none; box-shadow: none; margin: 10px 0;">
      <li>
        <div class="collapsible-header white-text" style="border: 1px solid #444;">
          <i class="material-icons blue-text">explore</i>
          NEXUS_CONNECTIONS (<?= count($content['nexus_ids']) ?>)
        </div>
        <div class="collapsible-body white-text">
          <div class="nexus-details-loading" id="nexus-details-<?= $anchor->id ?>">
            <div class="card-inner-bevel subtle-scanning-line-effect" style="padding: 5px;">
              <?php foreach ($content['nexus_ids'] as $key => $nexus) {
                echo '<pre class="debugger-info">';
                var_dump($nexus);
                echo '</pre>';
                $year = date('Y', strtotime($nexus->created_at));
                $date = date('m/d/Y h:ia', strtotime($nexus->created_at));
              ?>
                <div class="nexus-link-item" data-nexus-id="<?= $nexus->id ?>" data-anchor-id="<?= $anchor->id ?>" data-lat="<?= $nexus->lat ?>" data-lng="<?= $nexus->lng ?>" style="margin-bottom: 5px;">
                  <a href="#!" onclick="$(this).next('.nexus-detail-body').slideToggle();">
                    🛰️ <?= $date ?>
                  </a>
                  <div class="nexus-detail-body" style="display: none;">
                    <div class='nexus-detail-container'>
                      <a href="#!" onclick="warpToNexus(<?= $anchor->id ?>, <?= $nexus->id ?>, '<?= date('m/d/Y h:ia', strtotime($nexus->created_at)) ?>')">
                        <blockquote><strong>
                            <? echo $nexus->content_type ?>:</strong><br>

                          <? echo $nexus->content; ?>
                        </blockquote>
                      </a>
                    </div>
                  </div>
                </div>
              <?php }; ?>
            </div>
          </div>
        </div>
      </li>
    </ul>
  <?php endif; ?>

  <div class="card-action" style="display: flex; padding: 2px 0; border-top: 1px solid #222; background: rgba(0,0,0,0.2);">
    <button onclick="vouchForStitch(<?= $anchor->id ?>)" class="btn-flat green-text text-accent-3" style="font-weight: bold;"><i class="material-icons">verified_user</i>
      <div style="white-space: nowrap;">VOUCH (<span id="vouch-count-<?= $anchor->id ?>"><?= $anchor->vouch_count ?? 0 ?></span>) </div>
    </button>
    <? /*
    */ ?>
    <button onclick="nexusLinkStitch($(this).closest('.stitch-wrapper'), true)" class="btn-flat purple-text nexus-toggle-btn" style="font-weight: bold; margin-left: 15px; margin-bottom: 5px;"><i class="material-icons">call_split</i>
      BRANCH
    </button>
    <button onclick="nexusLinkStitch($(this).closest('.stitch-wrapper'))" class="btn-flat purple-text" style="font-weight: bold; margin-left: 15px; margin-bottom: 5px;"><i class="material-icons">add</i>
      ADD
    </button>
    <button onclick="shareStitch(<?= $anchor->id ?>)" class="btn-flat purple-text share_btn" style="font-weight: bold; margin-left: 15px; margin-bottom: 5px;"><i class="material-icons">share</i>
      SHARE
    </button>
    <button onclick="saveStitch(<?= $anchor->id ?>)"
      class="btn-flat btn-save"
      style="font-weight: bold; margin-left: 15px;"><i class="fas fa-gem"></i>
      SAVE
    </button>
  </div>
</div>