DROP TABLE IF EXISTS `users_mail`;

CREATE TABLE `users_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(8) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(512) NOT NULL,
  `role` varchar(8) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `phone_number` varchar(9) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=cp1250 COLLATE=cp1250_polish_ci;

LOCK TABLES `users_mail` WRITE;

INSERT INTO `users_mail` VALUES (1,'U001','admin','mail@mail.com','$2y$10$BG4S71KXVfLOw0T11TN3Ke8.WyE9Fmg7BKZ9e6lFoODzfCBgPP.BO','admin',1,NULL,'2025-01-31 12:18:50');

UNLOCK TABLES;

DROP TABLE IF EXISTS `users_phone`;

CREATE TABLE `users_phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(8) NOT NULL,
  `phone_number` varchar(9) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `firebase_token` varchar(512) DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `one_time_password` varchar(6) DEFAULT NULL,
  `otp_time` datetime DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=cp1250 COLLATE=cp1250_polish_ci;

LOCK TABLES `users_phone` WRITE;

UNLOCK TABLES;