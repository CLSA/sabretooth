CREATE TRIGGER region_site_AFTER_DELETE
AFTER DELETE ON sabretooth.region_site
FOR EACH ROW
BEGIN
  CALL update_participant_site_for_region_site( OLD.id );
END$$