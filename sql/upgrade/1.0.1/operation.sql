-- Adding a new pull
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "user", "list", true, "Retrieves information on lists of users." );
