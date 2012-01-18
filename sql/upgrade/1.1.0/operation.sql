INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "delete", true, "Removes a phase's source-specific survey from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "edit", true, "Edits the details of a phase's source-specific survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "new", true, "Creates a new source-specific survey for a phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "add", true, "View a form for creating new source-specific survey for a phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "view", true, "View the details of a phase's particular source-specific survey." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "list", true, "Lists a phase's source-specific survey entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "source_survey", "primary", true, "Retrieves base source-specific survey information." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "add_source_survey", true, "A form to add a new source-specific survey to the phase." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "delete_source_survey", true, "Remove a phase's source-specific survey." );
