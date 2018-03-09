DROP PROCEDURE IF EXISTS patch_interview;
  DELIMITER //
  CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Adding note column to interview table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "note" );
    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN note TEXT NULL;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
