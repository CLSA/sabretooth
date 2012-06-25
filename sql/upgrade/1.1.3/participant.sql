DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "gender" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN gender ENUM('male','female') NOT NULL
      AFTER last_name;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "date_of_birth" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN date_of_birth DATE NOT NULL
      AFTER gender;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "age_group_id" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN age_group_id INT UNSIGNED NULL DEFAULT NULL
      AFTER date_of_birth;
      ALTER TABLE participant
      ADD INDEX fk_age_group_id (age_group_id ASC);
      ALTER TABLE participant
      ADD CONSTRAINT fk_participant_age_group_id
      FOREIGN KEY (age_group_id)
      REFERENCES age_group (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;

-- add the status types to the status column
ALTER TABLE participant MODIFY status ENUM('deceased','deaf','mentally unfit','language barrier','age range','not canadian','federal reserve','armed forces','institutionalized','noncompliant','other') NULL DEFAULT NULL;
