DROP PROCEDURE IF EXISTS patch_recording_file;
  DELIMITER //
  CREATE PROCEDURE patch_recording_file()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Creating new recording_file table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "recording_file" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS recording_file ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "recording_id INT UNSIGNED NOT NULL, ",
          "language_id INT UNSIGNED NOT NULL, ",
          "filename VARCHAR(255) NOT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_recording_id (recording_id ASC), ",
          "INDEX fk_language_id (language_id ASC), ",
          "UNIQUE INDEX uq_recording_id_language_id (recording_id ASC, language_id ASC), ",
          "CONSTRAINT fk_recording_file_recording_id ",
            "FOREIGN KEY (recording_id) ",
            "REFERENCES recording (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE CASCADE, ",
          "CONSTRAINT fk_recording_file_language_id ",
            "FOREIGN KEY (language_id) ",
            "REFERENCES ", @cenozo, ".language (id) ",
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
CALL patch_recording_file();
DROP PROCEDURE IF EXISTS patch_recording_file;
