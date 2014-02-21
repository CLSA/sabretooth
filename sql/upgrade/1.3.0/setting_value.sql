-- remove defunct machine callback timing setting values
DELETE FROM setting_value WHERE setting_id IN (
  SELECT id FROM setting
  WHERE category = "queue state"
  AND name LIKE "% not available" );
