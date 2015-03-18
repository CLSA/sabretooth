SELECT "Removing defunct 'queue state' setting values" AS "";

DELETE FROM setting_value WHERE setting_id IN (
  SELECT id FROM setting WHERE category = "queue state"
);
