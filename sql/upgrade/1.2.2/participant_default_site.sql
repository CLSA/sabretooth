DROP PROCEDURE IF EXISTS patch_participant_default_site;
DELIMITER //
CREATE PROCEDURE patch_participant_default_site()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = REPLACE( DATABASE(), 'sabretooth', 'cenozo' );

    SELECT "Recreating participant_default_site view" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = @cenozo
      AND TABLE_NAME = "participant_default_site"
      AND VIEW_DEFINITION LIKE "%service_region_site%" );
    IF @test = 1 THEN

      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW participant_default_site AS ",
        "SELECT service.id AS service_id, ",
               "participant.id AS participant_id, ",
               "IF( ",
                 "service_has_cohort.grouping = 'jurisdiction', ",
                 "jurisdiction.site_id, ",
                 "region_site.site_id ",
               ") AS site_id ",
        "FROM service ",
        "CROSS JOIN participant ",
        "JOIN service_has_cohort ON service.id = service_has_cohort.service_id ",
        "AND service_has_cohort.cohort_id = participant.cohort_id ",
        "LEFT JOIN participant_primary_address ON participant.id = participant_primary_address.participant_id ",
        "LEFT JOIN address ON participant_primary_address.address_id = address.id ",
        "LEFT JOIN jurisdiction ON address.postcode = jurisdiction.postcode ",
        "AND service.id = jurisdiction.service_id ",
        "LEFT JOIN region ON address.region_id = region.id ",
        "LEFT JOIN region_site ON region.id = region_site.region_id ",
        "AND service.id = region_site.service_id " );
      SELECT @sql AS "RUN THIS COMMAND IN CENOZO DATABASE:";

    END IF;
  END //
DELIMITER ;

CALL patch_participant_default_site();
DROP PROCEDURE IF EXISTS patch_participant_default_site;
