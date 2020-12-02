DROP PROCEDURE IF EXISTS patch_region_site;
DELIMITER //
CREATE PROCEDURE patch_region_site()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Moving region_site table from cenozo database" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "region_site";

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
        "CREATE TABLE IF NOT EXISTS region_site ( ",
          "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(), ",
          "create_timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', ",
          "site_id INT(10) UNSIGNED NOT NULL, ",
          "region_id INT(10) UNSIGNED NOT NULL, ",
          "language_id INT(10) UNSIGNED NOT NULL, ",
          "PRIMARY KEY (id), ",
          "UNIQUE INDEX uq_region_id_language_id (region_id ASC, language_id ASC), ",
          "INDEX fk_region_id (region_id ASC), ",
          "INDEX fk_site_id (site_id ASC), ",
          "INDEX fk_language_id (language_id ASC), ",
          "CONSTRAINT fk_region_site_language_id ",
            "FOREIGN KEY (language_id) ",
            "REFERENCES ", @cenozo, ".language (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_region_site_region_id ",
            "FOREIGN KEY (region_id) ",
            "REFERENCES ", @cenozo, ".region (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_region_site_site_id ",
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
        "WHERE grouping = 'region' ",
        "AND application_id = ", @application_id
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      IF @grouping THEN
        -- move region_sites from cenozo
        SET @sql = CONCAT(
          "INSERT INTO region_site ",
          "SELECT rs.* ",
          "FROM ", @cenozo, ".region_site AS rs ",
          "JOIN ", @cenozo, ".application_has_site ON rs.site_id = application_has_site.site_id ",
          "WHERE application_has_site.application_id = ", @application_id, " ",
          "GROUP BY region_id, language_id"
        );
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
      END IF;
    END IF;

  END //
DELIMITER ;

CALL patch_region_site();
DROP PROCEDURE IF EXISTS patch_region_site;


DELIMITER $$

DROP TRIGGER IF EXISTS region_site_AFTER_INSERT $$
CREATE DEFINER=CURRENT_USER TRIGGER region_site_AFTER_INSERT AFTER INSERT ON region_site FOR EACH ROW
BEGIN
  CALL update_participant_site_for_region_site( NEW.id );
END$$

DROP TRIGGER IF EXISTS region_site_AFTER_UPDATE $$
CREATE DEFINER=CURRENT_USER TRIGGER region_site_AFTER_UPDATE AFTER UPDATE ON region_site FOR EACH ROW
BEGIN
  CALL update_participant_site_for_region_site( NEW.id );
END$$

DROP TRIGGER IF EXISTS region_site_BEFORE_DELETE $$
CREATE DEFINER=CURRENT_USER TRIGGER region_site_BEFORE_DELETE BEFORE DELETE ON region_site FOR EACH ROW
BEGIN
  DELETE FROM participant_site
  WHERE site_id = OLD.site_id;
END$$

DROP TRIGGER IF EXISTS region_site_AFTER_DELETE $$
CREATE DEFINER=CURRENT_USER TRIGGER region_site_AFTER_DELETE AFTER DELETE ON region_site FOR EACH ROW
BEGIN
  CALL update_participant_site_for_region_site( OLD.id );
END$$

DELIMITER ;
