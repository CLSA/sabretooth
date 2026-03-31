CREATE TRIGGER appointment_has_vacancy_AFTER_DELETE
AFTER DELETE ON sabretooth.appointment_has_vacancy
FOR EACH ROW
BEGIN
  CALL update_vacancy_appointment_count( OLD.vacancy_id );
END$$