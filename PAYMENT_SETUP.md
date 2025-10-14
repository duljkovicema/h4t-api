# Konfiguracija plaćanja karticom

## Stripe konfiguracija

### 1. Stripe Account Setup
1. Registrirajte se na [Stripe.com](https://stripe.com)
2. Idite u Dashboard → Developers → API keys
3. Kopirajte **Secret key** (počinje s `sk_test_` za test, `sk_live_` za produkciju)

### 2. Environment Variables
Dodajte u svoj `.env` file ili postavite environment varijablu:

```bash
STRIPE_SECRET_KEY=sk_test_51... # Vaš Stripe secret key
```

### 3. Webhook konfiguracija
1. Idite u Stripe Dashboard → Developers → Webhooks
2. Kliknite "Add endpoint"
3. URL: `https://yourdomain.com/stripe-webhook`
4. Odaberite events: `checkout.session.completed`
5. Kopirajte **Signing secret** (počinje s `whsec_`)

### 4. Ažuriranje baze podataka
Pokrenite SQL skriptu:
```sql
-- Pogledajte update_database_payments.sql
```

## Testiranje

### Test kartice (Stripe)
- **Broj**: 4242 4242 4242 4242
- **Datum**: bilo koji budući datum
- **CVC**: bilo koji 3-znamenkasti broj
- **ZIP**: bilo koji ZIP kod

### Test flow
1. Kliknite "Kupi" na bilo kojem stablu
2. Odaberite "💳 Karticom (Stripe)"
3. Unesite test podatke
4. Provjerite da se stablo označilo kao kupljeno

## Produkcija

### 1. Live keys
- Zamijenite test keys s live keys
- Postavite `STRIPE_SECRET_KEY=sk_live_...`

### 2. Webhook URL
- Ažurirajte webhook URL na produkcijsku domenu
- Provjerite da je HTTPS aktivan

### 3. Security
- Nikad ne commitajte live keys u kod
- Koristite environment variables
- Omogućite HTTPS za sve webhook pozive

## Troubleshooting

### Česti problemi
1. **"Invalid API key"** - Provjerite da je STRIPE_SECRET_KEY ispravno postavljen
2. **Webhook ne radi** - Provjerite da je URL dostupan i da prima POST zahtjeve
3. **Plaćanje ne završava** - Provjerite deeplink konfiguraciju u app.config.js

### Debug
- Stripe Dashboard → Payments - vidite sva plaćanja
- Stripe Dashboard → Webhooks - vidite webhook pozive
- Console logovi u aplikaciji za debugging
