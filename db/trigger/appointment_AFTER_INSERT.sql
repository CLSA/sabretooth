CREATE TRIGGER appointment_AFTER_INSERT AFTER INSERT ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( NEW.interview_id );
END ;;
