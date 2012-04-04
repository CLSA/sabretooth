-- add the new timezone_offset and daylight_savings columns
-- we need to create a procedure which only alters the address table if these
-- columns are missing
DROP PROCEDURE IF EXISTS patch_address;
DELIMITER //
CREATE PROCEDURE patch_address()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "address"
      AND COLUMN_NAME = "timezone_offset" );
    IF @test = 0 THEN
      ALTER TABLE address
      ADD COLUMN daylight_savings TINYINT(1) NOT NULL
      AFTER postcode;
      ALTER TABLE address
      ADD COLUMN timezone_offset FLOAT NOT NULL
      AFTER postcode;
      -- now populate the new rows
      UPDATE address SET
      timezone_offset = (
        SELECT timezone_offset
        FROM postcode
        WHERE address.postcode LIKE CONCAT( postcode.name, "%" )
        ORDER BY CHAR_LENGTH( postcode.name ) DESC
        LIMIT 1 ),
      daylight_savings = (
        SELECT daylight_savings
        FROM postcode
        WHERE address.postcode LIKE CONCAT( postcode.name, "%" )
        ORDER BY CHAR_LENGTH( postcode.name ) DESC
        LIMIT 1 );
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_address();
DROP PROCEDURE IF EXISTS patch_address;
