<?php
render('pages/search_results/header.php', array('search_string' => $search_string));
?>

<div class="section no-pad-bot" id="index-banner" data-component="search-page">

<div class="container">
   <!--
   <div class="row center">
      <h3 class="header col s12 light"> to shew thyself approved unto God,</h3>
   </div>
   -->
   <div class="row center">
   </div>
   <div class="row center">
      <h1 class="center">Search</h1>
      <div class="primary-search-field input-field s6 s12 black-text">
         <!--<i class="grey-text material-icons prefix">search</i>-->

         <form action="search.php" method="get">

            <input name="s" id="index-search-field" value="<?= $search_string; ?>" type="text" class="search grey-text" placeholder="Search" >
         
         </form>

      </div>
      <!--<h5 class="header col s12 light">a workman that needeth not to be ashamed, rightly dividing the word of truth.</h5>-->
   </div>
   <div class="row center">

      <a href="#" id="index-search-btn" class="btn btn-large waves-effect waves-light grey lighten-4 grey-text text-darken-1"><i class="material-icons">search</i></a>
   </div>

</div>



</div>
