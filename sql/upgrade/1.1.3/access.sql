-- deleting the clerk and viewer roles until they are redesigned
DELETE FROM access
WHERE role_id IN (
  SELECT id
  FROM role
  WHERE name IN( "clerk", "viewer" )
);
