INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "spy", true, "Opens a listen-only connection to an existing operator's call." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "delete", true, "Removes a quota from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "edit", true, "Edits a quota's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "new", true, "Add a new quota to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "quota", "add", true, "View a form for creating a new quota." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "quota", "view", true, "View a quota's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "quota", "list", true, "List quotas in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "quota", "primary", true, "Retrieves base quota information." );
