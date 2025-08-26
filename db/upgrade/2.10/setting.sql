DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN

    SELECT "Adding appointment_duration column to setting table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "setting"
    AND column_name = "appointment_duration";

    IF @test = 0 THEN
      ALTER TABLE setting
      ADD COLUMN appointment_duration INT(10) UNSIGNED NOT NULL DEFAULT 60 AFTER calling_end_time;
    END IF;

  END //
DELIMITER ;

CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
