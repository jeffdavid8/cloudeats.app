<header class="header">

   <nav>
      <div class="nav-wrapper ">

         <?php render('components/header/header_left.php', array('search_string' => $search_string)); ?>

         <a href="/" ><a href="<?= config('base_url') ?>" ><img class="brand-logo center" src="images/mb-logo-black-circle-2020.png"></a></a>
         
         <?php render('components/header/header_right.php', array('search_string' => $search_string)); ?>

      </div>
   </nav>
      
   <div class="container">
      <div class="center">
         <div class="sm-search-field input-field col s6 s12 black-text">
            <!--<i class="grey-text material-icons prefix">search</i>-->
            <h1>Search</h1>
            <form action="search.php" method="get">
               <input name="s" value="<?= $search_string; ?>" type="text" class="scripture-search grey-text" placeholder="<?= $search_string; ?>" >
            </form>

         </div>
      </div>
   </div>

</header>


<?php render('components/sidenav/main_left.php'); ?>


<?php //render('components/dialogs/share.php', array()); ?>

