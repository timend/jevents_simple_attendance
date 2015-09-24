DROP TABLE IF EXISTS `#__simple_attendance`;
 
CREATE TABLE `#__simple_attendance` (
	`rp_id`       INT(11)     NOT NULL,
	`user_id`     INT(11)     NOT NULL,
	PRIMARY KEY (`rp_id`,`user_id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;