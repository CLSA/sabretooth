SELECT "Adding new operations" AS "";
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue", "repopulate", true, "Repopulate all queue participant lists." );
