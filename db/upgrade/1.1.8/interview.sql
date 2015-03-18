-- change the rescored column from enum to tinyint
-- we need to create a procedure which only alters the interview table if
-- the rescored column's type is enum
DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT DATA_TYPE
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "rescored" );
    IF @test = "enum" THEN
      ALTER TABLE interview
      MODIFY COLUMN rescored TINYINT(1) NOT NULL DEFAULT 0;
      UPDATE interview SET rescored = 0;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
