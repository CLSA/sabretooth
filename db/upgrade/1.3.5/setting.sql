SELECT "Removing defunct 'queue state' settings" AS "";

DELETE FROM setting WHERE category = "queue state";
