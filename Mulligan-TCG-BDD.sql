-- Adminer 5.3.0 MySQL 8.0.43 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `street_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `author_id` int NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL,
  `deleted_by_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_9474526C4B89032C` (`post_id`),
  KEY `IDX_9474526C727ACA70` (`parent_id`),
  KEY `IDX_9474526CF675F31B` (`author_id`),
  KEY `IDX_9474526CC76F1F52` (`deleted_by_id`),
  CONSTRAINT `FK_9474526C4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9474526C727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `comment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9474526CC76F1F52` FOREIGN KEY (`deleted_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9474526CF675F31B` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `comment_vote`;
CREATE TABLE `comment_vote` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `comment_id` int NOT NULL,
  `type` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_USER_COMMENT` (`user_id`,`comment_id`),
  KEY `IDX_7C262788A76ED395` (`user_id`),
  KEY `IDX_7C262788F8697D13` (`comment_id`),
  CONSTRAINT `FK_7C262788A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7C262788F8697D13` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `deck`;
CREATE TABLE `deck` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `game_id` int NOT NULL,
  `game_format_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `archetype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL,
  `valid_deck` tinyint(1) NOT NULL,
  `total_cards` int NOT NULL,
  `average_cost` double DEFAULT NULL,
  `deckcode` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `published_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hearthstone_class` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `likes_count` int NOT NULL,
  `last_liked_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4FAC3637989D9B62` (`slug`),
  KEY `IDX_4FAC3637A76ED395` (`user_id`),
  KEY `IDX_4FAC3637E48FD905` (`game_id`),
  KEY `IDX_4FAC363748F3707` (`game_format_id`),
  CONSTRAINT `FK_4FAC363748F3707` FOREIGN KEY (`game_format_id`) REFERENCES `game_format` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_4FAC3637A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4FAC3637E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `deck_card`;
CREATE TABLE `deck_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deck_id` int NOT NULL,
  `hearthstone_card_id` int DEFAULT NULL,
  `pokemon_card_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `magic_card_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2AF3DCED111948DC` (`deck_id`),
  KEY `IDX_2AF3DCED45175FA5` (`hearthstone_card_id`),
  KEY `IDX_2AF3DCED26A6E6B1` (`pokemon_card_id`),
  KEY `IDX_2AF3DCED9F096991` (`magic_card_id`),
  CONSTRAINT `FK_2AF3DCED111948DC` FOREIGN KEY (`deck_id`) REFERENCES `deck` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AF3DCED26A6E6B1` FOREIGN KEY (`pokemon_card_id`) REFERENCES `pokemon_card` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AF3DCED45175FA5` FOREIGN KEY (`hearthstone_card_id`) REFERENCES `hearthstone_card` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AF3DCED9F096991` FOREIGN KEY (`magic_card_id`) REFERENCES `magic_card` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `deck_like`;
CREATE TABLE `deck_like` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deck_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `deck_user_unique` (`deck_id`,`user_id`),
  KEY `IDX_9084048D111948DC` (`deck_id`),
  KEY `IDX_9084048DA76ED395` (`user_id`),
  CONSTRAINT `FK_9084048D111948DC` FOREIGN KEY (`deck_id`) REFERENCES `deck` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9084048DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `address_id` int DEFAULT NULL,
  `created_by_id` int NOT NULL,
  `reviewed_by_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `event_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibility` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `end_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `registration_deadline` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `max_participants` int DEFAULT NULL,
  `current_participants` int NOT NULL,
  `is_online` tinyint(1) NOT NULL,
  `organizer_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organizer_id` int NOT NULL,
  `tags` json DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT NULL,
  `rules` longtext COLLATE utf8mb4_unicode_ci,
  `prizes` longtext COLLATE utf8mb4_unicode_ci,
  `stream_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `reviewed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `review_comment` longtext COLLATE utf8mb4_unicode_ci,
  `discriminator_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3BAE0AA7F5B7AF75` (`address_id`),
  KEY `IDX_3BAE0AA7B03A8386` (`created_by_id`),
  KEY `IDX_3BAE0AA7FC6B21F1` (`reviewed_by_id`),
  CONSTRAINT `FK_3BAE0AA7B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3BAE0AA7F5B7AF75` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3BAE0AA7FC6B21F1` FOREIGN KEY (`reviewed_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `event_games`;
CREATE TABLE `event_games` (
  `event_id` int NOT NULL,
  `game_id` int NOT NULL,
  PRIMARY KEY (`event_id`,`game_id`),
  KEY `IDX_BE389A1D71F7E88B` (`event_id`),
  KEY `IDX_BE389A1DE48FD905` (`game_id`),
  CONSTRAINT `FK_BE389A1D71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BE389A1DE48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `event_registration`;
CREATE TABLE `event_registration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `deck_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registered_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `confirmed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `cancelled_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `additional_data` json DEFAULT NULL,
  `deck_list` longtext COLLATE utf8mb4_unicode_ci,
  `deck_list_submitted` tinyint(1) NOT NULL,
  `deck_list_submitted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `checked_in` tinyint(1) NOT NULL,
  `checked_in_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `seed_number` int DEFAULT NULL,
  `final_ranking` int DEFAULT NULL,
  `tournament_stats` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8FBBAD5471F7E88B` (`event_id`),
  KEY `IDX_8FBBAD54A76ED395` (`user_id`),
  KEY `IDX_8FBBAD54111948DC` (`deck_id`),
  CONSTRAINT `FK_8FBBAD54111948DC` FOREIGN KEY (`deck_id`) REFERENCES `deck` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8FBBAD5471F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8FBBAD54A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `forum`;
CREATE TABLE `forum` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_official` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `game_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_852BBECD989D9B62` (`slug`),
  UNIQUE KEY `UNIQ_FORUM_NAME` (`name`),
  KEY `IDX_852BBECDE48FD905` (`game_id`),
  CONSTRAINT `FK_852BBECDE48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `game`;
CREATE TABLE `game` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `display_order` int NOT NULL,
  `api_config` json DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_232B318C989D9B62` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `game_format`;
CREATE TABLE `game_format` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `display_order` int NOT NULL,
  `format_config` json DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_A38FDF17E48FD905` (`game_id`),
  CONSTRAINT `FK_A38FDF17E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `hearthstone_card`;
CREATE TABLE `hearthstone_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hearthstone_set_id` int NOT NULL,
  `external_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dbf_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artist` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` int DEFAULT NULL,
  `attack` int DEFAULT NULL,
  `health` int DEFAULT NULL,
  `card_class` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rarity` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci,
  `flavor` longtext COLLATE utf8mb4_unicode_ci,
  `mechanics` json DEFAULT NULL,
  `faction` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_standard_legal` tinyint(1) NOT NULL,
  `is_wild_legal` tinyint(1) NOT NULL,
  `is_collectible` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_synced_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_662860429F75D7B0` (`external_id`),
  KEY `IDX_66286042CBC7BC51` (`hearthstone_set_id`),
  CONSTRAINT `FK_66286042CBC7BC51` FOREIGN KEY (`hearthstone_set_id`) REFERENCES `hearthstone_set` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `hearthstone_set`;
CREATE TABLE `hearthstone_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `external_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symbol_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_cards` int NOT NULL,
  `is_standard_legal` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B0183BD99F75D7B0` (`external_id`),
  KEY `IDX_B0183BD9E48FD905` (`game_id`),
  CONSTRAINT `FK_B0183BD9E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `magic_card`;
CREATE TABLE `magic_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `magic_set_id` int NOT NULL,
  `oracle_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scryfall_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `printed_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mana_cost` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cmc` double DEFAULT NULL,
  `type_line` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printed_type_line` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oracle_text` longtext COLLATE utf8mb4_unicode_ci,
  `printed_text` longtext COLLATE utf8mb4_unicode_ci,
  `flavor_text` longtext COLLATE utf8mb4_unicode_ci,
  `power` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toughness` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colors` json DEFAULT NULL,
  `color_identity` json DEFAULT NULL,
  `keywords` json DEFAULT NULL,
  `produced_mana` json DEFAULT NULL,
  `rarity` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_standard_legal` tinyint(1) NOT NULL,
  `is_commander_legal` tinyint(1) NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url_large` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artist` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artist_ids` json DEFAULT NULL,
  `illustration_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layout` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frame` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frame_effects` json DEFAULT NULL,
  `border_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `security_stamp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `watermark` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_promo` tinyint(1) NOT NULL,
  `is_reprint` tinyint(1) NOT NULL,
  `is_reserved` tinyint(1) NOT NULL,
  `is_full_art` tinyint(1) NOT NULL,
  `is_textless` tinyint(1) NOT NULL,
  `is_booster` tinyint(1) NOT NULL,
  `is_digital` tinyint(1) NOT NULL,
  `games` json DEFAULT NULL,
  `finishes` json DEFAULT NULL,
  `multiverse_ids` json DEFAULT NULL,
  `mtgo_id` int DEFAULT NULL,
  `arena_id` int DEFAULT NULL,
  `tcgplayer_id` int DEFAULT NULL,
  `cardmarket_id` int DEFAULT NULL,
  `edhrec_rank` int DEFAULT NULL,
  `penny_rank` int DEFAULT NULL,
  `released_at` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_synced_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4BF8B0E4E13D77CC` (`oracle_id`),
  UNIQUE KEY `UNIQ_4BF8B0E4C4213070` (`scryfall_id`),
  KEY `IDX_4BF8B0E4B549583A` (`magic_set_id`),
  KEY `idx_magic_card_oracle_id` (`oracle_id`),
  KEY `idx_magic_card_scryfall_id` (`scryfall_id`),
  KEY `idx_magic_card_standard_legal` (`is_standard_legal`),
  KEY `idx_magic_card_commander_legal` (`is_commander_legal`),
  KEY `idx_magic_card_rarity` (`rarity`),
  KEY `idx_magic_card_cmc` (`cmc`),
  CONSTRAINT `FK_4BF8B0E4B549583A` FOREIGN KEY (`magic_set_id`) REFERENCES `magic_set` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `magic_set`;
CREATE TABLE `magic_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `scryfall_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scryfall_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `search_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_synced_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_62271026C4213070` (`scryfall_id`),
  UNIQUE KEY `UNIQ_62271026E2D2D211` (`set_code`),
  KEY `IDX_62271026E48FD905` (`game_id`),
  KEY `idx_magic_set_code` (`set_code`),
  KEY `idx_magic_set_scryfall_id` (`scryfall_id`),
  KEY `idx_magic_set_type` (`set_type`),
  CONSTRAINT `FK_62271026E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `read_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_event_id` int DEFAULT NULL,
  `related_user_id` int DEFAULT NULL,
  `priority` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CAA76ED395` (`user_id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `IDX_BF5476CAD774A626` (`related_event_id`),
  KEY `IDX_BF5476CA98771930` (`related_user_id`),
  KEY `idx_type_created` (`type`,`created_at`),
  CONSTRAINT `FK_BF5476CA98771930` FOREIGN KEY (`related_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BF5476CAD774A626` FOREIGN KEY (`related_event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `pokemon_card`;
CREATE TABLE `pokemon_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pokemon_set_id` int NOT NULL,
  `external_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `illustrator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rarity` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `types` json DEFAULT NULL,
  `hp` int DEFAULT NULL,
  `is_standard_legal` tinyint(1) NOT NULL,
  `is_expanded_legal` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_synced_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2ABDE6909F75D7B0` (`external_id`),
  KEY `IDX_2ABDE6907C12714C` (`pokemon_set_id`),
  CONSTRAINT `FK_2ABDE6907C12714C` FOREIGN KEY (`pokemon_set_id`) REFERENCES `pokemon_set` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `pokemon_set`;
CREATE TABLE `pokemon_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `external_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symbol_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_cards` int NOT NULL,
  `official_cards` int NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4C8EB7F89F75D7B0` (`external_id`),
  KEY `IDX_4C8EB7F8E48FD905` (`game_id`),
  CONSTRAINT `FK_4C8EB7F8E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `post`;
CREATE TABLE `post` (
  `id` int NOT NULL AUTO_INCREMENT,
  `author_id` int NOT NULL,
  `forum_id` int DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL,
  `is_pinned` tinyint(1) NOT NULL,
  `is_locked` tinyint(1) NOT NULL,
  `attachments` json DEFAULT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_preview` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `post_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_by_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5A8A6C8D989D9B62` (`slug`),
  KEY `IDX_5A8A6C8DF675F31B` (`author_id`),
  KEY `IDX_5A8A6C8D29CCBAD0` (`forum_id`),
  KEY `IDX_5A8A6C8DC76F1F52` (`deleted_by_id`),
  CONSTRAINT `FK_5A8A6C8D29CCBAD0` FOREIGN KEY (`forum_id`) REFERENCES `forum` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_5A8A6C8DC76F1F52` FOREIGN KEY (`deleted_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_5A8A6C8DF675F31B` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `post_save`;
CREATE TABLE `post_save` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_USER_POST_SAVE` (`user_id`,`post_id`),
  KEY `IDX_9C335DD5A76ED395` (`user_id`),
  KEY `IDX_9C335DD54B89032C` (`post_id`),
  CONSTRAINT `FK_9C335DD54B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9C335DD5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `post_vote`;
CREATE TABLE `post_vote` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `type` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_USER_POST` (`user_id`,`post_id`),
  KEY `IDX_9345E26FA76ED395` (`user_id`),
  KEY `IDX_9345E26F4B89032C` (`post_id`),
  CONSTRAINT `FK_9345E26F4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9345E26FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `role_request`;
CREATE TABLE `role_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reviewed_by_id` int DEFAULT NULL,
  `requested_role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `admin_response` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `reviewed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `shop_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_address_id` int DEFAULT NULL,
  `siren_data` json DEFAULT NULL,
  `verification_score` int DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `google_place_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_875A2A64A76ED395` (`user_id`),
  KEY `IDX_875A2A64FC6B21F1` (`reviewed_by_id`),
  KEY `IDX_875A2A648FC44253` (`shop_address_id`),
  CONSTRAINT `FK_875A2A648FC44253` FOREIGN KEY (`shop_address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `FK_875A2A64A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_875A2A64FC6B21F1` FOREIGN KEY (`reviewed_by_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `shop`;
CREATE TABLE `shop` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int DEFAULT NULL,
  `address_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_hours` json DEFAULT NULL,
  `services` json DEFAULT NULL,
  `specialized_games` json DEFAULT NULL,
  `scraping_data` json DEFAULT NULL,
  `verification_data` json DEFAULT NULL,
  `confidence_score` int DEFAULT NULL,
  `stats` json DEFAULT NULL,
  `config` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_featured` tinyint(1) NOT NULL,
  `display_order` int NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_verified_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `claimed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_AC6A4CA2989D9B62` (`slug`),
  UNIQUE KEY `UNIQ_AC6A4CA27E3C61F9` (`owner_id`),
  KEY `IDX_AC6A4CA2F5B7AF75` (`address_id`),
  CONSTRAINT `FK_AC6A4CA27E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AC6A4CA2F5B7AF75` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `tournament`;
CREATE TABLE `tournament` (
  `id` int NOT NULL,
  `game_format_id` int NOT NULL,
  `tournament_format` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_phase` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `swiss_rounds` int DEFAULT NULL,
  `current_round` int NOT NULL,
  `top_cut_size` int DEFAULT NULL,
  `match_timer` int NOT NULL,
  `break_timer` int DEFAULT NULL,
  `bracket_data` json DEFAULT NULL,
  `standings` json DEFAULT NULL,
  `pairings` json DEFAULT NULL,
  `prize_pool` decimal(10,2) DEFAULT NULL,
  `prize_distribution` json DEFAULT NULL,
  `allow_decklist` tinyint(1) NOT NULL,
  `require_decklist` tinyint(1) NOT NULL,
  `started_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `finished_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `tournament_config` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BD5FB8D948F3707` (`game_format_id`),
  CONSTRAINT `FK_BD5FB8D948F3707` FOREIGN KEY (`game_format_id`) REFERENCES `game_format` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_BD5FB8D9BF396750` FOREIGN KEY (`id`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `tournament_match`;
CREATE TABLE `tournament_match` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` int NOT NULL,
  `round_id` int NOT NULL,
  `player1_id` int NOT NULL,
  `player2_id` int DEFAULT NULL,
  `winner_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_number` int DEFAULT NULL,
  `player1_score` int DEFAULT NULL,
  `player2_score` int DEFAULT NULL,
  `game_results` json DEFAULT NULL,
  `started_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `finished_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `duration` int DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `additional_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_BB0D551C33D1A3E7` (`tournament_id`),
  KEY `IDX_BB0D551CA6005CA0` (`round_id`),
  KEY `IDX_BB0D551CC0990423` (`player1_id`),
  KEY `IDX_BB0D551CD22CABCD` (`player2_id`),
  KEY `IDX_BB0D551C5DFCD4B8` (`winner_id`),
  CONSTRAINT `FK_BB0D551C33D1A3E7` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BB0D551C5DFCD4B8` FOREIGN KEY (`winner_id`) REFERENCES `event_registration` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_BB0D551CA6005CA0` FOREIGN KEY (`round_id`) REFERENCES `tournament_round` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BB0D551CC0990423` FOREIGN KEY (`player1_id`) REFERENCES `event_registration` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BB0D551CD22CABCD` FOREIGN KEY (`player2_id`) REFERENCES `event_registration` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `tournament_round`;
CREATE TABLE `tournament_round` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` int NOT NULL,
  `round_number` int NOT NULL,
  `round_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `finished_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `time_limit` int DEFAULT NULL,
  `pairings_generated` tinyint(1) NOT NULL,
  `pairings_generated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `all_matches_finished` tinyint(1) NOT NULL,
  `pairings_data` json DEFAULT NULL,
  `standings` json DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `additional_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_4B87A2D33D1A3E7` (`tournament_id`),
  CONSTRAINT `FK_4B87A2D33D1A3E7` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `reset_password_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_password_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` longtext COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favorite_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_login_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `address_id` int DEFAULT NULL,
  `selected_games` json DEFAULT NULL,
  `followed_events` json DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  UNIQUE KEY `UNIQ_8D93D64986CC499D` (`pseudo`),
  KEY `IDX_8D93D649F5B7AF75` (`address_id`),
  CONSTRAINT `FK_8D93D649F5B7AF75` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2025-08-12 07:26:42 UTC