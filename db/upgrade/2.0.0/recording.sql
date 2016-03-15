DROP PROCEDURE IF EXISTS patch_recording;
  DELIMITER //
  CREATE PROCEDURE patch_recording()
  BEGIN

    SELECT "Replacing recording table with new schema" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "recording"
      AND COLUMN_NAME = "participant_id" );
    IF @test = 1 THEN
      DROP TABLE recording;

      CREATE TABLE IF NOT EXISTS recording (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        rank INT NOT NULL,
        name VARCHAR(45) NOT NULL,
        record TINYINT(1) NOT NULL,
        timer INT NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE INDEX uq_rank (rank ASC),
        UNIQUE INDEX uq_name (name ASC))
      ENGINE = InnoDB;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_recording();
DROP PROCEDURE IF EXISTS patch_recording;
