SELECT "Creating new update_appointment_vacancies procedure" AS "";

DROP procedure IF EXISTS update_appointment_vacancies;

DELIMITER $$
CREATE PROCEDURE update_appointment_vacancies (IN proc_appointment_id INT(10) UNSIGNED)
BEGIN
  IF proc_appointment_id IS NOT NULL THEN
    UPDATE appointment
    SET start_vacancy_id = (
      SELECT vacancy.id
      FROM appointment_has_vacancy
      JOIN vacancy ON appointment_has_vacancy.vacancy_id = vacancy.id
      WHERE appointment_id = proc_appointment_id
      ORDER BY vacancy.datetime LIMIT 1
    ),
    end_vacancy_id = (
      SELECT vacancy.id
      FROM appointment_has_vacancy
      JOIN vacancy ON appointment_has_vacancy.vacancy_id = vacancy.id
      WHERE appointment_id = proc_appointment_id
      ORDER BY vacancy.datetime DESC LIMIT 1
    )
    WHERE id = proc_appointment_id;
  END IF;
END$$

DELIMITER ;
