SELECT "Adding new triggers to phone_call table" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS phone_call_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER phone_call_AFTER_INSERT AFTER INSERT ON phone_call FOR EACH ROW
BEGIN
  CALL update_assignment_last_phone_call( NEW.assignment_id );
END;$$


DROP TRIGGER IF EXISTS phone_call_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER phone_call_AFTER_UPDATE AFTER UPDATE ON phone_call FOR EACH ROW
BEGIN
  CALL update_assignment_last_phone_call( NEW.assignment_id );
END;$$


DROP TRIGGER IF EXISTS phone_call_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER phone_call_AFTER_DELETE AFTER DELETE ON phone_call FOR EACH ROW
BEGIN
  CALL update_assignment_last_phone_call( OLD.assignment_id );
END;$$

DELIMITER ;
