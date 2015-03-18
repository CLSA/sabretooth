-- create the participant_last_interview view
SELECT "Creating the participant_last_interview view" AS "";

CREATE OR REPLACE VIEW participant_last_interview AS
SELECT interview_1.participant_id, interview_1.id AS interview_id
FROM interview AS interview_1
JOIN qnaire AS qnaire_1 ON interview_1.qnaire_id = qnaire_1.id
WHERE qnaire_1.rank = (
  SELECT MAX( qnaire_2.rank )
  FROM qnaire AS qnaire_2
  JOIN interview AS interview_2 ON qnaire_2.id = interview_2.qnaire_id
  WHERE interview_2.participant_id = interview_1.participant_id );
