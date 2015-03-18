DROP VIEW IF EXISTS participant_last_consent;
DROP TABLE IF EXISTS participant_last_consent;
CREATE OR REPLACE VIEW participant_last_consent AS
SELECT participant.id AS participant_id, t1.id AS consent_id, t1.event AS event
FROM participant
LEFT JOIN consent AS t1
ON participant.id = t1.participant_id
AND t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t1.participant_id = t2.participant_id )
GROUP BY participant.id;
