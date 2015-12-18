DROP PROCEDURE IF EXISTS patch_shift_template;
  DELIMITER //
  CREATE PROCEDURE patch_shift_template()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_queue_state_site_id" );

    SELECT "Converting shift template times from UTC to site time" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM shift_template
      WHERE start_time > end_time );
    IF @test > 0 THEN
      SET @sql = CONCAT(
        "UPDATE shift_template ",
        "JOIN ", @cenozo, ".site ",
        "ON shift_template.site_id = site.id ",
        "SET start_time = TIME( CONVERT_TZ( CONCAT( '2000-01-01 ', start_time ), 'UTC', site.timezone ) ), ",
            "end_time = TIME( CONVERT_TZ( CONCAT( '2000-01-01 ', end_time ), 'UTC', site.timezone ) )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_shift_template();
DROP PROCEDURE IF EXISTS patch_shift_template;
