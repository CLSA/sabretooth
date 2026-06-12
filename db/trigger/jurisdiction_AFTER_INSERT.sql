CREATE TRIGGER jurisdiction_AFTER_INSERT AFTER INSERT ON jurisdiction FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( NEW.id );
END ;;
