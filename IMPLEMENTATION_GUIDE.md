# Vodič za Implementaciju Sistema Notifikacija

## 🚀 Brza Implementacija

### 1. MySQL Setup
```bash
# Pokrenite SQL kod u vašoj MySQL bazi
mysql -u agilosor_izuna -p agilosor_h4t < create_notifications_tables.sql
```

### 2. Testiranje Backend-a
```bash
# Testiranje API-ja
curl "https://www.agilos-it.com/web/h4t-api/notifications.php?action=unseen&user_id=1&category=Moja%20stabla"
```

### 3. Admin Panel
Otvorite: `https://www.agilos-it.com/web/h4t-api/admin-notifications.php`
- Lozinka: `admin123` (promijenite u produkciji!)

## 📱 Frontend Promjene

### Dodane funkcionalnosti:
1. **Žute točkice** na svim tabovima
2. **Modal notifikacije** s gumbom "Zatvori"
3. **Automatska provjera** notifikacija
4. **Hook `useNotifications`** za upravljanje stanjem

### Kategorije tabova:
- `"Moja stabla"` - MyTreesScreen
- `"Sva stabla"` - AllTreesScreen  
- `"Moj Co2"` - MyCO2Screen

## 🔧 API Endpoints

| Endpoint | Metoda | Opis |
|----------|--------|------|
| `/notifications.php?action=unseen&user_id=1&category=Moja%20stabla` | GET | Provjeri neviđene notifikacije |
| `/notifications.php?action=mark_seen` | POST | Označi kao viđeno |
| `/notifications.php?action=create` | POST | Kreiraj novu notifikaciju |
| `/notifications.php?action=check&user_id=1` | GET | Sve notifikacije korisnika |

## 🎯 Kako Dodati Novi Tab

### 1. Kreiraj screen s notifikacijama:
```javascript
function NoviTabScreen() {
  const { notifications, checkNotifications, showNotification } = useNotifications(1);

  useEffect(() => {
    checkNotifications("Novi Tab"); // Promijenite kategoriju
  }, []);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Novi Tab</Text>
        {notifications["Novi Tab"] && (
          <TouchableOpacity 
            style={styles.notificationDot}
            onPress={() => showNotification("Novi Tab")}
          >
            <Text style={styles.dotText}>🔔</Text>
          </TouchableOpacity>
        )}
      </View>
      {/* Sadržaj taba */}
    </View>
  );
}
```

### 2. Dodaj u Drawer Navigator:
```javascript
<Drawer.Screen name="Novi Tab" component={NoviTabScreen} />
```

### 3. Dodaj kategoriju u admin panel:
```php
<option value="Novi Tab">Novi Tab</option>
```

## 🧪 Testiranje

### 1. Kreiraj test notifikaciju:
```sql
INSERT INTO notifications (name, kategorija, body) 
VALUES ('Test', 'Moja stabla', 'Ovo je test notifikacija');
```

### 2. Provjeri u aplikaciji:
- Otvori "Moja stabla" tab
- Trebala bi se pojaviti žuta točkica
- Klikni na točkicu da otvoriš modal
- Zatvori modal - točkica nestaje

## 🎨 Prilagodba Stilova

### Žuta točkica:
```javascript
notificationDot: {
  backgroundColor: "#fbbf24", // Žuta boja
  borderRadius: 15,
  width: 30,
  height: 30,
  // Dodajte shadow za bolji efekt
}
```

### Modal notifikacije:
```javascript
modalContent: {
  backgroundColor: "#fff",
  borderRadius: 20,
  padding: 20,
  // Prilagodite stilove
}
```

## 🔒 Sigurnost

### Admin panel:
- Promijenite lozinku u `admin-notifications.php`
- Dodajte pravu autentifikaciju
- Ograničite pristup po IP adresi

### API sigurnost:
- Dodajte rate limiting
- Validirajte sve inpute
- Koristite prepared statements (već implementirano)

## 📊 Monitoring

### Statistike u admin panelu:
- Ukupno notifikacija
- Broj kategorija
- Viđene/neviđene notifikacije

### Logovi:
- Sve greške se logiraju u PHP error log
- Dodajte custom logiranje ako treba

## 🚨 Troubleshooting

### Problem: Žute točkice se ne pojavljuju
**Rješenje:**
1. Provjerite da li su notifikacije u bazi
2. Provjerite API URL u kodu
3. Provjerite network zahtjeve u developer tools

### Problem: Modal se ne otvara
**Rješenje:**
1. Provjerite da li je `showModal` state true
2. Provjerite da li postoji `currentNotification`
3. Provjerite console za greške

### Problem: Notifikacije se ne označavaju kao viđene
**Rješenje:**
1. Provjerite da li API endpoint radi
2. Provjerite da li se poziva `markAsSeen` funkcija
3. Provjerite database connection

## 📈 Optimizacija

### Performanse:
- Indeksi su već dodani u SQL
- Koristite LIMIT za velike rezultate
- Cache notifikacije ako je potrebno

### Skalabilnost:
- Dodajte paginaciju za veliki broj notifikacija
- Implementirajte batch operations
- Koristite Redis za cache ako je potrebno
