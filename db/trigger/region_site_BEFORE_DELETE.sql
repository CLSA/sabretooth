CREATE TRIGGER region_site_BEFORE_DELETE
BEFORE DELETE ON sabretooth.region_site
FOR EACH ROW
BEGIN
  DELETE FROM participant_site
  WHERE site_id = OLD.site_id;
END$$