-- -----------------------------------------------------
-- Roles
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- make sure all roles exist
INSERT IGNORE INTO cenozo.role( name, tier, all_sites ) VALUES
( "administrator", 3, true ),
( "curator", 2, true ),
( "helpline", 1, true ),
( "opal", 1, true ),
( "operator", 1, false ),
( "supervisor", 2, false );

-- add states to roles
-- TODO

COMMIT;
