<?php
$href = 'http://vi/mediawiki/index.php?curid=' . $result['pageid'];
?>
<div class="result_container row">
   <div class="verse_wrapper"> 
      <h5><a href="<?= $href ?>"><?= $result['title']  ?></a></h5>
      <div><i class="fab fa-wikipedia-w"></i> <a href="<?= $href ?>">(<?= $href ?>)</a></div>
      <div class="verse result" title="<?= $result['reference_friendly'] ?>" data-pageid="<?= $result['pageid']; ?>">

         <p><?= $result['snippet'] ?><br/>
         <?= date('m-d-Y', time($result['timestamp'])) ?></p>

      </div>
   </div>
</div>
