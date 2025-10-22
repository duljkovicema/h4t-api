# Sistem Notifikacija - H4T Aplikacija

## Pregled
Implementiran je kompletan sistem notifikacija za H4T aplikaciju koji uključuje:
- MySQL tablice za pohranu notifikacija
- Backend API za upravljanje notifikacijama
- Frontend komponente s žutim točkicama
- Modal za prikaz notifikacija

## MySQL Tablice

### 1. Kreiranje tablica
Pokrenite SQL kod iz `create_notifications_tables.sql`:

```sql
-- Tablica za notifikacije
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    kategorija VARCHAR(100) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tablica za korisničke notifikacije
CREATE TABLE user_notif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id INT NOT NULL,
    seen_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
);
```

### 2. Primjer podataka
U tablici su već dodane primjer notifikacije za sve kategorije:
- "Moja stabla" - "Uslikajte svoje prvo stablo..."
- "Sva stabla" - "Pogledajte sva stabla u vašoj zajednici..."
- "Moj Co2" - "Izračunajte svoju CO2 emisiju..."

## Backend API (notifications.php)

### Dostupni endpointi:

#### 1. Provjera neviđenih notifikacija
```
GET /notifications.php?action=unseen&user_id=1&category=Moja stabla
```

#### 2. Označavanje kao viđeno
```
POST /notifications.php?action=mark_seen
Content-Type: application/json
{
  "user_id": 1,
  "notification_id": 1
}
```

#### 3. Kreiranje nove notifikacije (admin)
```
POST /notifications.php?action=create
Content-Type: application/json
{
  "name": "Nova notifikacija",
  "kategorija": "Moja stabla",
  "body": "Sadržaj notifikacije"
}
```

#### 4. Dohvaćanje svih notifikacija korisnika
```
GET /notifications.php?action=check&user_id=1
```

## Frontend Implementacija

### Funkcionalnosti:
1. **Žute točkice** - Pojavljuju se na svakom tabu kada postoji neviđena notifikacija
2. **Modal notifikacije** - Prikazuje se kada korisnik klikne na točkicu
3. **Automatska provjera** - Notifikacije se provjeravaju prilikom učitavanja svakog taba
4. **Označavanje kao viđeno** - Automatski se označava kada korisnik zatvori modal

### Kategorije tabova:
- "Moja stabla" - za MyTreesScreen
- "Sva stabla" - za AllTreesScreen  
- "Moj Co2" - za MyCO2Screen

## Kako dodati novu notifikaciju

### 1. Kroz admin panel (preporučeno)
```php
// Kreiranje notifikacije
$response = file_get_contents('https://www.agilos-it.com/web/h4t-api/notifications.php?action=create', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode([
            'name' => 'Nova notifikacija',
            'kategorija' => 'Moja stabla',
            'body' => 'Sadržaj notifikacije'
        ])
    ]
]));
```

### 2. Direktno u bazu
```sql
INSERT INTO notifications (name, kategorija, body) 
VALUES ('Nova notifikacija', 'Moja stabla', 'Sadržaj notifikacije');
```

## Kako dodati novi tab s notifikacijama

### 1. Dodajte novi screen u `index.tsx`:
```javascript
function NoviTabScreen() {
  const { notifications, checkNotifications, showNotification } = useNotifications(1);

  useEffect(() => {
    checkNotifications("Novi Tab");
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

### 2. Dodajte u Drawer Navigator:
```javascript
<Drawer.Screen name="Novi Tab" component={NoviTabScreen} />
```

## Testiranje

### 1. Testiranje backend-a
```bash
# Provjera neviđenih notifikacija
curl "https://www.agilos-it.com/web/h4t-api/notifications.php?action=unseen&user_id=1&category=Moja%20stabla"

# Označavanje kao viđeno
curl -X POST "https://www.agilos-it.com/web/h4t-api/notifications.php?action=mark_seen" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "notification_id": 1}'
```

### 2. Testiranje frontend-a
1. Otvorite aplikaciju
2. Provjerite da li se pojavljuju žute točkice na tabovima
3. Kliknite na točkicu da otvorite modal
4. Zatvorite modal i provjerite da li se točkica nestala

## Napomene

- Notifikacije se provjeravaju svaki put kada se učitava tab
- Žute točkice se prikazuju samo za neviđene notifikacije
- Modal se prikazuje samo kada postoji notifikacija za taj tab
- Sve notifikacije se automatski označavaju kao viđene kada korisnik zatvori modal
- Sistem je optimiziran za performanse s indeksima u bazi podataka
