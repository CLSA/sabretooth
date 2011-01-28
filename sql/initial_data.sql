-- -----------------------------------------------------
-- Data for table .`operation`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- generic operations
DELETE FROM `operation`;
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("login", "halt", "Logs out all users (except the user who executes this operation).");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("login", "suspend", "Prevents all users from logging in (except the user who executes this operation).");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("voip", "halt", "Disconnects all VOIP sessions.");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("voip", "suspend", "Prevents any new VOIP sessions from connecting.");

-- user
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("user", "remove", "Removes a user from the system.");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("user", "edit", "Edits a user's details.");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("user", "view", "View a user's details.");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("user", "add", "Add a new user to the system.");
INSERT INTO `operation` (`name`, `action`, `description`)
VALUES ("user", "llist", "List users in the system.");

-- build role permissions
DELETE FROM `role`;
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "administrator");
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "clerk");
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "operator");
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "supervisor" );
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "technician" );
INSERT INTO `role` (`id`, `name`)
VALUES (NULL, "viewer" );

DELETE FROM `role_has_operation`;
INSERT INTO `role_has_operation` (`role_id`, `operation`, `action`)
SELECT role.id, operation.name, operation.action
FROM role, operation
WHERE role.name in( "administrator", "supervisor" )
AND operation.name="user"
AND operation.action="llist";

-- TODO finish list

COMMIT;
