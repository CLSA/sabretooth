SELECT "Adding missing records to interview_last_appointment caching table" AS "";

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
);
