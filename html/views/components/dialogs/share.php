<style>

</style>

<!-- Modal Structure -->
<div id="share_dialog" class="modal modal-fixed-footer">
   <div class="modal-content">
      <h4>Share</h4>

      <h5>Link</h5>
      <div class="row">
         <div class="col s12 m8">
            <div class="input-field meme-select-wrapper">
               <select id="meme_select" class="icons">
                  <option value="/images/memes/2.jpg" disabled selected>Select an image</option>
                  <?php
                  $images_dir = getcwd() . '/images/memes';
                  $images = get_dir_contents($images_dir);
                  $types=Array(1 => 'jpg', 2 => 'jpeg', 3 => 'png', 4 => 'gif'); //store all the image extension types in array

$imgname = ""; //get image name here
                  foreach ($images as $image)
                  {
                     $image = str_replace($images_dir, '/images/memes', $image);
                     $filename = str_replace('/images/memes/', '', $image);
                     $ext = explode(".",$filename); //explode and find value after dot
                     if (($filename != '2.jpg') && (in_array($ext[1],$types)))
                     {
                     ?>
                     <option value="<?= $image ?>" data-icon="<?= $image ?>"><?= $filename ?></option>
                     <?
                     }
                  }
                  ?>
               </select>
               <label>Image</label>
            </div>
            <div class="input-field">
               <input id="page_link_text" type="text" value="<?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"></input><button title="Page link" class="btn indigo copy_page_link_btn">Copy</button>

            </div>
         </div>
         <div class="col s12 m4">
            <img class="meme_preview responsive-img" src="/images/memes/2.jpg" style="max-height: 10em;"/>
         </div>
      </div>
      <hr></hr>
      <h5>Embed</h5>
      <div class="row">
         <div class="col s12">
            <div class="input-field">
               <textarea id="page_embed_code"><iframe src="<?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>" name="bibleBot" scrolling="Yes" height="500px" width="100%" style="border: none;"></iframe></textarea><button title="Page link" class="btn indigo copy_page_embed_code_btn">Copy</button>
            </div>
         </div>
      </div>
   </div>
   <div class="modal-footer">
      <a href="#!" class="modal-close waves-effect waves-white btn-flat btnOk">Ok</a>
   </div>
</div>

<script>
mb.dialogs.share = function(command, args, callback) 
{
   var dialog = this;
   var el = $('#share_dialog');
   var imports = [];
   var commands =  {
      'init': function(args, callback)
      {
         el.modal({
            'onOpenStart': false,
         });
         el.find('.copy_page_link_btn').on('click', function(e) {
            e.preventDefault();
            var element = document.getElementById('page_link_text');
            element.select();
            document.execCommand('copy',false);
         
            notify('<i class="fas fa-copy"></i> &nbsp; Page link copied to clipboard');
            return false;
         });
         el.find('.copy_page_embed_code_btn').on('click', function(e) {
            e.preventDefault();
            var embedCode = el.find('textarea.page_embed_code').val();
            var element = document.getElementById('page_embed_code');
            element.select();
            document.execCommand('copy',false);
            notify('<i class="fas fa-copy"></i> &nbsp; Page embed code copied to clipboard');
            return false;
         });
         el.find('#meme_select').change(function(e)
         {
            function replaceQueryParam(param, newval, search) {
               var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
               var query = search.replace(regex, "$1").replace(/&$/, '');
           
               return (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
            }
            
            // Update Preview Image
            var image = this.value;
            el.find('img.meme_preview').attr('src', image);

            // Update page link input
            var name = image.replace('/images/memes/', '');
            var url = document.getElementById('page_link_text').value;
            document.getElementById('page_link_text').value = replaceQueryParam('m', name, url);
         });
      },
      'open': function()
      {
         el.modal('open');
      },
   };

   function importFile(merge, callback)
   {
      var package = {
         'action': 'upload_session_bookmarks',
         'data': {
            'imports': imports,
            'merge': merge,
         }
      };
   
      $.ajax({
         url:'api.php', 
         dataType: 'json',
         method: 'POST',
         data: package,
         success: function(data) 
         {
   
            //$('.sidenav .bookmark_menu .bookmark_links li').remove();
            var $bookmarks = $('.bookmark_menu .collapsible-body > ul.bookmark_links');
   
            $bookmarks.find('.verse_link').remove();
   
            $.each(data.bookmarks, function(index, bookmark) 
            {
               $bookmarks.append($(bookmark));
            });
            M.toast({
               html: 'Bookmarks Imported!',
               displayLength: 5000
            });
            console.log(callback);
            if (typeof callback === 'function')
            {
               callback(imports);
            }

         },
         error: function(response) 
         {
            //console.log('There was a problem with the api call');
            console.log(response);
         },
      });
   };
   
   if ((typeof command !== 'undefined') && (typeof commands[command] === 'function'))
   {
      commands[command](args, callback);
   }
};

   $(document).ready(function()
   {
      mb.dialogs.share('init', 0, function(imports)
      {
         
      });

      /*
      mb.dialogs.create_new_bookmark('init', 0, function(imports)
      {
         //console.log(imports);
      });
      */

      $('.header .share_btn').on('click', function()
      {
         mb.dialogs.share('open');
      });

   });


</script>
