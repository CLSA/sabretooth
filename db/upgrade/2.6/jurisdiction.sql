DROP PROCEDURE IF EXISTS patch_jurisdiction;
DELIMITER //
CREATE PROCEDURE patch_jurisdiction()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Moving jurisdiction table from cenozo database" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "jurisdiction";

    SET @sql = CONCAT(
      "SELECT id INTO @application_id ",
      "FROM ", @cenozo, ".application ",
      "WHERE name = SUBSTRING( DATABASE(), POSITION( 'sabretooth' IN DATABASE() ) )"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
    
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS jurisdiction ( ",
          "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
         "create_timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', ",
         "site_id INT(10) UNSIGNED NOT NULL, ",
         "postcode VARCHAR(7) NOT NULL, ",
         "longitude FLOAT NOT NULL, ",
         "latitude FLOAT NOT NULL, ",
         "PRIMARY KEY (id), ",
         "UNIQUE INDEX uq_postcode (postcode ASC), ",
         "INDEX fk_site_id (site_id ASC), ",
         "INDEX dk_postcode (postcode ASC), ",
         "CONSTRAINT fk_jurisdiction_site_id ",
           "FOREIGN KEY (site_id) ",
           "REFERENCES ", @cenozo, ".site (id) ",
           "ON DELETE NO ACTION ",
           "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "SELECT COUNT(*) INTO @grouping ",
        "FROM ", @cenozo, ".application_has_cohort ",
        "WHERE grouping = 'jurisdiction' ",
        "AND application_id = ", @application_id
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      IF @grouping THEN
        -- move jurisdictions from cenozo
        SET @sql = CONCAT(
          "INSERT INTO jurisdiction ",
          "SELECT j.* ",
          "FROM ", @cenozo, ".jurisdiction AS j ",
          "JOIN ", @cenozo, ".application_has_site ON j.site_id = application_has_site.site_id ",
          "WHERE application_has_site.application_id = ", @application_id, " ",
          "GROUP BY postcode"
        );
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
      END IF;
    END IF;

  END //
DELIMITER ;

CALL patch_jurisdiction();
DROP PROCEDURE IF EXISTS patch_jurisdiction;


DELIMITER $$

DROP TRIGGER IF EXISTS jurisdiction_AFTER_INSERT $$
CREATE DEFINER=CURRENT_USER TRIGGER jurisdiction_AFTER_INSERT AFTER INSERT ON jurisdiction FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( NEW.id );
END$$

DROP TRIGGER IF EXISTS jurisdiction_AFTER_UPDATE $$
CREATE DEFINER=CURRENT_USER TRIGGER jurisdiction_AFTER_UPDATE AFTER UPDATE ON jurisdiction FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( NEW.id );
END$$

DROP TRIGGER IF EXISTS jurisdiction_BEFORE_DELETE $$
CREATE DEFINER=CURRENT_USER TRIGGER jurisdiction_BEFORE_DELETE BEFORE DELETE ON jurisdiction FOR EACH ROW
BEGIN
  DELETE FROM participant_site
  WHERE site_id = OLD.site_id;
END$$

DROP TRIGGER IF EXISTS jurisdiction_AFTER_DELETE $$
CREATE DEFINER=CURRENT_USER TRIGGER jurisdiction_AFTER_DELETE AFTER DELETE ON jurisdiction FOR EACH ROW
BEGIN
  CALL update_participant_site_for_jurisdiction( OLD.id );
END$$

DELIMITER ;
