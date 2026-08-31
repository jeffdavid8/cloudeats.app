var weather = null;
const oneMinute = 1000 * 60;
const oneHour = oneMinute * 60;
const oneDay = oneHour * 24;
var ttl = oneHour / 2;
var refreshWeatherData = false;

function updateWeather() {
  console.log("Refreshing weather data...");
  getWeatherData(renderWeather);
  window.setTimeout(updateWeather, oneMinute * 10);
}

function getWeatherData(callback) {
  $.ajax({
    url: "https://api.weather.gov/alerts/active?area=" + weather.context.properties.relativeLocation.properties.state,
    method: "GET",
    dataType: "json",
    global: false,
    success: function (data) {
      weather.alerts = data; // ✅ FIXED: Changed 'json' to 'data' to match your parameter
      $.ajax({
        url: weather.context.properties.forecast,
        method: "GET",
        dataType: "json",
        global: false,
        headers: {}, // 🛡️ Keep it clean
      }).done(function (json) {
        weather.forecast = json;
        $.ajax({
          url: weather.context.properties.forecastHourly,
          method: "GET",
          dataType: "json",
          global: false,
          headers: {}, // 🛡️ Keep it clean
        }).done(function (json) {
          weather.forecastHourly = json;
          updateWeatherStorage();
          loading(0);
          if (typeof callback === "function") callback();
        });
      });
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Weather Alert Signal Lost. Proceeding to Forecast...");
      // 🛡️ To break the loop, we still try to load the rest or just fail gracefully
      loading(0);
    },
  });
}

function setWeatherStorage(position) {
  var coords = position.coords.latitude + "," + position.coords.longitude;
  var weatherUrl = "https://api.weather.gov/points/" + coords;
  var updated = new Date().getTime();

  $.ajax({
    url: weatherUrl,
    method: "GET",
    dataType: "json",
    global: false,
    headers: {}, // 🛡️ STRIP THE TOKENS HERE TOO
  })
    .done(function (context) {
      weather = {
        context: context,
        updated: updated,
      };
      getWeatherData(renderWeather);
      $(".live-status").removeClass("hide");
    })
    .fail(function () {
      console.error("Point Metadata Failure. Weather Offline.");
      $(".live-status").addClass("hide");
      loading(0);
    });
}


function updateWeatherStorage() {
  weather.updated = new Date().getTime();
  localStorage.weather = JSON.stringify(weather);
}

function renderWeather() {
  // Forecast
  document.title =
    "" +
    weather.forecastHourly.properties.periods[0].temperature +
    "°" +
    " - " +
    weather.forecastHourly.properties.periods[0].shortForecast;

  $(".page_title").html(
    weather.context.properties.relativeLocation.properties.city +
      ", " +
      weather.context.properties.relativeLocation.properties.state +
      " Weather",
  );

  $(".current_weather").html(
    weather.forecastHourly.properties.periods[0].temperature +
      "°" +
      weather.forecast.properties.periods[0].temperatureUnit +
      ' <img src="' +
      weather.forecastHourly.properties.periods[0].icon.replace(",0", "") +
      '" />',
  );

  $(".current_weather_details").html(
    '<a class="radar_link" href="https://radar.weather.gov/?settings=v1_eyJhZ2VuZGEiOnsiaWQiOm51bGwsImNlbnRlciI6Wy05NS4xNDIsMzUuODg5XSwibG9jYXRpb24iOm51bGwsInpvb20iOjV9LCJhbmltYXRpbmciOnRydWUsImJhc2UiOiJzdGFuZGFyZCIsImFydGNjIjpmYWxzZSwiY291bnR5IjpmYWxzZSwiY3dhIjpmYWxzZSwicmZjIjpmYWxzZSwic3RhdGUiOmZhbHNlLCJtZW51Ijp0cnVlLCJzaG9ydEZ1c2VkT25seSI6ZmFsc2UsIm9wYWNpdHkiOnsiYWxlcnRzIjowLjgsImxvY2FsIjowLjYsImxvY2FsU3RhdGlvbnMiOjAuOCwibmF0aW9uYWwiOjAuNn19" target="_blank"><img data-v-26f286d2="" src="https://radar.weather.gov/ridge/standard/CONUS_loop.gif?refreshed=' +
      new Date().getTime() +
      '" class="mapImage responsive-img"></a>' +
      weather.forecast.properties.periods[0].detailedForecast,
  );

  $(".forecast").html("");
  weather.forecast.properties.periods.forEach(function (period, i, array) {
    var time = new Date(period.startTime.replace("T", " ")).toLocaleString(
      "en-US",
      { hour: "numeric", hour12: true },
    );

    $(".forecast").append(
      '<div class="period" title="' +
        period.detailedForecast +
        '"><img src="' +
        period.icon.replace(",0", "") +
        '" />' +
        period.name +
        " - " +
        period.temperature +
        "°</div>",
    );
  });

  // Hourly
  $(".hourly_forecast").html(
    '<div class="hide-on-small-only"><h5>Hourly Forecast</h5></div>',
  );
  weather.forecastHourly.properties.periods.forEach(
    function (period, i, array) {
      var time = new Date(period.startTime.replace("T", " ")).toLocaleString(
        "en-US",
        { hour: "numeric", hour12: true },
      );
      var date = new Date(
        period.startTime.replace("T", " "),
      ).toLocaleDateString();
      var days = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ];
      var day = days[new Date(period.startTime.replace("T", " ")).getDay()];

      if (period.number % 12 == 0 || period.number == 1) {
        title = "<h6>" + day + " " + date + "</h6>";
      } else {
        title = "";
      }

      $(".hourly_forecast").append(
        '<div class="row period" title="' +
          period.shortForecast +
          '">' +
          title +
          '</div> <div class="row" title="' +
          period.shortForecast +
          '">' +
          time +
          '  <span class="temp">' +
          period.temperature +
          "°</span>" +
          ' <img class="hourly_icon" src="' +
          period.icon.replace(",0", "").replace(",0", "") +
          '" /></div></div>',
      );
    },
  );

  $(".forecastTabs").tabs();
}

function displayCurrentTime() {
  var dt = new Date();
  var refreshRate = 1000 * 2; //Refresh rate 1000 milli sec means 1 sec
  var cDate = dt.getMonth() + 1 + "/" + dt.getDate() + "/" + dt.getFullYear();

  //$('#clock').html(cDate + " - " + dt.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
  $("#clock").html(
    dt.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" }),
  );

  // This checks for outdated weather data more frequently, so it more quickly updates the UI when a PC wakes up from sleep mode
  var now = dt.getTime();
  var updated = new Date(weather.updated).getTime();
  var refreshWeatherData = now - updated > ttl;
  if (refreshWeatherData) updateWeather();

  window.setTimeout("displayCurrentTime()", refreshRate);
}

$("document").ready(function ($) {
  async function getIpBasedLocation() {
    notify("Attempting to retrieve alternative GPS sources");

    // 🛰️ Using our mb.get wrapper instead of raw fetch
    // This ensures our 'isExternal' logic strips the CSRF tokens!
    mb.get("https://ipapi.co/json/", function (data) {
      if (data && data.latitude) {
        var position = {
          coords: {
            latitude: data.latitude,
            longitude: data.longitude,
          },
        };
        setWeatherStorage(position);
        loading(0);

        // Log the success for the Architect
        console.log("IP-Based Location Secured:", data.city, data.country_name);
      }
    }).fail(function (error) {
      loading(0);
      $(".page_title").html("No location services available");
      notify("Error fetching IP-based location.");
      console.error("Location Signal Lost:", error);
    });
  }

  if (typeof Storage !== "undefined") {
    // Code for localStorage/sessionStorage.
    if (localStorage.weather) {
      // Just check if it exists
      weather = JSON.parse(localStorage.weather);
      updateWeather();
    } else {
      weather = {
        updated: "",
      };
      loading(1);
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function (position) {
            // Success callback: location data is available in 'position'
            setWeatherStorage(position);
          },
          function (error) {
            // Error callback: handle potential errors
            switch (error.code) {
              case error.PERMISSION_DENIED:
                console.log("User denied the request for Geolocation.");
                break;
              case error.POSITION_UNAVAILABLE:
                notify("Location information is unavailable.");
                break;
              case error.TIMEOUT:
                notify("The request to get user location timed out.");
                break;
              case error.UNKNOWN_ERROR:
                notify("An unknown error occurred.");
                break;
            }
            getIpBasedLocation();
          },
          {
            enableHighAccuracy: true, // Request the highest possible accuracy
            timeout: 3000, // Maximum time (in milliseconds) allowed to return a position
            maximumAge: 0, // Don't use a cached position, get a real current position
          },
        );
      } else if (!navigator.geolocation) {
        //NO BROWSER BASED GEOLOCATION
        //alert("Sorry, your browser does not support location services.");
        // Try using IP based location
        getIpBasedLocation();
      }
    }
  } else {
    alert("Sorry, your browser does not support browser storage.");
  }

  displayCurrentTime();
});
