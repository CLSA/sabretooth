SELECT "Creating new update_vacancy_appointment_count procedure" AS "";

DROP procedure IF EXISTS update_vacancy_appointment_count;

DELIMITER $$
CREATE PROCEDURE update_vacancy_appointment_count (IN proc_vacancy_id INT(10) UNSIGNED)
BEGIN
  IF proc_vacancy_id IS NOT NULL THEN
    UPDATE vacancy
    SET appointments = ( SELECT COUNT(*) FROM appointment WHERE vacancy_id = proc_vacancy_id )
    WHERE id = proc_vacancy_id;
  END IF;
END$$

DELIMITER ;
