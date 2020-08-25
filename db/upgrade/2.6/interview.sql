DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Adding new method column to interview table" AS "";
    
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "method";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN method ENUM('phone', 'web') NOT NULL DEFAULT 'phone' AFTER site_id;
    END IF;

  END //
DELIMITER ;

CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
