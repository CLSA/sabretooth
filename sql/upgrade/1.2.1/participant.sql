DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'mastodon', DATABASE() ) - 1 ),
                          'cenozo' );

    -- add the all_sites column if it is missing
    SELECT "Adding 'duplicate' to participant.status enum column" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "status"
      AND COLUMN_TYPE NOT LIKE "%duplicate%" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "ALTER TABLE ", @cenozo, ".participant ",
        "MODIFY COLUMN status ENUM('deceased','deaf','mentally unfit','language barrier',",
          "'age range','not canadian','federal reserve','armed forces','institutionalized',",
          "'noncompliant','sourcing required','unreachable','duplicate','other') NULL DEFAULT NULL" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
