DROP VIEW IF EXISTS participant_last_appointment;
DROP TABLE IF EXISTS participant_last_appointment;
CREATE OR REPLACE VIEW participant_last_appointment AS
SELECT participant.id AS participant_id, t1.id AS appointment_id, t1.reached
FROM participant
LEFT JOIN appointment t1
ON participant.id = t1.participant_id
AND t1.datetime = (
  SELECT MAX( t2.datetime ) FROM appointment t2
  WHERE t1.participant_id = t2.participant_id )
GROUP BY participant.id;
