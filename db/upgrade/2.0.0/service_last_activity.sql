DROP PROCEDURE IF EXISTS patch_service_last_activity;
  DELIMITER //
  CREATE PROCEDURE patch_service_last_activity()
  BEGIN

    SELECT "Creating new service_last_activity view" AS "";

    CREATE OR REPLACE VIEW service_last_activity AS
    SELECT service.id AS service_id,
           activity.id AS activity_id
    FROM service
    JOIN activity on service.id = activity.service_id
    WHERE activity.datetime = (
      SELECT MAX( a.datetime )
      FROM activity a
      WHERE a.service_id = service.id
    );

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_service_last_activity();
DROP PROCEDURE IF EXISTS patch_service_last_activity;
