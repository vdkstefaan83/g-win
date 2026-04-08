# G‑Win Developer Documentation

Technische documentatie voor ontwikkelaars. Zie `README.md` voor een algemeen overzicht.

---

## Inhoudsopgave

1. [Architectuur](#architectuur)
2. [Multi-site systeem](#multi-site-systeem)
3. [Meertalig systeem (NL/FR)](#meertalig-systeem-nlfr)
4. [Afsprakensysteem](#afsprakensysteem)
5. [Betaalflow](#betaalflow)
6. [Mail & SMS Templates](#mail--sms-templates)
7. [Blokken systeem](#blokken-systeem)
8. [Webshop](#webshop)
9. [SEO](#seo)
10. [Bestanden uploaden](#bestanden-uploaden)
11. [Cron jobs](#cron-jobs)
12. [Database schema](#database-schema)
13. [Routing](#routing)
14. [Middleware](#middleware)
15. [Veelvoorkomende patronen](#veelvoorkomende-patronen)

---

## Architectuur

### MVC Framework

```
Request → public/index.php → core/App.php
  → Middleware (CSRF, Auth)
  → Router (Bramus) matches route
  → Controller method called
  → Model queries database (PDO)
  → Controller calls render()
  → Twig renders template
  → Response sent
```

### Base Controller (`core/Controller.php`)

Elke controller extends `Core\Controller` en heeft toegang tot:

```php
$this->render('template.twig', $data)  // Render Twig template
$this->json($data, $status)            // JSON response
$this->redirect($url)                  // HTTP redirect
$this->back()                          // Redirect naar referer
$this->input($key, $default)           // $_POST of $_GET waarde
$this->validate($rules)               // Validatie (required|max:255|email)
$this->isPost()                        // Check POST method
$this->isAjax()                        // Check XMLHttpRequest
```

De `render()` methode injecteert automatisch:
- `site`, `layout`, `lang`, `csrf_token`, `csrf_field`
- `header_menu`, `footer_menu`, `services_menu` (front-end only)
- `contact_address`, `contact_phone`, `contact_email`, `site_description`
- `nl_url`, `fr_url`, `canonical_url`, `site_url`
- `shop_enabled`, `current_user`, `flash`

### Base Model (`core/Model.php`)

Elke model extends `Core\Model` en heeft:

```php
$this->findAll($orderBy, $direction)      // Alle records
$this->findById($id)                      // Eén record op ID
$this->findBy($column, $value)            // Eén record op kolom
$this->findAllBy($column, $value)         // Meerdere records op kolom
$this->create($data)                      // INSERT, returns ID
$this->update($id, $data)                 // UPDATE
$this->delete($id)                        // DELETE
$this->query($sql, $params)              // Raw PDO query
$this->count($column, $where, $params)    // COUNT
```

---

## Multi-site systeem

### Hoe het werkt

1. **Request binnenkomt** → `LangResolver` detecteert taal uit URL prefix
2. **`SiteResolver`** bepaalt de site op basis van het domein (`$_SERVER['HTTP_HOST']`)
3. Site config wordt geladen uit `app/Config/sites.php` (fallback) en `sites` tabel (database)
4. **Layout** wordt bepaald door `sites.layout` kolom
5. **Menu's** worden geladen per site + locatie + taal
6. **Content** (blokken, producten, afspraaktypes) wordt gefilterd op site via pivot tabellen

### Site-gerelateerde tabellen

```
sites                    → id, name, slug, domain, layout
site_domains             → site_id, domain, default_lang, is_primary
block_sites              → block_id, site_id
menu_sites               → menu_id, site_id
product_sites            → product_id, site_id
appointment_type_sites   → appointment_type_id, site_id
page_sites               → page_id, site_id
```

### Filtering logica

- **G‑Win** (slug `gwin`): toont altijd ALLES (geen filtering)
- **Andere sites**: tonen alleen gekoppelde items via pivot tabel

```php
// In ShopController:
$siteFilter = ($dbSite['slug'] !== 'gwin') ? (int)$dbSite['id'] : null;
$products = $productModel->getActive($lang, 'sort_order', 'ASC', $siteFilter);
// null = geen filter (alle producten), int = filter op site_id
```

### Nieuwe site toevoegen

1. Admin → Sites → Nieuw
2. Vul naam, slug, domein, layout in
3. Koppel domeinen (meerdere mogelijk, met standaardtaal per domein)
4. Koppel content: menu's, blokken, producten, afspraaktypes
5. Optioneel: maak nieuwe layout in `views/layouts/{naam}/`

---

## Meertalig systeem (NL/FR)

### URL structuur

```
NL: /zwangerschapsbeeldjes          (geen prefix)
FR: /fr/sculptures-de-grossesse     (/fr/ prefix + vertaalde slug)
```

### translation_of patroon

NL record is altijd de **master** (`translation_of = NULL`).
FR record verwijst naar NL via `translation_of = NL.id`.

```
pages:
  id=3  lang=nl  translation_of=NULL  slug=zwangerschapsbeeldjes    ← MASTER
  id=13 lang=fr  translation_of=3     slug=sculptures-de-grossesse  ← VERTALING
```

Dit patroon wordt gebruikt door: `pages`, `page_categories`, `blocks`, `products`, `menus`.

### Automatische slug-vertaling

**In menu's** (`Menu::getByLocationAndSite()`):
- Bij NL→FR: zoekt FR vertaling van page_id en category slugs
- Werkt voor zowel NL fallback menus als eigen FR menus met NL slugs

**In taalswitch** (`Controller::resolveAlternateUrl()`):
- Zoekt de vertaalde slug in `pages` en `page_categories`
- Ondersteunt `/slug` en `/category/page` patronen
- Template variabelen: `nl_url`, `fr_url`

### FR content fallback

Als FR content ontbreekt, wordt NL content als fallback gebruikt:

- **Blokken**: NL blokken getoond, FR vertalingen preferred via `translation_of`
- **Afbeeldingen**: FR erft `intro_image` van NL master via `COALESCE()`
- **Page images**: FR pagina zonder eigen images → NL master images
- **Menu's**: geen FR menu → NL menu met auto-vertaalde slugs

---

## Afsprakensysteem

### Database structuur

```
appointment_types          → id, slug, name_nl/fr, description_nl/fr, is_active
appointment_flow_steps     → id, appointment_type_id, step_type, config (JSON), sort_order
appointment_slots          → id, day_of_week, start_time, end_time, type, appointment_type_id
appointments               → id, customer_id, type, date, start_time, end_time, status, payment_status
appointment_date_proposals → id, appointment_id, proposed_date, proposed_time, is_selected
appointment_notifications  → id, appointment_id, type, channel, sent_at, status
appointment_type_sites     → appointment_type_id, site_id
```

### Flow systeem

Elk afspraaktype heeft configureerbare stappen (`appointment_flow_steps`):

```
Zwangerschap:  date_picker(za) → time_picker → details_form → [send_email] → [payment]
Kind:          date_picker(zo) → details_form → [send_email] → [payment]
Afhalen:       date_proposals(3) → details_form → [send_email]
```

Stappen met `[]` zijn backend-only (niet zichtbaar voor klant).

### Step types en config

| Step type | Config JSON | Beschrijving |
|-----------|------------|-------------|
| `date_picker` | `{"day_of_week": 6}` | Kalender, 6=zaterdag, null=alle |
| `time_picker` | `{}` | Slots uit appointment_slots |
| `date_proposals` | `{"num_proposals": 3}` | Klant stelt N datums voor |
| `details_form` | `{}` | Naam, email, telefoon, notities |
| `send_email` | `{"template_slug": "payment_request", "trigger": "on_confirm", "send_sms": true}` | Template + trigger moment |
| `payment` | `{"deposit_from_settings": true}` | Mollie voorschot |

### Triggers voor send_email stap

| Trigger | Wanneer |
|---------|---------|
| `on_submit` | Klant verstuurt formulier |
| `on_confirm` | Admin bevestigt afspraak |
| `on_payment` | Klant betaalt voorschot |
| `on_reminder` | X dagen voor afspraak (cron) |
| `on_overdue` | Betaaldeadline verstreken (cron) |
| `on_cancel` | Afspraak geannuleerd |

### Datum blokkering

Opgeslagen in `settings` als JSON (`blocked_dates`):

```json
[
  {"date": "2026-04-12", "period": "hele_dag", "reason": "Paasvakantie"},
  {"date": "2026-04-13", "period": "voormiddag", "reason": "Dokter"},
  {"date": "2026-04-14", "period": "namiddag", "reason": ""}
]
```

- `hele_dag`: datum niet selecteerbaar in kalender
- `voormiddag`: uren voor 13:00 geblokkeerd
- `namiddag`: uren vanaf 13:00 geblokkeerd
- Datumbereik: admin kan van-tot selecteren, alle dagen ertussen worden geblokkeerd

### Appointment status flow

```
                    ┌─ cancelled
                    │
pending ──→ confirmed ──→ completed
  │              │
  └── cancelled  └── cancelled
```

### Payment status flow

```
none ──→ pending ──→ paid
           │
           └──→ overdue ──→ cancelled (automatisch via cron)
```

### Front-end flow (`views/front/appointments/flow.twig`)

Alpine.js multi-step formulier:
1. Ontvangt `flow_steps` als JSON via `<script>` tag
2. `currentStep` index navigeert door stappen
3. Per `step_type` rendert het juiste component
4. `date_picker`: kalender met `calDays()` functie, checkt blocked dates
5. `time_picker`: AJAX fetch naar `/api/appointment-slots`
6. `date_proposals`: mini-kalenders per voorstel, uur dropdown gefilterd op blocked periods
7. `details_form`: intl-tel-input voor telefoonnummer
8. Submit: dynamisch form aangemaakt en gepost

### Afhalen flow (date_proposals)

1. Klant kiest N datums + uren (mini-kalender per voorstel)
2. Geblokkeerde datums niet selecteerbaar
3. Voormiddag/namiddag blokkering filtert beschikbare uren
4. Opgeslagen in `appointment_date_proposals` tabel
5. Admin ziet voorstellen in `/admin/appointments/{id}`
6. Admin klikt "Selecteer" bij gewenste datum
7. Afspraak wordt bevestigd met gekozen datum/tijd

---

## Betaalflow

### Mollie integratie

**Configuratie** in `.env`:
```
MOLLIE_API_KEY=live_xxxxx
MOLLIE_WEBHOOK_URL=https://gwin.vanderkerken.com/webhook/mollie
```

### Webshop betalingen

```
Klant vult checkout in
  → CheckoutService::createOrder()
  → PaymentService::createPayment() → Mollie API
  → Redirect naar Mollie checkout
  → Klant betaalt
  → Mollie POST /webhook/mollie
  → PaymentService::handleWebhook()
  → Order status → 'paid'
  → MailService::sendOrderConfirmation()
```

### Afspraak betalingen

```
Admin klikt "Bevestigen" in /admin/appointments/{id}
  → AppointmentPaymentService::createPaymentRequest()
  → Genereert payment_token + deadline
  → AppointmentNotificationService::sendPaymentRequest() (email + SMS)
  → Klant ontvangt betaallink

Klant klikt betaallink
  → AppointmentController::pay($token)
  → AppointmentPaymentService::initiatePayment() → Mollie API
  → Redirect naar Mollie checkout
  → Klant betaalt
  → Mollie POST /webhook/mollie
  → AppointmentPaymentService::handleWebhook()
  → Appointment status → 'confirmed', payment_status → 'paid'
  → sendPaymentConfirmation() (email met ICS bijlage + SMS)
```

### Payments tabel

```sql
payments:
  id, order_id, appointment_id, mollie_id, amount, status, payment_type, paid_at
  -- payment_type: 'order' of 'appointment'
  -- status: 'open', 'paid', 'failed', 'cancelled'
```

### Webhook route

`POST /webhook/mollie` is uitgezonderd van CSRF (`CsrfMiddleware::$excludedPaths`).

---

## Mail & SMS Templates

### Database structuur

```sql
mail_templates:
  id, name, slug, subject_nl, subject_fr, body_nl, body_fr, sms_nl, sms_fr, available_variables, is_active
```

### Variabelen

Beschikbaar in templates als `{variabele_naam}`:

| Variabele | Beschrijving |
|-----------|-------------|
| `{voornaam}` | Voornaam klant |
| `{achternaam}` | Achternaam klant |
| `{datum}` | Afspraak datum (dd/mm/yyyy) |
| `{tijdstip}` | Starttijd (HH:MM) |
| `{tijdstip_zin}` | " om 14:00" of leeg |
| `{type}` | Afspraaktype naam (vertaald) |
| `{bedrag}` | Voorschotbedrag (€50,00) |
| `{deadline}` | Betaaldeadline (dd/mm/yyyy) |
| `{betaallink}` | Volledige betaal URL |
| `{bestelnummer}` | Bestelnummer (#1234) |
| `{totaal}` | Besteltotaal |
| `{bestel_items}` | HTML tabel met bestelregels |

### Template rendering

```php
// In code:
$rendered = MailTemplate::renderTemplate('payment_request', $lang, $variables);
// Returns: ['subject' => '...', 'body' => '...', 'sms' => '...']
// Of false als template niet gevonden

// Variabelen worden vervangen:
// "Beste {voornaam}" → "Beste Stefaan"
// Ongebruikte variabelen worden verwijderd
```

### Standaard templates

| Slug | Trigger | E-mail | SMS |
|------|---------|--------|-----|
| `payment_request` | Admin bevestigt | Betaalverzoek met link | Kort bericht met link |
| `payment_reminder` | Cron: deadline verstreken | Herinnering met nieuwe deadline | Herinnering |
| `payment_confirmed` | Webhook: betaling ontvangen | Bevestiging + ICS bijlage | Bevestiging |
| `cancellation` | Cron: reminder deadline verstreken | Annulering | Annulering |
| `pre_appointment_reminder` | Cron: X dagen voor afspraak | Herinnering met praktische info | Herinnering |
| `order_confirmation` | Webhook: bestelling betaald | Bevestiging met productlijst | — |

### NotificationService flow

```php
// Voorbeeld: betaalverzoek versturen
$notificationService->sendPaymentRequest($appointment, $paymentUrl);

// Intern:
// 1. buildVariables() → bouwt alle template variabelen
// 2. sendTemplatedEmail() → laadt template uit DB, rendert, stuurt
// 3. sendTemplatedSms() → zelfde template, SMS veld
// 4. logNotification() → logt in appointment_notifications tabel
```

### ICS kalender bijlage

Bij `payment_confirmed` wordt een ICS bestand meegezonden:

```php
$ics = MailService::generateIcs($appointment);
MailService::sendWithAttachment($email, $subject, $body, $ics, 'afspraak-gwin.ics');
```

ICS bevat: datum, tijd, locatie (Duivenstuk 4, Bavikhove), type als titel.

---

## Blokken systeem

### Block types en rendering

Blokken worden opgeslagen in `blocks` tabel met `type` ENUM:

```
hero, feature, text, cta, gallery, youtube, vimeo, sketchfab
```

### Toewijzing

Een blok kan worden toegewezen aan:
- **Homepage**: `page_id = NULL, page_category_id = NULL`
- **Specifieke pagina**: `page_id = {id}`
- **Paginacategorie**: `page_category_id = {id}`

### Laden per context

```php
// Homepage (per site):
$blockModel->getActiveBySite($siteId, $lang);

// Specifieke pagina:
$blockModel->getActiveByPage($pageId, $lang);

// Categorie:
$blockModel->getActiveByCategory($categoryId, $lang);
```

### NL/FR vertaling van blokken

Alle methodes starten met NL master blokken (`translation_of IS NULL`), zoeken per blok de FR vertaling, en erven image/link_url/options over:

```php
// Pseudo:
foreach ($nlBlocks as $nlBlock) {
    $frBlock = findTranslation($nlBlock['id'], 'fr');
    if ($frBlock) {
        // Gebruik FR tekst, erf image van NL
        if (empty($frBlock['image'])) $frBlock['image'] = $nlBlock['image'];
        $results[] = $frBlock;
    } else {
        $results[] = $nlBlock; // Fallback
    }
}
```

### Homepage rendering

`views/front/home/index.twig` groepeert opeenvolgende blokken van hetzelfde type en rendert ze in **sort_order volgorde** (niet per type-groep).

### Video/embed blokken

`views/components/block-video.twig` handelt YouTube, Vimeo en Sketchfab af:
- Extraheert video ID uit diverse URL formaten
- Voegt parameters toe op basis van `options` JSON (autoplay, muted, loop, autospin)
- Sketchfab autoplay alleen als `sketchfab_premium` setting aan staat

### Options JSON per type

```json
// Hero:
{"show_appointment_btn": true, "show_shop_btn": true}

// YouTube/Vimeo:
{"autoplay": true, "muted": true, "loop": false}

// Sketchfab:
{"autoplay": true, "autospin": true}
```

---

## Webshop

### Winkelwagen

Sessie-gebaseerd via `CartService`:

```php
// AJAX endpoints:
POST /cart/add       → CartController::add()      // product_id, quantity
POST /cart/update    → CartController::update()    // item_id, quantity
POST /cart/remove    → CartController::remove()    // item_id
GET  /winkelwagen    → CartController::index()     // Toon winkelwagen
```

### Checkout flow

```
/afrekenen (GET) → CheckoutController::index()
  → Toon formulier (naam, adres, betaalmethode)

/afrekenen (POST) → CheckoutController::store()
  → Validatie
  → Customer aanmaken/updaten
  → Order aanmaken met items
  → PaymentService::createPayment() → Mollie
  → Redirect naar Mollie checkout

/checkout/success → Bedankpagina
/checkout/cancel  → Annuleringspagina
```

### Product site-filtering

```php
// Product model:
public function getActive($lang, $orderBy, $direction, ?int $siteId = null)
// Als $siteId niet null: INNER JOIN product_sites
// G-Win site (slug 'gwin'): $siteId = null → geen filter
```

---

## SEO

### Automatisch gegenereerd (per pagina)

Via `views/components/seo-head.twig` (geïncludeerd in alle layouts):

```html
<meta name="description" content="...">
<link rel="canonical" href="https://...">
<link rel="alternate" hreflang="nl" href="https://...">
<link rel="alternate" hreflang="fr" href="https://...">
<link rel="alternate" hreflang="x-default" href="https://...">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="...">
<meta property="og:url" content="...">
<meta name="twitter:card" content="summary_large_image">
```

### Structured Data

Via `views/components/seo-jsonld.twig`:
- **LocalBusiness** schema (altijd)
- **BreadcrumbList** (als breadcrumbs beschikbaar)
- **Product** schema (op productpagina's, in `shop/show.twig`)

### Sitemap

`/sitemap.xml` → `SitemapController::index()`
- Dynamisch gegenereerd
- Alle pagina's, categorieën, producten
- Hreflang alternates (NL/FR)

### G‑Win no-break

In `Controller::render()` wordt "G-Win" automatisch vervangen door "G‑Win" (non-breaking hyphen U+2011) in alle HTML output, zodat het nooit over twee regels gesplitst wordt.

---

## Bestanden uploaden

### FileUpload helper (`core/Helpers/FileUpload.php`)

```php
FileUpload::uploadImage($file, 'pages')   // Upload naar public/uploads/pages/
// Returns: random hex filename (e.g. "a1b2c3d4.jpg") of false
// Validatie: jpeg/png/gif/webp, max 5MB

FileUpload::delete('pages/a1b2c3d4.jpg')  // Verwijdert bestand
```

### Upload directories

```
public/uploads/
├── pages/         # Pagina afbeeldingen + block images
└── products/      # Product afbeeldingen
```

### Logo's

```
public/assets/images/
├── {layout}_nl_liggend.png    # NL logo per layout
├── {layout}_fr_liggend.png    # FR logo (optioneel, fallback naar NL)
├── {layout}_liggend.png       # Taal-onafhankelijk logo (fallback)
└── logogwin.png               # Favicon
```

Logo resolution volgorde in `site_logo()`:
1. `{layout}_{lang}_liggend.png`
2. `{layout}_{lang}_logo_liggend.png`
3. `{layout}_liggend.png` (zonder taal)

Cache-busting via `?v={filemtime}`.

---

## Cron jobs

### Setup

```bash
*/15 * * * * php /pad/naar/g-win/public/cron.php appointments
```

### AppointmentCronHandler

```php
checkPaymentDeadlines()
// Vindt appointments met verlopen payment_deadline (geen reminder gestuurd)
// → Stuurt herinnering email + SMS
// → Zet payment_status = 'overdue'
// → Berekent nieuwe deadline (+ extra dagen uit settings)

checkReminderDeadlines()
// Vindt appointments met verlopen reminder_deadline
// → Annuleert afspraak
// → Stuurt annuleringsmail + SMS
// → Released het tijdslot

sendPreAppointmentReminders()
// Vindt bevestigde appointments binnen X dagen (uit settings)
// → Stuurt herinnering email + SMS met praktische info
```

---

## Database schema

### Kern tabellen

| Tabel | Relaties | Beschrijving |
|-------|----------|-------------|
| `sites` | → site_domains, *_sites | Multi-site configuratie |
| `pages` | → page_images, page_sites | CMS pagina's met NL/FR |
| `page_categories` | → pages | Hiërarchische categorieën |
| `blocks` | → block_sites | Content blokken per site/pagina |
| `menus` | → menu_items, menu_sites | Navigatie per site/locatie |
| `products` | → product_images, product_sites | Webshop producten |
| `categories` | → products | Product categorieën |
| `customers` | → appointments, orders | Klantgegevens |
| `appointments` | → appointment_notifications, date_proposals | Afspraken |
| `appointment_types` | → flow_steps, slots, type_sites | Configureerbare types |
| `orders` | → order_items, payments | Bestellingen |
| `payments` | — | Mollie transacties |
| `mail_templates` | — | E-mail + SMS templates |
| `settings` | — | Systeeminstellingen |
| `users` | — | Admin gebruikers |

### Pivot tabellen

| Tabel | Koppelt |
|-------|---------|
| `page_sites` | pages ↔ sites |
| `block_sites` | blocks ↔ sites |
| `menu_sites` | menus ↔ sites |
| `product_sites` | products ↔ sites |
| `appointment_type_sites` | appointment_types ↔ sites |

---

## Routing

### Conventie

```php
GET  /admin/{resource}              → index()
GET  /admin/{resource}/create       → create()
POST /admin/{resource}/store        → store()
GET  /admin/{resource}/{id}/edit    → edit($id)
POST /admin/{resource}/{id}/update  → update($id)
POST /admin/{resource}/{id}/delete  → destroy($id)
POST /admin/{resource}/reorder      → reorder()  // AJAX, JSON body
```

### Route volgorde

Specifieke routes MOETEN boven dynamische `{id}` routes staan:

```php
// GOED:
$router->post('/admin/pages/images/delete', ...);  // Specifiek
$router->post('/admin/pages/reorder', ...);         // Specifiek
$router->get('/admin/pages/{id}/edit', ...);         // Dynamisch

// FOUT: {id} vangt "images" op als ID
$router->get('/admin/pages/{id}/edit', ...);
$router->post('/admin/pages/images/delete', ...);  // Wordt nooit bereikt!
```

### Front-end catch-all routes

Onderaan in routes.php staan catch-all routes voor pagina's/categorieën:

```php
$router->get('/{category}/{page}', ...);  // Categorie + subpagina
$router->get('/{slug}', ...);              // Categorie of standalone pagina
```

---

## Middleware

### CSRF (`CsrfMiddleware`)

- Verplicht voor alle POST requests
- Token via `$_POST['_csrf_token']` of `X-CSRF-TOKEN` header
- Uitgezonderd: `/webhook/mollie`

### Admin Auth

- Sessie-gebaseerd
- `Auth::user()` retourneert huidige gebruiker of null
- Admin routes controleren authenticatie in elke controller

---

## Veelvoorkomende patronen

### Twig JSON in HTML attributen

**PROBLEEM**: Twig escaped `"` naar `&quot;` in HTML attributen.

**FOUT**:
```twig
<div x-data="{ items: {{ items|json_encode|raw }} }">
```

**GOED**:
```html
<script>var _items = [{% for item in items %}{...}{% endfor %}];</script>
<div x-data="{ items: _items }">
```

### NL/FR blok loading

Altijd starten met NL master blokken, dan FR vertaling zoeken:

```php
$nlBlocks = query("WHERE lang = 'nl' AND translation_of IS NULL");
foreach ($nlBlocks as $nl) {
    $fr = query("WHERE translation_of = {$nl['id']} AND lang = 'fr'");
    $results[] = $fr ?: $nl;
}
```

### Options JSON decodering

De `options` kolom bevat JSON maar komt als string uit de database. Decodering via `decodeOptions()` in het Block model of handmatig in controllers.

### Drag-and-drop sortering

Patroon voor alle CRUD lijsten:

```html
<!-- Template: -->
<tbody id="sortableList">
    <tr data-id="{{ item.id }}">...</tr>
</tbody>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
new Sortable(document.getElementById('sortableList'), {
    animation: 150,
    onEnd: function() {
        var order = [...document.querySelectorAll('#sortableList tr[data-id]')]
            .map(r => parseInt(r.dataset.id));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/{resource}/reorder', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token }}');
        xhr.send(JSON.stringify(order));
    }
});
</script>
```

```php
// Controller:
public function reorder(): void {
    $items = json_decode(file_get_contents('php://input'), true);
    foreach ($items as $index => $id) {
        $this->model->update((int)$id, ['sort_order' => $index]);
    }
    $this->json(['success' => true]);
}
```
