-- add the new index to the start_datetime and end_datetime columns
-- we need to create a procedure which only alters the away_time table if the
-- start_datetime or end_datetime column indices are missing
DROP PROCEDURE IF EXISTS patch_away_time_keys;
DELIMITER //
CREATE PROCEDURE patch_away_time_keys()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "away_time"
      AND COLUMN_NAME = "start_datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE away_time
      ADD INDEX dk_start_datetime (start_datetime ASC);
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "away_time"
      AND COLUMN_NAME = "end_datetime"
      AND COLUMN_KEY = "" );
    IF @test = 1 THEN
      ALTER TABLE away_time
      ADD INDEX dk_end_datetime (end_datetime ASC);
    END IF;
  END //
DELIMITER ;

-- add the new foreign references to the site and role tables
-- we need to create a procedure which only alters the away_time table if the
-- site_id or role_id columns are missing
DROP PROCEDURE IF EXISTS patch_away_time_columns;
DELIMITER //
CREATE PROCEDURE patch_away_time_columns()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "away_time"
      AND COLUMN_NAME = "site_id" );
    IF @test = 0 THEN
      -- add the column
      ALTER TABLE away_time
      ADD COLUMN site_id INT UNSIGNED NOT NULL
      AFTER user_id;
      -- populate the column
      UPDATE away_time
      SET site_id = (
        SELECT site_id FROM access WHERE user_id = away_time.user_id AND role_id = (
          SELECT id FROM role WHERE name = "operator" ) );
      -- create the constraint
      ALTER TABLE away_time
      ADD CONSTRAINT fk_away_time_site_id
      FOREIGN KEY (site_id)
      REFERENCES site (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "away_time"
      AND COLUMN_NAME = "role_id" );
    IF @test = 0 THEN
      -- add the column
      ALTER TABLE away_time
      ADD COLUMN role_id INT UNSIGNED NOT NULL
      AFTER site_id;
      -- populate the column
      UPDATE away_time
      SET role_id = ( SELECT id FROM role WHERE name = "operator" );
      -- create the constraint
      ALTER TABLE away_time
      ADD CONSTRAINT fk_away_time_role_id
      FOREIGN KEY (role_id)
      REFERENCES role (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_away_time_keys();
CALL patch_away_time_columns();
DROP PROCEDURE IF EXISTS patch_away_time_keys;
DROP PROCEDURE IF EXISTS patch_away_time_columns;
