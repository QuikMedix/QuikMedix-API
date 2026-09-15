# Set up pharmacy tariffs

A new database can contain no tariff plans, areas, or state reference rows. The
pharmacy form lists saved plans and areas; it does not create them automatically.
A tariff plan is required for administrator-created pharmacies. Area selection is
optional on the creation form; selected areas are saved as the default tariff tier.

## Local setup

1. Set `GOOGLE_MAPS_API_KEY` in `.env`. Enable Maps JavaScript API for the area
   editor and Geocoding API for pharmacy address lookup. Use Google Cloud key
   restrictions appropriate to both browser and server requests. Do not commit
   the key. See the [Google Maps setup guide](https://developers.google.com/maps/documentation/javascript/cloud-setup).
2. If Laravel configuration is cached, run `php artisan config:clear`.
3. Open `/settings/plans/add` and enter the delivery prices and order allowance
   for your business, including the fallback rate for locations outside assigned
   areas. No sample prices are inserted automatically.
4. To use area-based pricing, open `/settings/area/add`, enter a name and state,
   and draw the coverage boundary. Click at least three corners, choose **Finish
   area**, adjust the boundary if needed, then save. Standard US state choices
   are available even when the `states` table is empty.
5. Reload `/pharmacys/add`, select an admin zone and tariff plan, choose any
   applicable tariff areas, and save the pharmacy. Additional area tiers can be
   configured on the pharmacy edit page.

The area editor uses `google.maps.Polygon` and the Maps event API. It does not
load the [retired Drawing library](https://developers.google.com/maps/deprecations#drawing_library_may_2026).
Missing Maps configuration and unsuccessful address lookups produce validation
messages instead of attempting to save an invalid location.

## Offline checks

```sh
php tests/smoke/tariff-setup.php
node tests/smoke/tariff-area-editor.js
```

These checks use an in-memory SQLite database, fake geocoding responses, and
Maps API test doubles. They do not create real tariffs, pharmacies, or orders.
