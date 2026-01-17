-- 1. 기본 테이블
CREATE TABLE `Users` (
  `user_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `email_norm` VARCHAR(255),
  `name` VARCHAR(50) UNIQUE,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `SocialAccount` (
  `social_account_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `provider` VARCHAR(50) NOT NULL,
  `provider_user_id` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `Region` (
  `region_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `country_code` CHAR(2) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `PlaceCategory` (
  `category_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `Place` (
  `place_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `category_id` BIGINT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `lat` DECIMAL(10, 7),
  `lng` DECIMAL(10, 7),
  `external_provider` VARCHAR(32) NOT NULL,
  `external_ref` VARCHAR(128),
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `Trip` (
  `trip_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `region_id` BIGINT,
  `title` VARCHAR(100) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `TripDay` (
  `trip_day_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `trip_id` BIGINT NOT NULL,
  `day_no` INT NOT NULL,
  `memo` VARCHAR(255),
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
CREATE TABLE `ScheduleItem` (
  `schedule_item_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `trip_day_id` BIGINT NOT NULL,
  `place_id` BIGINT,
  `seq_no` INT NOT NULL,
  `visit_time` DATETIME,
  `memo` VARCHAR(255),
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL
);
-- 2. 유니크 인덱스
-- 소셜 계정: provider + provider_user_id 한 번만
CREATE UNIQUE INDEX `uq_social_account_provider_user` ON `SocialAccount` (`provider`, `provider_user_id`);
-- 소셜 계정: 1:1 매핑 (유저당 하나)
CREATE UNIQUE INDEX `uq_social_account_user` ON `SocialAccount` (`user_id`);
-- 장소: 외부 provider + ref 한 번만
CREATE UNIQUE INDEX `uq_place_external_provider_ref` ON `Place` (`external_provider`, `external_ref`);
-- TripDay: 한 Trip 안에서 day_no 유니크
CREATE UNIQUE INDEX `uq_trip_day_trip_day_no` ON `TripDay` (`trip_id`, `day_no`);
-- ScheduleItem: 한 TripDay 안에서 seq_no 유니크
CREATE UNIQUE INDEX `uq_schedule_item_trip_day_seq_no` ON `ScheduleItem` (`trip_day_id`, `seq_no`);
-- 3. 외래 키
ALTER TABLE `SocialAccount`
ADD CONSTRAINT `fk_social_account_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`);
ALTER TABLE `Place`
ADD CONSTRAINT `fk_place_category` FOREIGN KEY (`category_id`) REFERENCES `PlaceCategory` (`category_id`);
ALTER TABLE `Trip`
ADD CONSTRAINT `fk_trip_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`);
ALTER TABLE `Trip`
ADD CONSTRAINT `fk_trip_region` FOREIGN KEY (`region_id`) REFERENCES `Region` (`region_id`);
ALTER TABLE `TripDay`
ADD CONSTRAINT `fk_trip_day_trip` FOREIGN KEY (`trip_id`) REFERENCES `Trip` (`trip_id`);
ALTER TABLE `ScheduleItem`
ADD CONSTRAINT `fk_schedule_item_trip_day` FOREIGN KEY (`trip_day_id`) REFERENCES `TripDay` (`trip_day_id`);
ALTER TABLE `ScheduleItem`
ADD CONSTRAINT `fk_schedule_item_place` FOREIGN KEY (`place_id`) REFERENCES `Place` (`place_id`);