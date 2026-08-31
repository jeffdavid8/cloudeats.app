<?
render('components/header/header.php');

   /* Show query errors */
   if (!empty($search_results['errors']))
   {
      render('components/search_result_errors.php', array('errors' => $search_results['errors']));
   } 
   elseif (count($search_results['wikipedia_results']['query']['search']))
   {
      ?>
      <div class="results">
         <div class="search_string">Showing results for <br/><span class="highlight"><?= $search_string ?></span></div>
         
         <?
         $search_results_books = array();
         /* Results output */
         foreach ($search_results['wikipedia_results']['query']['search'] as $result)
         {
            render('components/wikipedia_search_result.php', array('result' => $result));
         }

         if (!$_SESSION['paypal_notification'])
         {
            $_SESSION['paypal_notification'] = true;
            render('components/paypal_notification.php');
         }
         
         $domain = $_SERVER['HTTP_HOST'];
         $path = $_SERVER['SCRIPT_NAME'];
         $queryString = $_SERVER['QUERY_STRING'];
         $url = "http://" . $domain . $path . "?" . $queryString;

         ?>
         <div class="fb-like left" style="margin-top: 1em;" data-href="<?= $url; ?>" data-colorscheme="dark" data-width="" data-layout="standard" data-action="like" data-size="small" data-share="true"></div>
         
      </div>

      <div class="container">
         <div class="row">
         <div class="divider"></div>
         </div>
      </div>
      <?

      render('components/bibleproject_video_list.php', array(
         'search_results_books' => $search_results_books,
         'bibleproject_video_count' => $bibleproject_video_count,
      ));

   }
   ?>

