-- remove participant_sync operations
DELETE FROM role_has_operation WHERE operation_id IN (
  SELECT id FROM operation
  WHERE subject = "participant"
  AND name = "sync" );
