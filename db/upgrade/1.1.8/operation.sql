INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "delete", true, "Removes a participant's callback from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "edit", true, "Edits the details of a participant's callback." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "new", true, "Creates new callback entry for a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "add", true, "View a form for creating new callbacks for a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "view", true, "View the details of a participant's particular callback." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "list", true, "Lists a participant's callbacks." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "primary", true, "Retrieves base callback information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "calendar", true, "Shows callbacks in a calendar format." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "feed", true, "Retrieves a list of callbacks for a given time-span." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_callback", true, "A form to create a new callback to add to a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_callback", true, "Remove a participant's callback." );
