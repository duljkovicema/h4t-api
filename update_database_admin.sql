-- Ažuriranje baze podataka za admin funkcionalnost
-- Dodavanje admin flag-a i high_value kolone

-- Dodaj admin flag u users tablicu
ALTER TABLE users 
ADD COLUMN is_admin BOOLEAN DEFAULT FALSE COMMENT 'Označava da li je korisnik admin';

-- Dodaj high_value flag u trees tablicu
ALTER TABLE trees 
ADD COLUMN high_value BOOLEAN DEFAULT FALSE COMMENT 'Označava da li je stablo high value';

-- Dodaj indekse za bolje performanse
CREATE INDEX idx_users_is_admin ON users(is_admin);
CREATE INDEX idx_trees_high_value ON trees(high_value);

-- Postavi prvog korisnika kao admin (opcionalno)
-- UPDATE users SET is_admin = TRUE WHERE id = 1;

-- Komentar za dokumentaciju
-- is_admin: TRUE = admin korisnik, FALSE = obični korisnik
-- high_value: TRUE = high value stablo, FALSE = obično stablo
