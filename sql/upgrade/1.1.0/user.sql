-- add the new index to the active column
-- we need to create a procedure which only alters the user table if the active column
-- index is missing
DROP PROCEDURE IF EXISTS patch_user;
DELIMITER //
CREATE PROCEDURE patch_user()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "user"
      AND COLUMN_NAME = "active"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE user
      ADD INDEX dk_active (active ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_user();
DROP PROCEDURE IF EXISTS patch_user;
