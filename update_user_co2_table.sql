-- Dodavanje novih kolona u user_co2 tablicu za prosjeke
ALTER TABLE user_co2 
ADD COLUMN IF NOT EXISTS yearly_average DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS monthly_average DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS daily_average DECIMAL(10,2) DEFAULT NULL;

-- Ažuriraj postojeće zapise s izračunatim prosjecima
UPDATE user_co2 
SET 
    yearly_average = CASE 
        WHEN years > 0 THEN co2 / years 
        ELSE NULL 
    END,
    monthly_average = CASE 
        WHEN years > 0 THEN co2 / (years * 12) 
        ELSE NULL 
    END,
    daily_average = CASE 
        WHEN years > 0 THEN co2 / (years * 365) 
        ELSE NULL 
    END
WHERE yearly_average IS NULL;
