CREATE TRIGGER appointment_mail_BEFORE_INSERT
BEFORE INSERT ON appointment_mail FOR EACH ROW
BEGIN
  IF( "immediately" = NEW.delay_unit AND NEW.delay_offset IS NOT NULL ) THEN
    SET NEW.delay_offset = NULL;
  ELSE
    IF( "immediately" != NEW.delay_unit AND NEW.delay_offset IS NULL ) THEN
      SET NEW.delay_offset = 1;
    END IF;
  END IF;
END$$