SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "add", true,
"View a form for creating new association between regions and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "delete", true,
"Removes an association between a region and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "edit", true,
"Edits an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "list", true,
"List associations between regions and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "new", true,
"Add a new association between a region and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "view", true,
"View an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "add", true,
"View a form for creating new association between postcodes and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "delete", true,
"Removes an association between a postcode and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "edit", true,
"Edits an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "list", true,
"List associations between postcodes and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "new", true,
"Add a new association between a postcode and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "view", true,
"View an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview_method", "list", true,
"Lists interviews." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_interview_method", true,
"A form to add new interview methods to a qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_interview_method", true,
"Remove interview methods from a questionnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new_interview_method", true,
"Add an interview method to a qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ivr_appointment", "add", true,
"View a form for creating new IVR appointments for a participant." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ivr_appointment", "calendar", true,
"Shows IVR appointments in a calendar format." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ivr_appointment", "delete", true,
"Removes a participant's IVR appointment from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ivr_appointment", "edit", true,
"Edits the details of a participant's IVR appointment." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "ivr_appointment", "feed", true,
"Retrieves a list of IVR appointments for a given time-span." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ivr_appointment", "list", true,
"Lists a participant's IVR appointments." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ivr_appointment", "new", true,
"Creates new IVR appointment entry for a participant." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "ivr_appointment", "report", true,
"Download a IVR appointment report." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ivr_appointment", "report", true,
"Set up a IVR appointment report." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ivr_appointment", "view", true,
"View the details of a participant's particular IVR appointment." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_ivr_appointment", true,
"A form to create a new IVR appointment to add to a participant." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_ivr_appointment", true,
"Remove a participant's IVR appointment." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "quota", "add_qnaire", true,
"A form to disable a quota for a particular qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "delete_qnaire", true,
"Re-enable quotas for a particular qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "new_qnaire", true,
"Disable a quota for a particular qnaire." );
