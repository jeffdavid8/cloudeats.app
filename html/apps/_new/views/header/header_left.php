<ul class="left">
   <li>
      <a href="#" data-target="slide-out" class="sidenav-trigger main-menu-btn show-on-large"><i class="material-icons">menu</i></a>
   </li>
   <li class="hide show-on-medium-and-up">
      <a href="/"><i class="material-icons">home</i></a>
   </li>
   
   <?php
   if (!empty($search_string)) 
   { 
      ?>
      <li>
         <a title="Start new search" href="/" target="_blank" class="new_window_btn"><i class="material-icons">add</i></a>
      </li>
      <?php
   }
   ?>

</ul>
