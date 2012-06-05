DELETE FROM operation
WHERE subject = "role"
AND name IN ( "add_operation", "new_operation", "delete_operation" );

-- add the new alternate operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "list_alternate", true, "Lists a participant's alternate for sourcing purposes." );
