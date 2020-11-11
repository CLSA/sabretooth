DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN

    SELECT "Adding new web_version column to qnaire table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire"
    AND column_name = "web_version";

    IF @test = 0 THEN
      ALTER TABLE qnaire ADD COLUMN web_version TINYINT(1) NOT NULL DEFAULT 0 AFTER script_id;
    END IF;

    SELECT "Adding new allow_missing_consent column to qnaire table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire"
    AND column_name = "allow_missing_consent";

    IF @test = 0 THEN
      ALTER TABLE qnaire ADD COLUMN allow_missing_consent TINYINT(1) NOT NULL DEFAULT 1 AFTER script_id;
    END IF;

    SELECT "Replacing delay with delay_offset and delay_unit columns in qnaire table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire"
    AND column_name = "delay";

    IF @test = 1 THEN
      ALTER TABLE qnaire
      ADD COLUMN `delay_unit` ENUM('day', 'week', 'month') NOT NULL DEFAULT 'week' AFTER delay;

      ALTER TABLE qnaire
      CHANGE COLUMN delay delay_offset INT(11) NOT NULL DEFAULT 0;
    END IF;

  END //
DELIMITER ;

CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
