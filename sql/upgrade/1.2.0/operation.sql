-- event
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "event", "delete", true, "Removes a participant's event entry from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "event", "edit", true, "Edits the details of a participant's event entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "event", "new", true, "Creates new event entry for a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "event", "add", true, "View a form for creating new event entry for a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "event", "view", true, "View the details of a participant's particular event entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "event", "list", true, "Lists a participant's event entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "event", "primary", true, "Retrieves base event information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_event", true, "A form to create a new event entry to add to a participant." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_event", true, "Remove a participant's event entry." );

-- participant report
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "report", true, "Set up a participant report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "report", true, "Download a participant report." );

-- qnaire event types
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_event_type", true, "A form to add an event type to a qnaire." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new_event_type", true, "Add an event type to a qnaire." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_event_type", true, "Remove a qnaire's event type." );
