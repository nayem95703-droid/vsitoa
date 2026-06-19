-- Backfill user_id for existing accounts where only id was populated.
UPDATE users
SET user_id = id
WHERE user_id IS NULL AND id IS NOT NULL;
