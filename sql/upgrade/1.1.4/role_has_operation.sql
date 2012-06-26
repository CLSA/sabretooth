-- rename the participant list_alternate widget
DELETE FROM role_has_operation
WHERE operation_id = (
  SELECT id FROM operation
  WHERE type = "widget"
  AND subject = "participant"
  AND name = "list_alternate" );
