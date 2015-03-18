-- removing "rescore_sid" column
DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN
    SELECT "Removing defunct rescore_sid column from interview table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "rescore_sid" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN rescore_sid;
    END IF;

    SELECT "Adding new interview_method_id column to qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "interview_method_id" );
    IF @test = 0 THEN
      ALTER TABLE qnaire
      ADD COLUMN interview_method_id INT UNSIGNED NOT NULL
      AFTER rank;

      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      SET @sql = CONCAT(
        "ALTER TABLE qnaire ",
        "ADD INDEX fk_interview_method_id( interview_method_id ASC ), "
        "ADD CONSTRAINT fk_qnaire_interview_method_id ",
        "FOREIGN KEY( interview_method_id ) REFERENCES interview_method( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      UPDATE qnaire SET interview_method_id = (
        SELECT id FROM interview_method WHERE name = "operator" );

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
