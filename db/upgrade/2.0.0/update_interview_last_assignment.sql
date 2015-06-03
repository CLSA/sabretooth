SELECT "Creating new update_interview_last_assignment procedure" AS "";

DROP procedure IF EXISTS update_interview_last_assignment;

DELIMITER $$
CREATE PROCEDURE update_interview_last_assignment(IN proc_interview_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO interview_last_assignment( interview_id, assignment_id )
  SELECT interview.id, assignment.id
  FROM interview
  LEFT JOIN assignment ON interview.id = assignment.interview_id
  AND assignment.start_datetime <=> (
    SELECT MAX( start_datetime )
    FROM assignment
    WHERE interview.id = assignment.interview_id
    GROUP BY assignment.interview_id
    LIMIT 1
  )
  WHERE interview.id = proc_interview_id;
END$$

DELIMITER ;
