-- -----------------------------------------------------
-- Data for table .operation
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- settings
DELETE FROM setting;
INSERT INTO setting( category, name, value )
VALUES( "callback limit", "consecutive failed attempts", "10" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "answering machine", "4320" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "busy", "15" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "fax", "15" );
INSERT INTO setting( category, name, value )
VALUES( "callback delay", "no answer", "2160" );

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

-- access
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "access", "delete", true, "Removes access from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "access", "list", true, "List system access entries." );

-- activity
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "activity", "list", true, "List system activity." );

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

-- contact
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "delete", true, "Removes a participant's contact entry from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "edit", true, "Edits the details of a participant's contact entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "contact", "new", true, "Creates new contact enry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "add", true, "View a form for creating new contact entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "view", true, "View the details of a participant's particular contact entry." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "contact", "list", true, "Lists a participant's contact entries." );

-- interview
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "view", true, "View an interview's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "list", true, "List interviews in the system." );

-- operation
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operation", "list", true, "List operations in the system." );

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
VALUES( "widget", "qnaire", "add_sample", true, "View samples to add to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "new_sample", true, "Adds new samples to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "delete_sample", true, "Remove samples from a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_phase", true, "View surveys to add as a new phase to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "delete_phase", true, "Remove phases from a questionnaire." );

-- queue
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "list", true, "List queues in the system." );

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
VALUES( "widget", "sample", "add_qnaire", true, "View questionnaires to add to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "new_qnaire", true, "Adds new questionnaires to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "delete_qnaire", true, "Remove questionnaires from a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "add_participant", true, "View participants to add to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "new_participant", true, "Adds new participants to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "delete_participant", true, "Remove participants from a sample." );

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
VALUES( "action", "self", "set_site", false, "Change the current user's active site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_role", false, "Change the current user's active role." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "self", "set_theme", false, "Change the current user's web interface theme." );

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
VALUES( "widget", "shift", "list", true, "List shifts in the system." );

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
VALUES( "widget", "site", "add_access", true, "View users to grant access to the site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "new_access", true, "Grant access to a site." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "site", "delete_access", true, "Remove accesss from a site." );

-- survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "survey", "enable", true, "Enable the survey panel for Limesurvey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "survey", "disable", true, "Disable the survey panel for Limesurvey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "survey", "list", true, "List surveys in the system." );

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
VALUES( "widget", "user", "add_access", true, "View sites to grant the user access to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "new_access", true, "Grant this user access to sites." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "user", "delete_access", true, "Removes this user's access to a site." );


-- build role permissions
DELETE FROM role;
INSERT INTO role( name ) VALUES( "administrator" );
INSERT INTO role( name ) VALUES( "clerk" );
INSERT INTO role( name ) VALUES( "operator" );
INSERT INTO role( name ) VALUES( "supervisor" );
INSERT INTO role( name ) VALUES( "technician" );
INSERT INTO role( name ) VALUES( "viewer" );

-- for now we'll put all operations into administrator
-- TODO: pre-define access for all roles
DELETE FROM role_has_operation;
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name in( "administrator" );

-- define the interview queues
INSERT INTO queue( name, view, description )
VALUES( "Missed", "queue_missed",
"Participants with missed appointments.  This list is generated by searching for all
participants which have appointments in the past.  When the appointment time (on any day)
occurs the participant is contacted with the purpose of scheduling a new appointment." );
INSERT INTO queue( name, view, description )
VALUES( "Appointments", "queue_appointment",
"Participants with an upcomming appointment.  This list is generated by searching for all
participants which have appointments in the future.  When the appointment time occurs the
participant is contacted and the associated interview is continued at it's current phase.
If a connection is made then the appointment is cleared from the system." );
INSERT INTO queue( name, view, description )
VALUES( "Available", "queue_available",
"Participants with specific availability. This list is generated by searching for all
participants which have one or more specific availability times and have started an interview
associated with the current site.  When the availability time occurs the participant is
contacted and the associated interview is continued at it's current phase." );
INSERT INTO queue( name, view, description )
VALUES( "Previous: Busy", "queue_previous_busy",
"Participants who have previously been contacted.  This list in generated by searching for
all participants which have been previously contacted with the outcome of the call being a
busy line.  This status is associated with a delay time.  Once the delay time has been reached
they are contacted again." );
INSERT INTO queue( name, view, description )
VALUES( "Previous: No Answer", "queue_previous_no_answer",
"Participants who have previously been contacted.  This list in generated by searching for
all participants which have been previously contacted with the outcome of the call being a
ring-out.  This status is associated with a delay time.  Once the delay time has been reached
they are contacted again." );
INSERT INTO queue( name, view, description )
VALUES( "Previous: Answering Machine", "queue_previous_answering_machine",
"Participants who have previously been contacted.  This list in generated by searching for
all participants which have been previously contacted with the outcome of the call being an
answering machine.  This status is associated with a delay time.  Once the delay time has
been reached they are contacted again." );
INSERT INTO queue( name, view, description )
VALUES( "Previous: Fax", "queue_previous_fax",
"Participants who have previously been contacted.  This list in generated by searching for
all participants which have been previously contacted with the outcome of the call being a
fax machine.  This status is associated with a delay time.  Once the delay time has been
reached they are contacted again." );
INSERT INTO queue( name, view, description )
VALUES( "General", "queue_general",
"Participants who have not yet been contacted.  This list is generated by searching for all
participants who have never been called.  This list does not include participants with
availability times." );

COMMIT;
