DROP PROCEDURE IF EXISTS patch_appointment_mail;
DELIMITER //
CREATE PROCEDURE patch_appointment_mail()
  BEGIN

    SELECT "Adding delay_offset and delay_unit columns to appointment_mail table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "appointment_mail"
    AND column_name = "delay";

    IF @test = 1 THEN
      ALTER TABLE appointment_mail DROP INDEX uq_site_id_language_id_delay;

      ALTER TABLE appointment_mail
        CHANGE COLUMN delay delay_offset INT(10) UNSIGNED NULL DEFAULT NULL,
        ADD COLUMN delay_unit ENUM('days', 'immediately') NOT NULL DEFAULT 'days' AFTER delay_offset;
    END IF;

  END //
DELIMITER ;

CALL patch_appointment_mail();
DROP PROCEDURE IF EXISTS patch_appointment_mail;


DELIMITER $$

DROP TRIGGER IF EXISTS appointment_mail_BEFORE_INSERT$$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_mail_BEFORE_INSERT BEFORE INSERT ON appointment_mail FOR EACH ROW
BEGIN
  IF( "immediately" = NEW.delay_unit AND NEW.delay_offset IS NOT NULL ) THEN
    SET NEW.delay_offset = NULL;
  ELSE
    IF( "immediately" != NEW.delay_unit AND NEW.delay_offset IS NULL ) THEN
      SET NEW.delay_offset = 1;
    END IF;
  END IF;
END$$

DROP TRIGGER IF EXISTS appointment_mail_BEFORE_UPDATE;
CREATE DEFINER = CURRENT_USER TRIGGER appointment_mail_BEFORE_UPDATE BEFORE UPDATE ON appointment_mail FOR EACH ROW
BEGIN
  IF( "immediately" = NEW.delay_unit AND NEW.delay_offset IS NOT NULL ) THEN
    SET NEW.delay_offset = NULL;
  ELSE
    IF( "immediately" != NEW.delay_unit AND NEW.delay_offset IS NULL ) THEN
      SET NEW.delay_offset = 1;
    END IF;
  END IF;
END$$

DELIMITER ;
