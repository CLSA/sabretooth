-- remove defunct machine callback timing setting values
DELETE FROM setting_value WHERE setting_id IN (
  SELECT id FROM setting
  WHERE category = "callback timing"
  AND name IN ( "machine message", "machine no message" ) );
