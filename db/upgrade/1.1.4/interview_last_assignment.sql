CREATE OR REPLACE VIEW interview_last_assignment AS
SELECT interview_1.id AS interview_id,
       assignment_1.id AS assignment_id
FROM assignment assignment_1
JOIN interview interview_1
WHERE interview_1.id = assignment_1.interview_id
AND assignment_1.start_datetime = (
  SELECT MAX( assignment_2.start_datetime )
  FROM assignment assignment_2
  JOIN interview interview_2
  WHERE interview_2.id = assignment_2.interview_id
  AND interview_1.id = interview_2.id
  GROUP BY interview_2.id
);
