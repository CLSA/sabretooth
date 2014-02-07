SELECT "Removing defunct operations from roles" AS "";

DELETE FROM role_has_operation
WHERE operation_id IN (
  SELECT id FROM operation WHERE subject = "interview" AND name = "rescore" );
