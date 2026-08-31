/**
 * Weather Widget Row Component
 * Manages weather data display including current conditions, hourly and daily forecasts
 * Fetches data from National Weather Service API
 */
mb.registerComponent('weather-widget-row', function($element, data) {
    console.log('Weather Widget Row component initialized');
    
    // Configuration
    const config = {
        hourlyForecastUrl: "https://api.weather.gov/gridpoints/IWX/53,7/forecast/hourly",
        dailyForecastUrl: "https://api.weather.gov/gridpoints/IWX/53,7/forecast"
    };
    
    // Cache DOM elements
    const $hourlyForecast = $element.find('.hourly_forecast');
    const $currentWeatherDetails = $element.find('.current_weather_details_container');
    const $futureForecast = $element.find('.future_forecast');
    const $forecastToggle = $element.find('#forecast-toggle-container');
    
    // Load hourly forecast data
    function loadHourlyForecast() {
        $.get(config.hourlyForecastUrl)
            .done(function(json) {
                console.log('Hourly forecast loaded successfully');
                displayHourlyForecast(json.properties.periods);
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load hourly forecast:', error);
                $hourlyForecast.html('<div class="col s12">Failed to load hourly forecast</div>');
            });
    }
    
    // Load daily forecast data
    function loadDailyForecast() {
        $.get(config.dailyForecastUrl)
            .done(function(json) {
                console.log('Daily forecast loaded successfully');
                displayCurrentConditions(json.properties.periods[0]);
                displayDailyForecast(json.properties.periods);
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load daily forecast:', error);
                $currentWeatherDetails.html('Failed to load current conditions');
                $futureForecast.html('<div class="col s12">Failed to load daily forecast</div>');
            });
    }
    
    // Display hourly forecast periods
    function displayHourlyForecast(periods) {
        $hourlyForecast.empty();
        
        periods.forEach(function(period, i) {
            const time = new Date(period.startTime.replace('T',' ')).toLocaleString('en-US', {hour:'numeric', hour12: true});
            const date = new Date(period.startTime.replace('T',' ')).toLocaleDateString();
            
            console.log('Processing hourly period:', period);
            
            let title = '';
            if ((period.number % 12 === 0) || (period.number === 1)) {
                title = date + '<br/>';
            }
            
            const $periodDiv = $('<div class="col period"></div>')
                .attr('title', period.shortForecast)
                .html(title + ' ' + time + ' ' + period.temperature);
            
            $hourlyForecast.append($periodDiv);
        });
    }
    
    // Display current weather conditions
    function displayCurrentConditions(currentPeriod) {
        $currentWeatherDetails.html(currentPeriod.detailedForecast);
    }
    
    // Display daily forecast periods
    function displayDailyForecast(periods) {
        $futureForecast.empty();
        
        periods.forEach(function(period, i) {
            const time = new Date(period.startTime.replace('T',' ')).toLocaleString('en-US', {hour:'numeric', hour12: true});
            
            const $periodDiv = $('<div class="col period"></div>')
                .attr('title', period.detailedForecast)
                .html('<img src="' + period.icon + '" />' + period.name + ' ' + period.temperature);
            
            $futureForecast.append($periodDiv);
        });
    }
    
    // Initialize forecast toggle functionality
    function initializeForecastToggle() {
        // Bind click handler to the forecast toggle button
        $element.find('.forecast-toggle-btn').on('click', function(e) {
            e.preventDefault();
            $forecastToggle.toggleClass('hide');
        });
        
        console.log('Forecast toggle functionality ready');
    }
    
    // Initialize the weather widget
    function initializeWeatherWidget() {
        console.log('Loading weather data...');
        
        // Load both hourly and daily forecasts
        loadHourlyForecast();
        loadDailyForecast();
        
        // Initialize additional functionality
        initializeForecastToggle();
    }
    
    // Start loading weather data
    initializeWeatherWidget();
    
    // Expose public methods
    return {
        refresh: initializeWeatherWidget,
        toggleForecast: function() {
            $forecastToggle.toggleClass('hide');
        }
    };
});