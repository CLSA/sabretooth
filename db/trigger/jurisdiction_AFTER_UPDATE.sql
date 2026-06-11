CREATE TRIGGER jurisdiction_AFTER_UPDATE AFTER UPDATE ON jurisdiction FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( NEW.id );
END ;;