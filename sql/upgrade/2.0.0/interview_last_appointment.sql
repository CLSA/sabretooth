CREATE OR REPLACE VIEW interview_last_appointment AS
SELECT interview.id AS interview_id, t1.id AS appointment_id, t1.reached
FROM interview
LEFT JOIN appointment t1
ON interview.id = t1.interview_id
AND t1.datetime = (
  SELECT MAX( t2.datetime ) FROM appointment t2
  WHERE t1.interview_id = t2.interview_id )
GROUP BY interview.id;
