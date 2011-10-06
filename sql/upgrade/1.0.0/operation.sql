-- Adding a new widget
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "view", true, "View a queue's details and list of participants." );
