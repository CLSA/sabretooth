-- Adding a new widgets
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "view", true, "View a queue's details and list of participants." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "view", true, "View interview details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "list", true, "Lists interviews." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "interview", "edit", true, "Edits the details of an interview." );
