-- -----------------------------------------------------
-- Queues
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- define the interview queues

INSERT INTO queue SET
name = "all",
title = "All Participants",
rank = NULL,
parent_queue_id = NULL,
description = "All participants in the database.";

INSERT INTO queue SET
name = "no_qnaire",
title = "No questionnaire",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "all" ) AS tmp ),
description = "Participants who have never been assigned to any questionnaire.";

INSERT INTO queue SET
name = "qnaire",
title = "Questionnaire",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "all" ) AS tmp ),
description = "Participants who have been assigned to the questionnaire.";

INSERT INTO queue SET
name = "complete",
title = "Completed questionnaires",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who have finished the questionnaire.";

INSERT INTO queue SET
name = "incomplete",
title = "Uncomplete questionnaires",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who have not finished the questionnaire (including those who haven't
started yet).";

INSERT INTO queue SET
name = "ineligible",
title = "Ineligible participants",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "incomplete" ) AS tmp ),
description = "Participants who have not finished the questionnaire and are not eligible to
continue due to refusal or a permanent condition.";

INSERT INTO queue SET
name = "eligible",
title = "Eligible participants",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "incomplete" ) AS tmp ),
description = "Participants who have not finished the questionnaire and are eligible to continue.";

INSERT INTO queue SET
name = "assigned",
title = "Currently assigned",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are currently assigned to an operator.
This list only includes participants who have not finished the questionnaire.";

INSERT INTO queue SET
name = "not_assigned",
title = "Not assigned",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are not assigned to an operator.
This list only includes participants who have not finished the questionnaire.";

INSERT INTO queue SET
name = "appointment",
title = "Participants with appointments",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "not_assigned" ) AS tmp ),
description = "Eligible participants who have an appointment.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "upcoming_appointment",
title = "Appointment upcoming",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment in the future.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "assignable_appointment",
title = "Appointment assignnable",
rank = 1,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an immediate appointment which is ready to be assigned.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "missed_appointment",
title = "Appointment missed",
rank = 2,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment which was missed.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "no_appointment",
title = "Participants without appointments",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "not_assigned" ) AS tmp ),
description = "Eligible participants who do not have an appointment.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "availability",
title = "Participants with availability",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_appointment" ) AS tmp ),
description = "Participants without an appointment but with availability time(s).
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "not_available",
title = "Unavailable participants",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "availability" ) AS tmp ),
description = "Participants who are not currently available.
This list only includes participants who do not have an appointment, have not finished the
questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "available",
title = "Available participants",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "availability" ) AS tmp ),
description = "Participants who are currently available.
This list only includes participants who do not have an appointment, have not finished the
questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "available_old",
title = "Previously assigned available participants",
rank = 3,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "available" ) AS tmp ),
description = "Participants who are currently available and who have been assigned to an operator
in the past.
This list only includes participants who do not have an appointment, have not finished the
questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "available_new",
title = "Never assigned available participants",
rank = 10,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "available" ) AS tmp ),
description = "Participants who are currently available and have never been assigned to an operator
in the past.
This list only includes participants who do not have an appointment, have not finished the
questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_availability",
title = "Participants without availability",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_appointment" ) AS tmp ),
description = "Participants without an appointment or any availability times.
This list only includes participants who have not finished the questionnaire and are not currently
assigned to an operator.";

INSERT INTO queue SET
name = "new_participant",
title = "Never assigned participants",
rank = 11,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_availability" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "old_participant",
title = "Previously assigned participants",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_availability" ) AS tmp ),
description = "Participants who have been previously assigned.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted",
title = "Last call: contacted",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in direct contact.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted_waiting",
title = "Last call: contacted (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "contacted" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted_ready",
title = "Last call: contacted (ready)",
rank = 4,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "contacted" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy",
title = "Last call: busy line",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in a busy line.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy_waiting",
title = "Last call: busy line (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "busy" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy_ready",
title = "Last call: busy line (ready)",
rank = 5,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "busy" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax",
title = "Last call: fax line",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in a fax line.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax_waiting",
title = "Last call: fax line (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "fax" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax_ready",
title = "Last call: fax line (ready)",
rank = 6,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "fax" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer",
title = "Last call: no answer",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in no answer.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_waiting",
title = "Last call: no answer (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_ready",
title = "Last call: no answer (ready)",
rank = 7,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "no_answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine",
title = "Last call: answering machine",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message",
title = "Last call: answering machine, message left",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and a message was
left.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_waiting",
title = "Last call: answering machine, message left (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and no message was
left.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_ready",
title = "Last call: answering machine, message left (ready)",
rank = 8,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message",
title = "Last call: answering machine, message not left",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_waiting",
title = "Last call: answering machine, message not left (waiting)",
rank = NULL,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine_no_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_ready",
title = "Last call: answering machine, message not left (ready)",
rank = 9,
parent_queue_id = ( SELECT id FROM( SELECT id FROM queue WHERE name = "machine_no_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, do not have availablity times,
have not finished the questionnaire and are not currently assigned to an operator.";

COMMIT;
