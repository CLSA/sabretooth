-- removing "rescored" column
DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN
    SELECT "Removing defunct rescored column from interview table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "rescored" );
    IF @test = 1 THEN
      ALTER TABLE interview DROP COLUMN rescored;
    END IF;

    SELECT "Adding new interview_method_id column to interview table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 0 THEN
      ALTER TABLE interview
      ADD COLUMN interview_method_id INT UNSIGNED NOT NULL
      AFTER qnaire_id;

      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      SET @sql = CONCAT(
        "ALTER TABLE interview ",
        "ADD INDEX fk_interview_method_id( interview_method_id ASC ), "
        "ADD CONSTRAINT fk_interview_interview_method_id ",
        "FOREIGN KEY( interview_method_id ) REFERENCES interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      UPDATE interview SET interview_method_id = (
        SELECT id FROM interview_method WHERE name = "operator" );

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
