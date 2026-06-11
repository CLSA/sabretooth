CREATE TRIGGER region_site_BEFORE_DELETE
  BEFORE DELETE ON region_site
  FOR EACH ROW

  BEGIN

    REPLACE INTO cenozo.participant_site( application_id, participant_id, site_id, default_site_id )
    SELECT
      application_has_cohort.application_id,
      participant.id,
      application_has_participant.preferred_site_id,
      NULL
    FROM cenozo.application_has_cohort
    JOIN cenozo.participant ON application_has_cohort.cohort_id = participant.cohort_id
    LEFT JOIN cenozo.participant_primary_address ON participant.id = participant_primary_address.participant_id
    LEFT JOIN cenozo.address ON participant_primary_address.address_id = address.id
    LEFT JOIN cenozo.region ON address.region_id = region.id
    LEFT JOIN region_site ON region.id = region_site.region_id
    LEFT JOIN cenozo.site AS region_site_site
      ON region_site.site_id = region_site_site.id
      AND participant.language_id = region_site.language_id
    LEFT JOIN cenozo.application_has_participant
      ON application_has_cohort.application_id = application_has_participant.application_id
      AND application_has_participant.participant_id = participant.id
    WHERE region_site.site_id <=> region_site_site.id
    AND application_has_cohort.application_id = 49
    AND application_has_cohort.grouping = "region"
    AND region_site.id = OLD.id;

  END */;;