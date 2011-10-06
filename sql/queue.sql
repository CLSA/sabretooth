-- -----------------------------------------------------
-- Queues
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- define the interview queues

INSERT INTO queue SET
name = "all",
title = "All Participants",
rank = NULL,
qnaire_specific = false,
parent_queue_id = NULL,
description = "All participants in the database.";

INSERT INTO queue SET
name = "finished",
title = "Finished all questionnaires",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "all" ) AS tmp ),
description = "Participants who have completed all questionnaires.";

INSERT INTO queue SET
name = "ineligible",
title = "Not eligible to answer questionnaires",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "all" ) AS tmp ),
description = "Participants who are not eligible to answer questionnaires due to a permanent
condition, because they are inactive or because they do not have a phone number.";

INSERT INTO queue SET
name = "inactive",
title = "Inactive participants",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have
been marked as inactive.";

INSERT INTO queue SET
name = "sourcing required",
title = "Participants without a phone number",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have
no active phone numbers.";

INSERT INTO queue SET
name = "refused consent",
title = "Participants who refused consent",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have
refused consent.";

INSERT INTO queue SET
name = "deceased",
title = "Deceased participants",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are
deceased.";

INSERT INTO queue SET
name = "deaf",
title = "Deaf participants",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are hard
of hearing.";

INSERT INTO queue SET
name = "mentally unfit",
title = "Mentally unfit participants",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are
mentally unfit.";

INSERT INTO queue SET
name = "language barrier",
title = "Participants with a language barrier",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because of a language
barrier.";

INSERT INTO queue SET
name = "age range",
title = "Participants whose age is outside of the valid range",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because their age is
not within the valid range.";

INSERT INTO queue SET
name = "other",
title = "Participants with an undefined condition",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have
been identified to have an undefined condition (other).";

INSERT INTO queue SET
name = "eligible",
title = "Eligible to answer questionnaires",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "all" ) AS tmp ),
description = "Participants who are eligible to answer questionnaires.";

INSERT INTO queue SET
name = "qnaire",
title = "Questionnaire",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are currently assigned to the questionnaire.";

INSERT INTO queue SET
name = "restricted",
title = "Restricted from calling",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants whose city, province or postcode have been restricted.";

INSERT INTO queue SET
name = "qnaire waiting",
title = "Waiting to begin",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are waiting the scheduled cool-down period before
beginning the questionnaire.";

INSERT INTO queue SET
name = "assigned",
title = "Currently assigned",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are currently assigned to an operator.";

INSERT INTO queue SET
name = "not assigned",
title = "Not assigned",
rank = NULL,
qnaire_specific = true,
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
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not assigned" ) AS tmp ),
description = "Participants who have an (unassigned) appointment.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "upcoming appointment",
title = "Appointment upcoming",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment in the future.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "assignable appointment",
title = "Appointment assignnable",
rank = 1,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an immediate appointment which is ready to be assigned.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "missed appointment",
title = "Appointment missed",
rank = 2,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment which was missed.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no appointment",
title = "Participants without appointments",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not assigned" ) AS tmp ),
description = "Participants who do not have an appointment.
This list only includes participants who are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new participant",
title = "Never assigned participants",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no appointment" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "new participant not available",
title = "New participants, not available",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new participant" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new participant available",
title = "New participants, available",
rank = 21,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new participant" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "new participant always available",
title = "New participants without availability",
rank = 22,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new participant" ) AS tmp ),
description = "Participants who have never been assigned to an operator.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "old participant",
title = "Previously assigned participants",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no appointment" ) AS tmp ),
description = "Participants who have been previously assigned.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted",
title = "Last call: contacted",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in direct contact.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "contacted waiting",
title = "Last call: contacted (waiting)",
rank = NULL,
qnaire_specific = true,
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
name = "contacted ready",
title = "Last call: contacted (ready)",
rank = NULL,
qnaire_specific = true,
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
name = "contacted not available",
title = "Last call: contacted (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted ready" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted available",
title = "Last call: contacted (available)",
rank = 3,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted ready" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "contacted always available",
title = "Last call: contacted (without availability)",
rank = 4,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted ready" ) AS tmp ),
description = "Participants who's last call resulted in direct contact and the scheduled call
back time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy",
title = "Last call: busy line",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in a busy line.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "busy waiting",
title = "Last call: busy line (waiting)",
rank = NULL,
qnaire_specific = true,
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
name = "busy ready",
title = "Last call: busy line (ready)",
rank = NULL,
qnaire_specific = true,
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
name = "busy not available",
title = "Last call: busy (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy ready" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy available",
title = "Last call: busy (available)",
rank = 5,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy ready" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "busy always available",
title = "Last call: busy (without availability)",
rank = 6,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy ready" ) AS tmp ),
description = "Participants who's last call resulted in a busy line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax",
title = "Last call: fax line",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in a fax line.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "fax waiting",
title = "Last call: fax line (waiting)",
rank = NULL,
qnaire_specific = true,
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
name = "fax ready",
title = "Last call: fax line (ready)",
rank = NULL,
qnaire_specific = true,
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
name = "fax not available",
title = "Last call: fax (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax ready" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax available",
title = "Last call: fax (available)",
rank = 7,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax ready" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "fax always available",
title = "Last call: fax (without availability)",
rank = 8,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax ready" ) AS tmp ),
description = "Participants who's last call resulted in a fax line and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "not reached",
title = "Last call: not reached",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "not reached waiting",
title = "Last call: not reached (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "not reached ready",
title = "Last call: not reached (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "not reached not available",
title = "Last call: not reached (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached ready" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "not reached available",
title = "Last call: not reached (available)",
rank = 9,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached ready" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "not reached always available",
title = "Last call: not reached (without availability)",
rank = 10,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached ready" ) AS tmp ),
description = "Participants who's last call resulted in reaching a person other than the
participant and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer",
title = "Last call: no answer",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in no answer.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer waiting",
title = "Last call: no answer (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer ready",
title = "Last call: no answer (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer not available",
title = "Last call: no answer (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer ready" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer available",
title = "Last call: no answer (available)",
rank = 11,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer ready" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "no answer always available",
title = "Last call: no answer (without availability)",
rank = 12,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer ready" ) AS tmp ),
description = "Participants who's last call resulted in no answer and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message",
title = "Last call: answering machine, message left",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and a message was
left.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message waiting",
title = "Last call: answering machine, message left (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine and no message was
left.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message ready",
title = "Last call: answering machine, message left (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message not available",
title = "Last call: answering machine, message left (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message available",
title = "Last call: answering machine, message left (available)",
rank = 13,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine message always available",
title = "Last call: answering machine, message left (without availability)",
rank = 14,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message",
title = "Last call: answering machine, message not left",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message waiting",
title = "Last call: answering machine, message not left (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine no message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has not yet been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message ready",
title = "Last call: answering machine, message not left (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine no message" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message not available",
title = "Last call: answering machine, message not left (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine no message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message available",
title = "Last call: answering machine, message not left (available)",
rank = 15,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id 
    FROM queue
    WHERE name = "machine no message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "machine no message always available",
title = "Last call: answering machine, message not left (without availability)",
rank = 16,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "machine no message ready" ) AS tmp ),
description = "Participants who's last call resulted in an answering machine, a message was not left
and the scheduled call back time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "hang up",
title = "Last call: hang up",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in a hang up.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "hang up waiting",
title = "Last call: hang up (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up" ) AS tmp ),
description = "Participants who's last call resulted in a hang up and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "hang up ready",
title = "Last call: hang up (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up" ) AS tmp ),
description = "Participants who's last call resulted in a hang up and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "hang up not available",
title = "Last call: hang up (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up ready" ) AS tmp ),
description = "Participants who's last call resulted in a hang up and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "hang up available",
title = "Last call: hang up (available)",
rank = 17,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up ready" ) AS tmp ),
description = "Participants who's last call resulted in a hang up and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "hang up always available",
title = "Last call: hang up (without availability)",
rank = 18,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up ready" ) AS tmp ),
description = "Participants who's last call resulted in a hang up and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "soft refusal",
title = "Last call: soft refusal",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "old participant" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "soft refusal waiting",
title = "Last call: soft refusal (waiting)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal and the scheduled call back
time has not yet been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "soft refusal ready",
title = "Last call: soft refusal (ready)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment and are not currently assigned
to an operator.";

INSERT INTO queue SET
name = "soft refusal not available",
title = "Last call: soft refusal (not available)",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal ready" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are not currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "soft refusal available",
title = "Last call: soft refusal (available)",
rank = 19,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal ready" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, are currently available
and are not currently assigned to an operator.";

INSERT INTO queue SET
name = "soft refusal always available",
title = "Last call: soft refusal (without availability)",
rank = 20,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal ready" ) AS tmp ),
description = "Participants who's last call resulted in a soft refusal and the scheduled call back
time has been reached.
This list only includes participants who do not have an appointment, have no availability times
and are not currently assigned to an operator.";

COMMIT;
