-- Adding a new operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "user", "list", true, "Retrieves information on lists of users." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "set_password", true, "Sets a user's password." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "sync", true, "A form to synchronise participants between Sabretooth and Mastodon." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "sync", true, "Returns a summary of changes to be made given a list of UIDs to sync." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "sync", true, "Updates participants with their information in Mastodon." );
