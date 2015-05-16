-- MySQL dump 10.13  Distrib 5.6.16, for Win32 (x86)
--
-- Host: engine1.brainyping.com    Database: brainyping
-- ------------------------------------------------------
-- Server version	5.6.21

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bg_proc_last_exec`
--

DROP TABLE IF EXISTS `bg_proc_last_exec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bg_proc_last_exec` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proc_name` varchar(30) CHARACTER SET utf8 NOT NULL,
  `date_ts` int(11) DEFAULT NULL,
  `date_str` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `execution_time` decimal(7,3) DEFAULT NULL,
  `note` varchar(500) CHARACTER SET utf8 DEFAULT NULL,
  `hosts` smallint(5) unsigned DEFAULT NULL,
  `minutes_alert` smallint(6) NOT NULL DEFAULT '1',
  `alert_sent` int(11) DEFAULT NULL,
  `date_ts_db` int(11) NOT NULL DEFAULT '0',
  `check_time_alignment` tinyint(1) NOT NULL DEFAULT '0',
  `timeline_chart_long` tinyint(1) NOT NULL DEFAULT '0',
  `timeline_chart_short` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `machine_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  `db_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  `start_run_ts` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`proc_name`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idx_proc_name` (`proc_name`),
  KEY `idx_db_id` (`db_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bg_proc_logs`
--

DROP TABLE IF EXISTS `bg_proc_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bg_proc_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proc_name` varchar(45) CHARACTER SET utf8 NOT NULL,
  `ts_start` int(11) NOT NULL,
  `ts_stop` int(11) NOT NULL,
  `chart_value` varchar(150) CHARACTER SET utf8 DEFAULT NULL,
  `chart_value_generated` tinyint(1) NOT NULL DEFAULT '0',
  `machine_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  `db_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  PRIMARY KEY (`id`),
  KEY `idx_proc_name` (`proc_name`),
  KEY `idx_chart_value_gen` (`chart_value_generated`),
  KEY `db_id` (`db_id`),
  KEY `idx_ts_stop` (`ts_stop`)
) ENGINE=MyISAM AUTO_INCREMENT=216909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `check_types`
--

DROP TABLE IF EXISTS `check_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_types` (
  `id` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `descr` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `long_descr` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `fa_icon` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `date_ts` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `common_ports`
--

DROP TABLE IF EXISTS `common_ports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `common_ports` (
  `port` int(11) NOT NULL,
  `name` varchar(20) CHARACTER SET utf8 NOT NULL,
  `enabled` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_ts` int(11) DEFAULT NULL,
  `date_str` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `subject` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `body` varchar(15000) CHARACTER SET utf8 DEFAULT NULL,
  `from_` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `to_` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `bcc` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `sent_date_ts` int(11) DEFAULT NULL,
  `sent_date_str` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `machine_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `db_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `token` char(100) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_UNIQUE` (`token`),
  KEY `idx_sent_date_ts` (`sent_date_ts`)
) ENGINE=InnoDB AUTO_INCREMENT=1257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `host_checks_schedule`
--

DROP TABLE IF EXISTS `host_checks_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `host_checks_schedule` (
  `id_host` int(11) NOT NULL DEFAULT '0',
  `ts_check` int(11) NOT NULL DEFAULT '0',
  `date_check` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `daycode` int(8) NOT NULL DEFAULT '0',
  `interval_minutes` smallint(6) NOT NULL DEFAULT '0',
  `aggregated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_host`,`ts_check`),
  KEY `idx_id_host_daycode` (`id_host`,`daycode`),
  KEY `idx_aggregated` (`aggregated`),
  KEY `idx_ts_check` (`ts_check`),
  KEY `idx_id_host_checkssched` (`id_host`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `host_contacts`
--

DROP TABLE IF EXISTS `host_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `host_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_host` int(11) DEFAULT NULL,
  `id_contact` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_host_contacts_id_contact` (`id_contact`),
  KEY `fk_host_contacts_id_host` (`id_host`),
  CONSTRAINT `fk_host_contacts_id_contact` FOREIGN KEY (`id_contact`) REFERENCES `user_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_host_contacts_id_host` FOREIGN KEY (`id_host`) REFERENCES `hosts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `host_logs`
--

DROP TABLE IF EXISTS `host_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `host_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `public_token` char(20) CHARACTER SET utf8 NOT NULL,
  `ts` int(11) DEFAULT NULL,
  `event` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `id_host` int(11) DEFAULT NULL,
  `machine_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  `db_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  PRIMARY KEY (`id`),
  KEY `fk_id_host_hosts` (`public_token`),
  KEY `idx_db_id` (`db_id`),
  KEY `fk_id_host_hostlogs_idx` (`id_host`)
) ENGINE=InnoDB AUTO_INCREMENT=38262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `host_subscriptions`
--

DROP TABLE IF EXISTS `host_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `host_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contact_type` varchar(45) CHARACTER SET utf8 NOT NULL,
  `contact` varchar(150) CHARACTER SET utf8 NOT NULL,
  `friendly_name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `validation_token` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '-',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `added_ts` int(11) DEFAULT NULL,
  `validated_ts` int(11) DEFAULT NULL,
  `id_host` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_user_contact` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_contact_type_idx` (`id_contact_type`),
  KEY `idx_id_host` (`id_host`),
  KEY `fk_id_user_idx` (`id_user`),
  KEY `fk_id_contact_host_subs_idx` (`id_user_contact`),
  CONSTRAINT `fk_contact_type_host_subs` FOREIGN KEY (`id_contact_type`) REFERENCES `user_contact_types` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_id_contact_host_subs` FOREIGN KEY (`id_user_contact`) REFERENCES `user_contacts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_id_host_host_subs` FOREIGN KEY (`id_host`) REFERENCES `hosts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_id_user_host_subs` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hosts`
--

DROP TABLE IF EXISTS `hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(250) CHARACTER SET utf8 NOT NULL,
  `title` varchar(45) CHARACTER SET utf8 NOT NULL,
  `check_running` tinyint(1) DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `added_ts` int(11) NOT NULL,
  `next_check_ts` int(11) NOT NULL,
  `last_alert_ts` int(11) NOT NULL DEFAULT '0',
  `next_check_str` varchar(25) CHARACTER SET utf8 DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `minutes` tinyint(4) NOT NULL,
  `port` smallint(5) unsigned DEFAULT NULL,
  `log_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `check_session` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `check_started_ts` int(11) DEFAULT NULL,
  `check_started_str` varchar(25) CHARACTER SET utf8 DEFAULT NULL,
  `last_check_ts` int(11) DEFAULT NULL,
  `last_check_str` varchar(25) CHARACTER SET utf8 DEFAULT NULL,
  `check_result` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `check_result_since_ts` int(11) DEFAULT NULL,
  `check_result_since_str` varchar(25) CHARACTER SET utf8 DEFAULT NULL,
  `latest_results` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `public` tinyint(1) DEFAULT '1',
  `last_check_avg` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `check_type` varchar(20) CHARACTER SET utf8 NOT NULL,
  `public_token` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `delete_queue` int(11) DEFAULT NULL,
  `num_contacts` tinyint(4) NOT NULL DEFAULT '0',
  `homepage` tinyint(1) NOT NULL DEFAULT '0',
  `check_reservation_code` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `check_reservation_ts` int(11) NOT NULL DEFAULT '0',
  `keyword` varchar(200) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `edited_ts` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_public_token` (`public_token`),
  KEY `idx_enabled` (`enabled`),
  KEY `idx_next_check_ts` (`next_check_ts`),
  KEY `idx_last_check_ts` (`last_check_ts`),
  KEY `idx_check_result` (`check_result`),
  KEY `idx_public` (`public`),
  KEY `idx_hosts_id_user` (`id_user`),
  KEY `idx_title` (`title`),
  KEY `fk_checktype` (`check_type`),
  KEY `idx_ping_running` (`check_running`),
  KEY `idx_homepage` (`homepage`),
  KEY `idx_check_res_code` (`check_reservation_code`),
  KEY `idx_next_ts_check_running` (`next_check_ts`,`check_running`),
  CONSTRAINT `fk_checktype` FOREIGN KEY (`check_type`) REFERENCES `check_types` (`id`),
  CONSTRAINT `fk_id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1810 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hosts_visits_counter`
--

DROP TABLE IF EXISTS `hosts_visits_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hosts_visits_counter` (
  `id_host` int(11) DEFAULT NULL,
  `ts` int(11) DEFAULT NULL,
  KEY `idx_id_host` (`id_host`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process` varchar(45) CHARACTER SET utf8 NOT NULL,
  `date_ts` int(11) NOT NULL,
  `date_str` varchar(25) CHARACTER SET utf8 NOT NULL,
  `log_note` varchar(500) CHARACTER SET utf8 NOT NULL,
  `log_level` varchar(45) CHARACTER SET utf8 NOT NULL,
  `session_id` varchar(45) CHARACTER SET utf8 NOT NULL,
  `log_seq` smallint(6) NOT NULL,
  `machine_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  `db_id` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'DEFAULT',
  PRIMARY KEY (`id`),
  KEY `idx_db_id` (`db_id`)
) ENGINE=MyISAM AUTO_INCREMENT=55874 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results` (
  `id_host` int(11) NOT NULL,
  `result` varchar(15) CHARACTER SET utf8 NOT NULL,
  `requests` tinyint(1) DEFAULT NULL,
  `requests_ok` tinyint(1) DEFAULT NULL,
  `reply_best` decimal(9,3) DEFAULT NULL,
  `reply_average` decimal(9,3) DEFAULT NULL,
  `reply_worst` decimal(9,3) DEFAULT NULL,
  `current_date_ts` int(11) DEFAULT NULL,
  `current_date_str` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `ts_check_triggered` int(11) DEFAULT NULL,
  `ts_check_delay` int(11) DEFAULT NULL,
  `session_id` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `host` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `port` smallint(5) unsigned DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `public` tinyint(1) DEFAULT NULL,
  `ip` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `daycode` int(11) DEFAULT NULL,
  `aggregated` tinyint(4) NOT NULL DEFAULT '0',
  `seconds_previous_result` int(11) NOT NULL DEFAULT '0',
  `details` text CHARACTER SET utf8,
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `result_was` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `time_spent` decimal(6,3) NOT NULL DEFAULT '0.000',
  `error_code` char(20) CHARACTER SET utf8 DEFAULT '-',
  `machine_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `db_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `current_date_ts_00` int(11) DEFAULT NULL,
  `current_date_ts_000` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_host` (`id_host`),
  KEY `idx_daycode` (`daycode`),
  KEY `idx_aggregated` (`aggregated`),
  KEY `idx_aggregated_daycode` (`aggregated`,`daycode`),
  KEY `idx_daycode_idhost` (`daycode`,`id_host`),
  KEY `idx_id_host_current_date` (`id_host`,`current_date_ts`),
  KEY `fk_ts_check_id_host_scheduled_check` (`id_host`,`ts_check_triggered`),
  KEY `idx_current_date_ts` (`current_date_ts`),
  KEY `idx_current_date_ts_00` (`current_date_ts_00`),
  KEY `idx_current_date_ts_000` (`current_date_ts_000`)
) ENGINE=InnoDB AUTO_INCREMENT=39166268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results_daily`
--

DROP TABLE IF EXISTS `results_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `daycode` int(11) DEFAULT NULL,
  `id_host` int(11) DEFAULT NULL,
  `uptime` mediumint(9) DEFAULT NULL,
  `downtime` mediumint(9) DEFAULT NULL,
  `unknowntime` mediumint(9) DEFAULT NULL,
  `monitor_off` mediumint(9) DEFAULT NULL,
  `generated_ts` int(11) DEFAULT NULL,
  `planned_checks` smallint(6) DEFAULT NULL,
  `completed_checks` smallint(6) DEFAULT NULL,
  `day_details` text CHARACTER SET utf8,
  `log` text CHARACTER SET utf8,
  `ts` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_daycode_idhost` (`daycode`,`id_host`),
  KEY `idx_idhost` (`id_host`),
  KEY `idx_daycode` (`daycode`)
) ENGINE=InnoDB AUTO_INCREMENT=136219 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results_latest_24h`
--

DROP TABLE IF EXISTS `results_latest_24h`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results_latest_24h` (
  `id_host` int(11) NOT NULL,
  `ts_trunc` double DEFAULT NULL,
  `reply_avg` decimal(10,3) DEFAULT NULL,
  `NOK` decimal(23,0) DEFAULT NULL,
  `TOT` decimal(23,0) DEFAULT NULL,
  `generated_ts` int(11) NOT NULL DEFAULT '0',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `idx_id_host` (`id_host`),
  KEY `idx_ts_trunc` (`ts_trunc`)
) ENGINE=InnoDB AUTO_INCREMENT=13641632 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results_temp`
--

DROP TABLE IF EXISTS `results_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results_temp` (
  `id_host` int(11) NOT NULL,
  `result` varchar(15) CHARACTER SET utf8 NOT NULL,
  `requests` tinyint(1) DEFAULT NULL,
  `requests_ok` tinyint(1) DEFAULT NULL,
  `reply_best` decimal(9,3) DEFAULT NULL,
  `reply_average` decimal(9,3) DEFAULT NULL,
  `reply_worst` decimal(9,3) DEFAULT NULL,
  `current_date_ts` int(11) DEFAULT NULL,
  `current_date_str` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `ts_check_triggered` int(11) DEFAULT NULL,
  `ts_check_delay` int(11) DEFAULT NULL,
  `session_id` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `host` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `port` smallint(5) unsigned DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `public` tinyint(1) DEFAULT NULL,
  `ip` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `daycode` int(11) DEFAULT NULL,
  `aggregated` tinyint(4) NOT NULL DEFAULT '0',
  `seconds_previous_result` int(11) NOT NULL DEFAULT '0',
  `details` text CHARACTER SET utf8,
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `result_was` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `time_spent` decimal(6,3) NOT NULL DEFAULT '0.000',
  `error_code` char(20) CHARACTER SET utf8 DEFAULT '-',
  `machine_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `db_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `current_date_ts_00` int(11) DEFAULT NULL,
  `current_date_ts_000` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_host` (`id_host`),
  KEY `idx_daycode` (`daycode`),
  KEY `idx_aggregated` (`aggregated`),
  KEY `idx_aggregated_daycode` (`aggregated`,`daycode`),
  KEY `idx_daycode_idhost` (`daycode`,`id_host`),
  KEY `idx_id_host_current_date` (`id_host`,`current_date_ts`),
  KEY `fk_ts_check_id_host_scheduled_check` (`id_host`,`ts_check_triggered`),
  KEY `idx_current_date_ts` (`current_date_ts`),
  KEY `idx_current_date_ts_00` (`current_date_ts_00`),
  KEY `idx_current_date_ts_000` (`current_date_ts_000`)
) ENGINE=InnoDB AUTO_INCREMENT=39166215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stats_data`
--

DROP TABLE IF EXISTS `stats_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_data` (
  `id_stat` varchar(30) CHARACTER SET utf8 NOT NULL,
  `stat_value` mediumtext CHARACTER SET utf8,
  `generated_ts` int(11) NOT NULL DEFAULT '0',
  `generated_str` char(20) CHARACTER SET utf8 DEFAULT NULL,
  `generated_time_spent` decimal(7,3) DEFAULT NULL,
  `stat_descr` text CHARACTER SET utf8,
  `update_interval_minutes` smallint(6) NOT NULL DEFAULT '1440',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_ts_trunc` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_id_stats` (`id_stat`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_tables_config`
--

DROP TABLE IF EXISTS `sync_tables_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_tables_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `int_schema` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `int_table` varchar(45) CHARACTER SET utf8 NOT NULL COMMENT 'Source table name',
  `last_sync_ts` int(11) NOT NULL DEFAULT '0' COMMENT 'last sync timestamp',
  `last_sync_str` char(25) CHARACTER SET utf8 DEFAULT NULL,
  `sync_interval` decimal(5,1) NOT NULL COMMENT 'sync table every XX minutes',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `time_spent` decimal(7,3) NOT NULL DEFAULT '0.000',
  `friendly_name` varchar(45) CHARACTER SET utf8 NOT NULL,
  `lookup_field` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `sync_reverse` tinyint(1) NOT NULL DEFAULT '0',
  `ext_schema` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `ext_table` varchar(45) CHARACTER SET utf8 NOT NULL COMMENT 'destination table name',
  `ext_host` varchar(45) CHARACTER SET utf8 NOT NULL,
  `ext_port` int(11) NOT NULL,
  `ext_user` varchar(45) CHARACTER SET utf8 NOT NULL,
  `ext_pwd` varchar(45) CHARACTER SET utf8 NOT NULL,
  `ext_charset` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT 'utf-8',
  `ext_timezone_offset` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '+1:00' COMMENT 'something like ''+1:00'' or ''-2:00''',
  `nrecs` int(11) NOT NULL,
  `nrec_insert` int(11) NOT NULL DEFAULT '0',
  `nrec_update` int(11) NOT NULL DEFAULT '0',
  `nrec_delete` int(11) DEFAULT NULL,
  `force_run` tinyint(1) NOT NULL DEFAULT '0',
  `local_sync` tinyint(1) NOT NULL DEFAULT '0',
  `sync_will_merge` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Means that destination table will me a result of multiple identic tables merged.  When this flag is 1 interface will look for a field named db_id that is the source db identifier.\nAlso, destination table should not have a unique primary key as the source because id keys could be duplicated.',
  `insert_` tinyint(1) NOT NULL DEFAULT '0',
  `update_` tinyint(1) NOT NULL DEFAULT '0',
  `update_cursor_field` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `update_cursor_value_included` tinyint(1) NOT NULL DEFAULT '0',
  `delete_by_cursor` tinyint(1) NOT NULL DEFAULT '0',
  `delete_by_lookup` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Perform a "NOT IN" operation so use it only with table not so big....',
  `delete_cursor_field` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `return_code` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `return_desc` varchar(2000) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friendly_name_UNIQUE` (`friendly_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_tables_logs`
--

DROP TABLE IF EXISTS `sync_tables_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_tables_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_ts` int(11) DEFAULT NULL,
  `date_str` varchar(25) CHARACTER SET utf8 DEFAULT NULL,
  `step_` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `friendly_name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `friendly_group_name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `group_seq` tinyint(2) DEFAULT NULL,
  `time_spent` decimal(8,3) DEFAULT NULL,
  `nrec_insert` int(11) DEFAULT NULL,
  `nrec_update` int(11) DEFAULT NULL,
  `nrec_delete` int(11) DEFAULT NULL,
  `return_code` varchar(10) CHARACTER SET utf8 DEFAULT NULL,
  `return_descr` varchar(2000) CHARACTER SET utf8 DEFAULT NULL,
  `machine_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `db_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=688737 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tempy`
--

DROP TABLE IF EXISTS `tempy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tempy` (
  `@ts` bigint(20) DEFAULT NULL,
  `newts` decimal(22,0) DEFAULT NULL,
  `rn` bigint(21) DEFAULT NULL,
  `id_host` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_contact_types`
--

DROP TABLE IF EXISTS `user_contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_contact_types` (
  `id` varchar(30) CHARACTER SET utf8 NOT NULL,
  `name` varchar(150) CHARACTER SET utf8 NOT NULL,
  `enabled` tinyint(11) DEFAULT '1',
  `id_ai` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_ai_UNIQUE` (`id_ai`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_contacts`
--

DROP TABLE IF EXISTS `user_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact` varchar(100) CHARACTER SET utf8 NOT NULL,
  `validation_token` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `validated` tinyint(4) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `contact_type_id` varchar(20) CHARACTER SET utf8 NOT NULL,
  `primary_contact` tinyint(4) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `friendly_name` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '-',
  PRIMARY KEY (`id`),
  KEY `fk_user_contacts_id_user` (`id_user`),
  KEY `fk_user_contacts_contact_type` (`contact_type_id`),
  CONSTRAINT `fk_user_contacts_contact_type` FOREIGN KEY (`contact_type_id`) REFERENCES `user_contact_types` (`id`),
  CONSTRAINT `fk_user_contacts_id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 NOT NULL,
  `email_verification_code` varchar(10) CHARACTER SET utf8mb4 NOT NULL,
  `email_verification_ts` int(11) NOT NULL DEFAULT '0',
  `email_verified` tinyint(4) NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 NOT NULL,
  `date_added_ts` int(11) NOT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
  `last_login_ts` int(11) NOT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '0',
  `public_token` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `idx_public_token` (`public_token`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `vw_checks_schedule_with_results`
--

DROP TABLE IF EXISTS `vw_checks_schedule_with_results`;
/*!50001 DROP VIEW IF EXISTS `vw_checks_schedule_with_results`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_checks_schedule_with_results` (
  `id_host` tinyint NOT NULL,
  `daycode` tinyint NOT NULL,
  `date_check` tinyint NOT NULL,
  `interval_minutes` tinyint NOT NULL,
  `ts_check` tinyint NOT NULL,
  `current_date_ts` tinyint NOT NULL,
  `result` tinyint NOT NULL,
  `result_was` tinyint NOT NULL,
  `source` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_checks_schedule_with_results_temp`
--

DROP TABLE IF EXISTS `vw_checks_schedule_with_results_temp`;
/*!50001 DROP VIEW IF EXISTS `vw_checks_schedule_with_results_temp`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_checks_schedule_with_results_temp` (
  `id_host` tinyint NOT NULL,
  `daycode` tinyint NOT NULL,
  `date_check` tinyint NOT NULL,
  `interval_minutes` tinyint NOT NULL,
  `ts_check` tinyint NOT NULL,
  `current_date_ts` tinyint NOT NULL,
  `result` tinyint NOT NULL,
  `result_was` tinyint NOT NULL,
  `source` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'brainyping'
--

--
-- Final view structure for view `vw_checks_schedule_with_results`
--

/*!50001 DROP TABLE IF EXISTS `vw_checks_schedule_with_results`*/;
/*!50001 DROP VIEW IF EXISTS `vw_checks_schedule_with_results`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`brainyping`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_checks_schedule_with_results` AS select `hcs`.`id_host` AS `id_host`,`hcs`.`daycode` AS `daycode`,`hcs`.`date_check` AS `date_check`,`hcs`.`interval_minutes` AS `interval_minutes`,`hcs`.`ts_check` AS `ts_check`,ifnull(`r`.`current_date_ts`,'-') AS `current_date_ts`,ifnull(`r`.`result`,'-') AS `result`,ifnull(`r`.`result_was`,'-') AS `result_was`,ifnull(`r`.`source`,'-') AS `source` from (`host_checks_schedule` `hcs` left join `results` `r` on(((`hcs`.`id_host` = `r`.`id_host`) and (`hcs`.`ts_check` = `r`.`ts_check_triggered`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_checks_schedule_with_results_temp`
--

/*!50001 DROP TABLE IF EXISTS `vw_checks_schedule_with_results_temp`*/;
/*!50001 DROP VIEW IF EXISTS `vw_checks_schedule_with_results_temp`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`brainyping`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_checks_schedule_with_results_temp` AS select `hcs`.`id_host` AS `id_host`,`hcs`.`daycode` AS `daycode`,`hcs`.`date_check` AS `date_check`,`hcs`.`interval_minutes` AS `interval_minutes`,`hcs`.`ts_check` AS `ts_check`,ifnull(`r`.`current_date_ts`,'-') AS `current_date_ts`,ifnull(`r`.`result`,'-') AS `result`,ifnull(`r`.`result_was`,'-') AS `result_was`,ifnull(`r`.`source`,'-') AS `source` from (`host_checks_schedule` `hcs` left join `results_temp` `r` on(((`hcs`.`id_host` = `r`.`id_host`) and (`hcs`.`ts_check` = `r`.`ts_check_triggered`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-05-16 12:32:37
