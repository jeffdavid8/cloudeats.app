<ul class="left">
   <li>
      <a href="#" data-target="slide-out" class="sidenav-trigger main-menu-btn show-on-large"><i class="material-icons">menu</i></a>
   </li>
   <? /* <li class="hide-on-small-only">
      <a href="?app=bibleBot"><i class="material-icons">home</i></a>
   </li>
   */ ?>
   <li>
      <a title="Copy link" class="copyUrlBtn" href="#!"><i class="material-icons">share</i></a>
   </li>
   <? /*
   <li>
      <a title="Share link" class="shareUrlBtn" href="#!"><i class="material-icons">share</i></a>
   </li>
   */ ?>
   <?
   if (!empty($search_string)) 
   { ?>
   <li>
      <a title="Bookmark this search" class="bookmarkSearchBtn" data-key="<?= $search_string ?>" href="#!"><i class="material-icons">bookmark_outline</i></a>
   </li>
  <? } ?>
</ul>
<script>
(function($) 
{
  $('.header .shareUrlBtn').on('click', function(e)
  {
    e.preventDefault();
    const params = new URLSearchParams(window.location.search);
    let facet = params.get('s');
    if (facet === null)
    {
      facet = '';
    }
    else 
    {
      facet = decodeURIComponent(facet)
    }
    $('.share.page.layer').show();
    $('body').addClass('no-scroll-y');
    //$('.share.page.layer input.page_title').focus();
  });
  $('.header .copyUrlBtn').on('click', function()
  {
    copyText(location.href);
    notify('Link copied to clipboard');
  });

  $('.header .bookmarkSearchBtn').on('click', function(e)
  {
    e.preventDefault();
    bookmark_verse($(this));
  });

})(jQuery);
</script>

