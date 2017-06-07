SELECT "Creating new update_interview_last_appointment procedure" AS "";

DROP procedure IF EXISTS update_interview_last_appointment;

DELIMITER $$
CREATE PROCEDURE update_interview_last_appointment (IN proc_interview_id INT(10) UNSIGNED)
BEGIN
  REPLACE INTO interview_last_appointment( interview_id, appointment_id )
  SELECT interview.id, appointment.id
  FROM interview
  LEFT JOIN appointment ON interview.id = appointment.interview_id
  LEFT JOIN vacancy ON appointment.start_vacancy_id = vacancy.id
  AND vacancy.datetime <=> (
    SELECT MAX( datetime )
    FROM appointment
    JOIN vacancy ON appointment.start_vacancy_id = vacancy.id
    WHERE interview.id = appointment.interview_id
    GROUP BY appointment.interview_id
    LIMIT 1
  )
  WHERE interview.id = proc_interview_id;
END$$
DELIMITER ;
