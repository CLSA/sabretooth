INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "delete", true, "Removes a away time from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "edit", true, "Edits a away time's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "new", true, "Add a new away time to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "add", true, "View a form for creating a new away time." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "view", true, "View a away time's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "list", true, "List away times in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "away_time", "primary", true, "Retrieves base away time information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "report", true, "Set up a appointment report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "report", true, "Download a appointment report." );
