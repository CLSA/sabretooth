DELETE FROM operation
WHERE subject = "role"
AND name IN ( "add_operation", "new_operation", "delete_operation" );
