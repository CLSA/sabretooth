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
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
