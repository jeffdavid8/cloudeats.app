<?php
render('components/header/header.php', array('search_string' => ''));
?>
<div class="container">

  <div class="row">

    <div class="col s12 page_top">

        <div class="page_title left">Weather</div> 
        <div class="current_weather right"></div>
        <div id="clock" class="right"></div>

    </div>
  </div>
  <div class="hide-on-small-only">
    <div class="row">
      <div class="col s12 m6 l8 section">
        <div class="current_weather_details"></div>
        <div class="forecast "></div>
      </div>
      <div class="col s12 m6 l4 section">
        <div class="hourly_forecast"></div>
      </div>
    </div>
  </div>
  <div class="hide-on-med-and-up">
    <div class="row">
      <div class="current_weather_details"></div>
    </div>
    <div class="row">
      <div class="col s12">
        <ul class="forecastTabs tabs-fixed-width">
          <li class="tab col s6"><a class="active" href="#hourlyTab">Hourly</a></li>
          <li class="tab col s6"><a class="" href="#futureTab">Futurecast</a></li>
        </ul>
      </div>
    </div>
    <div class="row">
      <div id="hourlyTab" class="col s12">
        <div class="hourly_forecast"></div>
      </div>
      <div id="futureTab" class="col s12">
        <div class="forecast"></div>
      </div>
    </div>
  </div>
</div>

<script>
  (function($) {
    
  })(jQuery);
</script>

