<?php
$structure = App::getInstance()->structure();
?>
<ul class="collapsible collapsible-accordion">
   <li>
      <a class="collapsible-header">Documentation <i class="fas fa-book-reader"></i></a>
      <div class="collapsible-body">
         <ul>
         <?php
         $row_limit = 3;
         foreach ($structure['documentation'] as $doc)
         {
            ?>
            <li>
               <?= '<a target="_blank" href="' .$doc['href'] . '">' . $doc['icon'] . $doc['title'] . '</a>' ?>
            </li>
            <?
         }
         ?>
         </ul>
      </div>
   </li>
</ul>



