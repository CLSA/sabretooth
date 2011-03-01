-- -----------------------------------------------------
-- Data for table .operation
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

-- access
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "access", "delete", true, "Removes access from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "access", "list", true, "List system access entries." );

-- activity
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "activity", "list", true, "List system activity." );

-- availability
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "availability", "list", true, "List availabilities in the system." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "availability", "view", true, "View the details of an availability." );

-- consent
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "consent", "list", true, "List consent activity in the system." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "consent", "view", true, "View the details of consent activity." );

-- contact
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "contact", "list", true, "List contacts in the system." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "contact", "view", true, "View the details of a contact." );

-- interview
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "list", true, "List interviews in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "view", true, "View an interview's details." );

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
VALUES( "action", "participant", "new_sample", true, "Add new samples to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "participant", "delete_sample", true, "Remove samples from a participant." );

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
VALUES( "widget", "qnaire", "list", true, "List questionnaires in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "view", true, "View a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_sample", true, "View samples to add to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "new_sample", true, "Add new samples to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "qnaire", "delete_sample", true, "Remove samples from a questionnaire." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "widget", "qnaire", "add_survey", true, "View surveys to add to a questionnaire." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "action", "qnaire", "new_survey", true, "Add new surveys to a questionnaire." );
-- INSERT INTO operation( type, subject, name, restricted, description )
-- VALUES( "action", "qnaire", "delete_survey", true, "Remove surveys from a questionnaire." );

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
VALUES( "action", "role", "new_operation", true, "Add new operations to a role." );
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
VALUES( "action", "sample", "new_qnaire", true, "Add new questionnaires to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "delete_qnaire", true, "Remove questionnaires from a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "sample", "add_participant", true, "View participants to add to a sample." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "action", "sample", "new_participant", true, "Add new participants to a sample." );
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

-- site
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

COMMIT;
