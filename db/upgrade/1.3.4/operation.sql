SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add", true,
"View a form for creating a new collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add_participant", true,
"A form to add a participant to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add_user", true,
"A form to add a user to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete", true,
"Removes a collection from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete_participant", true,
"Remove a collection's participant." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete_user", true,
"Remove a collection's user." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "edit", true,
"Edits a collection's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "list", true,
"List collections in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new", true,
"Add a new collection to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new_participant", true,
"Add a participant to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new_user", true,
"Add a user to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "view", true,
"View a collection's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "sample", "report", true,
"Download a sample report." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "report", true,
"Set up a sample report." );
