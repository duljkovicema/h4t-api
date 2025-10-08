-- Ažuriranje baze podataka za senzorske podatke
-- Dodavanje novih kolona u trees tablicu

ALTER TABLE trees 
ADD COLUMN altitude DECIMAL(10,6) NULL COMMENT 'Nadmorska visina stabla',
ADD COLUMN sensor_data TEXT NULL COMMENT 'JSON podaci senzora (GPS, žiroskop, magnetometar, barometar)',
ADD COLUMN analysis_confidence DECIMAL(3,2) NULL COMMENT 'Pouzdanost AI analize (0.00-1.00)';

-- Dodavanje indeksa za bolje performanse
CREATE INDEX idx_trees_altitude ON trees(altitude);
CREATE INDEX idx_trees_analysis_confidence ON trees(analysis_confidence);

-- Komentar za dokumentaciju
-- sensor_data JSON struktura:
-- {
--   "location": {
--     "latitude": 45.123456,
--     "longitude": 15.123456,
--     "altitude": 100.5,
--     "accuracy": 5.0,
--     "heading": 45.0,
--     "speed": 0.0
--   },
--   "altitude": 100.5,
--   "heading": 45.0,
--   "speed": 0.0,
--   "accuracy": 5.0,
--   "accelerometer": {
--     "x": 0.1,
--     "y": 0.2,
--     "z": 9.8
--   },
--   "gyroscope": {
--     "x": 0.01,
--     "y": 0.02,
--     "z": 0.03
--   },
--   "magnetometer": {
--     "x": 25.5,
--     "y": -10.2,
--     "z": 45.8
--   },
--   "barometer": {
--     "pressure": 1013.25
--   },
--   "timestamp": "2024-01-15T10:30:00.000Z"
-- }
