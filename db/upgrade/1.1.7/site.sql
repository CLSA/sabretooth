-- add the new voip columns
-- we need to create a procedure which only alters the site table if the
-- the voip columns do not exist
DROP PROCEDURE IF EXISTS patch_site;
DELIMITER //
CREATE PROCEDURE patch_site()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "site"
      AND COLUMN_NAME = "voip_host" );
    IF @test = 0 THEN
      ALTER TABLE site
      ADD COLUMN voip_host VARCHAR(45) NULL;
    END IF;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "site"
      AND COLUMN_NAME = "voip_xor_key" );
    IF @test = 0 THEN
      ALTER TABLE site
      ADD COLUMN voip_xor_key VARCHAR(45) NULL;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_site();
DROP PROCEDURE IF EXISTS patch_site;
