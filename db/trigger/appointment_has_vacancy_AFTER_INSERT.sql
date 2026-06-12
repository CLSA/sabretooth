CREATE TRIGGER appointment_has_vacancy_AFTER_INSERT AFTER INSERT ON appointment_has_vacancy FOR EACH ROW
BEGIN
  CALL update_vacancy_appointment_count( NEW.vacancy_id );
  CALL update_appointment_vacancies( NEW.appointment_id );
END ;;
