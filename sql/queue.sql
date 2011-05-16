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
name = "ineligible",
title = "Ineligible participants",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "all" ) AS tmp ),
description = "Participants who are not eligible to answer questionnaires due to a permanent
condition or because they are inactive.";

INSERT INTO queue SET
name = "eligible",
title = "Eligible participants",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "all" ) AS tmp ),
description = "Participants who are eligible to answer questionnaires.";

INSERT INTO queue SET
name = "qnaire_waiting",
title = "Waiting for questionnaire",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are waiting the scheduled cool-down period before
beginning the questionnaire.";

INSERT INTO queue SET
name = "qnaire",
title = "Questionnaire",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are currently assigned to the questionnaire.";

INSERT INTO queue SET
name = "assigned",
title = "Currently assigned",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are currently assigned to an operator.";

INSERT INTO queue SET
name = "not_assigned",
title = "Not assigned",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are not assigned to an operator.";

INSERT INTO queue SET
name = "appointment",
title = "Participants with appointments",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not_assigned" ) AS tmp ),
description = "Participants who have an (unassigned) appointment.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "upcoming_appointment",
title = "Appointment upcoming",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment in the future.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "assignable_appointment",
title = "Appointment assignnable",
rank = 1,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an immediate appointment which is ready to be assigned.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "missed_appointment",
title = "Appointment missed",
rank = 2,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment which was missed.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_appointment",
title = "Participants without appointments",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not_assigned" ) AS tmp ),
description = "Participants who do not have an appointment.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new_participant",
title = "Never assigned participants",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_appointment" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "new_participant_no_availability",
title = "New participants without availability",
rank = 16,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new_participant" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new_participant_availability",
title = "New participants with availability",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new_participant" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new_participant_not_available",
title = "New participants, not available",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new_participant_availability" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new_participant_available",
title = "New participants, available",
rank = 15,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new_participant_availability" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "old_participant",
title = "Previously assigned participants",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_appointment" ) AS tmp ),
description = "Participants who have been previously assigned.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted",
title = "Last call: contacted",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in direct contact.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted_waiting",
title = "Last call: contacted (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted_ready",
title = "Last call: contacted (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted_no_availability",
title = "Last call: contacted (without availability)",
rank = 4,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted_ready" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted_availability",
title = "Last call: contacted (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted_ready" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted_not_available",
title = "Last call: contacted (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted_availability" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted_available",
title = "Last call: contacted (available)",
rank = 3,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted_availability" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy",
title = "Last call: busy line",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in a busy line.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "busy_waiting",
title = "Last call: busy line (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "busy_ready",
title = "Last call: busy line (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "busy_no_availability",
title = "Last Call: busy (without availability)",
rank = 6,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy_ready" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy_availability",
title = "Last Call: busy (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy_ready" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy_not_available",
title = "Last Call: busy (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy_availability" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy_available",
title = "Last Call: busy (available)",
rank = 5,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy_availability" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax",
title = "Last call: fax line",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in a fax line.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "fax_waiting",
title = "Last call: fax line (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "fax_ready",
title = "Last call: fax line (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "fax_no_availability",
title = "Last Call: fax (without availability)",
rank = 8,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax_ready" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax_availability",
title = "Last Call: fax (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax_ready" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax_not_available",
title = "Last Call: fax (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax_availability" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax_available",
title = "Last Call: fax (available)",
rank = 7,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax_availability" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer",
title = "Last call: no answer",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in no answer.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_waiting",
title = "Last call: no answer (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_ready",
title = "Last call: no answer (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_no_availability",
title = "Last Call: no answer (without availability)",
rank = 10,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer_ready" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_availability",
title = "Last Call: no answer (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer_ready" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_not_available",
title = "Last Call: no answer (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer_availability" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no_answer_available",
title = "Last Call: no answer (available)",
rank = 9,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no_answer_availability" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message",
title = "Last call: answering machine, message left",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and a message was
left.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_waiting",
title = "Last call: answering machine, message left (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and no message was
left.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_ready",
title = "Last call: answering machine, message left (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_no_availability",
title = "Last Call: answering machine, message left (without availability)",
rank = 12,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message_ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_availability",
title = "Last Call: answering machine, message left (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message_ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_not_available",
title = "Last Call: answering machine, message left (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message_availability" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_message_available",
title = "Last Call: answering machine, message left (available)",
rank = 11,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_message_availability" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message",
title = "Last call: answering machine, message not left",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old_participant" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_waiting",
title = "Last call: answering machine, message not left (waiting)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_no_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_ready",
title = "Last call: answering machine, message not left (ready)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_no_message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_no_availability",
title = "Last Call: answering machine, message not left (without availability)",
rank = 14,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_no_message_ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_availability",
title = "Last Call: answering machine, message not left (with availability)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_no_message_ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, have availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_not_available",
title = "Last Call: answering machine, message not left (not available)",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine_no_message_availability" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine_no_message_available",
title = "Last Call: answering machine, message not left (available)",
rank = 13,
parent_queue_id = (
  SELECT id FROM(
    SELECT id 
    FROM queue
    WHERE name = "machine_no_message_availability" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "complete",
title = "Completed all questionnaires",
rank = NULL,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who have completed all questionnaires.";

COMMIT;
