CREATE TRIGGER appointment_AFTER_UPDATE AFTER UPDATE ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( NEW.interview_id );
END ;;
