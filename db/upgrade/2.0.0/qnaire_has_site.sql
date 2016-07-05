DROP PROCEDURE IF EXISTS patch_qnaire_has_site;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire_has_site()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Creating new qnaire_has_site table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire_has_site" );
    IF @test = 0 THEN
      DROP TABLE IF EXISTS qnaire_has_site;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS qnaire_has_site ( ",
          "qnaire_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (qnaire_id, site_id), ",
          "INDEX fk_site_id (site_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "CONSTRAINT fk_qnaire_has_site_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE CASCADE, ",
          "CONSTRAINT fk_qnaire_has_site_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE CASCADE) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire_has_site();
DROP PROCEDURE IF EXISTS patch_qnaire_has_site;
