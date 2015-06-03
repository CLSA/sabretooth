SELECT "Creating new update_assignment_last_phone_call procedure" AS "";

DROP procedure IF EXISTS update_assignment_last_phone_call;

DELIMITER $$
CREATE PROCEDURE update_assignment_last_phone_call(IN proc_assignment_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO assignment_last_phone_call( assignment_id, phone_call_id )
  SELECT assignment.id, phone_call.id
  FROM assignment
  LEFT JOIN phone_call ON assignment.id = phone_call.assignment_id
  AND phone_call.start_datetime <=> (
    SELECT MAX( start_datetime )
    FROM phone_call
    WHERE assignment.id = phone_call.assignment_id
    GROUP BY phone_call.assignment_id
    LIMIT 1
  )
  WHERE assignment.id = proc_assignment_id;
END$$

DELIMITER ;
