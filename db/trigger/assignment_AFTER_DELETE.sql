CREATE TRIGGER assignment_AFTER_DELETE AFTER DELETE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( OLD.interview_id );
END ;;