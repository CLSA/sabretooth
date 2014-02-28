SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "recording", "list", true,
"Provides a list of recordings for a particular participant and qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "cedar_instance", "add", true,
"View a form for creating a new cedar instance." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "cedar_instance", "delete", true,
"Removes a cedar instance from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "cedar_instance", "edit", true,
"Edits a cedar instance's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "cedar_instance", "list", true,
"List cedar instances in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "cedar_instance", "new", true,
"Add a new cedar instance to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "cedar_instance", "view", true,
"View a cedar instance's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "self", "temporary_file", false,
"Upload a temporary file to the server." );
