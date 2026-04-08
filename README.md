# G‑Win — Multi-site CMS, E-commerce & Appointment Platform

Multi-site platform voor 3D scanning, sculpting en aanverwante diensten. Eén codebase bedient meerdere domeinen met eigen layout, menu's en content.

## Sites

| Site | Domein | Layout | Beschrijving |
|------|--------|--------|-------------|
| G‑Win | gwin.vanderkerken.com | `gwin` | Hoofdsite — 3D Scanning & Sculpting |
| Zwangerschapsbeeldje | zwangerschap.vanderkerken.com | `zwangerschap` | Zwangerschapsbeeldjes |
| Sterrenkindje | sterrenkindje.vanderkerken.com | `sterrenkindje` | Herdenkingsbeeldjes |
| 3D Awards, Art & Logo | awards.vanderkerken.com | `awards` | Design Awards & gepersonaliseerde kunst |
| 3D-Scannen | scannen.vanderkerken.com | `3dscan` | 3D-scandiensten |

## Tech Stack

| Laag | Technologie |
|------|-------------|
| Backend | PHP 8.1+ custom MVC |
| Templates | Twig 3.x |
| Routing | Bramus Router |
| Database | MySQL 5.7+ (PDO) |
| Frontend | Tailwind CSS (CDN), Alpine.js |
| WYSIWYG | Quill Editor |
| Betalingen | Mollie API (Bancontact, PayPal, iDEAL) |
| Agenda | Google Calendar API (OAuth2) |
| E-mail | PHPMailer SMTP |
| SMS | ClickSend API |
| Drag & Drop | SortableJS |
| Telefoon input | intl-tel-input |

## Projectstructuur

```
g-win/
├── app/
│   ├── Config/                 # routes.php, sites.php, database.php, lang.php
│   ├── Controllers/
│   │   ├── Admin/              # 18 admin controllers (CRUD)
│   │   └── Front/              # 9 front-end controllers
│   ├── Models/                 # 24 data models
│   ├── Services/               # 9 business logic services
│   ├── Helpers/                # DateHelper
│   └── Commands/               # Cron handlers
├── core/                       # MVC framework
│   ├── App.php                 # Bootstrap, Twig setup, routing
│   ├── Controller.php          # Base controller (render, json, redirect, validate)
│   ├── Model.php               # Base model (CRUD, query builder)
│   ├── Database.php            # PDO singleton
│   ├── Auth.php                # Authenticatie
│   ├── Session.php             # Flash messages, sessie
│   ├── Csrf.php                # CSRF token management
│   ├── Helpers/                # FileUpload, SiteResolver, LangResolver
│   └── Middleware/             # Auth, Admin, CSRF middleware
├── database/
│   ├── schema.sql              # Volledige database schema
│   └── migrations/             # 34 incrementele SQL migraties
├── public/                     # Web root (document root)
│   ├── index.php               # Entry point
│   ├── .htaccess               # URL rewriting
│   ├── robots.txt              # Crawler instructies
│   ├── assets/
│   │   ├── css/app.css         # Frontend styles
│   │   ├── js/app.js           # Frontend JavaScript
│   │   └── images/             # Logo's per layout
│   └── uploads/                # User uploads (pages/, products/)
├── storage/
│   ├── cache/twig/             # Twig cache
│   └── logs/                   # Error logs
└── views/
    ├── layouts/                # 6 site themes + admin
    ├── components/             # Herbruikbare componenten (SEO, video, etc.)
    ├── admin/                  # Admin pagina's
    ├── front/                  # Klant-pagina's
    └── errors/                 # 404, 500
```

## Features

### Multi-site
- Eén codebase, meerdere domeinen met eigen layout/kleuren/logo
- Per site configureerbare menu's (header, footer, diensten)
- Per site filterbare producten en afspraaktypes
- G‑Win site toont altijd alles, andere sites tonen alleen gekoppelde items
- Logo's per layout: `{layout}_{lang}_liggend.png` met automatische cache-busting

### Meertalig (NL + FR)
- NL standaard, FR via `/fr/` prefix
- `translation_of` kolom koppelt NL↔FR records
- Automatische slug-vertaling in menu's en taalswitch
- FR pagina's erven afbeeldingen van NL master
- UI-teksten via `app/Config/lang.php`

### Content Management
- **Pagina's** — Quill editor, NL/FR tabs, intro afbeelding, meta SEO
- **Paginacategorieën** — overzichtspagina's met subpagina's
- **Blokken** — toewijsbaar aan homepage, pagina of categorie
- **Drag-and-drop** — pagina's, blokken, producten, menu's
- **Menu's** — NL/FR tabs, 3 locaties, automatische slug-vertaling

### Blok Types

| Type | Beschrijving | Opties |
|------|-------------|--------|
| `hero` | Hoofdbanner | CTA knoppen |
| `feature` | Dienst-kaart | Link URL |
| `gallery` | Portfolio | — |
| `text` | Tekst sectie | — |
| `cta` | Call-to-action | Achtergrond, link |
| `youtube` | YouTube embed | Autoplay, muted, loop |
| `vimeo` | Vimeo embed | Autoplay, muted, loop |
| `sketchfab` | 3D model | Autoplay, autospin (Premium) |

### E-commerce
- Producten met NL/FR, categorieën, meerdere afbeeldingen
- Winkelwagen (sessie-gebaseerd)
- Checkout via Mollie (Bancontact, PayPal, iDEAL)
- Bestelbevestiging per e-mail
- Per site filterbare producten

### Afsprakensysteem
- **Afspraaktypes** met per-site zichtbaarheid
- **Flow Builder** per type:
  - `date_picker` — kalender met dagfilter en blokkering (hele dag/voormiddag/namiddag)
  - `time_picker` — tijdsloten met info tekst
  - `date_proposals` — klant stelt N datums+tijden voor
  - `details_form` — contactgegevens met intl telefoon
  - `send_email` — template + SMS trigger
  - `payment` — Mollie voorschot
- **Tijdslotbeheer** via admin
- **Datum blokkering** — hele dag/voormiddag/namiddag, datumbereik
- **Google Calendar sync** + **ICS bijlage** in bevestigingsmail

### Communicatie
- **Mail templates** — admin beheerbaar, NL/FR, Quill editor
- **SMS templates** — per template, zelfde variabelen
- **Triggers** — indiening, bevestiging, betaling, herinnering, annulering
- **ClickSend SMS** met internationaal telefoonnummer

### SEO
- Meta description, canonical URL, hreflang (NL/FR/x-default)
- Open Graph + Twitter Cards
- JSON-LD (LocalBusiness, Product, BreadcrumbList)
- Dynamische `/sitemap.xml`
- `robots.txt`

## Installatie

```bash
git clone git@github.com:vdkstefaan83/g-win.git
cd g-win
composer install
cp .env.example .env

mysql -u root -p -e "CREATE DATABASE gwin CHARACTER SET utf8mb4;"
mysql -u root -p gwin < database/schema.sql
for f in database/migrations/*.sql; do mysql -u root -p gwin < "$f"; done

chmod -R 775 storage/ public/uploads/
```

Document root → `public/`

### .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gwin.vanderkerken.com

DB_HOST=localhost
DB_NAME=gwin
DB_USER=root
DB_PASS=

SITE_DEFAULT=gwin

MOLLIE_API_KEY=live_xxxxx
MOLLIE_WEBHOOK_URL=https://gwin.vanderkerken.com/webhook/mollie
MOLLIE_REDIRECT_URL=https://gwin.vanderkerken.com

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USER=noreply@g-win.be
MAIL_PASS=
MAIL_FROM=noreply@g-win.be

CLICKSEND_API_USERNAME=
CLICKSEND_API_KEY=
CLICKSEND_SENDER_NAME=G-Win

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://gwin.vanderkerken.com/admin/google-calendar/callback
```

## Admin

Login: `/admin/login`

### Sidebar

| Sectie | Pagina's |
|--------|---------|
| Content | Sites, Pagina's, Paginacategorieën, Menu's, Blokken |
| Afspraken | Afspraken, Tijdsloten, Afspraaktypes, Klanten |
| Webshop | Producten, Categorieën, Bestellingen |
| Communicatie | Mail templates |
| Systeem | Gebruikers, Instellingen, Google Calendar |

### Instellingen (`/admin/settings`)

| Groep | Instelling |
|-------|-----------|
| Site | Beschrijving, Tagline, OG afbeelding |
| Contact | Adres, Telefoon, E-mail |
| Integraties | Sketchfab Premium |
| Afspraken | Max maanden, Tijdslot info NL/FR, Afhalen uren |
| Betaling | Voorschot, Betaaltermijn, Herinneringstermijn |

## Layout Themes

| Layout | Primair | CTA | Hover |
|--------|---------|-----|-------|
| `gwin` | #1e3a8a | #F97316 | #14b8a6 |
| `zwangerschap` | #353f37 | #d69b4b | #a3b8a6 |
| `sterrenkindje` | #6e395e | #d8c3a5 | #eacbe1 |
| `3dscan` | #333333 | #FF6B00 | #14b8a6 |
| `awards` | #333333 | #FF6B00 | #14b8a6 |

Alle layouts definiëren `krijgers`/`krijgers-gold` als Tailwind aliassen.

## Cron

```bash
*/15 * * * * php /pad/naar/g-win/public/cron.php appointments
```

## Beveiliging

- CSRF op alle POST (uitgezonderd Mollie webhook)
- Bcrypt wachtwoorden
- PDO prepared statements
- Twig auto-escaping
- File upload validatie (MIME, max 5MB)

## Licentie

Privé project — alle rechten voorbehouden.
