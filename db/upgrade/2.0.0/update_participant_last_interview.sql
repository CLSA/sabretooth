SELECT "Creating new update_participant_last_interview procedure" AS "";

DROP procedure IF EXISTS update_participant_last_interview;

DELIMITER $$
CREATE PROCEDURE update_participant_last_interview(IN proc_participant_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO participant_last_interview( participant_id, interview_id )
  SELECT participant.id, interview.id
  FROM participant
  LEFT JOIN interview ON participant.id = interview.participant_id
  AND interview.start_datetime <=> (
    SELECT MAX( start_datetime )
    FROM interview
    WHERE participant.id = interview.participant_id
    GROUP BY interview.participant_id
    LIMIT 1
  )
  WHERE participant.id = proc_participant_id;
END$$

DELIMITER ;
