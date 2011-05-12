-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- generic operations
DELETE FROM operation;
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "login", "halt", true, "Logs out all users (except the user who executes this operation)." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "login", "suspend", true, "Prevents all users from logging in (except the user who executes this operation)." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "voip", "halt", true, "Disconnects all VOIP sessions." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "voip", "suspend", true, "Prevents any new VOIP sessions from connecting." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "voip", "dtmf", false, "Sends DTMF tones." );

-- access
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "access", "delete", true, "Removes access from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "access", "list", true, "List system access entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "access", "primary", true, "Retrieves base access information." );

-- activity
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "activity", "list", true, "List system activity." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "activity", "primary", true, "Retrieves base activity information." );

-- appointment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "appointment", "delete", true, "Removes a participant's appointment from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "appointment", "edit", true, "Edits the details of a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "appointment", "new", true, "Creates new appointment enry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "add", true, "View a form for creating new appointments for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "view", true, "View the details of a participant's particular appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "list", true, "Lists a participant's appointments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "calendar", true, "Shows appointments in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "appointment", "feed", true, "Retrieves a list of shifts for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "appointment", "primary", true, "Retrieves base appointment information." );

-- assignment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "view", true, "View assignment details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "list", true, "Lists assignments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "assignment", "begin", true, "Requests a new assignment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "assignment", "end", true, "Ends the current assignment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "assignment", "primary", true, "Retrieves base assignment information." );

-- availability
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "availability", "delete", true, "Removes a participant's availability entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "availability", "edit", true, "Edits the details of a participant's availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "availability", "new", true, "Creates new availability entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "add", true, "View a form for creating new availability entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "view", true, "View the details of a participant's particular availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "list", true, "Lists a participant's availability entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "availability", "primary", true, "Retrieves base availability information." );

-- consent
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "consent", "delete", true, "Removes a participant's consent entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "consent", "edit", true, "Edits the details of a participant's consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "consent", "new", true, "Creates new consent enry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "add", true, "View a form for creating new consent entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "view", true, "View the details of a participant's particular consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "list", true, "Lists a participant's consent entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "consent", "primary", true, "Retrieves base consent information." );

-- contact
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "delete", true, "Removes a participant's contact entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "edit", true, "Edits the details of a participant's contact entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "new", true, "Creates a new contact entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "add", true, "View a form for creating new contact entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "view", true, "View the details of a participant's particular contact entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "list", true, "Lists a participant's contact entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "contact", "primary", true, "Retrieves base contact information." );

-- notes
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "note", "delete", true, "Removes a note from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "note", "edit", true, "Edits the details of a note." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "note", "new", false, "Creates a new note." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "note", "list", false, "Lists a participant's note entries." );

-- operation
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operation", "list", true, "List operations in the system." );

-- operator
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operator", "assignment", true, "Displays the operator's assignment manager." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "operator", "begin_break", true, "Register the start of a break." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "operator", "end_break", true, "Register the end of a break." );

-- participant
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete", true, "Removes a participant from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "edit", true, "Edits a participant's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "new", true, "Add a new participant to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add", true, "View a form for creating a new participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "view", true, "View a participant's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "list", true, "List participants in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_sample", true, "View samples to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "new_sample", true, "Adds new samples to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_sample", true, "Remove samples from a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_appointment", true, "A form to create a new appointment to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_appointment", true, "Remove a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_availability", true, "A form to create a new availability entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_availability", true, "Remove a participant's availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_consent", true, "A form to create a new consent entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_consent", true, "Remove a participant's consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_contact", true, "A form to create a new contact entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_contact", true, "Remove a participant's contact entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "tree", true, "Displays participants in a tree format, revealing which queue the belong to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "participant", "primary", true, "Retrieves base participant information." );

-- phase
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "phase", "delete", true, "Removes a phase from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "phase", "edit", true, "Edits a phase's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "phase", "new", true, "Creates a new questionnaire phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "add", true, "View a form for creating a new phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "view", true, "View the details of a questionnaire's phases." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "list", true, "Lists a questionnaire's phases." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "phase", "primary", true, "Retrieves base phase information." );

-- phone call
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone_call", "list", true, "Lists phone calls." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "phone_call", "begin", true, "Starts a new phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "phone_call", "end", true, "Ends the current phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "phone_call", "primary", true, "Retrieves base phone call information." );

-- qnaire
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "delete", true, "Removes a questionnaire from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "edit", true, "Edits a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "new", true, "Add a new questionnaire to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add", true, "View a form for creating a new questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "view", true, "View a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "list", true, "List questionnaires in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_phase", true, "View surveys to add as a new phase to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "delete_phase", true, "Remove phases from a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "qnaire", "primary", true, "Retrieves base qnaire information." );

-- queue
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "list", true, "List queues in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "queue", "primary", true, "Retrieves base queue information." );

-- role
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "role", "delete", true, "Removes a role from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "role", "edit", true, "Edits a role's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "role", "new", true, "Add a new role to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "add", true, "View a form for creating a new role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "view", true, "View a role's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "list", true, "List roles in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "add_operation", true, "View operations to add to a role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "role", "new_operation", true, "Adds new operations to a role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "role", "delete_operation", true, "Remove operations from a role." );

-- sample
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "delete", true, "Removes a sample from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "edit", true, "Edits a sample's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "new", true, "Add a new sample to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "add", true, "View a form for creating a new sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "view", true, "View a sample's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "list", true, "List samples in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "add_participant", true, "View participants to add to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "new_participant", true, "Adds new participants to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "delete_participant", true, "Remove participants from a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "sample", "primary", true, "Retrieves base sample information." );

-- self
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "home", false, "The current user's home screen." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "menu", false, "The current user's main menu." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "settings", false, "The current user's settings manager." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "shortcuts", false, "The current user's shortcut icon set." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "status", false, "The current user's status." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "password", false, "Dialog for changing the user's password." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_password", false, "Changes the user's password." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "dialing_pad", false, "A telephone dialing pad widget." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "calculator", false, "A calculator widget." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_site", false, "Change the current user's active site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_role", false, "Change the current user's active role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_theme", false, "Change the current user's web interface theme." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "self", "primary", false, "Retrieves the current user's information." );

-- setting
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "setting", "edit", true, "Edits a setting's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "setting", "view", true, "View a setting's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "setting", "list", true, "List settings in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "setting", "primary", true, "Retrieves base setting information." );

-- shift
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "shift", "delete", true, "Removes a shift from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "shift", "edit", true, "Edits a shift's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "shift", "new", true, "Add a new shift to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "add", true, "View a form for creating a new shift." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "view", true, "View a shift's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "calendar", true, "Shows shifts in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "shift", "feed", true, "Retrieves a list of shifts for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "shift", "primary", true, "Retrieves base shift information." );

-- site
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "edit", true, "Edits a site's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "new", true, "Add a new site to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add", true, "View a form for creating a new site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "view", true, "View a site's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "list", true, "List sites in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add_shift", true, "View users to add shifts to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "delete_shift", true, "Remove shifts from a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add_access", true, "View users to grant access to the site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "calendar", true, "A calendar listing the number of operators free for an appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "new_access", true, "Grant access to a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "delete_access", true, "Remove accesss from a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "site", "feed", true, "Retrieves a list of site free appointment times for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "site", "primary", true, "Retrieves base site information." );

-- survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "survey", "list", true, "List surveys in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "survey", "primary", true, "Retrieves base survey information." );

-- user
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "delete", true, "Removes a user from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "edit", true, "Edits a user's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "new", true, "Add a new user to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add", true, "View a form for creating a new user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "view", true, "View a user's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "list", true, "List users in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_shift", true, "View shift form for adding the user to a new shift." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "delete_shift", true, "Remove shifts from a user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_access", true, "View sites to grant the user access to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "new_access", true, "Grant this user access to sites." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "delete_access", true, "Removes this user's access to a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "reset_password", true, "Resets a user's password." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "datum", "user", "primary", true, "Retrieves base user information." );

COMMIT;
