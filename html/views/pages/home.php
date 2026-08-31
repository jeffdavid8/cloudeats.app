<?
$mb = App::getInstance();
$structure = $mb->structure();
$autoIndex = [];
foreach ($structure as $structure_key => $s) {
  foreach ($s as $item) {
    $keywords = (!empty($item['keywords'])) ? $item['keywords'] : array();
    $markup = '<a target="_blank" href="' . $item['href'] . '" data-command="' . $item['command'] . '">' . $item['icon'] . '<p>' . $item['title'] . '</p></a>';
    $autoIndex[] = array(
      'title' => $item['title'],
      'command' => $item['command'],
      'markup' => $markup,
      'keywords' => $keywords,
    );
  }
}
?>

<div class="container" data-component="base-home-page">
    <div class="row">

      <? render('components/search_input.php', array('autoIndex'=>$autoIndex)); ?>

    </div>

    <div id="search_results" class="row"></div>

    <? //render('components/weather_widget_row.php') 
    ?>

  </div>

  <div class="container" style="margin-bottom: 10em;">

    <hr />
    <h3 class="section-header">Apps</h3>
    <div class="row">
      <?php
      foreach ($structure['apps'] as $app) {
        ?>
        <div class="item col s4 m4 l2">
          <a class="waves-effect waves-light<?= (!empty($app['description'])) ? ' tooltipped' : '' ?>" data-position="top" data-tooltip="<?= $app['description'] ?>" href="<?= $app['href'] ?>">
            <div class="center promo panel">
              <?= $app['icon'] ?>
              <span class="icon-title"><?= $app['title'] ?></span>
            </div>
          </a>
        </div>
      <?
      }
      ?>
    </div>

  <hr />
  <h3 style="margin-bottom: 1em">Commands</h3>
  <div class="row" data-component="command-elements">
    <?php
    foreach ($structure['commands'] as $command) 
    { ?>
      <div class="item col s4 m4 l2">
        <a class="command-element <?= (!empty($command['description'])) ? 'tooltipped' : '' ?>" data-position="top" data-tooltip="<?= $command['description'] ?>" href="" data-command="<?= $command['command'] ?>">
          <div class="center promo panel">
            <?= $command['icon'] ?>
            <span class="icon-title"><?= $command['title'] ?></span>
          </div>
        </a>
      </div>
    <?
    }
    ?>
  </div>

  <hr />
  <h3 style="margin-bottom: 1em">Documentation</h3>
  <div class="row">
    <?php
    foreach ($structure['documentation'] as $doc) 
    { ?>
      <div class="item col s4 m4 l2">
        <a class="<?= (!empty($doc['description'])) ? 'tooltipped' : '' ?>" data-position="top" data-tooltip="<?= $doc['description'] ?>" href="<?= $doc['href'] ?>">
          <div class="center promo panel">
            <?= $doc['icon'] ?>
            <span class="icon-title"><?= $doc['title'] ?></span>
          </div>
        </a>
      </div>
    <?
    }
    ?>
  </div>


</div>


