DROP PROCEDURE IF EXISTS patch_activity;
  DELIMITER //
  CREATE PROCEDURE patch_activity()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_system_message_site_id" );

    SELECT "Redefining activity table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "activity"
      AND COLUMN_NAME = "service_id" );
    IF @test = 0 THEN
      DROP TABLE IF EXISTS activity;

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS activity ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "role_id INT UNSIGNED NOT NULL, ",
          "service_id INT UNSIGNED NOT NULL, ",
          "elapsed FLOAT NOT NULL DEFAULT 0, ",
          "status INT NULL, ",
          "datetime DATETIME NOT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_user_id (user_id ASC), ",
          "INDEX fk_site_id (site_id ASC), ",
          "INDEX fk_role_id (role_id ASC), ",
          "INDEX fk_service_id (service_id ASC), ",
          "INDEX dk_datetime (datetime ASC), ",
          "INDEX dk_elapsed (elapsed ASC), ",
          "CONSTRAINT fk_activity_user_id ",
            "FOREIGN KEY (user_id) ",
            "REFERENCES ", @cenozo, ".user (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_activity_service_id ",
            "FOREIGN KEY (service_id) ",
            "REFERENCES service (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
