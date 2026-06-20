-- Add user_id column if it does not already exist
ALTER TABLE users
  ADD COLUMN user_id INT DEFAULT NULL,
  ADD UNIQUE INDEX uq_user_id (user_id);

-- Backfill user_id for existing accounts where only id was populated.
UPDATE users
SET user_id = id
WHERE user_id IS NULL AND id IS NOT NULL;
