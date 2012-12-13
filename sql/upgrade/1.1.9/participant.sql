-- add the new "sourcing required" option to the status enum column
-- we need to create a procedure which only alters the participant table if the
-- the status column is missing the "sourcing required" option
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
      AND COLUMN_NAME = "status"
      AND COLUMN_TYPE NOT LIKE "%sourcing required%" );
    IF @test = 1 THEN
      ALTER TABLE participant
      MODIFY COLUMN status enum('deceased','deaf','mentally unfit','language barrier','age range','not canadian','federal reserve','armed forces','institutionalized','noncompliant','sourcing required','other') DEFAULT NULL
      AFTER site_id;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
