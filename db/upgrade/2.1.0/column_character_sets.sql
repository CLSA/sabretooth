DROP PROCEDURE IF EXISTS patch_column_character_sets;
DELIMITER //
CREATE PROCEDURE patch_column_character_sets()
  BEGIN
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );
    SET @application = ( SELECT SUBSTRING( DATABASE(), LOCATE( '@', USER() )+1 ) );

    SET @sql = CONCAT(
      "SELECT version INTO @version FROM ", @cenozo, ".application ",
      "WHERE name = '", @application, "'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    IF @version = "2.0.0" THEN
      CALL _patch_column_character_sets();
    END IF;
  END //
DELIMITER ;


DROP PROCEDURE IF EXISTS _patch_column_character_sets;
DELIMITER //
CREATE PROCEDURE _patch_column_character_sets()
  BEGIN

    -- Declare '_val' variables to read in each record from the cursor
    DECLARE table_name_val VARCHAR(64);
    DECLARE column_name_val VARCHAR(64);
    DECLARE data_type_val VARCHAR(64);
    DECLARE column_type_val LONGTEXT;
    DECLARE is_nullable_val VARCHAR(3);
    DECLARE character_maximum_length_val BIGINT(21) UNSIGNED;
    DECLARE column_default_val LONGTEXT;
    DECLARE column_comment_val VARCHAR(1024);

    -- Declare variables used just for cursor and loop control
    DECLARE no_more_rows BOOLEAN;
    DECLARE loop_cntr INT DEFAULT 0;
    DECLARE num_rows INT DEFAULT 0;

    -- Declare the cursor
    DECLARE the_cursor CURSOR FOR
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
           CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT, COLUMN_COMMENT
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND DATA_TYPE IN ( "char", "text", "tinytext", "mediumtext", "longtext", "varchar" );

    -- Declare 'handlers' for exceptions
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET no_more_rows = TRUE;

    -- 'open' the cursor and capture the number of rows returned
    -- (the 'select' gets invoked when the cursor is 'opened')
    OPEN the_cursor;
    select FOUND_ROWS() into num_rows;

    the_loop: LOOP

      FETCH the_cursor
      INTO table_name_val, column_name_val, data_type_val, column_type_val, is_nullable_val,
          character_maximum_length_val, column_default_val, column_comment_val;

      -- break out of the loop if
        -- 1) there were no records, or
        -- 2) we've processed them all
      IF no_more_rows THEN
        CLOSE the_cursor;
        LEAVE the_loop;
      END IF;

      SELECT CONCAT( "Repairing ", table_name_val, ".", column_name_val, " character set" ) AS "";

      -- convert the column to latin1
      SET @sql = CONCAT(
        "ALTER TABLE ", table_name_val, " ",
        "MODIFY ", column_name_val, " ", column_type_val, " ",
        "CHARACTER SET 'latin1' ",
        IF( "YES" = is_nullable_val, "NULL", "NOT NULL" ) );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- convert the column to a binary type
      SET @sql = CONCAT(
        "ALTER TABLE ", table_name_val, " MODIFY ", column_name_val, " ",
        CASE data_type_val
          WHEN "char" THEN CONCAT( "binary(", character_maximum_length_val, ")" )
          WHEN "text" THEN "blob"
          WHEN "tinytext" THEN "tinyblob"
          WHEN "mediumtext" THEN "mediumblob"
          WHEN "longtext" THEN "longblob"
          WHEN "varchar" THEN CONCAT( "varbinary(", character_maximum_length_val, ")" )
          ELSE "ERROR"
        END, " ",
        IF( "YES" = is_nullable_val, "NULL", "NOT NULL" ) );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- convert the column to utf8
      SET @sql = CONCAT(
        "ALTER TABLE ", table_name_val, " ",
        "MODIFY ", column_name_val, " ", column_type_val, " ",
        "CHARACTER SET 'utf8' ",
        IF( "YES" = is_nullable_val, "NULL", "NOT NULL" ),
        IF(
          column_default_val IS NOT NULL AND
          column_default_val NOT IN ( "CURRENT_TIMESTAMP", "0000-00-00 00:00:00" ),
          CONCAT( " DEFAULT '", column_default_val, "'" ),
          ""
        ),
        IF(
          0 < CHAR_LENGTH( column_comment_val ),
          CONCAT( " COMMENT \"", column_comment_val, "\"" ),
          ""
        ) );
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
DROP PROCEDURE IF EXISTS _patch_column_character_sets;
