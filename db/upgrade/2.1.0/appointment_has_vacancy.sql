DROP PROCEDURE IF EXISTS patch_appointment_has_vacancy;
  DELIMITER //
  CREATE PROCEDURE patch_appointment_has_vacancy()
  BEGIN

    SET @cenozo = ( 
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );
    SET @application = ( SELECT SUBSTRING( DATABASE(), LOCATE( '@', USER() )+1 ) );

    SELECT "Creating new appointment_has_vacancy table" AS "";

    CREATE TABLE IF NOT EXISTS appointment_has_vacancy (
      appointment_id INT UNSIGNED NOT NULL,
      vacancy_id INT UNSIGNED NOT NULL,
      update_timestamp TIMESTAMP NOT NULL,
      create_timestamp TIMESTAMP NOT NULL,
      PRIMARY KEY (appointment_id, vacancy_id),
      INDEX fk_vacancy_id (vacancy_id ASC),
      INDEX fk_appointment_id (appointment_id ASC),
      CONSTRAINT fk_appointment_has_vacancy_appointment_id
        FOREIGN KEY (appointment_id)
        REFERENCES appointment (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
      CONSTRAINT fk_appointment_has_vacancy_vacancy_id
        FOREIGN KEY (vacancy_id)
        REFERENCES vacancy (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
    ENGINE = InnoDB;

    SET @test = ( SELECT COUNT(*) FROM appointment_has_vacancy );
    IF @test = 0 THEN
      SELECT "Rounding all appointments to the nearest half-hour increment" AS "";

      UPDATE appointment
      SET datetime = CONCAT(
        IF(
          ( TIME_TO_SEC( datetime ) + 900 ) / 86400 >= 1,
          DATE( datetime ) + INTERVAL 1 DAY,
          DATE( datetime )
        ),
        " ",
        IF(
          ( TIME_TO_SEC( datetime ) + 900 ) / 86400 >= 1,
          SEC_TO_TIME( FLOOR( ( TIME_TO_SEC( datetime ) + 900 ) / 1800 - 48 ) * 1800 ),
          SEC_TO_TIME( FLOOR( ( TIME_TO_SEC( datetime ) + 900 ) / 1800 ) * 1800 )
        )
      )
      WHERE TIME( datetime ) != SEC_TO_TIME( FLOOR( ( TIME_TO_SEC( datetime ) + 900 ) / 1800 ) * 1800 );

      CREATE TEMPORARY TABLE temp_appointment (
        appointment_id INT UNSIGNED NOT NULL,
        site_id INT UNSIGNED NOT NULL,
        datetime DATETIME NOT NULL,
        type ENUM('long','short') NOT NULL
      );

      SET @sql = CONCAT(
        "INSERT INTO temp_appointment ",
        "SELECT appointment.id, IFNULL( interview.site_id, participant_site.site_id ), datetime, type ",
        "FROM appointment ",
        "JOIN interview ON appointment.interview_id = interview.id ",
        "JOIN ", @cenozo, ".participant_site ON interview.participant_id = participant_site.participant_id ",
        "JOIN ", @cenozo, ".application ON participant_site.application_id = application.id ",
        "WHERE IFNULL( interview.site_id, participant_site.site_id ) IS NOT NULL ",
        "AND application.name = '", @application, "'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      CALL _link_appointments_with_vacancies();

      DROP TABLE temp_appointment;

      SELECT "Update the vacancy's appointments column" AS "";

      UPDATE vacancy SET appointments = (
        SELECT COUNT(*) FROM appointment_has_vacancy WHERE vacancy_id = vacancy.id
      );
    END IF;
  END //
DELIMITER ;


DROP PROCEDURE IF EXISTS _link_appointments_with_vacancies;
  DELIMITER //
  CREATE PROCEDURE _link_appointments_with_vacancies()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE appointment_id_val INT UNSIGNED;
    DECLARE site_id_val INT UNSIGNED;
    DECLARE datetime_val DATETIME;
    DECLARE type_val INT UNSIGNED;

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT appointment_id, site_id, datetime, type
    FROM temp_appointment
    ORDER BY datetime;

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    SELECT FOUND_ROWS() INTO num_rows;

    the_loop: LOOP

      FETCH the_cursor
      INTO appointment_id_val, site_id_val, datetime_val, type_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
        CLOSE the_cursor;
        LEAVE the_loop;
      END IF;

      -- associate vacancies for every 30 minute increment
      SET @cur_datetime = datetime_val;
      SET @max_datetime = IF( type_val = "short", datetime_val + INTERVAL 1 HOUR, datetime_val + INTERVAL 2 HOUR );

      REPEAT
        INSERT INTO appointment_has_vacancy( appointment_id, vacancy_id )
        SELECT appointment_id_val, vacancy.id
        FROM vacancy
        WHERE site_id = site_id_val
        AND datetime = @cur_datetime;

        -- if there is no vacancy then create one
        SET @row_count = ( SELECT ROW_COUNT() );
        IF @row_count = 0 THEN
          INSERT INTO vacancy
          SET site_id = site_id_val,
              datetime = @cur_datetime,
              operators = 1,
              appointments = 1;

          INSERT INTO appointment_has_vacancy
          SET appointment_id = appointment_id_val,
              vacancy_id = LAST_INSERT_ID();
        END IF;

        SET @cur_datetime = ADDTIME( @cur_datetime, "00:30:00" );
      UNTIL @cur_datetime >= @max_datetime END REPEAT;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;


-- now call the procedure and remove the procedure
CALL patch_appointment_has_vacancy();
DROP PROCEDURE IF EXISTS patch_appointment_has_vacancy;
DROP PROCEDURE IF EXISTS _link_appointments_with_vacancies;


SELECT "Editing appointment_has_vacancy triggers" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS appointment_has_vacancy_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_has_vacancy_AFTER_INSERT AFTER INSERT ON appointment_has_vacancy FOR EACH ROW
BEGIN
  CALL update_vacancy_appointment_count( NEW.vacancy_id );
END;$$

DROP TRIGGER IF EXISTS appointment_has_vacancy_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_has_vacancy_AFTER_UPDATE AFTER UPDATE ON appointment_has_vacancy FOR EACH ROW
BEGIN
  IF OLD.vacancy_id != NEW.vacancy_id THEN 
    CALL update_vacancy_appointment_count( OLD.vacancy_id );
    CALL update_vacancy_appointment_count( NEW.vacancy_id );
  END IF;
END;$$

DROP TRIGGER IF EXISTS appointment_has_vacancy_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_has_vacancy_AFTER_DELETE AFTER DELETE ON appointment_has_vacancy FOR EACH ROW
BEGIN
  CALL update_vacancy_appointment_count( OLD.vacancy_id );
END;$$

DELIMITER ;
