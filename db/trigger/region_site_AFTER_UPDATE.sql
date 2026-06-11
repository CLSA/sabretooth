CREATE TRIGGER region_site_AFTER_UPDATE AFTER UPDATE ON region_site FOR EACH ROW
BEGIN
  CALL update_participant_site_for_region_site( NEW.id );
END ;;