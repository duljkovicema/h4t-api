-- Ažuriranje baze podataka za podršku kartičnog plaćanja
-- Dodavanje kolone payment_type u payments tablicu

ALTER TABLE payments 
ADD COLUMN payment_type VARCHAR(20) DEFAULT 'revolut' COMMENT 'Tip plaćanja: revolut ili card',
ADD COLUMN tree_id INT NULL COMMENT 'ID stabla koje se kupuje';

-- Dodavanje indeksa za bolje performanse
CREATE INDEX idx_payments_payment_type ON payments(payment_type);
CREATE INDEX idx_payments_tree_id ON payments(tree_id);

-- Komentar za dokumentaciju
-- payment_type može biti:
-- - 'revolut' - za Revolut plaćanja (default)
-- - 'card' - za Stripe kartična plaćanja
