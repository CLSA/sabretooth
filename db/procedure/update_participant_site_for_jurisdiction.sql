CREATE PROCEDURE update_participant_site_for_jurisdiction( IN proc_jurisdiction_id INT(10) UNSIGNED )
BEGIN

    REPLACE INTO cenozo.participant_site( application_id, participant_id, site_id, default_site_id )
    SELECT
      application_has_cohort.application_id,
      participant.id,
      IF(
        ISNULL( application_has_participant.preferred_site_id ),
        jurisdiction.site_id,
        application_has_participant.preferred_site_id
      ) AS site_id,
      jurisdiction.site_id AS default_site_id
    FROM cenozo.application_has_cohort
    JOIN cenozo.participant ON application_has_cohort.cohort_id = participant.cohort_id
    LEFT JOIN cenozo.participant_primary_address ON participant.id = participant_primary_address.participant_id
    LEFT JOIN cenozo.address ON participant_primary_address.address_id = address.id
    LEFT JOIN jurisdiction ON address.postcode = jurisdiction.postcode
    LEFT JOIN cenozo.site AS jurisdiction_site ON jurisdiction.site_id = jurisdiction_site.id
    LEFT JOIN cenozo.application_has_participant
      ON application_has_cohort.application_id = application_has_participant.application_id
      AND application_has_participant.participant_id = participant.id
    WHERE jurisdiction.site_id <=> jurisdiction_site.id
    AND application_has_cohort.application_id = 49
    AND application_has_cohort.grouping = "jurisdiction"
    AND jurisdiction.id = proc_jurisdiction_id;

  END ;;
