CREATE TRIGGER interview_AFTER_UPDATE
AFTER UPDATE ON sabretooth.interview
FOR EACH ROW
BEGIN
  IF OLD.start_datetime != NEW.start_datetime THEN
    CALL update_participant_last_interview( NEW.participant_id );
    CALL update_interview_last_assignment( NEW.id );
  END IF;
END$$