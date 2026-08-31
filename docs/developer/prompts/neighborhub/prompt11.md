================================================================================
PROMPT 11: THE WONDER CITY DISPATCH INTEGRATION FEED (WONDERCITY/DISPATCH_FEED.PHP)
Act as a Principal Systems Integration and UI Engineer. Write the complete presentation and data parsing script for the file:
/html/apps/neighborhub/views/wondercity/dispatch_feed.php

This view serves as the cross-application logging and dispatch stream that connects Neighborhub to the broader ecosystem by leveraging the shared Stitch model (stitch.model.php).

It must construct:

Stitch Model Entry Consumption: Utilizing the static inheritance methods of the Stitch class, query for all elements where content_type = 'wonder_city_dispatch'. Order the collections in descending chronological order to maintain a live running ticker of platform operations.

Object Payload Parsing: Iterate through the matching array of instantiated Stitch objects. Pull metrics directly out of each object's components:

Use $stitch->getFormattedDate() or $stitch->getEra() to render the systemic timestamp anchor.

Pull latitude/longitude spatial markers ($stitch->lat, $stitch->lng) if present to identify the event's geographical tracking coordinates.

Read sub-attributes directly from the automatically decoded content array ($stitch->content), capturing properties such as the tracking status badges (ORDER_PLACED, COURIER_ASSIGNED, TRANSIT_UPDATE, DELIVERY_COMPLETED) and descriptive text.

Activity Dispatch Interface UI: Structure a clean, scanning log matrix. Render individual entries inside a responsive card component. Implement color-coded badges to separate courier tracking updates from incoming commercial orders.

Graceful Empty State Fallback: If the collection array contains zero logs, display a fallback card matching our system utilities layout: "Sovereign Ledger Online: Awaiting incoming local dispatch logs via Stitch..."

Asynchronous Ingestion Stream: Embed a local JavaScript block using the framework's native AJAX methods (mb.ajax() or standard jQuery handlers). This script should periodically check the dispatch feed container to update or append fresh Stitch data streams seamlessly without forcing a full viewport reload.

Output the code block cleanly from top to bottom with zero omissions, zero placeholders, and zero summarized code cuts.