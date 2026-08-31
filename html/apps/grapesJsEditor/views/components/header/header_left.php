<ul class="left">
   <li>
      <a href="#" data-target="slide-out" class="sidenav-trigger sidenav-close main-menu-btn show-on-large"><i class="material-icons">menu</i></a>
   </li>
   <li class="">
      <a id="toggle-source-view" class="tryit-toolbar-btn" title="Toggle Source View">&#60;&#47;&#62;</a>
   </li>
   <li>
      <a href="#" id="header-save-block" class="tryit-toolbar-btn" title="Save Block"><i class="material-icons">save</i></a>
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
