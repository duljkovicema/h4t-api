-- Test notifikacije za različite kategorije
-- Ovo možete pokrenuti da testirate sistem notifikacija

-- Dodaj test notifikacije za sve kategorije
INSERT INTO notifications (name, kategorija, body) VALUES
('Test notifikacija 1', 'Moja stabla', 'Ovo je test notifikacija za Moja stabla tab.'),
('Test notifikacija 2', 'Sva stabla', 'Ovo je test notifikacija za Sva stabla tab.'),
('Test notifikacija 3', 'Moj Co2', 'Ovo je test notifikacija za Moj Co2 tab.'),
('Test notifikacija 4', 'RLG', 'Ovo je test notifikacija za RLG tab.');

-- Provjeri sve notifikacije
SELECT * FROM notifications ORDER BY created_at DESC;

-- Provjeri korisničke notifikacije (za user_id = 1)
SELECT 
    n.name,
    n.kategorija,
    n.body,
    un.seen_at,
    CASE 
        WHEN un.seen_at IS NULL THEN 'Neviđeno'
        ELSE 'Viđeno'
    END as status
FROM notifications n
LEFT JOIN user_notif un ON n.id = un.notification_id AND un.user_id = 1
ORDER BY n.created_at DESC;

-- Simuliraj da je korisnik vidio notifikaciju
-- (Ovo će se automatski pozvati iz aplikacije)
INSERT INTO user_notif (user_id, notification_id, seen_at) 
VALUES (1, 1, CURRENT_TIMESTAMP);

-- Provjeri neviđene notifikacije za određenu kategoriju
SELECT n.id, n.name, n.kategorija, n.body
FROM notifications n
LEFT JOIN user_notif un ON n.id = un.notification_id AND un.user_id = 1
WHERE n.kategorija = 'Moja stabla' 
AND (un.seen_at IS NULL OR un.id IS NULL)
ORDER BY n.created_at DESC
LIMIT 1;

-- Očisti test podatke (opcionalno)
-- DELETE FROM user_notif WHERE user_id = 1;
-- DELETE FROM notifications WHERE name LIKE 'Test notifikacija%';
