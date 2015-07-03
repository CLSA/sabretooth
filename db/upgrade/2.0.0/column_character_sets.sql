DROP PROCEDURE IF EXISTS patch_column_character_sets;
DELIMITER //
CREATE PROCEDURE patch_column_character_sets()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE table_name_val VARCHAR(64);
    DECLARE column_name_val VARCHAR(64);
    DECLARE is_nullable_val VARCHAR(3);

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND DATA_TYPE = "mediumtext";

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH  the_cursor
      INTO   table_name_val, column_name_val, is_nullable_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
          CLOSE the_cursor;
          LEAVE the_loop;
      END IF;

      SELECT CONCAT( "Repairing column type for column ", column_name_val, " in table ", table_name_val ) AS "";

      -- convert the table to utf8
      SET @sql = CONCAT(
        "ALTER TABLE ", table_name_val, " ",
        "MODIFY ", column_name_val, " TEXT", IF( "NO" = is_nullable_val, " NOT NULL", "" ) );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- count the number of times looped
      SET loop_cntr = loop_cntr + 1;

    END LOOP the_loop;

  END //
DELIMITER ;

CALL patch_column_character_sets();
DROP PROCEDURE IF EXISTS patch_column_character_sets;
