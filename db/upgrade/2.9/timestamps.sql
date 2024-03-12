DROP PROCEDURE IF EXISTS patch_timestamps;
DELIMITER //
CREATE PROCEDURE patch_timestamps()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE table_name_val VARCHAR(255);
    DECLARE column_name_val VARCHAR(255);

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT table_name, column_name
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND data_type = "timestamp"
    AND IFNULL( column_default, '' ) != "current_timestamp()";

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH  the_cursor
      INTO   table_name_val, column_name_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
          CLOSE the_cursor;
          LEAVE the_loop;
      END IF;

      SELECT CONCAT( "Setting default values for timestamps columns in table ", table_name_val ) AS "";

      -- convert the table to utf8mb4
      SET @sql = CONCAT(
        "ALTER TABLE ", table_name_val, " ",
        "MODIFY create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), ",
        "MODIFY update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP()"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;

CALL patch_timestamps();
DROP PROCEDURE IF EXISTS patch_timestamps;
