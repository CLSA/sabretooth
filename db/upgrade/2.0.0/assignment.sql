SELECT "Adding new triggers to assignment table" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS assignment_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_INSERT AFTER INSERT ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( NEW.interview_id );
  CALL update_assignment_last_phone_call( NEW.id );
END;$$


DROP TRIGGER IF EXISTS assignment_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_UPDATE AFTER UPDATE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( NEW.interview_id );
  CALL update_assignment_last_phone_call( NEW.id );
END;$$


DROP TRIGGER IF EXISTS assignment_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER assignment_AFTER_DELETE AFTER DELETE ON assignment FOR EACH ROW
BEGIN
  CALL update_interview_last_assignment( OLD.interview_id );
END;$$

DELIMITER ;
