-- add the new index to the city and postcode columns
-- we need to create a procedure which only alters the address table if the
-- city or postcode column indeces are missing
DROP PROCEDURE IF EXISTS patch_address;
DELIMITER //
CREATE PROCEDURE patch_address()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "address"
      AND COLUMN_NAME = "city"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE address
      ADD INDEX dk_city (city ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "address"
      AND COLUMN_NAME = "postcode"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE address
      ADD INDEX dk_postcode (postcode ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_address();
DROP PROCEDURE IF EXISTS patch_address;
