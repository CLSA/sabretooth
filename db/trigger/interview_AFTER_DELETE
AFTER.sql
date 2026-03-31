CREATE TRIGGER interview_AFTER_DELETE
AFTER DELETE ON sabretooth.interview
FOR EACH ROW
BEGIN
  CALL update_participant_last_interview( OLD.participant_id );
END$$