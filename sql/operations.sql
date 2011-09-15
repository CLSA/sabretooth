-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

DELETE FROM operation;

-- access
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "access", "delete", true, "Removes access from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "access", "list", true, "List system access entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "access", "primary", true, "Retrieves base access information." );

-- activity
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "activity", "list", true, "List system activity." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "activity", "primary", true, "Retrieves base activity information." );

-- address
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "address", "delete", true, "Removes a participant's address entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "address", "edit", true, "Edits the details of a participant's address entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "address", "new", true, "Creates a new address entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "address", "add", true, "View a form for creating new address entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "address", "view", true, "View the details of a participant's particular address entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "address", "list", true, "Lists a participant's address entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "address", "primary", true, "Retrieves base address information." );

-- appointment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "delete", true, "Removes a participant's appointment from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "edit", true, "Edits the details of a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "new", true, "Creates new appointment enry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "add", true, "View a form for creating new appointments for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "view", true, "View the details of a participant's particular appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "list", true, "Lists a participant's appointments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "calendar", true, "Shows appointments in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "feed", true, "Retrieves a list of shifts for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "primary", true, "Retrieves base appointment information." );

-- assignment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "view", true, "View assignment details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "list", true, "Lists assignments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "begin", true, "Requests a new assignment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "end", true, "Ends the current assignment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "assignment", "primary", true, "Retrieves base assignment information." );

-- availability
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "availability", "delete", true, "Removes a participant's availability entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "availability", "edit", true, "Edits the details of a participant's availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "availability", "new", true, "Creates new availability entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "add", true, "View a form for creating new availability entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "view", true, "View the details of a participant's particular availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "list", true, "Lists a participant's availability entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "availability", "primary", true, "Retrieves base availability information." );

-- consent
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "consent", "delete", true, "Removes a participant's consent entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "consent", "edit", true, "Edits the details of a participant's consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "consent", "new", true, "Creates new consent enry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "add", true, "View a form for creating new consent entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "view", true, "View the details of a participant's particular consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent", "list", true, "Lists a participant's consent entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "consent", "primary", true, "Retrieves base consent information." );

-- notes
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "delete", true, "Removes a note from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "edit", true, "Edits the details of a note." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "new", false, "Creates a new note." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "note", "list", false, "Lists a participant's note entries." );

-- operation
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operation", "list", true, "List operations in the system." );

-- operator
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operator", "assignment", true, "Displays the operator's assignment manager." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "operator", "begin_break", true, "Register the start of a break." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "operator", "end_break", true, "Register the end of a break." );

-- participant
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete", true, "Removes a participant from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "edit", true, "Edits a participant's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "new", true, "Add a new participant to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add", true, "View a form for creating a new participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "view", true, "View a participant's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "list", true, "List participants in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_appointment", true, "A form to create a new appointment to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_appointment", true, "Remove a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_availability", true, "A form to create a new availability entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_availability", true, "Remove a participant's availability entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_consent", true, "A form to create a new consent entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_consent", true, "Remove a participant's consent entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_address", true, "A form to create a new address entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_address", true, "Remove a participant's address entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_phone", true, "A form to create a new phone entry to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_phone", true, "Remove a participant's phone entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "tree", true, "Displays participants in a tree format, revealing which queue the belong to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "primary", true, "Retrieves base participant information." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "withdraw", true, "Withdraws the participant (or cancels the withdraw).  This is meant to be used during an interview if the participant suddenly wishes to withdraw." );

-- phase
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "delete", true, "Removes a phase from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "edit", true, "Edits a phase's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "new", true, "Creates a new questionnaire phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "add", true, "View a form for creating a new phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "view", true, "View the details of a questionnaire's phases." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "list", true, "Lists a questionnaire's phases." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "phase", "primary", true, "Retrieves base phase information." );

-- phone
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone", "delete", true, "Removes a participant's phone entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone", "edit", true, "Edits the details of a participant's phone entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone", "new", true, "Creates a new phone entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone", "add", true, "View a form for creating new phone entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone", "view", true, "View the details of a participant's particular phone entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone", "list", true, "Lists a participant's phone entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "phone", "primary", true, "Retrieves base phone information." );

-- phone call
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone_call", "list", true, "Lists phone calls." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone_call", "begin", true, "Starts a new phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone_call", "end", true, "Ends the current phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "phone_call", "primary", true, "Retrieves base phone call information." );

-- qnaire
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete", true, "Removes a questionnaire from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "edit", true, "Edits a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new", true, "Add a new questionnaire to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add", true, "View a form for creating a new questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "view", true, "View a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "list", true, "List questionnaires in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_phase", true, "View surveys to add as a new phase to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_phase", true, "Remove phases from a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "qnaire", "primary", true, "Retrieves base qnaire information." );

-- queue
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "list", true, "List queues in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "queue", "primary", true, "Retrieves base queue information." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "delete", true, "Removes a queue restriction from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "edit", true, "Edits a queue restriction's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "new", true, "Add a new queue restriction to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "add", true, "View a form for creating a new queue restriction." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "view", true, "View a queue restriction's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "list", true, "List queue restrictions in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "queue_restriction", "primary", true, "Retrieves base queue restriction information." );

-- reports
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "productivity", "report", true, "Set up a productivity report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "productivity", "report", true, "Download a productivity report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant_status", "report", true, "Set up a participant status report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant_status", "report", true, "Download a participant status report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent_form", "report", true, "Set up a consent form report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "consent_form", "report", true, "Download a consent form report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "call_attempts", "report", true, "Set up a call attempts report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "call_attempts", "report", true, "Download a call attempts report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "call_history", "report", true, "Set up a call history report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "call_history", "report", true, "Download a call history report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "consent_outstanding", "report", true, "Set up a written consent outstanding report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "consent_outstanding", "report", true, "Download a written consent outstanding report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "mailout_required", "report", true, "Set up a new mailout required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "mailout_required", "report", true, "Download a new mailout required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sourcing_required", "report", true, "Set up a new sourcing required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "sourcing_required", "report", true, "Download a new sourcing required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "demographics", "report", true, "Set up a new demographics report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "demographics", "report", true, "Download a new demographics report." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "daily_shift", "report", true, "Set up a new daily shift report." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "pull", "daily_shift", "report", true, "Download a new daily shift report." );

-- role
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "role", "delete", true, "Removes a role from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "role", "edit", true, "Edits a role's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "role", "new", true, "Add a new role to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "add", true, "View a form for creating a new role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "view", true, "View a role's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "list", true, "List roles in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "role", "add_operation", true, "View operations to add to a role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "role", "new_operation", true, "Adds new operations to a role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "role", "delete_operation", true, "Remove operations from a role." );

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
VALUES( "push", "self", "set_password", false, "Changes the user's password." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "timer", false, "A timer widget used to count time and play sounds." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "dialing_pad", false, "A telephone dialing pad widget." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "calculator", false, "A calculator widget." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "timezone_calculator", false, "A timezone calculator widget." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "self", "set_site", false, "Change the current user's active site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "self", "set_role", false, "Change the current user's active role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "self", "set_theme", false, "Change the current user's web interface theme." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "self", "primary", false, "Retrieves the current user's information." );

-- setting
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "setting", "edit", true, "Edits a setting's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "setting", "view", true, "View a setting's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "setting", "list", true, "List settings in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "setting", "primary", true, "Retrieves base setting information." );

-- shift
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift", "delete", true, "Removes a shift from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift", "edit", true, "Edits a shift's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift", "new", true, "Add a new shift to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "add", true, "View a form for creating a new shift." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "view", true, "View a shift's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "calendar", true, "Shows shifts in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift", "feed", true, "Retrieves a list of shifts for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift", "primary", true, "Retrieves base shift information." );

-- shift_template
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift_template", "delete", true, "Removes a shift template from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift_template", "edit", true, "Edits a shift template's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "shift_template", "new", true, "Add a new shift template to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift_template", "add", true, "View a form for creating a new shift template." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift_template", "view", true, "View a shift template's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift_template", "calendar", true, "Shows shift templates in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift_template", "feed", true, "Retrieves a list of shift templates for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift_template", "primary", true, "Retrieves base shift template information." );

-- site
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "site", "edit", true, "Edits a site's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "site", "new", true, "Add a new site to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add", true, "View a form for creating a new site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "view", true, "View a site's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "list", true, "List sites in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add_shift", true, "View users to add shifts to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "site", "delete_shift", true, "Remove shifts from a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "add_access", true, "View users to grant access to the site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "calendar", true, "A calendar listing the number of operators free for an appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "site", "new_access", true, "Grant access to a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "site", "delete_access", true, "Remove accesss from a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "site", "feed", true, "Retrieves a list of site free appointment times for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "site", "primary", true, "Retrieves base site information." );

-- survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "survey", "list", true, "List surveys in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "survey", "primary", true, "Retrieves base survey information." );

-- system message
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "system_message", "delete", true, "Removes a system message from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "system_message", "edit", true, "Edits a system message's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "system_message", "new", true, "Add a new system message to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "system_message", "add", true, "View a form for creating a new system message." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "system_message", "view", true, "View a system message's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "system_message", "list", true, "List system messages in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "system_message", "primary", true, "Retrieves base system message information." );

-- user
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete", true, "Removes a user from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "edit", true, "Edits a user's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "new", true, "Add a new user to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add", true, "View a form for creating a new user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "view", true, "View a user's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "list", true, "List users in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_shift", true, "View shift form for adding the user to a new shift." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_shift", true, "Remove shifts from a user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_access", true, "View sites to grant the user access to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "new_access", true, "Grant this user access to sites." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_access", true, "Removes this user's access to a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "reset_password", true, "Resets a user's password." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "user", "primary", true, "Retrieves base user information." );

-- voip
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "dtmf", true, "Sends a DTMF tone to the Asterisk server." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "play", true, "Plays a sound over the Asterisk server." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "begin_monitor", true, "Starts monitoring the active call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "end_monitor", true, "Stops monitoring the active call." );

COMMIT;
