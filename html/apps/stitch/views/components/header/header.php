<style type="text/css">
   body {
      padding-top: 65px;
   }
</style>

<?php
// Set default search_string if not provided
$search_string = $search_string ?? '';
?>

<header class="header">

   <nav>
      <div class="nav-wrapper ">

         <? render('components/header/header_left.php', array('search_string' => $search_string)); ?>

         <a href="<?= config('base_url') ?>"><img class="brand-logo center" src="images/mb-logo-black-circle-2020.png"></a>

         <? render('components/header/header_right.php', array('search_string' => $search_string)); ?>

      </div>

      <div class="row" style="background-color: #1c1631e3; backdrop-filter: blur(5px); margin-bottom: 0;">
         <div class="col s7 m9 l10">
            <div class="hide-on-small-only">
               <? render('components/scrolling-ticker.php'); ?>
            </div>
            <div class="hide-on-med-and-up" style="display: flex; flex-direction: row;">
               
            </div>
         </div>
         <div class="col s5 m3 l2 right">
            <? render('components/stitch_action_menu.php'); ?>
         </div>
      </div>
   </nav>

   <? render('components/hud-container.php'); ?>

</header>


<? render('components/sidenav/main_left.php'); ?>


<? //render('components/dialogs/share.php', array()); 
?>