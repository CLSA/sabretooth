-- create new participant_last_written_consent view
CREATE OR REPLACE VIEW participant_last_written_consent AS
SELECT participant_id, id AS consent_id
FROM consent AS t1
WHERE t1.date = (
  SELECT MAX( t2.date )
  FROM consent AS t2
  WHERE t1.participant_id = t2.participant_id
  AND event LIKE 'written %'
  GROUP BY t2.participant_id );
