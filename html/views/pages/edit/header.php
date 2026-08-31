<div class="header" data-component="edit-header">

   <nav>
      <div class="nav-wrapper ">

         <? render('pages/edit/header_left.php'); ?>

         <a href="/"><img class="brand-logo center" src="images/bibleBot-logo-96.png"></a>

         <? render('pages/edit/header_right.php'); ?>

      </div>
   </nav>

   <? render('components/sidenav/main_left.php'); ?>

</div>

<? render('components/dialogs/bookmark_collection_title.php', array()); ?>
<? //render('components/dialogs/create_new_bookmark.php', array()); ?>
<? render('components/dialogs/share.php', array()); ?>
<? render('components/dialogs/open.php', array()); ?>
<? render('components/dialogs/save.php', array()); ?>
<? render('components/dialogs/remove_all_bookmarks.php', array()); ?>

<div class="tap-target" id="save-notify-tap-target" data-target="save_btn">
   <div class="tap-target-content">
      <h5>You have pending changes</h5>
      <p>Hit the checkmark button to apply your changes.</p>
   </div>
</div>
