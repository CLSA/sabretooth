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
name = "not canadian",
title = "Participants who are not Canadian",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are not
a Canadian citizen.";

INSERT INTO queue SET
name = "federal reserve",
title = "Participants who live on a federal reserve",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they reside
on a federal reserve.";

INSERT INTO queue SET
name = "armed forces",
title = "Participants who are in the armed forces",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are full
time members of the armed forces.";

INSERT INTO queue SET
name = "institutionalized",
title = "Participants who are intitutionalized",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are
institutionalized.";

INSERT INTO queue SET
name = "noncompliant",
title = "Participants who are not complying with the rules of the study.",
rank = NULL,
qnaire_specific = false,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they are
not complying with the rules of the study.  This list may include participants who are being abusive
to CLSA staff.";

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
description = "Participants who have an (unassigned) appointment.";

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
description = "Participants who have an appointment in the future.";

INSERT INTO queue SET
name = "assignable appointment",
title = "Appointment assignable",
rank = 1,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an immediate appointment which is ready to be assigned.";

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
description = "Participants who have an appointment which was missed.";

INSERT INTO queue SET
name = "callback",
title = "Participants with callbacks",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not assigned" ) AS tmp ),
description = "Participants who have an (unassigned) callback.";

INSERT INTO queue SET
name = "upcoming callback",
title = "Callback upcoming",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "callback" ) AS tmp ),
description = "Participants who have an callback in the future.";

INSERT INTO queue SET
name = "assignable callback",
title = "Callback assignable",
rank = 3,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "callback" ) AS tmp ),
description = "Participants who have an immediate callback which is ready to be assigned.";

INSERT INTO queue SET
name = "no appointment",
title = "Participants without appointments or callbacks",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not assigned" ) AS tmp ),
description = "Participants who do not have an appointment or callback.";

INSERT INTO queue SET
name = "quota disabled",
title = "Participant's quota is disabled",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no appointment" ) AS tmp ),
description = "Participants who belong to a quota which has been disabled";

INSERT INTO queue SET
name = "outside calling time",
title = "Outside calling time",
rank = NULL,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no appointment" ) AS tmp ),
description = "Participants whose local time is outside of the valid calling hours.";

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
description = "Participants whose local time is within the valid calling hours and
have never been assigned to an operator.";

INSERT INTO queue SET
name = "new participant available",
title = "New participants, available",
rank = 18,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new participant" ) AS tmp ),
description = "New participants who are available.";

INSERT INTO queue SET
name = "new participant not available",
title = "New participants, not available",
rank = 19,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "new participant" ) AS tmp ),
description = "New participants who are not available.";

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
description = "Participants whose local time is within the valid calling hours and
have been previously assigned.";

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
description = "Participants whose last call result was 'contacted'.";

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
description = "Participants whose last call result was 'contacted' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "contacted available",
title = "Last call: contacted (available)",
rank = 4,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted" ) AS tmp ),
description = "Available participants whose last call result was 'contacted' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "contacted not available",
title = "Last call: contacted (not available)",
rank = 5,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "contacted" ) AS tmp ),
description = "Unavailable participants whose last call result was 'contacted' and the scheduled call
back time has been reached.";

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
description = "Participants whose last call result was 'busy'.";

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
description = "Participants whose last call result was 'busy' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "busy available",
title = "Last call: busy (available)",
rank = 6,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy" ) AS tmp ),
description = "Available participants whose last call result was 'busy' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "busy not available",
title = "Last call: busy (not available)",
rank = 7,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "busy" ) AS tmp ),
description = "Unavailable participants whose last call result was 'busy' and the scheduled call
back time has been reached.";

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
description = "Participants whose last call result was 'fax'.";

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
description = "Participants whose last call result was 'fax' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "fax available",
title = "Last call: fax (available)",
rank = 8,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax" ) AS tmp ),
description = "Available participants whose last call result was 'fax' and the scheduled call 
back time has been reached.";

INSERT INTO queue SET
name = "fax not available",
title = "Last call: fax (not available)",
rank = 9,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "fax" ) AS tmp ),
description = "Unavailable participants whose last call result was 'fax' and the scheduled call 
back time has been reached.";

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
description = "Participants whose last call result was 'no answer'.";

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
description = "Participants whose last call result was 'no answer' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "no answer available",
title = "Last call: no answer (available)",
rank = 10,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer" ) AS tmp ),
description = "Available participants whose last call result was 'no answer' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "no answer not available",
title = "Last call: no answer (not available)",
rank = 11,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "no answer" ) AS tmp ),
description = "Unavailable participants whose last call result was 'no answer' and the scheduled call
back time has been reached.";

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
description = "Participants whose last call result was 'machine message', 'machine no message',
'not reached', 'disconnected' or 'wrong number'.";

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
description = "Participants whose last call result was 'machine message', 'machine no message',
'not reached', 'disconnected' or 'wrong number' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "not reached available",
title = "Last call: not reached (available)",
rank = 12,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached" ) AS tmp ),
description = "Available participants whose last call result was 'machine message',
'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "not reached not available",
title = "Last call: not reached (not available)",
rank = 13,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "not reached" ) AS tmp ),
description = "Unavailable participants whose last call result was 'machine message',
'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled
call back time has been reached.";

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
description = "Participants whose last call result was 'hang up'.";

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
description = "Participants whose last call result was 'hang up' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "hang up available",
title = "Last call: hang up (available)",
rank = 14,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up" ) AS tmp ),
description = "Available participants whose last call result was 'hang up' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "hang up not available",
title = "Last call: hang up (not available)",
rank = 15,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "hang up" ) AS tmp ),
description = "Unavailable participants whose last call result was 'hang up' and the scheduled call
back time has been reached.";

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
description = "Participants whose last call result was 'soft refusal'.";

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
description = "Participants whose last call result was 'soft refusal' and the scheduled call back
time has not yet been reached.";

INSERT INTO queue SET
name = "soft refusal available",
title = "Last call: soft refusal (available)",
rank = 16,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal" ) AS tmp ),
description = "Available participants whose last call result was 'soft refusal' and the scheduled call
back time has been reached.";

INSERT INTO queue SET
name = "soft refusal not available",
title = "Last call: soft refusal (not available)",
rank = 17,
qnaire_specific = true,
parent_queue_id = (
  SELECT id FROM(
    SELECT id
    FROM queue
    WHERE name = "soft refusal" ) AS tmp ),
description = "Unavailable participants whose last call result was 'soft refusal' and the scheduled
call back time has been reached.";

COMMIT;
