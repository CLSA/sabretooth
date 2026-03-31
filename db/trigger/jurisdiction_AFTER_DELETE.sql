CREATE TRIGGER jurisdiction_AFTER_DELETE
AFTER DELETE ON sabretooth.jurisdiction
FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( OLD.id );
END$$