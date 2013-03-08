-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- appointment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "delete", true, "Removes a participant's appointment from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "edit", true, "Edits the details of a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "new", true, "Creates new appointment entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "add", true, "View a form for creating new appointments for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "view", true, "View the details of a participant's particular appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "list", true, "Lists a participant's appointments." );
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

-- away_time
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "delete", true, "Removes an away time from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "edit", true, "Edits a away time's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "new", true, "Add a new away time to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "add", true, "View a form for creating a new away time." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "view", true, "View a away time's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "list", true, "List away times in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "away_time", "primary", true, "Retrieves base away time information." );

-- callback
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "delete", true, "Removes a participant's callback from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "edit", true, "Edits the details of a participant's callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "new", true, "Creates new callback entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "add", true, "View a form for creating new callbacks for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "view", true, "View the details of a participant's particular callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "list", true, "Lists a participant's callbacks." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "primary", true, "Retrieves base callback information." );

-- calendar
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "calendar", true, "Shows appointments in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "feed", true, "Retrieves a list of appointments for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "calendar", true, "Shows callbacks in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "feed", true, "Retrieves a list of callbacks for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "availability", "calendar", true, "Shows aggregate availabilities in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "availability", "feed", true, "Retrieves a list of aggregate availabilities for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift", "calendar", true, "Shows shifts in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift", "feed", true, "Retrieves a list of shifts for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "shift_template", "calendar", true, "Shows shift templates in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "shift_template", "feed", true, "Retrieves a list of shift templates for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site", "calendar", true, "A calendar listing the number of operators free for an appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "site", "feed", true, "Retrieves a list of free site appointment times for a given time-span." );

-- interview
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "view", true, "View interview details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "list", true, "Lists interviews." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "interview", "edit", true, "Edits the details of an interview." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "rescore", true, "Provides an interface to rescore interview based on recordings." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "interview", "list", true, "Retrieves base information for a list of interviews." );

-- opal_instance
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "delete", true, "Removes a opal instance from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "edit", true, "Edits a opal instance's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "opal_instance", "new", true, "Add a new opal instance to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "add", true, "View a form for creating a new opal instance." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "view", true, "View a opal instance's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "opal_instance", "list", true, "List opal instances in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "opal_instance", "primary", true, "Retrieves base opal instance information." );

-- operator
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "operator", "assignment", true, "Displays the operator's assignment manager." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "operator", "begin_break", true, "Register the start of a break." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "operator", "end_break", true, "Register the end of a break." );

-- participant
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_appointment", true, "A form to create a new appointment to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_appointment", true, "Remove a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_callback", true, "A form to create a new callback to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_callback", true, "Remove a participant's callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "tree", true, "Displays participants in a tree format, revealing which queue the belong to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "tree", true, "Returns the number of participants for every node of the participant tree." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "withdraw", true, "Withdraws the participant (or cancels the withdraw).  This is meant to be used during an interview if the participant suddenly wishes to withdraw." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "secondary", true, "Lists a participant's alternates for sourcing purposes." );

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
VALUES( "widget", "phase", "add_source_survey", true, "A form to add a new source-specific survey to the phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "delete_source_survey", true, "Remove a phase's source-specific survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "phase", "primary", true, "Retrieves base phase information." );

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
VALUES( "widget", "qnaire", "add_source_withdraw", true, "A form to add a new source-specific withdraw survey to the questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_source_withdraw", true, "Remove a questionnaire's source-specific withdraw survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "qnaire", "primary", true, "Retrieves base questionnaire information." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_event_type", true, "A form to add an event type to a qnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new_event_type", true, "Add an event type to a qnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_event_type", true, "Remove a qnaire's event type." );

-- queue
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "list", true, "List queues in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "view", true, "View a queue's details and list of participants." );
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

-- recording
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "recording", "list", true, "Lists recordings." );

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
VALUES( "widget", "mailout_required", "report", true, "Set up a new mailout required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "mailout_required", "report", true, "Download a new mailout required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant_tree", "report", true, "Set up a participant tree report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant_tree", "report", true, "Download a participant tree report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "report", true, "Set up a appointment report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "report", true, "Download a appointment report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "report", true, "Set up a callback report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "report", true, "Download a callback report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "timing", "report", true, "Set up a timing report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "timing", "report", true, "Download a timing report." );

-- self
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "timer", false, "A timer widget used to count time and play sounds." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "dialing_pad", false, "A telephone dialing pad widget." );

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
VALUES( "pull", "shift_template", "primary", true, "Retrieves base shift template information." );

-- source_survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "delete", true, "Removes a phase's source-specific survey from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "edit", true, "Edits the details of a phase's source-specific survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_survey", "new", true, "Creates a new source-specific survey for a phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "add", true, "View a form for creating new source-specific survey for a phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "view", true, "View the details of a phase's particular source-specific survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_survey", "list", true, "Lists a phase's source-specific survey entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "source_survey", "primary", true, "Retrieves base source-specific survey information." );

-- source_withdraw
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "delete", true, "Removes a questionnaire's source-specific withdraw survey from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "edit", true, "Edits the details of a questionnaire's source-specific withdraw survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "source_withdraw", "new", true, "Creates a new source-specific withdraw survey for a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "add", true, "View a form for creating new source-specific withdraw survey for a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "view", true, "View the details of a questionnaire's particular source-specific withdraw survey." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "source_withdraw", "list", true, "Lists a questionnaire's source-specific withdraw survey entries." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "source_withdraw", "primary", true, "Retrieves base source-specific withdraw survey information." );

-- survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "survey", "list", true, "List surveys in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "survey", "primary", true, "Retrieves base survey information." );

-- user
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_shift", true, "View shift form for adding the user to a new shift." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_shift", true, "Remove shifts from a user." );

-- voip
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "dtmf", true, "Sends a DTMF tone to the Asterisk server." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "play", true, "Plays a sound over the Asterisk server." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "begin_monitor", true, "Starts monitoring the active call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "end_monitor", true, "Stops monitoring the active call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "spy", true, "Opens a listen-only connection to an existing operator's call." );

COMMIT;
