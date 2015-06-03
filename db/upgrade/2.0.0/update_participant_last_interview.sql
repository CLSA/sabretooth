SELECT "Creating new update_participant_last_interview procedure" AS "";

DROP procedure IF EXISTS update_participant_last_interview;

DELIMITER $$
CREATE PROCEDURE update_participant_last_interview(IN proc_participant_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO participant_last_interview( participant_id, interview_id )
  SELECT participant_id, interview.id
  FROM interview
  WHERE start_datetime <=> (
    SELECT MAX( start_datetime )
    FROM interview AS interview2
    WHERE interview.participant_id = interview2.participant_id
    GROUP BY interview2.participant_id
    LIMIT 1
  )
  AND participant_id = proc_participant_id;
END$$

DELIMITER ;
