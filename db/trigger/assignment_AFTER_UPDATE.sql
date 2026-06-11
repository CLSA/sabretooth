CREATE TRIGGER assignment_AFTER_UPDATE AFTER UPDATE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( NEW.interview_id );
  CALL update_assignment_last_phone_call( NEW.id );
END ;;