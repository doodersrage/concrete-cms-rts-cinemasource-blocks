# concrete-cms-rts-cinemasource-blocks

Collection of blocks for interfacing with RTS POS and the Cinema Source (Webedia) API.

Allows purchasing tickets through the RTS POS API while Cinema Source supplies extended movie metadata and show schedules.

**Compatible with Concrete CMS 9.x** (PHP 8.1+). Also works as application-level custom blocks in Concrete 8.x.

## Requirements

- Concrete CMS 9.x
- PHP 8.1+ with cURL enabled
- Cinema Source / Webedia API credentials (`api_key`, `house_id`, API version)
- RTS POS credentials (host, username, password)

## Installation

1. Copy the repository contents into your Concrete site root, preserving the directory structure:
   - `application/blocks/` — custom blocks
   - `application/config/` — site configuration
   - `rts/` — standalone RTS proxy scripts (web-accessible)

2. Configure credentials in **`application/config/cinema_source.php`**:

```php
return [
    'cinema_source' => [
        'base_url' => 'https://webservice.cinema-source.com',
        'api_version' => '4.0',  // use the version assigned by Cinema Source
        'api_key' => 'YOUR_API_KEY',
        'house_id' => 'YOUR_HOUSE_ID',
    ],
    'rts' => [
        'host' => '72352.formovietickets.com',
        'port' => 2235,
        'username' => 'YOUR_RTS_USERNAME',
        'password' => 'YOUR_RTS_PASSWORD',
        'use_sandbox' => false,
    ],
    'site' => [
        'process_complete_url' => 'https://yoursite.com/rts/procComp.php',
        'return_url' => 'https://yoursite.com/showtimes',
        'conv_fee' => 1.35,
    ],
];
```

3. Optionally copy **`rts/config.sample.php`** to **`rts/config.php`** if the RTS scripts run outside Concrete's config path.

4. Install blocks from the Concrete dashboard (**Blocks → Install Block**) or refresh block types.

5. Add the **West World Media** block to a page. It fetches Cinema Source + RTS data and writes `application/files/listingcache.js`. Other listing/gallery blocks consume that cache.

## Blocks

| Block | Purpose |
|-------|---------|
| West World Media | Fetches Cinema Source + RTS data, caches JSON for front-end scripts |
| Movie Listing | Current showtimes listing |
| Movie Listing Soon | Coming-soon listings |
| Movie Gallery | Carousel gallery with buy links |
| Movie Gallery Soon | Coming-soon carousel |

Block-level settings on **West World Media** override values from `cinema_source.php`.

## Cinema Source API

The integration uses the Webedia/Cinema Source XML webservice:

```
https://webservice.cinema-source.com/{version}/?apikey={key}&query=...
```

Supported queries used by this project:

- `query=theater&schedule=yes&house_id={id}&showdate={Ymd}&enddate={Ymd}`
- `query=movie&stars=yes&photos=all&movie_id={id}`

Set **`api_version`** to the version assigned by Cinema Source (typically **4.0** for current accounts; older accounts may still use 3.8).

## RTS POS

The `/rts/` scripts proxy XML requests to RTS:

- `req.php` — general RTS XML proxy (used by checkout JavaScript)
- `sess.php` — checkout session storage
- `redir.php` — payment processor redirect
- `procComp.php` — payment completion and ticket purchase
- `barcode.php` — ticket barcode images

## Upgrading from Concrete 5.7

Key changes in this version:

- Replaced deprecated `Loader::` calls with `$this->app->make()`
- PHP 8 compatibility (`ereg_replace` removed, typed properties)
- Cinema Source API over HTTPS with configurable version (default 4.0)
- Credentials moved to config files instead of hardcoded in source
- RTS URLs and convenience fee exposed via `rtsConfig` in `listingcache.js`

After upgrading, clear the expensive cache and delete `application/files/listingcache.js` to force a refresh.

## License

MIT — see [license](license)
