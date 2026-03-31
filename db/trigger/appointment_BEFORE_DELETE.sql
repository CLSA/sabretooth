CREATE TRIGGER appointment_BEFORE_DELETE
BEFORE DELETE ON sabretooth.appointment
FOR EACH ROW
BEGIN
  DELETE FROM appointment_has_vacancy WHERE appointment_id = OLD.id;
END$$