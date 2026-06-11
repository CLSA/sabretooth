CREATE TABLE recording (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  rank int(11) NOT NULL,
  name varchar(45) NOT NULL,
  record tinyint(1) NOT NULL,
  timer int(11) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rank (rank),
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;