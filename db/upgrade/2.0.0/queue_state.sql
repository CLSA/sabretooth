DROP PROCEDURE IF EXISTS patch_queue_state;
  DELIMITER //
  CREATE PROCEDURE patch_queue_state()
  BEGIN

    SELECT "Dropping defunct queue_state table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_state" );
    IF @test = 1 THEN
      DROP TABLE queue_state;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_state();
DROP PROCEDURE IF EXISTS patch_queue_state;
