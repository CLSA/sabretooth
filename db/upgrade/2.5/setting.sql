DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN

    SELECT "Adding new mail_name and mail_address columns to setting table" AS "";
    
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "setting"
    AND column_name = "mail_name";

    IF @test = 0 THEN
      ALTER TABLE setting ADD COLUMN mail_name VARCHAR(255) NULL AFTER site_id;
    END IF;

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "setting"
    AND column_name = "mail_address";

    IF @test = 0 THEN
      ALTER TABLE setting ADD COLUMN mail_address VARCHAR(127) NULL AFTER mail_name;
    END IF;

  END //
DELIMITER ;

CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
