-- add the new rescored column
-- we need to create a procedure which only alters the interview table if the
-- rescored column is missing
DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "interview"
      AND COLUMN_NAME = "rescored" );
    IF @test = 0 THEN
      ALTER TABLE interview
      ADD COLUMN rescored ENUM('Yes','No','N/A') NOT NULL DEFAULT 'N/A'
      AFTER completed;
      ALTER TABLE interview
      ADD INDEX dk_rescored (rescored ASC);
      UPDATE interview SET rescored = 'No';
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
