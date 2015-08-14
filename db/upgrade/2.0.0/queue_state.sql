DROP PROCEDURE IF EXISTS patch_queue_state;
  DELIMITER //
  CREATE PROCEDURE patch_queue_state()
  BEGIN

    SELECT "Removing enabled column from queue_state table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_state"
      AND COLUMN_NAME = "enabled" );
    IF @test = 1 THEN
      ALTER TABLE queue_state DROP COLUMN enabled;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_state();
DROP PROCEDURE IF EXISTS patch_queue_state;
