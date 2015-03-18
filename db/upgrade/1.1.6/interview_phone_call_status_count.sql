DROP VIEW IF EXISTS interview_phone_call_status_count;
DROP TABLE IF EXISTS interview_phone_call_status_count;
CREATE OR REPLACE VIEW interview_phone_call_status_count AS
SELECT interview.id interview_id, phone_call.status status, COUNT( phone_call.id ) total
FROM interview
JOIN assignment ON interview.id = assignment.interview_id
JOIN phone_call ON assignment.id = phone_call.assignment_id
GROUP BY interview.id, phone_call.status;
