<?php

class HubRouteFactory
{
    private static $baseScheme = "https://www.google.com/maps/dir/?api=1";

    /**
     * Generates a platform-agnostic deep link for multi-stop courier routes
     * * @param string $destination Final drop-off point (Customer porch)
     * @param array $waypoints Array of store string locations or lat/lng frames
     * @param string $mode 'driving', 'bicycling', 'walking'
     * @return string Validly encoded deep link URL string
     */
    public static function createCourierRoute($destination, array $waypoints = [], $mode = 'driving')
    {
        // Fallback guard if destination is empty
        if (empty($destination)) {
            return "#error-missing-destination";
        }

        // Leave origin blank so Google Maps defaults to the phone's live GPS point
        $url = self::$baseScheme . "&origin=" . urlencode("") . "&destination=" . urlencode($destination);

        if (!empty($waypoints)) {
            // Clean strings and join them with pipe characters according to Google requirements
            $encodedWaypoints = array_map('urlencode', $waypoints);
            $url .= "&waypoints=" . implode('|', $encodedWaypoints);
        }

        $url .= "&travelmode=" . urlencode($mode);

        return $url;
    }

    /**
     * Simple Helper to generate a baseline delivery fee based on waypoint stop counts
     * (e.g. Base local Matthews rate + incremental charge per out-of-town waypoint like Gaston)
     */
    public static function calculateEstimatedDeliveryFee(array $waypoints = [])
    {
        $baseFee = 3.50; // Standard local flat fee
        $perStopPremium = 2.00; // Extra compensation for adding another shopping location
        
        return $baseFee + (count($waypoints) * $perStopPremium);
    }
}