# concrete-cms-rts-cinemasource-blocks

Concrete CMS package for **Cinema Source (Webedia)** showtime data and **RTS POS** online ticketing.

## Installation (Concrete CMS 9)

1. Copy the `packages/rts_cinema_source` directory into your Concrete site's `packages/` folder.
2. In the Concrete dashboard, go to **Extend → Add Functionality** and install **RTS Cinema Source**.
3. Open **Dashboard → RTS Cinema Source** and enter your Cinema Source and RTS credentials.
4. Add blocks to your pages:
   - **West World Media** — required on any page that uses the other blocks (builds listing cache + checkout modal)
   - **Movie Listing** / **Movie Gallery** — current showtimes
   - **Movie Listing Soon** / **Movie Gallery Soon** — coming soon

## Package structure

```
packages/rts_cinema_source/
├── controller.php              # Package install, routes, services
├── config/cinema_source.php    # Default settings
├── blocks/                     # Five custom blocks
├── controllers/single_page/    # Dashboard settings controller
├── single_pages/dashboard/     # Dashboard settings view
└── src/
    ├── RouteList.php           # API route registration
    ├── Api/Controller/         # RTS proxy endpoints
    └── Service/                # Config + RTS client + barcode
```

## Dashboard settings

All credentials and checkout URLs are configured at **Dashboard → RTS Cinema Source**:

- Cinema Source API key, version, house ID
- RTS host, port, username, password, sandbox mode
- Payment complete URL, return URL, convenience fee

Settings are stored in Concrete's generated config overrides (`application/config/generated_overrides/rts_cinema_source/`).

## Package API routes

The legacy `/rts/*.php` scripts are replaced by package routes:

| Route | Purpose |
|-------|---------|
| `POST /api/rts_cinema_source/proxy` | RTS XML proxy |
| `POST /api/rts_cinema_source/session` | Checkout session storage |
| `GET /api/rts_cinema_source/redirect` | Payment processor redirect |
| `POST /api/rts_cinema_source/complete` | Payment callback + ticket purchase |
| `GET /api/rts_cinema_source/barcode` | Ticket barcode image |

The West World Media block writes these URLs into `listingcache.js` as `rtsConfig` for front-end checkout JavaScript.

## Cinema Source API

```
https://webservice.cinema-source.com/{version}/?apikey={key}&query=...
```

Default API version is **4.0** (configure the version assigned by Webedia).

## License

MIT — see [license](license)
