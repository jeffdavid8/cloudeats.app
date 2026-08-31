<ul class="collapsible collapsible-accordion">
   <li>
      <a class="collapsible-header">Table of Contents<i class="material-icons">local_florist</i></a>
      <div class="collapsible-body">
         <ul>
         <?php
            $bible = BibleJson::getInstance();
            foreach ($bible->booksFullnames as $book)
            {
               ?>
               <li>
                  <?= '<a href="?s=' . $book . '%201"><i class="fas fa-book"></i>' . $book . '</a>' ?>
               </li>
               <?               
            }
            ?>
         </ul>
      </div>
   </li>
</ul>
