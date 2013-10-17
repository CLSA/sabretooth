DROP PROCEDURE IF EXISTS patch_region_site;
DELIMITER //
CREATE PROCEDURE patch_region_site()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'sabretooth', DATABASE() ) - 1 ),
                          'cenozo' );

    SELECT "Renaming service_region_site to region_site" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "region_site" );
    IF @test = 0 THEN

      SET @sql = CONCAT(
        "CREATE TABLE ", @cenozo, ".region_site ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT , ",
          "update_timestamp TIMESTAMP NOT NULL , ",
          "create_timestamp TIMESTAMP NOT NULL COMMENT 'Used to determine a participant\\'s default site.' , ",
          "service_id INT UNSIGNED NOT NULL , ",
          "region_id INT UNSIGNED NOT NULL , ",
          "site_id INT UNSIGNED NOT NULL , ",
          "PRIMARY KEY (id) , ",
          "INDEX fk_service_id (service_id ASC) , ",
          "INDEX fk_region_id (region_id ASC) , ",
          "INDEX fk_site_id (site_id ASC) , ",
          "UNIQUE INDEX uq_service_id_region_id (service_id ASC, region_id ASC) , ",
          "CONSTRAINT fk_region_site_service_id ",
            "FOREIGN KEY (service_id ) ",
            "REFERENCES ", @cenozo, ".service (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_region_site_region_id ",
            "FOREIGN KEY (region_id ) ",
            "REFERENCES ", @cenozo, ".region (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_region_site_site_id ",
            "FOREIGN KEY (site_id ) ",
            "REFERENCES ", @cenozo, ".site (id ) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO ", @cenozo, ".region_site SELECT * FROM ", @cenozo, ".service_region_site" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "DROP TABLE ", @cenozo, ".service_region_site" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;
  END //
DELIMITER ;

CALL patch_region_site();
DROP PROCEDURE IF EXISTS patch_region_site;
