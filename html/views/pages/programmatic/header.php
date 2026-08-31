<div class="header" data-component="programmatic-header">

   <nav>
      <div class="nav-wrapper ">

         <? render('pages/programmatic/header_left.php'); ?>

         <img class="brand-logo center" src="images/bibleBot-logo-96.png">

         <? render('pages/programmatic/header_right.php'); ?>

      </div>
   </nav>

   <? render('components/sidenav/programmatic_left.php'); ?>
      
</div>

<? render('components/dialogs/bookmark_collection_title.php', array()); ?>
<? render('components/dialogs/share.php', array()); ?>
<? render('components/dialogs/open.php', array()); ?>
<? render('components/dialogs/save.php', array()); ?>
<? render('components/dialogs/remove_all_bookmarks.php', array()); ?>
