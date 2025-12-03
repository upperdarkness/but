-- Make planet owner column nullable to allow NULL for unowned planets
-- This fixes the foreign key constraint issue when creating unowned planets
ALTER TABLE planets ALTER COLUMN owner DROP NOT NULL;
ALTER TABLE planets ALTER COLUMN owner DROP DEFAULT;
ALTER TABLE planets ALTER COLUMN owner SET DEFAULT NULL;

-- Update existing planets with owner = 0 to NULL
UPDATE planets SET owner = NULL WHERE owner = 0;

COMMENT ON COLUMN planets.owner IS 'Ship ID that owns this planet, NULL for unowned planets';
