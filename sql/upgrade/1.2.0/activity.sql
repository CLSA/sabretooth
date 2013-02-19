-- censor passwords
UPDATE activity SET query = "(censored)"
WHERE operation_id IN (
  SELECT id FROM operation
  WHERE name = "set_password"
  OR ( subject = "opal_instance" AND name = "new" ) )
AND query != "(censored)";

-- remove participant_sync activity
DELETE FROM activity WHERE operation_id IN (
  SELECT id FROM operation
  WHERE subject = "participant"
  AND name = "sync" );

-- remove consent outstanding report
DELETE FROM activity WHERE operation_id IN (
  SELECT id FROM operation
  WHERE subject = "consent_outstanding" );
