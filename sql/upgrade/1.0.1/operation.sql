-- Adding a new operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "user", "list", true, "Retrieves information on lists of users." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "set_password", true, "Sets a user's password." );
