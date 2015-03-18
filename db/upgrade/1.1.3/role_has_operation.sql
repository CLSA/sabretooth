DELETE FROM role_has_operation
WHERE operation_id IN (
  SELECT id
  FROM operation
  WHERE subject = "role"
  AND name IN ( "add_operation", "new_operation", "delete_operation" )
);

-- deleting the clerk and viewer roles until they are redesigned
DELETE FROM role_has_operation
WHERE role_id IN (
  SELECT id
  FROM role
  WHERE name IN( "clerk", "viewer" )
);
