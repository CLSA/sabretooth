DROP PROCEDURE IF EXISTS patch_export;
DELIMITER //
CREATE PROCEDURE patch_export()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Creating new export table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS export ( ",
        "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "title VARCHAR(255) NOT NULL, ",
        "user_id INT UNSIGNED NOT NULL, ",
        "description TEXT NULL DEFAULT NULL, ",
        "PRIMARY KEY (id), ",
        "UNIQUE INDEX uq_title (title ASC), ",
        "INDEX fk_user_id (user_id ASC), ",
        "CONSTRAINT fk_export_user_id ",
          "FOREIGN KEY (user_id) ",
          "REFERENCES ", @cenozo, ".user (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB " );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
    
  END //
DELIMITER ;

CALL patch_export();
DROP PROCEDURE IF EXISTS patch_export;
