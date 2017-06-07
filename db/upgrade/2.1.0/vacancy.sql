DROP PROCEDURE IF EXISTS patch_vacancy;
  DELIMITER //
  CREATE PROCEDURE patch_vacancy()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS vacancy ( ",
        "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "site_id INT UNSIGNED NOT NULL, ",
        "datetime DATETIME NOT NULL, ",
        "operators INT NOT NULL DEFAULT 1, ",
        "appointments INT NOT NULL DEFAULT 0, ",
        "PRIMARY KEY (id), ",
        "INDEX dk_datetime (datetime ASC), ",
        "INDEX fk_site_id (site_id ASC), ",
        "UNIQUE INDEX uq_site_id_datetime (site_id ASC, datetime ASC), ",
        "CONSTRAINT fk_vacancy_site_id ",
          "FOREIGN KEY (site_id) ",
          "REFERENCES ", @cenozo, ".site (id) ",
          "ON DELETE NO ACTION ",
          "ON UPDATE NO ACTION) ",
      "ENGINE = InnoDB" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;


DROP PROCEDURE IF EXISTS add_vacancies;
DELIMITER //
  CREATE PROCEDURE add_vacancies()
  BEGIN

    SELECT "Importing vacancies from existing shift and shift-templates" AS "";

    SET @test = ( SELECT COUNT(*) FROM vacancy );
    IF @test = 0 THEN
      -- create a list of all vacancies by day
      CREATE TEMPORARY TABLE temp_vacancy (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        update_timestamp TIMESTAMP NOT NULL,
        create_timestamp TIMESTAMP NOT NULL,
        site_id INT UNSIGNED NOT NULL,
        date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        operators INT UNSIGNED NOT NULL,
        PRIMARY KEY (id)
      );

      -- start with shift templates
      SET @cur_date = ( SELECT MIN( start_date ) FROM shift_template );
      SET @max_date = ( SELECT MAX( start_date ) FROM shift_template );

      REPEAT
        INSERT INTO temp_vacancy(
          update_timestamp, create_timestamp, site_id, date, start_time, end_time, operators )
        SELECT update_timestamp, create_timestamp, site_id, @cur_date,
          SUBTIME(
            start_time,
            TIMEDIFF(
              TIME( convert_tz( CONCAT( @cur_date, " 12:00:00" ), "UTC", "Canada/Eastern" ) ),
              TIME( convert_tz( "2000-01-01 12:00:00", "UTC", "Canada/Eastern" ) )
            )
          ) AS start_time,
          SUBTIME(
            end_time,
            TIMEDIFF(
              TIME( convert_tz( CONCAT( @cur_date, " 12:00:00" ), "UTC", "Canada/Eastern" ) ),
              TIME( convert_tz( "2000-01-01 12:00:00", "UTC", "Canada/Eastern" ) )
            )
          ) AS end_time,
          operators
        FROM shift_template
        WHERE start_date <= @cur_date
        AND end_date >= @cur_date
        AND CASE DAYOFWEEK( @cur_date )
          WHEN 1 THEN sunday = 1
          WHEN 2 THEN monday = 1
          WHEN 3 THEN tuesday = 1
          WHEN 4 THEN wednesday = 1
          WHEN 5 THEN thursday = 1
          WHEN 6 THEN friday = 1
          WHEN 7 THEN saturday = 1
          ELSE 0
        END = 1;

        SET @cur_date = DATE_ADD( @cur_date, INTERVAL 1 DAY );
      UNTIL @cur_date > @max_date END REPEAT;

      -- remove all temporary vacancies which are on the same day as a shift
      SET @sql = CONCAT(
        "CREATE TEMPORARY TABLE delete_vacancy ",
        "SELECT temp_vacancy.id ",
        "FROM temp_vacancy ",
        "JOIN ", @cenozo, ".site ON temp_vacancy.site_id = site.id ",
        "JOIN shift ON temp_vacancy.site_id = shift.site_id ",
        "AND temp_vacancy.date = DATE( CONVERT_TZ( shift.start_datetime, 'UTC', site.timezone ) )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DELETE FROM temp_vacancy
      WHERE id IN ( SELECT id FROM delete_vacancy );
      DROP TABLE delete_vacancy;

      -- overwrite shift template entries with shifts
      SET @sql = CONCAT(
        "SELECT MIN( DATE( CONVERT_TZ( start_datetime, 'UTC', site.timezone ) ) ) INTO @cur_date ",
        "FROM shift JOIN ", @cenozo, ".site ON shift.site_id = site.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "SELECT MAX( DATE( CONVERT_TZ( start_datetime, 'UTC', site.timezone ) ) ) INTO @max_date ",
        "FROM shift JOIN ", @cenozo, ".site ON shift.site_id = site.id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      REPEAT
        SET @sql = CONCAT(
          "INSERT INTO temp_vacancy( ",
            "update_timestamp, create_timestamp, site_id, date, start_time, end_time, operators ) ",
          "SELECT shift.update_timestamp, shift.create_timestamp, ",
            "site_id, @cur_date, TIME( start_datetime ), TIME( end_datetime ), 1 ",
          "FROM shift ",
          "JOIN ", @cenozo, ".site ON shift.site_id = site.id ",
          "WHERE DATE( CONVERT_TZ( start_datetime, 'UTC', site.timezone ) ) <= @cur_date ",
          "AND DATE( CONVERT_TZ( start_datetime, 'UTC', site.timezone ) ) >= @cur_date" );
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;

        SET @cur_date = DATE_ADD( @cur_date, INTERVAL 1 DAY );
      UNTIL @cur_date > @max_date END REPEAT;

      -- now create a list of all vacancies by half-hour increments
      CALL _create_vacancy_records();

      DROP TABLE temp_vacancy;

    END IF;

  END //
DELIMITER ;


DROP PROCEDURE IF EXISTS _create_vacancy_records;
DELIMITER //
CREATE PROCEDURE _create_vacancy_records()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE update_timestamp_val TIMESTAMP;
    DECLARE create_timestamp_val TIMESTAMP;
    DECLARE site_id_val INT UNSIGNED;
    DECLARE date_val DATE;
    DECLARE start_time_val TIME;
    DECLARE end_time_val TIME;
    DECLARE operators_val INT UNSIGNED;

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT update_timestamp, create_timestamp, site_id, date, start_time, end_time, operators
    FROM temp_vacancy
    ORDER BY date;

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    SELECT FOUND_ROWS() INTO num_rows;

    the_loop: LOOP

      FETCH the_cursor
      INTO update_timestamp_val, create_timestamp_val,
           site_id_val, date_val, start_time_val, end_time_val, operators_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
        CLOSE the_cursor;
        LEAVE the_loop;
      END IF;

      -- add vacancies for every 30 minute increment
      SET @cur_datetime = CONCAT( date_val, " ", start_time_val );
      SET @max_datetime = CONCAT( date_val, " ", end_time_val );
      IF @cur_datetime > @max_datetime THEN
        SET @max_datetime = DATE_ADD( @max_datetime, INTERVAL 1 DAY );
      END IF;

      REPEAT
        INSERT INTO vacancy( update_timestamp, create_timestamp, site_id, datetime, operators )
        SELECT update_timestamp_val, create_timestamp_val,
               site_id_val, @cur_datetime, operators_val
        ON DUPLICATE KEY UPDATE operators = operators + VALUES( operators );

        SET @cur_datetime = ADDTIME( @cur_datetime, "00:30:00" );
      UNTIL @cur_datetime >= @max_datetime END REPEAT;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;


CALL patch_vacancy();
CALL add_vacancies();
DROP PROCEDURE IF EXISTS patch_vacancy;
DROP PROCEDURE IF EXISTS add_vacancies;
DROP PROCEDURE IF EXISTS _create_vacancy_records;
