CREATE TABLE service (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  method enum('DELETE','GET','PATCH','POST','PUT') NOT NULL,
  subject varchar(45) NOT NULL,
  resource tinyint(1) NOT NULL DEFAULT 0,
  restricted tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_method_subject_resource (method,subject,resource)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
