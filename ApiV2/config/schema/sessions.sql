-- CakePHP database session schema for PostgreSQL.
CREATE TABLE sessions (
  id varchar(40) PRIMARY KEY,
  created timestamp DEFAULT CURRENT_TIMESTAMP,
  modified timestamp DEFAULT CURRENT_TIMESTAMP,
  data bytea,
  expires integer
);
