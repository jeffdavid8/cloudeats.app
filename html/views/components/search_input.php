<div class="primary-search-field input-field s12 center" data-component="search-input" data-auto-index="<?= htmlspecialchars(json_encode($autoIndex)); ?>">

  <!--<i style="top: 1rem;" class="material-icons prefix">search</i>-->

  <div class="prefix">
    <img src="images/mb-logo-black-circle-2020.png"><span>></span>
  </div>
  <input type="search" name="s" id="index-search-field" value="<?= $search_string; ?>" class="search" placeholder="Search" autocomplete="off">

</div>
