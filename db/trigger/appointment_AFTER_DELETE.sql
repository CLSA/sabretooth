CREATE TRIGGER appointment_AFTER_DELETE AFTER DELETE ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( OLD.interview_id );
END ;;