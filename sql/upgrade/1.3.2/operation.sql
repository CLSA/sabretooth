SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "add", true,
"View a form for creating new association between regions and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "delete", true,
"Removes an association between a region and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "edit", true,
"Edits an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "list", true,
"List associations between regions and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "new", true,
"Add a new association between a region and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "view", true,
"View an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "add", true,
"View a form for creating new association between postcodes and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "delete", true,
"Removes an association between a postcode and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "edit", true,
"Edits an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "list", true,
"List associations between postcodes and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "new", true,
"Add a new association between a postcode and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "view", true,
"View an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview_method", "list", true,
"Lists interviews." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_interview_method", true,
"A form to add new interview methods to a qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_interview_method", true,
"Remove interview methods from a questionnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new_interview_method", true,
"Add an interview method to a qnaire." );
