-- censor passwords
UPDATE activity SET query = "(censored)"
WHERE operation_id IN (
  SELECT id FROM operation
  WHERE name = "set_password"
  OR ( subject = "opal_instance" AND name = "new" ) )
AND query != "(censored)";
