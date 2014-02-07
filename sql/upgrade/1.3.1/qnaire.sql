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
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
