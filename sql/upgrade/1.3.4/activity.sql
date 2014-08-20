SELECT "Removing defunct operations from activity" AS "";

DELETE FROM activity
WHERE operation_id IN (
  SELECT id FROM operation WHERE subject = "call_attempts"
);
