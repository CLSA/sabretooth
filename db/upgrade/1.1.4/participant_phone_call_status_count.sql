-- create new participant_phone_call_status_count view
CREATE OR REPLACE VIEW participant_phone_call_status_count AS
SELECT participant.id participant_id, phone_call.status status, COUNT( phone_call.id ) total
FROM participant
JOIN interview ON participant.id = interview.participant_id
JOIN assignment ON interview.id = assignment.interview_id
JOIN phone_call ON assignment.id = phone_call.assignment_id
GROUP BY participant.id, phone_call.status;
