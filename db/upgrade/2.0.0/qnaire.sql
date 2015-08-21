DROP PROCEDURE IF EXISTS patch_qnaire;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire()
  BEGIN

    SELECT "Dropping default_interview_method_id column from qnaire table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "default_interview_method_id" );
    IF @test = 1 THEN
      ALTER TABLE qnaire
      DROP FOREIGN KEY fk_qnaire_default_interview_method_id,
      DROP INDEX fk_default_interview_method_id;

      ALTER TABLE qnaire DROP COLUMN default_interview_method_id;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
