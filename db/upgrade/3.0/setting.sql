DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN

    SELECT "Adding last_contacted columns to setting table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "setting"
    AND column_name = "last_contacted";

    IF @test = 0 THEN
      ALTER TABLE setting ADD COLUMN last_contacted TINYINT(1) NOT NULL DEFAULT 0 AFTER call_without_webphone;
    END IF;

  END //
DELIMITER ;

CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
