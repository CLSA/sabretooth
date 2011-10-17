-- remake the participant_for_queue view
DROP VIEW IF EXISTS `participant_for_queue` ;
DROP TABLE IF EXISTS `participant_for_queue`;
CREATE  OR REPLACE VIEW `participant_for_queue` AS
SELECT participant.*,
       first_address.city,
       first_address.region_id,
       first_address.postcode,
       COUNT( DISTINCT phone.id ) as phone_number_count,
       consent.event AS last_consent,
       assignment.id AS last_assignment_id,
       IFNULL( participant.site_id, primary_region.site_id ) AS base_site_id,
       assignment.id IS NOT NULL AND assignment.end_datetime IS NULL AS assigned,
       IF( current_interview.id IS NULL,
           ( SELECT id FROM qnaire WHERE rank = 1 ),
           IF( current_interview.completed, next_qnaire.id, current_qnaire.id )
       ) AS current_qnaire_id,
       IF( current_interview.id IS NULL,
           IF( participant.prior_contact_date IS NULL,
               NULL,
               participant.prior_contact_date + INTERVAL(
                 SELECT delay FROM qnaire WHERE rank = 1
               ) WEEK ),
           IF( current_interview.completed,
               IF( next_qnaire.id IS NULL,
                   NULL,
                   IF( next_prev_assignment.end_datetime IS NULL,
                       participant.prior_contact_date,
                       next_prev_assignment.end_datetime
                   ) + INTERVAL next_qnaire.delay WEEK
               ),
               NULL
           )
       ) AS start_qnaire_date
FROM participant
LEFT JOIN phone
ON phone.participant_id = participant.id
AND phone.active
AND phone.number IS NOT NULL
LEFT JOIN participant_primary_address
ON participant.id = participant_primary_address.participant_id 
LEFT JOIN address AS primary_address
ON participant_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region
ON primary_address.region_id = primary_region.id
LEFT JOIN participant_first_address
ON participant.id = participant_first_address.participant_id 
LEFT JOIN address AS first_address
ON participant_first_address.address_id = first_address.id
LEFT JOIN participant_last_consent
ON participant.id = participant_last_consent.participant_id 
LEFT JOIN consent
ON consent.id = participant_last_consent.consent_id
LEFT JOIN participant_last_assignment
ON participant.id = participant_last_assignment.participant_id 
LEFT JOIN assignment
ON participant_last_assignment.assignment_id = assignment.id
LEFT JOIN interview AS current_interview
ON current_interview.participant_id = participant.id
LEFT JOIN qnaire AS current_qnaire
ON current_qnaire.id = current_interview.qnaire_id
LEFT JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT JOIN qnaire AS next_prev_qnaire
ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id
LEFT JOIN interview AS next_prev_interview
ON next_prev_interview.qnaire_id = next_prev_qnaire.id
AND next_prev_interview.participant_id = participant.id
LEFT JOIN assignment next_prev_assignment
ON next_prev_assignment.interview_id = next_prev_interview.id
WHERE (
  current_qnaire.rank IS NULL OR
  current_qnaire.rank = (
    SELECT MAX( qnaire.rank )
    FROM interview, qnaire
    WHERE qnaire.id = interview.qnaire_id
    AND current_interview.participant_id = interview.participant_id
    GROUP BY current_interview.participant_id ) )
AND (
  next_prev_assignment.end_datetime IS NULL OR
  next_prev_assignment.end_datetime = (
    SELECT MAX( assignment.end_datetime )
    FROM interview, assignment
    WHERE interview.qnaire_id = next_prev_qnaire.id
    AND interview.id = assignment.interview_id
    AND next_prev_assignment.id = assignment.id
    GROUP BY next_prev_assignment.interview_id ) )
GROUP BY participant.id;
