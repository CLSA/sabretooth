-- create participant_for_queue table
DROP TABLE IF EXISTS participant_for_queue;

CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue
SELECT participant.id,
participant.person_id AS participant_person_id,
participant.active AS participant_active,
participant.uid AS participant_uid,
participant.source_id AS participant_source_id,
participant.cohort_id AS participant_cohort_id,
participant.first_name AS participant_first_name,
participant.last_name AS participant_last_name,
participant.gender AS participant_gender,
participant.date_of_birth AS participant_date_of_birth,
participant.age_group_id AS participant_age_group_id,
participant.state_id AS participant_state_id,
participant.language AS participant_language,
participant.use_informant AS participant_use_informant,
participant.override_quota AS participant_override_quota,
participant.email AS participant_email,
service_has_participant.service_id AS service_has_participant_service_id,
service_has_participant.participant_id AS service_has_participant_participant_id,
service_has_participant.preferred_site_id AS service_has_participant_preferred_site_id,
service_has_participant.datetime AS service_has_participant_datetime,
cohort.name AS cohort_name,
last_consent.id AS last_consent_id,
last_consent.participant_id AS last_consent_participant_id,
last_consent.accept AS last_consent_accept,
last_consent.written AS last_consent_written,
last_consent.date AS last_consent_date,
last_consent.note AS last_consent_note,
current_interview.id AS current_interview_id,
current_interview.qnaire_id AS current_interview_qnaire_id,
current_interview.participant_id AS current_interview_participant_id,
current_interview.require_supervisor AS current_interview_require_supervisor,
current_interview.completed AS current_interview_completed,
current_interview.rescored AS current_interview_rescored,
last_assignment.id AS last_assignment_id,
last_assignment.user_id AS last_assignment_user_id,
last_assignment.site_id AS last_assignment_site_id,
last_assignment.interview_id AS last_assignment_interview_id,
last_assignment.queue_id AS last_assignment_queue_id,
last_assignment.start_datetime AS last_assignment_start_datetime,
last_assignment.end_datetime AS last_assignment_end_datetime,
current_qnaire.id AS current_qnaire_id,
current_qnaire.name AS current_qnaire_name,
current_qnaire.rank AS current_qnaire_rank,
current_qnaire.prev_qnaire_id AS current_qnaire_prev_qnaire_id,
current_qnaire.delay AS current_qnaire_delay,
current_qnaire.withdraw_sid AS current_qnaire_withdraw_sid,
current_qnaire.rescore_sid AS current_qnaire_rescore_sid,
current_qnaire.description AS current_qnaire_description,
next_qnaire.id AS next_qnaire_id,
next_qnaire.name AS next_qnaire_name,
next_qnaire.rank AS next_qnaire_rank,
next_qnaire.prev_qnaire_id AS next_qnaire_prev_qnaire_id,
next_qnaire.delay AS next_qnaire_delay,
next_qnaire.withdraw_sid AS next_qnaire_withdraw_sid,
next_qnaire.rescore_sid AS next_qnaire_rescore_sid,
next_qnaire.description AS next_qnaire_description,
next_prev_qnaire.id AS next_prev_qnaire_id,
next_prev_qnaire.name AS next_prev_qnaire_name,
next_prev_qnaire.rank AS next_prev_qnaire_rank,
next_prev_qnaire.prev_qnaire_id AS next_prev_qnaire_prev_qnaire_id,
next_prev_qnaire.delay AS next_prev_qnaire_delay,
next_prev_qnaire.withdraw_sid AS next_prev_qnaire_withdraw_sid,
next_prev_qnaire.rescore_sid AS next_prev_qnaire_rescore_sid,
next_prev_qnaire.description AS next_prev_qnaire_description,
next_prev_interview.id AS next_prev_interview_id,
next_prev_interview.qnaire_id AS next_prev_interview_qnaire_id,
next_prev_interview.participant_id AS next_prev_interview_participant_id,
next_prev_interview.require_supervisor AS next_prev_interview_require_supervisor,
next_prev_interview.completed AS next_prev_interview_completed,
next_prev_interview.rescored AS next_prev_interview_rescored,
next_prev_assignment.id AS next_prev_assignment_id,
next_prev_assignment.user_id AS next_prev_assignment_user_id,
next_prev_assignment.site_id AS next_prev_assignment_site_id,
next_prev_assignment.interview_id AS next_prev_assignment_interview_id,
next_prev_assignment.queue_id AS next_prev_assignment_queue_id,
next_prev_assignment.start_datetime AS next_prev_assignment_start_datetime,
next_prev_assignment.end_datetime AS next_prev_assignment_end_datetime,
first_qnaire.id AS first_qnaire_id,
first_qnaire.name AS first_qnaire_name,
first_qnaire.rank AS first_qnaire_rank,
first_qnaire.prev_qnaire_id AS first_qnaire_prev_qnaire_id,
first_qnaire.delay AS first_qnaire_delay,
first_qnaire.withdraw_sid AS first_qnaire_withdraw_sid,
first_qnaire.rescore_sid AS first_qnaire_rescore_sid,
first_qnaire.description AS first_qnaire_description,
first_event.datetime AS first_event_datetime,
next_event.datetime AS next_event_datetime
FROM ", @cenozo, ".participant
JOIN ", @cenozo, ".service_has_participant
ON participant.id = service_has_participant.participant_id
AND service_has_participant.datetime IS NOT NULL
AND service_id = 3
JOIN ", @cenozo, ".cohort ON cohort.id = participant.cohort_id
JOIN ", @cenozo, ".participant_last_consent
ON participant.id = participant_last_consent.participant_id
LEFT
JOIN ", @cenozo, ".consent AS last_consent
ON last_consent.id = participant_last_consent.consent_id
LEFT
JOIN interview AS current_interview
ON current_interview.participant_id = participant.id
LEFT
JOIN interview_last_assignment
ON current_interview.id = interview_last_assignment.interview_id
LEFT
JOIN assignment AS last_assignment
ON interview_last_assignment.assignment_id = last_assignment.id
LEFT
JOIN qnaire AS current_qnaire
ON current_qnaire.id = current_interview.qnaire_id
LEFT
JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT
JOIN qnaire AS next_prev_qnaire
ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id
LEFT
JOIN interview AS next_prev_interview
ON next_prev_interview.qnaire_id = next_prev_qnaire.id
AND next_prev_interview.participant_id = participant.id
LEFT
JOIN assignment AS next_prev_assignment
ON next_prev_assignment.interview_id = next_prev_interview.id
CROSS
JOIN qnaire AS first_qnaire
ON first_qnaire.rank = 1
LEFT
JOIN ", @cenozo, ".event first_event
ON participant.id = first_event.participant_id
AND first_event.event_type_id IN
(
  SELECT event_type_id
  FROM qnaire_has_event_type
 
WHERE qnaire_id = first_qnaire.id
)
LEFT
JOIN ", @cenozo, ".event next_event
ON participant.id = next_event.participant_id
AND next_event.event_type_id IN
(
  SELECT event_type_id
  FROM qnaire_has_event_type
 
WHERE qnaire_id = next_qnaire.id
)
WHERE
(
  current_qnaire.rank IS NULL
  OR current_qnaire.rank =
  (
    SELECT MAX( qnaire.rank )
    FROM interview
    JOIN qnaire ON qnaire.id = interview.qnaire_id
   
WHERE interview.participant_id = current_interview.participant_id
    GROUP BY current_interview.participant_id
  )
)
AND
(
  next_prev_assignment.end_datetime IS NULL
  OR next_prev_assignment.end_datetime =
  (
    SELECT MAX( assignment.end_datetime )
    FROM interview
    JOIN assignment ON assignment.interview_id = interview.id
   
WHERE interview.qnaire_id = next_prev_qnaire.id
    AND assignment.id = next_prev_assignment.id
    GROUP BY next_prev_assignment.interview_id
  )
);

ALTER TABLE participant_for_queue
ADD INDEX fk_id ( id ),
ADD INDEX fk_participant_person_id ( participant_person_id ),
ADD INDEX fk_participant_gender ( participant_gender ),
ADD INDEX fk_participant_age_group_id ( participant_age_group_id ),
ADD INDEX fk_participant_active ( participant_active ),
ADD INDEX fk_participant_state_id ( participant_state_id ),
ADD INDEX fk_service_has_participant_preferred_site_id ( service_has_participant_preferred_site_id ),
ADD INDEX fk_current_interview_completed ( current_interview_completed ),
ADD INDEX fk_current_qnaire_id ( current_qnaire_id ),
ADD INDEX fk_next_qnaire_id ( next_qnaire_id ),
ADD INDEX fk_last_consent_accept ( last_consent_accept ),
ADD INDEX fk_last_assignment_id ( last_assignment_id );

-- create participant_for_queue_phone_count table
DROP TABLE IF EXISTS participant_for_queue_phone_count;

CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_phone_count
SELECT participant.person_id,
IF( phone.id IS NULL, 0, COUNT(*) ) phone_count
FROM ", @cenozo, ".participant
JOIN ", @cenozo, ".service_has_participant
ON participant.id = service_has_participant.participant_id
AND service_has_participant.service_id = 3
LEFT JOIN ", @cenozo, ".phone
ON participant.person_id = phone.person_id
AND phone.active
AND phone.number IS NOT NULL
GROUP BY participant.person_id;

ALTER TABLE participant_for_queue_phone_count
ADD INDEX dk_person_id ( person_id ),
ADD INDEX dk_phone_count ( phone_count );

-- create participant_for_queue_primary_region table
DROP TABLE IF EXISTS participant_for_queue_primary_region;

CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_primary_region
SELECT person_primary_address.person_id,
region.id AS primary_region_id,
region_site.site_id primary_region_site_id,
region_site.service_id primary_region_service_id
FROM ", @cenozo, ".person_primary_address
LEFT JOIN ", @cenozo, ".address ON person_primary_address.address_id = address.id
LEFT JOIN ", @cenozo, ".region ON address.region_id = region.id
LEFT JOIN ", @cenozo, ".region_site ON region.id = region_site.region_id;

ALTER TABLE participant_for_queue_primary_region
ADD INDEX dk_person_id ( person_id ),
ADD INDEX dk_primary_region_id ( primary_region_id ),
ADD INDEX dk_primary_region_site_id ( primary_region_site_id ),
ADD INDEX dk_primary_region_service_id ( primary_region_service_id );

-- create participant_for_queue_first_address table
DROP TABLE IF EXISTS participant_for_queue_first_address;

CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_first_address
SELECT person_first_address.person_id,
address.city AS first_address_city,
address.region_id AS first_address_region_id,
address.postcode AS first_address_postcode,
address.timezone_offset AS first_address_timezone_offset,
address.daylight_savings AS first_address_daylight_savings
FROM ", @cenozo, ".person_first_address
LEFT JOIN ", @cenozo, ".address ON person_first_address.address_id = address.id;

ALTER TABLE participant_for_queue_first_address
ADD INDEX dk_person_id ( person_id ),
ADD INDEX dk_first_address_city ( first_address_city ),
ADD INDEX dk_first_address_region_id ( first_address_region_id ),
ADD INDEX dk_first_address_postcode ( first_address_postcode ),
ADD INDEX dk_first_address_timezone_offset ( first_address_timezone_offset ),
ADD INDEX dk_first_address_daylight_savings ( first_address_daylight_savings );

-- populate "all" queue
SELECT COUNT( DISTINCT participant_for_queue.id )
FROM participant_for_queue

SET @participant_site_id = CONCAT(
  "IFNULL( service_has_participant_preferred_site_id, primary_region_site_id ) " );

-- join to the participant's primary region
SET @primary_region_join = CONCAT(
  "LEFT JOIN participant_for_queue_primary_region ",
  "ON participant_for_queue_primary_region.person_id = participant_person_id ",
  "AND primary_region_service_id = service_has_participant_service_id " );
 
-- join to the quota table based on site, region, gender and age group
SET @quota_join = CONCAT(
  "LEFT JOIN quota ",
  "ON quota.site_id = primary_region_site_id ",
  "AND quota.region_id = primary_region_id ",
  "AND quota.gender = participant_gender ",
  "AND quota.age_group_id = participant_age_group_id ",
  "LEFT JOIN quota_state ",
  "ON quota.id = quota_state.quota_id " );

SET @current_qnaire_id = CONCAT(
  "( ",
    "IF ",
    "( ",
      "current_interview_id IS NULL, ",
      "( SELECT id FROM qnaire WHERE rank = 1 ), ",
      "IF( current_interview_completed, next_qnaire_id, current_qnaire_id ) ",
    ") ",
  ")" );

SET @start_qnaire_date = CONCAT(
  "( ",
    "IF ",
    "( ",
      "current_interview_id IS NULL, ",
      "IF ",
      "( ",
        "( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), ",
        "IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, ",
        "NULL ",
      "), ",
      "IF ",
      "( ",
        "current_interview_completed, ",
        "GREATEST ",
        "( ",
          "IFNULL( next_event_datetime, ## ), ",
          "IFNULL( next_prev_assignment_end_datetime, ## ) ",
        ") + INTERVAL next_qnaire_delay WEEK, ",
        "NULL ",
      ") ",
    ") ",
  ")" );

-- populate "finished" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue
WHERE ", @current_qnaire_id, " IS NULL

-- populate "ineligible" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND ( participant_active = false OR participant_state_id IS NOT NULL OR phone_count = 0 OR last_consent_accept = 0 )

-- populate "inactive" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND ( participant_active = false OR participant_state_id IS NOT NULL OR phone_count = 0 OR last_consent_accept = 0 ) AND ", @current_qnaire_id, " IS NOT NULL AND participant_active = false

-- populate "refused consent" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND ( participant_active = false OR participant_state_id IS NOT NULL OR phone_count = 0 OR last_consent_accept = 0 ) AND ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND last_consent_accept = 0

-- populate "condition" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND ( participant_active = false OR participant_state_id IS NOT NULL OR phone_count = 0 OR last_consent_accept = 0 ) AND ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND participant_state_id IS NOT NULL

-- populate "eligible" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 )

-- populate "qnaire" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1

-- populate "qnaire waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NOT NULL AND DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) > DATE( UTC_TIMESTAMP() )

-- populate "assigned" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NOT NULL AND last_assignment_end_datetime IS NULL )

-- populate "appointment" queue
SELECT DISTINCT participant_for_queue.id
FROM appointment, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL

-- populate "upcoming appointment" queue
SELECT DISTINCT participant_for_queue.id
FROM appointment, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL AND UTC_TIMESTAMP() < appointment.datetime - INTERVAL 5 MINUTE

-- populate "quota disabled" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND quota_state.disabled = true AND participant_override_quota = true

-- populate "outside calling time" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND NOT ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' )

-- populate "callback" queue
SELECT DISTINCT participant_for_queue.id
FROM callback, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL

-- populate "upcoming callback" queue
SELECT DISTINCT participant_for_queue.id
FROM callback, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL AND UTC_TIMESTAMP() < callback.datetime - INTERVAL 5 MINUTE

-- populate "new participant" queue
SELECT DISTINCT participant_for_queue.id
FROM participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NOT NULL OR last_assignment_id IS NULL)

-- populate "old participant" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id

-- populate "contacted" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'contacted'

-- populate "contacted waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'contacted' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 10080 MINUTE

-- populate "busy" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'busy'

-- populate "busy waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'busy' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 15 MINUTE

-- populate "fax" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'fax'

-- populate "fax waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'fax' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 15 MINUTE

-- populate "no answer" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'no answer'

-- populate "no answer waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'no answer' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 1440 MINUTE

-- populate "not reached" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status IN ( 'machine message','machine no message','disconnected','wrong number','not reached' )

-- populate "not reached waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status IN ( 'machine message','machine no message','disconnected','wrong number','not reached' ) AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 4320 MINUTE

-- populate "hang up" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'hang up'

-- populate "hang up waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'hang up' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 2880 MINUTE

-- populate "soft refusal" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'soft refusal'

-- populate "soft refusal waiting" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'soft refusal' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL 525600 MINUTE

-- populate "assignable appointment" queue
SELECT DISTINCT participant_for_queue.id
FROM appointment, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL AND UTC_TIMESTAMP() >= appointment.datetime - INTERVAL 5 MINUTE AND UTC_TIMESTAMP() <= appointment.datetime + INTERVAL 15 MINUTE

-- populate "missed appointment" queue
SELECT DISTINCT participant_for_queue.id
FROM appointment, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL AND UTC_TIMESTAMP() > appointment.datetime + INTERVAL 15 MINUTE

-- populate "assignable callback" queue
SELECT DISTINCT participant_for_queue.id
FROM callback, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL AND UTC_TIMESTAMP() >= callback.datetime - INTERVAL 5 MINUTE

-- populate "contacted ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'contacted' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 10080 MINUTE

-- populate "busy ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'busy' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 15 MINUTE

-- populate "fax ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'fax' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 15 MINUTE

-- populate "no answer ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'no answer' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 1440 MINUTE

-- populate "not reached ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status IN ( 'machine message','machine no message','disconnected','wrong number','not reached' ) AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 4320 MINUTE

-- populate "hang up ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'hang up' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 2880 MINUTE

-- populate "soft refusal ready" queue
SELECT DISTINCT participant_for_queue.id
FROM assignment_last_phone_call, phone_call, participant_for_queue 
JOIN participant_for_queue_phone_count ON participant_for_queue_phone_count.person_id = participant_person_id
LEFT JOIN appointment ON appointment.participant_id = participant_for_queue.id AND appointment.assignment_id IS NULL
", @primary_region_join, "
", @quota_join, "
LEFT JOIN callback ON callback.participant_id = participant_for_queue.id AND callback.assignment_id IS NULL
WHERE ", @current_qnaire_id, " IS NOT NULL AND participant_active = true AND participant_state_id IS NULL AND phone_count > 0 AND ( last_consent_accept IS NULL OR last_consent_accept = 1 ) AND ", @current_qnaire_id, " = 1 AND ( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL OR DATE( ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) ) <= DATE( UTC_TIMESTAMP() ) ) AND ( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL ) AND appointment.id IS NULL AND ( quota_state.disabled IS NULL OR quota_state.disabled = false OR participant_override_quota = true ) AND ( TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) >= '09:00' AND TIME( UTC_TIMESTAMP() + INTERVAL first_address_timezone_offset HOUR ) < '21:00' ) AND callback.id IS NULL AND ( IF ( current_interview_id IS NULL, IF ( ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire_id ), IFNULL( first_event_datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire_delay WEEK, NULL ), IF ( current_interview_completed, GREATEST ( IFNULL( next_event_datetime, '' ), IFNULL( next_prev_assignment_end_datetime, '' ) ) + INTERVAL next_qnaire_delay WEEK, NULL ) ) ) IS NULL AND assignment_last_phone_call.assignment_id = last_assignment_id AND phone_call.id = assignment_last_phone_call.phone_call_id AND phone_call.status = 'soft refusal' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL 525600 MINUTE
