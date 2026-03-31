CREATE TRIGGER phone_call_AFTER_DELETE
AFTER DELETE ON sabretooth.phone_call
FOR EACH ROW
BEGIN
  CALL update_assignment_last_phone_call( OLD.assignment_id );
END$$