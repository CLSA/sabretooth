CREATE TRIGGER phone_call_AFTER_INSERT AFTER INSERT ON phone_call FOR EACH ROW
BEGIN
  CALL update_assignment_last_phone_call( NEW.assignment_id );
END ;;
