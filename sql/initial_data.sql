-- -----------------------------------------------------
-- Data for table .`operation`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- generic operations
DELETE FROM `operation`;
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "login", "halt", true, "Logs out all users (except the user who executes this operation).");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "login", "suspend", true, "Prevents all users from logging in (except the user who executes this operation).");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "voip", "halt", true, "Disconnects all VOIP sessions.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "voip", "suspend", true, "Prevents any new VOIP sessions from connecting.");

-- self
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("widget", "self", "home", false, "The current user's home screen.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("widget", "self", "settings", false, "The current user's settings manager.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("widget", "self", "shortcuts", false, "The current user's shortcut icon set.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "self", "set_site", false, "Change the current user's active site.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "self", "set_role", false, "Change the current user's active role.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "self", "set_theme", false, "Change the current user's web interface theme.");

-- user
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "user", "remove", true, "Removes a user from the system.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "user", "edit", true, "Edits a user's details.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("action", "user", "add", true, "Add a new user to the system.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("widget", "user", "view", true, "View a user's details.");
INSERT INTO `operation` (`type`, `subject`, `name`, `restricted`, `description`)
VALUES ("widget", "user", "list", true, "List users in the system.");


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
INSERT INTO `role_has_operation` (`role_id`, `type`, `subject`, `name`)
SELECT role.id, operation.type, operation.subject, operation.name
FROM role, operation
WHERE role.name in( "administrator", "supervisor" )
AND operation.subject="user";

COMMIT;
