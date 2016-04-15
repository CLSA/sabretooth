DROP PROCEDURE IF EXISTS patch_access;
DELIMITER //
CREATE PROCEDURE patch_access()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name IN ( "fk_activity_site_id", "fk_access_site_id" )
      GROUP BY unique_constraint_schema );

    SELECT "Creating new access table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS access ( ",
        "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "user_id INT UNSIGNED NOT NULL, ",
        "role_id INT UNSIGNED NOT NULL, ",
        "site_id INT UNSIGNED NOT NULL, ",
        "datetime DATETIME NULL, ",
        "microtime DOUBLE NULL, ",
        "PRIMARY KEY (id), ",
        "INDEX fk_user_id (user_id ASC), ",
        "INDEX fk_role_id (role_id ASC), ",
        "INDEX fk_site_id (site_id ASC), ",
        "UNIQUE INDEX uq_user_id_role_id_site_id (user_id ASC, role_id ASC, site_id ASC), ",
        "INDEX datetime_microtime (datetime ASC, microtime ASC), ",
        "CONSTRAINT fk_access_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE, ",
        "CONSTRAINT fk_access_role_id ",
          "FOREIGN KEY (role_id) ",
          "REFERENCES ", @cenozo, ".role (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE, ",
        "CONSTRAINT fk_access_site_id ",
          "FOREIGN KEY (site_id) ",
          "REFERENCES ", @cenozo, ".site (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE) ",
      "ENGINE = InnoDB" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_access();
DROP PROCEDURE IF EXISTS patch_access;
