-- add the new rescore_sid column
-- we need to create a procedure which only alters the qnaire table if the
-- rescore_sid column is missing
DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "qnaire"
      AND COLUMN_NAME = "rescore_sid" );
    IF @test = 0 THEN
      ALTER TABLE qnaire
      ADD COLUMN rescore_sid INT NULL DEFAULT NULL
      AFTER withdraw_sid;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
