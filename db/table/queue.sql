CREATE TABLE queue (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  name varchar(45) NOT NULL,
  title varchar(255) NOT NULL,
  rank int(10) unsigned DEFAULT NULL,
  time_specific tinyint(1) NOT NULL,
  parent_queue_id int(10) unsigned DEFAULT NULL,
  description mediumtext DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name),
  UNIQUE KEY uq_rank (rank),
  KEY fk_parent_queue_id (parent_queue_id),
  CONSTRAINT fk_queue_parent_queue_id
    FOREIGN KEY (parent_queue_id)
    REFERENCES queue (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
