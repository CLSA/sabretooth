-- remove all activity to *_operation operations
DELETE FROM activity
WHERE operation_id IN (
  SELECT id
  FROM operation
  WHERE subject = "role"
  AND name IN ( "add_operation", "new_operation", "delete_operation" )
);

-- delete all viewer activity
DELETE FROM activity
WHERE role_id = (
  SELECT id FROM role WHERE name = "viewer"
);

-- change clerk activity to the administrator role
UPDATE activity
SET role_id = (
  SELECT id FROM role WHERE name = "administrator"
)
WHERE role_id = (
  SELECT id FROM role WHERE name = "clerk"
);
