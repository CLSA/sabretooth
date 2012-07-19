-- add the new participant list pull
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "list", true, "Retrieves base information for a list of participant." );

-- add the new interview list pull
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "interview", "list", true, "Retrieves base information for a list of interviews." );

-- add the new opal_instance operations
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "delete", true, "Removes a opal instance from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "edit", true, "Edits a opal instance's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "new", true, "Add a new opal instance to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "add", true, "View a form for creating a new opal instance." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "view", true, "View a opal instance's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "list", true, "List opal instances in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "opal_instance", "primary", true, "Retrieves base opal instance information." );

-- rename the participant list_alternate widget
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "secondary", true, "Lists a participant's alternates for sourcing purposes." );
DELETE FROM operation WHERE type = "widget" AND subject = "participant" and name = "list_alternate";
