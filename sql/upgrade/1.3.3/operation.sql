SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "language", "edit", true,
"Edits a language's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "language", "list", true,
"List of languages." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "language", "view", true,
"View a language's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_language", true,
"View languages to restrict the user to." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_language", true,
"Removes this user's language restriction." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "new_language", true,
"Restricts this user to a particular language." );
