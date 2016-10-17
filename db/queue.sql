-- -----------------------------------------------------
-- Queues
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- define the interview queues

INSERT INTO queue SET
name = "all",
title = "All Participants",
rank = NULL,
time_specific = 0,
parent_queue_id = NULL
description = "All participants in the database.";

INSERT INTO queue SET
name = "finished",
title = "Finished all questionnaires",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "all" ) AS tmp ),
description = "Participants who have completed all questionnaires.";

INSERT INTO queue SET
name = "ineligible",
title = "Not eligible to answer questionnaires",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "all" ) AS tmp ),
description = "Participants who are not eligible to answer questionnaires due to a permanent condition, because they are inactive or because they do not have a phone number.";

INSERT INTO queue SET
name = "inactive",
title = "Inactive participants",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have been marked as inactive.";

INSERT INTO queue SET
name = "refused consent",
title = "Participants who refused consent",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have refused consent.";

INSERT INTO queue SET
name = "condition",
title = "Permanent Condition",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible for answering questionnaires because they have a permanent condition.";

INSERT INTO queue SET
name = "no address",
title = "Participants with no address",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible because they do not have an address.";

INSERT INTO queue SET
name = "no phone",
title = "Participants with no phone",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "ineligible" ) AS tmp ),
description = "Participants who are not eligible because they do not have an phone number.";

INSERT INTO queue SET
name = "eligible",
title = "Eligible to answer questionnaires",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "all" ) AS tmp ),
description = "Participants who are eligible to answer questionnaires.";

INSERT INTO queue SET
name = "qnaire",
title = "Questionnaire",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "eligible" ) AS tmp ),
description = "Eligible participants who are currently assigned to the questionnaire.";

INSERT INTO queue SET
name = "qnaire waiting",
title = "Waiting to begin",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are waiting the scheduled cool-down period before beginning the questionnaire.";

INSERT INTO queue SET
name = "assigned",
title = "Currently assigned",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Eligible participants who are currently assigned to an operator.";

INSERT INTO queue SET
name = "appointment",
title = "Participants with appointments",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who have an (unassigned) appointment.";

INSERT INTO queue SET
name = "upcoming appointment",
title = "Appointment upcoming",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment in the future.";

INSERT INTO queue SET
name = "assignable appointment",
title = "Appointment assignable",
rank = 1,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an immediate appointment which is ready to be assigned.";

INSERT INTO queue SET
name = "missed appointment",
title = "Appointment missed",
rank = 2,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "appointment" ) AS tmp ),
description = "Participants who have an appointment which was missed.";

INSERT INTO queue SET
name = "no active address",
title = "Participants with no active address",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who are unreachable since they currently have no active address.";

INSERT INTO queue SET
name = "no site",
title = "Participants who have no site",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who will not be assigned since they do not belong to any site.";

INSERT INTO queue SET
name = "qnaire disabled",
title = "Participants whose questionnaire is disabled",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who are unreachable since they currently have no active address.";

INSERT INTO queue SET
name = "quota disabled",
title = "Participant's quota is disabled",
rank = NULL,
time_specific = 0,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who belong to a quota which has been disabled";

INSERT INTO queue SET
name = "outside calling time",
title = "Outside calling time",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants whose local time is outside of the valid calling hours.";

INSERT INTO queue SET
name = "callback",
title = "Participants with callbacks",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants who have an (unassigned) callback.";

INSERT INTO queue SET
name = "upcoming callback",
title = "Callback upcoming",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "callback" ) AS tmp ),
description = "Participants who have an callback in the future.";

INSERT INTO queue SET
name = "assignable callback",
title = "Callback assignable",
rank = 3,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "callback" ) AS tmp ),
description = "Participants who have an immediate callback which is ready to be assigned.";

INSERT INTO queue SET
name = "new participant",
title = "Never assigned participants",
rank = 11,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants whose local time is within the valid calling hours and have never been assigned to an operator.";

INSERT INTO queue SET
name = "old participant",
title = "Previously assigned participants",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "qnaire" ) AS tmp ),
description = "Participants whose local time is within the valid calling hours and have been previously assigned.";

INSERT INTO queue SET
name = "contacted",
title = "Last call: contacted",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'contacted'.";

INSERT INTO queue SET
name = "contacted waiting",
title = "Last call: contacted (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "contacted" ) AS tmp ),
description = "Participants whose last call result was 'contacted' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "contacted ready",
title = "Last call: contacted (ready)",
rank = 4,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "contacted" ) AS tmp ),
description = "Available participants whose last call result was 'contacted' and the scheduled call back time has been reached.";

INSERT INTO queue SET
name = "busy",
title = "Last call: busy line",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'busy'.";

INSERT INTO queue SET
name = "busy waiting",
title = "Last call: busy line (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "busy" ) AS tmp ),
description = "Participants whose last call result was 'busy' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "busy ready",
title = "Last call: busy (ready)",
rank = 5,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "busy" ) AS tmp ),
description = "Available participants whose last call result was 'busy' and the scheduled call back time has been reached.";

INSERT INTO queue SET
name = "fax",
title = "Last call: fax line",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'fax'.";

INSERT INTO queue SET
name = "fax waiting",
title = "Last call: fax line (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "fax" ) AS tmp ),
description = "Participants whose last call result was 'fax' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "fax ready",
title = "Last call: fax (ready)",
rank = 6,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "fax" ) AS tmp ),
description = "Available participants whose last call result was 'fax' and the scheduled call  back time has been reached.";

INSERT INTO queue SET
name = "no answer",
title = "Last call: no answer",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'no answer'.";

INSERT INTO queue SET
name = "no answer waiting",
title = "Last call: no answer (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "no answer" ) AS tmp ),
description = "Participants whose last call result was 'no answer' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "no answer ready",
title = "Last call: no answer (ready)",
rank = 7,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "no answer" ) AS tmp ),
description = "Available participants whose last call result was 'no answer' and the scheduled call back time has been reached.";

INSERT INTO queue SET
name = "not reached",
title = "Last call: not reached",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'machine message', 'machine no message', 'not reached', 'disconnected' or 'wrong number'.";

INSERT INTO queue SET
name = "not reached waiting",
title = "Last call: not reached (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "not reached" ) AS tmp ),
description = "Participants whose last call result was 'machine message', 'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "not reached ready",
title = "Last call: not reached (ready)",
rank = 8,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "not reached" ) AS tmp ),
description = "Available participants whose last call result was 'machine message', 'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled call back time has been reached.";

INSERT INTO queue SET
name = "hang up",
title = "Last call: hang up",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'hang up'.";

INSERT INTO queue SET
name = "hang up waiting",
title = "Last call: hang up (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "hang up" ) AS tmp ),
description = "Participants whose last call result was 'hang up' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "hang up ready",
title = "Last call: hang up (ready)",
rank = 9,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "hang up" ) AS tmp ),
description = "Available participants whose last call result was 'hang up' and the scheduled call back time has been reached.";

INSERT INTO queue SET
name = "soft refusal",
title = "Last call: soft refusal",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "old participant" ) AS tmp ),
description = "Participants whose last call result was 'soft refusal'.";

INSERT INTO queue SET
name = "soft refusal waiting",
title = "Last call: soft refusal (waiting)",
rank = NULL,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "soft refusal" ) AS tmp ),
description = "Participants whose last call result was 'soft refusal' and the scheduled call back time has not yet been reached.";

INSERT INTO queue SET
name = "soft refusal ready",
title = "Last call: soft refusal (ready)",
rank = 10,
time_specific = 1,
parent_queue_id = ( SELECT id FROM ( SELECT id FROM queue WHERE name = "soft refusal" ) AS tmp ),
description = "Available participants whose last call result was 'soft refusal' and the scheduled call back time has been reached.";

COMMIT;
