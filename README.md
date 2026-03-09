# G-Win - 3D Scanning & Sculpting Platform

Multi-site PHP MVC applicatie voor 3D scanning & sculpting met e-commerce, afsprakensysteem en CMS.

## Features

- **Multi-site architectuur** - Meerdere domeinen vanuit één codebase, elk met eigen layout, menu's en homepage blokken
- **Afsprakensysteem** - Zwangerschapsbeeldjes (zaterdag) en beeldjes met kind (zondag) met tijdsloten, blokkering en bevestigingsflow
- **Webshop** - Producten met meerdere afbeeldingen, categorieën, winkelwagen, afrekenen via Bancontact of PayPal (Mollie)
- **CMS** - Pagina's, menu's en homepage blokken beheren per site via WYSIWYG editor (Quill)
- **Admin panel** - Gedeeld admin panel voor alle sites met CRUD voor alle entiteiten en AJAX filtering
- **Google Calendar** - OAuth2 integratie voor automatische synchronisatie van afspraken
- **Klantenbeheer** - Automatisch aangemaakt bij het inplannen van een afspraak

## Tech Stack

| Component | Technologie |
|-----------|-------------|
| Backend | PHP 8.1+ |
| MVC Framework | Custom |
| Templating | Twig 3.x |
| Router | Bramus Router |
| Database | MySQL met PDO |
| Frontend | Tailwind CSS (CDN), Alpine.js |
| WYSIWYG | Quill Editor |
| Betalingen | Mollie (Bancontact, PayPal) |
| Kalender | Google Calendar API (OAuth2) |

## Installatie

### Vereisten

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache met mod_rewrite (of Nginx)

### Stappen

1. **Clone de repository**
   ```bash
   git clone git@github.com:vdkstefaan83/g-win.git
   cd g-win
   ```

2. **Installeer dependencies**
   ```bash
   composer install
   ```

3. **Configuratie**
   ```bash
   cp .env.example .env
   ```
   Pas `.env` aan met je database credentials en overige instellingen.

4. **Database aanmaken**
   ```bash
   mysql -u root -p -e "CREATE DATABASE gwin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p gwin < database/schema.sql
   mysql -u root -p gwin < database/seeds/001_sites.sql
   mysql -u root -p gwin < database/seeds/002_users.sql
   mysql -u root -p gwin < database/seeds/003_appointment_slots.sql
   mysql -u root -p gwin < database/seeds/004_categories.sql
   ```

5. **Webserver configureren**

   De document root moet verwijzen naar de `public/` map.

   **Apache vhost voorbeeld:**
   ```apache
   <VirtualHost *:80>
       ServerName g-win.local
       DocumentRoot /path/to/g-win/public
       <Directory /path/to/g-win/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

6. **Schrijfrechten instellen**
   ```bash
   chmod -R 775 storage/
   chmod -R 775 public/uploads/
   ```

## Gebruik

### Admin Panel

Ga naar `/admin/login` en log in met:

| Veld | Waarde |
|------|--------|
| E-mail | `admin@g-win.be` |
| Wachtwoord | `password` |

> **Wijzig het standaard wachtwoord na eerste login via Admin > Gebruikers.**

### Afspraken

| Type | Dag | Tijdsloten |
|------|-----|------------|
| Zwangerschapsbeeldje | Zaterdag | 11:00 - 12:15 - 13:30 - 14:45 - 16:00 - 17:15 |
| Beeldje met kind | Zondag | Dag selecteren, admin bevestigt tijdstip |

- **Oranje** = in optie (ingeboekt, nog niet bevestigd)
- **Groen** = bevestigd
- Admin kan zaterdagen blokkeren voor privé aangelegenheden

### Betalingen configureren

Maak een account aan bij [Mollie](https://www.mollie.com/) en vul de API key in `.env`:

```env
MOLLIE_API_KEY=test_xxxxxxxxxxxxxxxxxxxxxxxx
MOLLIE_WEBHOOK_URL=https://jouwdomein.be/webhook/mollie
```

### Google Calendar koppelen

1. Maak een project aan in [Google Cloud Console](https://console.cloud.google.com/)
2. Activeer de Google Calendar API
3. Maak OAuth2 credentials aan (Web Application)
4. Vul de credentials in `.env`:
   ```env
   GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxxx
   GOOGLE_REDIRECT_URI=https://jouwdomein.be/admin/google-calendar/callback
   ```
5. Ga naar Admin > Google Calendar en klik op **Koppelen**

## Projectstructuur

```
g-win/
├── app/
│   ├── Config/          # Routes, sites, database config
│   ├── Controllers/
│   │   ├── Admin/       # 14 admin controllers
│   │   └── Front/       # 8 front-end controllers
│   ├── Models/          # 18 Eloquent-style models
│   └── Services/        # Payment, Calendar, Mail, Cart services
├── core/                # MVC framework kern
│   ├── App.php          # Bootstrap
│   ├── Controller.php   # Base controller
│   ├── Model.php        # Base model met CRUD
│   ├── Middleware/       # Auth, Admin, CSRF
│   └── Helpers/         # SiteResolver, FileUpload, Flash, Redirect
├── database/
│   ├── schema.sql       # Volledig database schema (18 tabellen)
│   └── seeds/           # Seed data
├── public/              # Web root
│   ├── index.php        # Single entry point
│   ├── assets/          # CSS, JS
│   └── uploads/         # Geüploade afbeeldingen
├── storage/             # Cache, logs, tokens
└── views/               # Twig templates
    ├── layouts/         # Per-site layouts + admin layout
    ├── components/      # Herbruikbare componenten
    ├── front/           # Front-end pagina's
    ├── admin/           # Admin pagina's
    └── errors/          # 404, 500
```

## Multi-site configuratie

Voeg een nieuwe site toe via Admin > Sites, en configureer de domein-mapping in `app/Config/sites.php`:

```php
return [
    'www.g-win.be'      => ['slug' => 'gwin', 'name' => 'G-Win', 'layout' => 'gwin'],
    'www.anderesite.be'  => ['slug' => 'andere', 'name' => 'Andere Site', 'layout' => 'andere'],
];
```

Maak een nieuwe layout aan in `views/layouts/andere/` met `base.twig`, `header.twig` en `footer.twig`.

## Beveiliging

- CSRF tokens verplicht voor alle POST requests
- Wachtwoorden gehashed met `password_hash()` (bcrypt)
- Session-based authenticatie met secure cookie settings
- Prepared statements (PDO) voor alle database queries
- File upload validatie (type, grootte)
