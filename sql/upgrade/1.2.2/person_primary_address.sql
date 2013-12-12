DROP PROCEDURE IF EXISTS patch_person_primary_address;
DELIMITER //
CREATE PROCEDURE patch_person_primary_address()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = REPLACE( DATABASE(), 'sabretooth', 'cenozo' );

    SELECT "Recreating person_primary_address view" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "person_primary_address"
      AND VIEW_DEFINITION LIKE "%service_region_site%" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW ", @cenozo, ".person_primary_address AS ",
        "SELECT person_id, id AS address_id ",
        "FROM ", @cenozo, ".address AS t1 ",
        "WHERE t1.rank = ( ",
          "SELECT MIN( t2.rank ) ",
          "FROM ", @cenozo, ".address AS t2 ",
          "JOIN ", @cenozo, ".region ON t2.region_id = region.id ",
          "JOIN ", @cenozo, ".region_site ON region.id = region_site.region_id ",
          "WHERE t2.active ",
          "AND t1.person_id = t2.person_id ",
          "GROUP BY t2.person_id )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      
    END IF;
  END //
DELIMITER ;

CALL patch_person_primary_address();
DROP PROCEDURE IF EXISTS patch_person_primary_address;
