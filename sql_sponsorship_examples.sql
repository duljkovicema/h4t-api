-- SQL helper for sponsorships
-- -----------------------------------------------
-- Schema helpers
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS tree_sponsorships (
  tree_id INT NOT NULL PRIMARY KEY,
  zone_sponsorship_id INT NOT NULL,
  sponsor_id INT NOT NULL,
  mode ENUM('per_tree','subzone','full_zone') NOT NULL,
  tree_message VARCHAR(255),
  zone_message VARCHAR(255),
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ts_tree FOREIGN KEY (tree_id) REFERENCES trees(id) ON DELETE CASCADE,
  CONSTRAINT fk_ts_zone FOREIGN KEY (zone_sponsorship_id) REFERENCES zone_sponsorships(id),
  CONSTRAINT fk_ts_sponsor FOREIGN KEY (sponsor_id) REFERENCES sponsors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Sample data
-- -----------------------------------------------
-- (A) Insert sponsors
INSERT INTO sponsors (name, logo_url, website_url)
VALUES
  ('GreenCorp', 'https://cdn.example.com/logos/greencorp.png', 'https://greencorp.example'),
  ('Cico Energy', 'https://cdn.example.com/logos/cico.png', 'https://cico.example'),
  ('ForestCare', 'https://cdn.example.com/logos/forestcare.png', 'https://forestcare.example')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  logo_url = VALUES(logo_url),
  website_url = VALUES(website_url);

-- (B) PER-TREE sponsorship (first X trees in a zone).
-- Use empty geometry so the spatial index stays valid.
INSERT INTO zone_sponsorships (
  zone_id,
  sponsor_id,
  mode,
  quota_total,
  quota_remaining,
  subzone_geom,
  visible_on_map,
  starts_at
) VALUES (
  1,
  1,
  'per_tree',
  100,
  100,
  ST_GeomFromText('GEOMETRYCOLLECTION EMPTY', 4326),
  0,
  NOW()
);

-- (C) SUBZONE sponsorship with GeoJSON polygon supplied by sponsor.
-- Users do not see the subzone; we still need a geometry to run ST_Within.
INSERT INTO zone_sponsorships (
  zone_id,
  sponsor_id,
  mode,
  subzone_geom,
  visible_on_map,
  starts_at
) VALUES (
  1,
  2,
  'subzone',
  ST_GeomFromGeoJSON('{
    "type": "Polygon",
    "coordinates": [[[15.971,45.821],[15.982,45.821],[15.982,45.829],[15.971,45.829],[15.971,45.821]]]
  }'),
  0,
  NOW()
);

-- (D) FULL-ZONE sponsorship (entire zone funded by sponsor).
-- visible_on_map=1 so frontend can render zone badge.
INSERT INTO zone_sponsorships (
  zone_id,
  sponsor_id,
  mode,
  subzone_geom,
  visible_on_map,
  starts_at,
  zone_message_override
) VALUES (
  2,
  3,
  'full_zone',
  ST_GeomFromText('GEOMETRYCOLLECTION EMPTY', 4326),
  1,
  NOW(),
  'Zone sponsored by ForestCare'
);

-- (E) Example assignment for an existing tree (replace :tree_id)
INSERT INTO tree_sponsorships (
  tree_id,
  zone_sponsorship_id,
  sponsor_id,
  mode,
  tree_message,
  zone_message
) VALUES (
  :tree_id,
  1,
  1,
  'per_tree',
  'Sponsored by GreenCorp',
  'Zone supported by GreenCorp'
)
ON DUPLICATE KEY UPDATE
  zone_sponsorship_id = VALUES(zone_sponsorship_id),
  sponsor_id = VALUES(sponsor_id),
  mode = VALUES(mode),
  tree_message = VALUES(tree_message),
  zone_message = VALUES(zone_message);


