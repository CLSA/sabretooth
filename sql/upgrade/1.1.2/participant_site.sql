CREATE OR REPLACE VIEW participant_site AS
SELECT participant.id AS participant_id, IF( ISNULL( participant.site_id ), region.site_id, participant.site_id ) AS site_id
FROM participant
LEFT JOIN participant_primary_address
ON participant.id = participant_primary_address.participant_id
LEFT JOIN address
ON participant_primary_address.address_id = address.id
LEFT JOIN region
ON address.region_id = region.id;
