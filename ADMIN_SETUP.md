# Admin Setup - High Value Stabla

## 1. Ažuriranje baze podataka

Pokrenite SQL skriptu:
```sql
-- Pogledajte update_database_admin.sql
```

## 2. Postavljanje admin korisnika

### Opcija A - Postavite postojećeg korisnika kao admin:
```sql
UPDATE users SET is_admin = TRUE WHERE id = 1; -- Zamijenite 1 s ID-om korisnika
```

### Opcija B - Kreirajte novog admin korisnika:
1. Registrirajte se normalno kroz aplikaciju
2. Postavite `is_admin = TRUE` u bazi podataka:
```sql
UPDATE users SET is_admin = TRUE WHERE email = 'admin@example.com';
```

## 3. Korištenje admin funkcionalnosti

### Automatska detekcija admin korisnika:
1. Idite na **"High Value"** tab u donjem meniju
2. Ako ste admin korisnik (is_admin = TRUE), vidjet ćete svoja stabla
3. Ako niste admin, vidjet ćete samo high value stabla

### Označavanje High Value stabala:
1. Admin korisnici vide svoja stabla s **"Označi HV"** gumbovima
2. Kliknite gumb da označite stablo kao high value
3. Stablo će se prikazati u High Value listi
4. Možete ponovno kliknuti da uklonite oznaku

## 4. Funkcionalnosti

### Admin može:
- ✅ Automatski se prepoznaje kao admin (bez dodatne prijave)
- ✅ Vidjeti svoja stabla u "High Value" tabu
- ✅ Označiti svoja stabla kao high value
- ✅ Ukloniti high value oznaku s svojih stabala

### Obični korisnici mogu:
- ✅ Vidjeti high value stabla (samo čitanje)
- ❌ Ne mogu označavati stabla (nema gumbova)

## 5. Baza podataka

### Novi kolone:
- `users.is_admin` - označava da li je korisnik admin
- `trees.high_value` - označava da li je stablo high value

### Indeksi:
- `idx_users_is_admin` - za brže pretraživanje admin korisnika
- `idx_trees_high_value` - za brže dohvaćanje high value stabala

## 6. API Endpoints

- `POST /admin-login` - admin prijava
- `POST /set-high-value` - označavanje high value stabla
- `GET /high-value-trees` - dohvaćanje high value stabala

## 7. Sigurnost

- Samo admin korisnici mogu označavati high value stabla
- Admin login provjerava `is_admin` flag u bazi
- Sve admin operacije zahtijevaju valjanu admin sesiju

## 8. Troubleshooting

### "Admin korisnik nije pronađen":
- Provjerite da je `is_admin = TRUE` u bazi podataka
- Provjerite da je email ispravan

### "Samo admin korisnici mogu označiti high value stabla":
- Morate se prijaviti kao admin prije označavanja
- Provjerite da je admin login uspješan

### Stabla se ne prikazuju:
- Provjerite da je `high_value = TRUE` u bazi podataka
- Provjerite da je API endpoint dostupan
