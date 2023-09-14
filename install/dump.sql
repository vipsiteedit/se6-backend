-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Сен 14 2023 г., 11:12
-- Версия сервера: 5.7.21-20-beget-5.7.21-20-1-log
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `gomarket_test`
--

-- --------------------------------------------------------

--
-- Структура таблицы `apps`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `apps`;
CREATE TABLE IF NOT EXISTS `apps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_name` varchar(40) NOT NULL COMMENT 'Application name',
  `caption` varchar(255) NOT NULL,
  `lang` varchar(6) NOT NULL DEFAULT 'ru',
  `id_lang` int(10) UNSIGNED NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `domainredirect` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Redirect to domain',
  `alias` text NOT NULL,
  `multidomain` tinyint(1) NOT NULL DEFAULT '0',
  `robots` text NOT NULL,
  `favicon` text NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `sms_phone` varchar(255) NOT NULL,
  `sms_sender` varchar(255) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`app_name`),
  KEY `created_at` (`created_at`),
  KEY `domain` (`domain`),
  KEY `is_main` (`is_main`),
  KEY `lang` (`lang`),
  KEY `updated_at` (`updated_at`),
  KEY `id_lang` (`id_lang`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Список приложений';

--
-- Дамп данных таблицы `apps`
--

INSERT INTO `apps` (`id`, `app_name`, `caption`, `lang`, `id_lang`, `domain`, `domainredirect`, `alias`, `multidomain`, `robots`, `favicon`, `from_email`, `sms_phone`, `sms_sender`, `is_main`, `updated_at`, `created_at`) VALUES
(1, '', 'Site name', 'ru', 2, NULL, 0, '', 0, 'User-agent: YandexBot\nDisallow: /support\nDisallow: */?*\nHost: {host}\nSitemap: {host}/sitemap.xml\n\nUser-agent: Googlebot\nDisallow: /support\nDisallow: */?*\nDisallow: /?q*\nDisallow: /?f*\nDisallow: /?db*\nSitemap: {host}/sitemap.xml\n\nUser-agent: *\nDisallow: /support\nDisallow: /?q*\nDisallow: /?f*\nDisallow: */?*\nSitemap: {host}/sitemap.xml\n', '', '', '', '', 0, '2023-09-14 08:05:58', '2018-08-03 12:25:12');

-- --------------------------------------------------------

--
-- Структура таблицы `app_collection_offer`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_collection_offer`;
CREATE TABLE IF NOT EXISTS `app_collection_offer` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `is_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Есть лимит',
  `count` int(11) NOT NULL,
  `info` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_collection` (`id_collection`),
  KEY `is_limit` (`is_limit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Торговое предложение';

-- --------------------------------------------------------

--
-- Структура таблицы `app_collection_offer_price`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_collection_offer_price`;
CREATE TABLE IF NOT EXISTS `app_collection_offer_price` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_offer` int(10) UNSIGNED NOT NULL,
  `id_typeprice` int(10) UNSIGNED NOT NULL,
  `price` double NOT NULL,
  `discount` double NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_offer` (`id_offer`),
  KEY `id_typeprice` (`id_typeprice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Цены товара';

-- --------------------------------------------------------

--
-- Структура таблицы `app_fields`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_fields`;
CREATE TABLE IF NOT EXISTS `app_fields` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_service` int(10) UNSIGNED DEFAULT NULL,
  `id_group` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('string','text','textedit','number','checkbox','radio','date','link','select') NOT NULL DEFAULT 'string',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `placeholder` varchar(255) NOT NULL,
  `mask` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `values` text,
  `min` int(10) UNSIGNED DEFAULT NULL,
  `max` int(10) UNSIGNED DEFAULT NULL,
  `size` varchar(40) NOT NULL,
  `child_level` tinyint(4) NOT NULL DEFAULT '0',
  `defvalue` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`id_service`) USING BTREE,
  KEY `code` (`code`),
  KEY `enabled` (`enabled`),
  KEY `sort` (`sort`),
  KEY `app_section_fields_ibfk_2` (`id_group`),
  KEY `id_service` (`id_service`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='Список переменных';

--
-- Дамп данных таблицы `app_fields`
--

INSERT INTO `app_fields` (`id`, `id_service`, `id_group`, `code`, `name`, `type`, `required`, `placeholder`, `mask`, `description`, `values`, `min`, `max`, `size`, `child_level`, `defvalue`, `enabled`, `sort`, `updated_at`, `created_at`) VALUES
(10, 11, NULL, 'price', 'Стоимость', 'string', 0, '', '', '', NULL, NULL, NULL, '', 0, NULL, 1, 0, '0000-00-00 00:00:00', '2019-01-14 19:43:25');

-- --------------------------------------------------------

--
-- Структура таблицы `app_fieldsgroup`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_fieldsgroup`;
CREATE TABLE IF NOT EXISTS `app_fieldsgroup` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_service` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_multi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Это группа списка',
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `id_service` (`id_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_fields_service`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_fields_service`;
CREATE TABLE IF NOT EXISTS `app_fields_service` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED NOT NULL,
  `sect` enum('page','person','section','request') NOT NULL DEFAULT 'section',
  `id_section` int(10) UNSIGNED DEFAULT NULL,
  `id_request` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sect` (`sect`),
  KEY `id_section` (`id_section`),
  KEY `id_request` (`id_request`),
  KEY `id_app` (`id_app`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Таблица разделов пользовательских полей';

--
-- Дамп данных таблицы `app_fields_service`
--

INSERT INTO `app_fields_service` (`id`, `id_app`, `sect`, `id_section`, `id_request`, `updated_at`, `created_at`) VALUES
(11, 1, 'section', 16, NULL, '0000-00-00 00:00:00', '2019-01-14 19:43:25'),
(12, 1, 'page', NULL, NULL, '0000-00-00 00:00:00', '2023-01-28 14:55:25');

-- --------------------------------------------------------

--
-- Структура таблицы `app_image`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_image`;
CREATE TABLE IF NOT EXISTS `app_image` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_folder` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_image` (`id_folder`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Справочник картинок' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Структура таблицы `app_image_folder`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_image_folder`;
CREATE TABLE IF NOT EXISTS `app_image_folder` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Папки для картинок' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Структура таблицы `app_language`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_language`;
CREATE TABLE IF NOT EXISTS `app_language` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(4) NOT NULL,
  `title` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='Таблица с языками';

--
-- Дамп данных таблицы `app_language`
--

INSERT INTO `app_language` (`id`, `code`, `title`, `updated_at`, `created_at`) VALUES
(1, 'en', 'English', '0000-00-00 00:00:00', '2018-08-20 15:35:21'),
(2, 'ru', 'Русский', '2018-09-11 05:51:12', '2018-08-20 15:35:48');

-- --------------------------------------------------------

--
-- Структура таблицы `app_ml`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_ml`;
CREATE TABLE IF NOT EXISTS `app_ml` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED NOT NULL,
  `code` varchar(125) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `id_app` (`id_app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_ml_translate`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_ml_translate`;
CREATE TABLE IF NOT EXISTS `app_ml_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_ml` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED NOT NULL,
  `content` text,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_lang` (`id_lang`),
  KEY `id_ml` (`id_ml`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_nav`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_nav`;
CREATE TABLE IF NOT EXISTS `app_nav` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_image` (`name`),
  KEY `FK_app_nav_id_app` (`id_app`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='Справочник картинок' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `app_nav`
--

INSERT INTO `app_nav` (`id`, `id_app`, `name`, `code`, `updated_at`, `created_at`) VALUES
(1, 1, 'Главное', 'main', NULL, '2019-09-23 08:46:49'),
(2, 1, 'Левое', 'left', NULL, '2023-01-28 14:53:58');

-- --------------------------------------------------------

--
-- Структура таблицы `app_nav_url`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_nav_url`;
CREATE TABLE IF NOT EXISTS `app_nav_url` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_nav` int(10) UNSIGNED NOT NULL,
  `id_url` int(10) UNSIGNED NOT NULL,
  `id_parent` int(10) UNSIGNED DEFAULT NULL,
  `id_group` int(10) UNSIGNED DEFAULT NULL,
  `id_collection` int(10) UNSIGNED DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_app_nav_url_id_collection` (`id_collection`),
  KEY `FK_app_nav_url_id_group` (`id_group`),
  KEY `FK_app_nav_url_id_nav` (`id_nav`),
  KEY `FK_app_nav_url_id_parent` (`id_parent`),
  KEY `FK_app_nav_url_id_url` (`id_url`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Справочник картинок' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `app_nav_url`
--

INSERT INTO `app_nav_url` (`id`, `id_nav`, `id_url`, `id_parent`, `id_group`, `id_collection`, `sort`, `is_active`, `updated_at`, `created_at`) VALUES
(1, 1, 77, NULL, NULL, NULL, 0, 1, NULL, '2019-09-23 08:47:12'),
(3, 2, 77, NULL, NULL, NULL, 0, 1, NULL, '2023-01-28 14:54:08');

-- --------------------------------------------------------

--
-- Структура таблицы `app_nav_url_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_nav_url_translate`;
CREATE TABLE IF NOT EXISTS `app_nav_url_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_nav_url` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_lang` (`id_lang`),
  KEY `id_nav_url` (`id_nav_url`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Справочник картинок' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `app_nav_url_translate`
--

INSERT INTO `app_nav_url_translate` (`id`, `id_nav_url`, `id_lang`, `name`, `updated_at`, `created_at`) VALUES
(1, 1, 2, 'Главная страница', '2019-09-23 08:47:26', '2019-09-23 08:47:12'),
(3, 3, 2, 'Главная страница', NULL, '2023-01-28 14:54:08');

-- --------------------------------------------------------

--
-- Структура таблицы `app_pages`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_pages`;
CREATE TABLE IF NOT EXISTS `app_pages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `template` text NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '5',
  `id_permission` int(10) UNSIGNED DEFAULT NULL,
  `is_show` tinyint(1) DEFAULT '1',
  `is_search` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `app_pages_ibfk_1` (`id_permission`),
  KEY `is_search` (`is_search`),
  KEY `is_show` (`is_show`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COMMENT='Список страниц сайта';

--
-- Дамп данных таблицы `app_pages`
--

INSERT INTO `app_pages` (`id`, `name`, `template`, `priority`, `id_permission`, `is_show`, `is_search`, `created_at`, `updated_at`) VALUES
(30, 'index', 'index', 5, NULL, 1, 1, '2019-01-14 10:59:38', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `app_pages_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_pages_translate`;
CREATE TABLE IF NOT EXISTS `app_pages_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_page` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `FK_app_pages_id_lang` (`id_lang`),
  KEY `id_page` (`id_page`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COMMENT='Список страниц сайта';

--
-- Дамп данных таблицы `app_pages_translate`
--

INSERT INTO `app_pages_translate` (`id`, `id_page`, `id_lang`, `title`, `page_title`, `meta_title`, `meta_keywords`, `meta_description`, `created_at`, `updated_at`) VALUES
(30, 30, 2, 'Главная страница', 'Создание сайтов и интернет-магазинов на CMS SiteEdit, продвижение, администрирование', 'Создание сайтов и интернет-магазинов на CMS SiteEdit, продвижение, администрирование', 'создание сайта на cms siteedit, создание интернет-магазина на cms siteedit, продвижение сайта, продвижение интернет-магазина, администрирование сайта, ведение сайта, администрирование интернет-магазина', 'Мы оказываем весь спектр услуг от проектирования и разработки сайта или интернет-магазина до seo продвижения и администрирования. Работаем с CMS SiteEdit.', '2019-01-14 10:59:58', '2019-01-15 09:08:30');

-- --------------------------------------------------------

--
-- Структура таблицы `app_page_permission`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_page_permission`;
CREATE TABLE IF NOT EXISTS `app_page_permission` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_page` int(10) UNSIGNED NOT NULL,
  `id_permission` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `app_page_permission_ibfk_1` (`id_page`),
  KEY `app_page_permission_ibfk_2` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Page permission';

-- --------------------------------------------------------

--
-- Структура таблицы `app_permission`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_permission`;
CREATE TABLE IF NOT EXISTS `app_permission` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Права доступа проекта';

-- --------------------------------------------------------

--
-- Структура таблицы `app_regions`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_regions`;
CREATE TABLE IF NOT EXISTS `app_regions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `id_country` int(10) UNSIGNED DEFAULT NULL,
  `id_region` int(10) UNSIGNED DEFAULT NULL,
  `id_city` int(10) UNSIGNED DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Региональная привязка';

-- --------------------------------------------------------

--
-- Структура таблицы `app_requests`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_requests`;
CREATE TABLE IF NOT EXISTS `app_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(125) NOT NULL,
  `sort` int(11) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table for request or messages';

-- --------------------------------------------------------

--
-- Структура таблицы `app_request_order`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_request_order`;
CREATE TABLE IF NOT EXISTS `app_request_order` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_request` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(125) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `utm` text,
  `id_object` int(10) UNSIGNED DEFAULT NULL COMMENT 'Join other object',
  `id_manager` int(10) UNSIGNED DEFAULT NULL,
  `is_showing` tinyint(1) NOT NULL DEFAULT '0',
  `commentary` text,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_request` (`id_request`),
  KEY `is_showing` (`is_showing`),
  KEY `id_manager` (`id_manager`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_request_order_values`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_request_order_values`;
CREATE TABLE IF NOT EXISTS `app_request_order_values` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` int(10) UNSIGNED NOT NULL,
  `id_field` int(10) UNSIGNED NOT NULL,
  `intvalue` int(11) DEFAULT NULL,
  `value` longtext,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_values` (`id_order`,`id_field`),
  KEY `intvalue` (`intvalue`),
  KEY `app_section_collection_values_ibfk_2` (`id_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список переменных коллекции';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section`;
CREATE TABLE IF NOT EXISTS `app_section` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED DEFAULT NULL,
  `id_parent` int(10) UNSIGNED DEFAULT NULL COMMENT 'Категория раздела',
  `id_page` int(10) UNSIGNED DEFAULT NULL,
  `alias` varchar(40) NOT NULL,
  `typename` varchar(255) NOT NULL DEFAULT 'text',
  `name` varchar(40) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `seo_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Выводить в поисковик',
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_show_default` tinyint(1) DEFAULT '1',
  `is_search_default` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`,`id_app`),
  KEY `created_at` (`created_at`),
  KEY `id_page` (`id_page`),
  KEY `id_parent` (`id_parent`),
  KEY `seo_enable` (`seo_enable`),
  KEY `sort` (`sort`),
  KEY `updated_at` (`updated_at`),
  KEY `visible` (`visible`),
  KEY `app_section_ibfk_2` (`id_app`),
  KEY `is_search_default` (`is_search_default`),
  KEY `is_show_default` (`is_show_default`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='Modules or block';

--
-- Дамп данных таблицы `app_section`
--

INSERT INTO `app_section` (`id`, `id_app`, `id_parent`, `id_page`, `alias`, `typename`, `name`, `visible`, `seo_enable`, `sort`, `is_show_default`, `is_search_default`, `updated_at`, `created_at`) VALUES
(16, 1, NULL, NULL, 'catalog', 'text', 'Каталог услуг', 1, 1, 0, 1, 1, '0000-00-00 00:00:00', '2019-01-14 13:23:35');

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection`;
CREATE TABLE IF NOT EXISTS `app_section_collection` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent` int(10) UNSIGNED DEFAULT NULL,
  `id_section` int(10) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `is_date_public` tinyint(1) NOT NULL DEFAULT '0',
  `date_public` datetime NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_show` tinyint(1) DEFAULT '1',
  `is_search` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `date_public` (`date_public`),
  KEY `is_date_public` (`is_date_public`),
  KEY `sort` (`sort`),
  KEY `updated_at` (`updated_at`),
  KEY `url` (`code`),
  KEY `visible` (`visible`),
  KEY `app_section_collection_ibfk_1` (`id_section`),
  KEY `app_section_collection_ibfk_2` (`id_parent`),
  KEY `is_search` (`is_search`),
  KEY `is_show` (`is_show`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коллекция записей раздела';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_comments`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_comments`;
CREATE TABLE IF NOT EXISTS `app_section_collection_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `name` varchar(125) NOT NULL,
  `email` varchar(125) DEFAULT NULL,
  `commentary` text NOT NULL,
  `response` text,
  `date` date NOT NULL,
  `mark` int(11) NOT NULL,
  `showing` enum('Y','N') DEFAULT 'N',
  `is_active` enum('Y','N') DEFAULT 'N',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `app_section_collection_comments_ibfk_1` (`id_collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_connect`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_connect`;
CREATE TABLE IF NOT EXISTS `app_section_collection_connect` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED NOT NULL COMMENT 'ID связанной секции',
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_item` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_collection` (`id_collection`),
  KEY `id_item` (`id_item`),
  KEY `id_section` (`id_section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Related records link';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_files`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_files`;
CREATE TABLE IF NOT EXISTS `app_section_collection_files` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED DEFAULT NULL,
  `id_owner` int(10) UNSIGNED DEFAULT NULL COMMENT 'Owner',
  `file` varchar(255) DEFAULT NULL COMMENT 'Имя файла в папке files',
  `name` varchar(255) DEFAULT NULL COMMENT 'Текст отображаемой ссылки на файл',
  `description` text NOT NULL,
  `icon` varchar(255) NOT NULL,
  `sort` int(11) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_app_files_collection_id` (`id_collection`),
  KEY `sort` (`sort`),
  KEY `app_section_collection_files_ibfk_2` (`id_owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_group`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_group`;
CREATE TABLE IF NOT EXISTS `app_section_collection_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_group` int(10) UNSIGNED NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `id_section` (`id_collection`),
  KEY `updated_at` (`updated_at`),
  KEY `app_section_collection_group_ibfk_2` (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_image`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_image`;
CREATE TABLE IF NOT EXISTS `app_section_collection_image` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_image` int(10) UNSIGNED NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_section_collection_imag` (`id_collection`,`id_image`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `FK_app_section_collection_ima2` (`id_image`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коллекция записей раздела';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_image_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_image_translate`;
CREATE TABLE IF NOT EXISTS `app_section_collection_image_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_image` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_section_collection_imag` (`id_image`,`id_lang`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `FK_app_section_collection_ima2` (`id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коллекция записей раздела';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_permission`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_permission`;
CREATE TABLE IF NOT EXISTS `app_section_collection_permission` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_permission` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_section` (`id_collection`),
  KEY `app_section_collection_permission_ibfk_2` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Page permission';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_region`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_region`;
CREATE TABLE IF NOT EXISTS `app_section_collection_region` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_region` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `app_section_collection_gcontacts_ibfk_1` (`id_collection`),
  KEY `app_section_collection_gcontacts_ibfk_2` (`id_region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Таблица связей с гео-контактами';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_reviews`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_reviews`;
CREATE TABLE IF NOT EXISTS `app_section_collection_reviews` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `mark` smallint(1) UNSIGNED NOT NULL,
  `merits` text,
  `demerits` text,
  `comment` text NOT NULL,
  `use_time` smallint(1) UNSIGNED NOT NULL DEFAULT '1',
  `date` datetime NOT NULL,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dislikes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `id_collection` (`id_collection`),
  KEY `app_section_collection_reviews_ibfk_1` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_reviews_votes`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_reviews_votes`;
CREATE TABLE IF NOT EXISTS `app_section_collection_reviews_votes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_review` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `vote` smallint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_shop_reviews_votes` (`id_review`,`id_user`),
  KEY `FK_shop_reviews_votes_se_user_id` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_similar`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_similar`;
CREATE TABLE IF NOT EXISTS `app_section_collection_similar` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_item` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_collection` (`id_collection`),
  KEY `id_item` (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Related records link';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_translate`;
CREATE TABLE IF NOT EXISTS `app_section_collection_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED DEFAULT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `note` text,
  `page_title` varchar(255) DEFAULT NULL,
  `meta_title` varchar(250) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `FK_app_section_collection_id_l` (`id_lang`),
  KEY `app_section_collection_ibfk_2` (`id_collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коллекция записей раздела';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_values`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_values`;
CREATE TABLE IF NOT EXISTS `app_section_collection_values` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `id_field` int(10) UNSIGNED NOT NULL,
  `intvalue` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_values` (`id_collection`,`id_field`),
  KEY `intvalue` (`intvalue`),
  KEY `app_section_collection_values_ibfk_2` (`id_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список переменных коллекции';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_collection_values_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_collection_values_translate`;
CREATE TABLE IF NOT EXISTS `app_section_collection_values_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_values` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `value` longtext,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_lang` (`id_lang`),
  KEY `id_values` (`id_values`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список переменных коллекции';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_groups`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_groups`;
CREATE TABLE IF NOT EXISTS `app_section_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED NOT NULL,
  `id_parent` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_show` tinyint(1) DEFAULT '1',
  `is_search` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_parent` (`id_parent`),
  KEY `sort` (`sort`),
  KEY `app_section_groups_ibfk_1` (`id_section`),
  KEY `is_search` (`is_search`),
  KEY `is_show` (`is_show`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Группы разделов';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_groups_image`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_groups_image`;
CREATE TABLE IF NOT EXISTS `app_section_groups_image` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` int(10) UNSIGNED NOT NULL,
  `id_image` int(10) UNSIGNED NOT NULL,
  `sort` int(10) NOT NULL DEFAULT '0',
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_section_groups_image` (`id_group`,`id_image`),
  KEY `FK_app_section_groups_image_i2` (`id_image`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Группы разделов';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_groups_image_translate`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_groups_image_translate`;
CREATE TABLE IF NOT EXISTS `app_section_groups_image_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_image` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_section_collection_imag` (`id_image`,`id_lang`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `FK_app_section_collection_ima2` (`id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коллекция записей раздела';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_groups_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_groups_translate`;
CREATE TABLE IF NOT EXISTS `app_section_groups_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` int(10) UNSIGNED NOT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `note` text,
  `page_title` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_lang` (`id_lang`) USING BTREE,
  KEY `id_group` (`id_group`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Группы разделов';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_group_tree`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_group_tree`;
CREATE TABLE IF NOT EXISTS `app_section_group_tree` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent` int(10) UNSIGNED NOT NULL,
  `id_child` int(10) UNSIGNED NOT NULL,
  `level` tinyint(4) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_section_group_tree` (`id_parent`,`id_child`),
  KEY `FK_group_tree_category_id_child` (`id_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_parametrs`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_parametrs`;
CREATE TABLE IF NOT EXISTS `app_section_parametrs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED NOT NULL,
  `field` varchar(40) NOT NULL,
  `value` text NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_section` (`id_section`,`field`),
  KEY `field` (`field`),
  KEY `id_collection` (`id_section`),
  KEY `id_field` (`field`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список переменных коллекции';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_permission`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_permission`;
CREATE TABLE IF NOT EXISTS `app_section_permission` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED NOT NULL,
  `id_permission` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `app_section_permission_ibfk_1` (`id_section`),
  KEY `app_section_permission_ibfk_2` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Page permission';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_pricetype`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_pricetype`;
CREATE TABLE IF NOT EXISTS `app_section_pricetype` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_section` (`id_section`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Тип цены';

-- --------------------------------------------------------

--
-- Структура таблицы `app_section_translate`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_section_translate`;
CREATE TABLE IF NOT EXISTS `app_section_translate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_section` int(10) UNSIGNED DEFAULT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` mediumtext,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `app_section_ibfk_2` (`id_section`),
  KEY `id_section` (`id_section`),
  KEY `id_lang` (`id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Modules or block';

-- --------------------------------------------------------

--
-- Структура таблицы `app_setting`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_setting`;
CREATE TABLE IF NOT EXISTS `app_setting` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'название параметра',
  `code` varchar(255) NOT NULL COMMENT 'уникальной код параметра',
  `type` enum('string','bool','select','text','password','text') NOT NULL DEFAULT 'string' COMMENT 'string - текстовое поле, bool - чекбокс, select - выбор из списка из поля list_values',
  `default` varchar(100) NOT NULL COMMENT 'значение по умолчанию',
  `list_values` text COMMENT 'список значений в формате value1|name1,value2|name2,value3|name3 ',
  `description` text COMMENT 'описание параметра',
  `sort` int(10) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 - неактивный параметр',
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_setting_code` (`code`),
  KEY `FK_app_setting_app_setting_group_id` (`id_group`)
) ENGINE=InnoDB AVG_ROW_LENGTH=8192 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_setting_group`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_setting_group`;
CREATE TABLE IF NOT EXISTS `app_setting_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort` int(10) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_setting_group_code` (`code`)
) ENGINE=InnoDB AVG_ROW_LENGTH=16384 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_setting_value`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_setting_value`;
CREATE TABLE IF NOT EXISTS `app_setting_value` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED NOT NULL,
  `id_setting` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_app_setting_value` (`id_app`,`id_setting`),
  KEY `FK_app_setting_value_app_settings_id` (`id_setting`)
) ENGINE=InnoDB AVG_ROW_LENGTH=8192 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `app_urls`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `app_urls`;
CREATE TABLE IF NOT EXISTS `app_urls` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_app` int(10) UNSIGNED NOT NULL,
  `id_page` int(10) UNSIGNED DEFAULT NULL,
  `id_section` int(10) UNSIGNED DEFAULT NULL,
  `pattern` varchar(255) DEFAULT NULL,
  `type` enum('link','page','group','item') NOT NULL DEFAULT 'page',
  `alias` varchar(40) NOT NULL,
  `template` varchar(125) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`,`id_app`) USING BTREE,
  UNIQUE KEY `pattern` (`pattern`),
  KEY `app_urls_ibfk_1` (`id_app`),
  KEY `app_urls_ibfk_2` (`id_page`),
  KEY `app_urls_ibfk_3` (`id_section`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8 COMMENT='Паттерны ссылок приложений';

--
-- Дамп данных таблицы `app_urls`
--

INSERT INTO `app_urls` (`id`, `id_app`, `id_page`, `id_section`, `pattern`, `type`, `alias`, `template`, `updated_at`, `created_at`) VALUES
(77, 1, 30, NULL, '/', 'page', 'aindex', 'index', '0000-00-00 00:00:00', '2019-01-14 10:59:38');

-- --------------------------------------------------------

--
-- Структура таблицы `session`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
-- Последняя проверка: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `SID` varchar(32) NOT NULL DEFAULT '',
  `TIMES` int(11) DEFAULT NULL,
  `IDUSER` bigint(20) NOT NULL DEFAULT '0',
  `GROUPUSER` int(11) NOT NULL DEFAULT '0',
  `ADMINUSER` varchar(10) DEFAULT '',
  `USER` varchar(40) DEFAULT '',
  `LOGIN` varchar(30) DEFAULT '',
  `PASSW` varchar(32) DEFAULT '',
  `PAGES` varchar(30) DEFAULT '',
  `BLOCK` char(1) DEFAULT 'Y',
  `IP` varchar(15) DEFAULT '',
  PRIMARY KEY (`SID`),
  KEY `GROUPUSER` (`GROUPUSER`),
  KEY `IDUSER` (`IDUSER`),
  KEY `IP` (`IP`),
  KEY `TIMES` (`TIMES`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_group`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_group`;
CREATE TABLE IF NOT EXISTS `se_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` tinyint(4) DEFAULT '1',
  `name` varchar(40) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `id_parent` int(10) UNSIGNED DEFAULT NULL,
  `email_settings` varchar(255) DEFAULT NULL COMMENT 'Настройки для email рассылок',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_parent` (`id_parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_notice`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_notice`;
CREATE TABLE IF NOT EXISTS `se_notice` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender` varchar(255) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `target` enum('email','sms') NOT NULL DEFAULT 'email',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Уведомления' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `se_notice`
--

INSERT INTO `se_notice` (`id`, `sender`, `recipient`, `name`, `subject`, `content`, `target`, `is_active`, `updated_at`, `created_at`) VALUES
(1, NULL, 'support@go-market.ru', 'Вопрос с сайта', 'Обратный звонок с сайта [THISNAMESITE]', '<p><strong>Данные пользователя</strong></p>\n\n<p><strong>Имя</strong>: [NAME]</p>\n\n<p><strong>Телефон</strong>: [PHONE]</p>\n\n<p><strong>E-mail</strong>: [EMAIL]</p>\n', 'email', 1, NULL, '2018-09-14 08:12:57');

-- --------------------------------------------------------

--
-- Структура таблицы `se_notice_log`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_notice_log`;
CREATE TABLE IF NOT EXISTS `se_notice_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_notice` int(10) UNSIGNED NOT NULL,
  `sender` varchar(255) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `target` enum('email','sms','telegram') NOT NULL DEFAULT 'email',
  `content` mediumtext,
  `status` tinyint(4) NOT NULL COMMENT '0-отправлено, 1-доставлено, 2- не доставлено',
  `service_info` varchar(255) DEFAULT NULL COMMENT 'Информация от сервиса',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_notice` (`id_notice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Лог уведомлений' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Структура таблицы `se_notice_trigger`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_notice_trigger`;
CREATE TABLE IF NOT EXISTS `se_notice_trigger` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_notice` int(10) UNSIGNED NOT NULL,
  `id_trigger` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_notice_trigger` (`id_notice`,`id_trigger`),
  KEY `FK_notice_trigger_trigger_id` (`id_trigger`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Связи уведомления - события' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `se_notice_trigger`
--

INSERT INTO `se_notice_trigger` (`id`, `id_notice`, `id_trigger`, `updated_at`, `created_at`) VALUES
(1, 1, 1, NULL, '2018-09-14 08:13:38');

-- --------------------------------------------------------

--
-- Структура таблицы `se_permission_object`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_permission_object`;
CREATE TABLE IF NOT EXISTS `se_permission_object` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `isMil` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `isMill` (`isMil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_permission_object_role`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_permission_object_role`;
CREATE TABLE IF NOT EXISTS `se_permission_object_role` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_object` int(10) UNSIGNED NOT NULL,
  `id_role` int(10) UNSIGNED NOT NULL,
  `mask` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Маска прав (4 бита)',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_permission_object_role` (`id_object`,`id_role`),
  KEY `FK_permission_object_role_permission_role_id` (`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_permission_role`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_permission_role`;
CREATE TABLE IF NOT EXISTS `se_permission_role` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Роли пользователей';

--
-- Дамп данных таблицы `se_permission_role`
--

INSERT INTO `se_permission_role` (`id`, `name`, `description`, `updated_at`, `created_at`) VALUES
(1, 'admin', 'Администратор', '2018-08-22 17:51:10', '2018-08-21 15:19:15'),
(2, 'representatives', 'Представитель', '2018-08-22 17:51:10', '2018-08-21 15:19:15'),
(3, 'Nablyudately', 'Наблюдатель', '0000-00-00 00:00:00', '2018-08-24 05:33:44');

-- --------------------------------------------------------

--
-- Структура таблицы `se_permission_role_user`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_permission_role_user`;
CREATE TABLE IF NOT EXISTS `se_permission_role_user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_role` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_permission_role_user` (`id_role`,`id_user`),
  KEY `FK_permission_role_user_se_user_id` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_settings`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_settings`;
CREATE TABLE IF NOT EXISTS `se_settings` (
  `version` varchar(10) NOT NULL DEFAULT '1',
  `db_version` mediumint(9) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `se_settings`
--

INSERT INTO `se_settings` (`version`, `db_version`) VALUES
('1', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `se_trigger`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_trigger`;
CREATE TABLE IF NOT EXISTS `se_trigger` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='События для уведомлений' ROW_FORMAT=COMPACT;

--
-- Дамп данных таблицы `se_trigger`
--

INSERT INTO `se_trigger` (`id`, `code`, `name`, `updated_at`, `created_at`) VALUES
(1, 'feedback', 'Обратная связь', NULL, '2018-09-14 08:13:36');

-- --------------------------------------------------------

--
-- Структура таблицы `se_user`
--
-- Создание: Сен 14 2023 г., 08:05
-- Последнее обновление: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user`;
CREATE TABLE IF NOT EXISTS `se_user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_affiliate` int(10) DEFAULT NULL,
  `username` varchar(125) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `tmppassw` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_manager` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `person_name` varchar(255) NOT NULL,
  `sex` char(1) DEFAULT 'N',
  `birth_date` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_confirm` tinyint(1) NOT NULL DEFAULT '0',
  `phone` varchar(20) NOT NULL,
  `phone_confirm` tinyint(1) NOT NULL DEFAULT '0',
  `country` varchar(128) NOT NULL,
  `time_last_send` int(15) NOT NULL DEFAULT '0',
  `send_try` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL,
  `dbl_auth` tinyint(1) NOT NULL DEFAULT '0',
  `ga_secret` varchar(255) NOT NULL,
  `id_lang` int(10) UNSIGNED DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email_confirm` (`email_confirm`),
  KEY `id_affiliate` (`id_affiliate`),
  KEY `is_active` (`is_active`),
  KEY `is_super_admin` (`is_super_admin`),
  KEY `password` (`password`),
  KEY `phone_confirm` (`phone_confirm`),
  KEY `tmppassw` (`tmppassw`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `se_user`
--

INSERT INTO `se_user` (`id`, `id_affiliate`, `username`, `password`, `tmppassw`, `is_active`, `is_super_admin`, `is_manager`, `last_login`, `person_name`, `sex`, `birth_date`, `email`, `email_confirm`, `phone`, `phone_confirm`, `country`, `time_last_send`, `send_try`, `ip`, `dbl_auth`, `ga_secret`, `id_lang`, `photo`, `updated_at`, `created_at`) VALUES
(1, NULL, 'admin', '9f2dd9992322fbea5a01c3cc3e7df0e2', NULL, 1, 1, 1, NULL, 'Админ', 'N', NULL, '', 0, '', 0, '', 0, 0, '', 0, '', NULL, NULL, '2023-01-15 18:42:31', '2018-08-03 08:15:09');

-- --------------------------------------------------------

--
-- Структура таблицы `se_userfields`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_userfields`;
CREATE TABLE IF NOT EXISTS `se_userfields` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sect` enum('person','company') NOT NULL DEFAULT 'person',
  `id_group` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('image','list','string','text','textedit','file','url','group') NOT NULL DEFAULT 'string',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `placeholder` varchar(255) NOT NULL,
  `mask` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `values` text,
  `min` int(10) UNSIGNED DEFAULT NULL,
  `max` int(10) UNSIGNED DEFAULT NULL,
  `size` varchar(40) NOT NULL,
  `child_level` tinyint(4) NOT NULL DEFAULT '0',
  `defvalue` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `code` (`code`),
  KEY `enabled` (`enabled`),
  KEY `sect` (`sect`),
  KEY `sort` (`sort`),
  KEY `se_userfields_ibfk_1` (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список переменных';

-- --------------------------------------------------------

--
-- Структура таблицы `se_userfields_group`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_userfields_group`;
CREATE TABLE IF NOT EXISTS `se_userfields_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sect` enum('person','company') NOT NULL DEFAULT 'person',
  `name` varchar(255) NOT NULL,
  `is_multi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Это группа списка',
  `sort` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `type` (`sect`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_user_collection`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user_collection`;
CREATE TABLE IF NOT EXISTS `se_user_collection` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_collection` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_collection` (`id_collection`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_user_file`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user_file`;
CREATE TABLE IF NOT EXISTS `se_user_file` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_file` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `se_user_file_ibfk_1` (`id_file`),
  KEY `se_user_file_ibfk_2` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_user_permission`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user_permission`;
CREATE TABLE IF NOT EXISTS `se_user_permission` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_permission` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `se_user_permission_ibfk_1` (`id_user`),
  KEY `se_user_permission_ibfk_2` (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `se_user_region`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user_region`;
CREATE TABLE IF NOT EXISTS `se_user_region` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_region` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `app_section_collection_gcontacts_ibfk_1` (`id_user`),
  KEY `app_section_collection_gcontacts_ibfk_2` (`id_region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Таблица связей с гео-контактами';

-- --------------------------------------------------------

--
-- Структура таблицы `se_user_values`
--
-- Создание: Сен 14 2023 г., 08:05
--

DROP TABLE IF EXISTS `se_user_values`;
CREATE TABLE IF NOT EXISTS `se_user_values` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_userfield` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `intvalue` int(11) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_user_field` (`id_userfield`),
  KEY `intvalue` (`intvalue`),
  KEY `se_user_values_ibfk_2` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Таблица дополнительных полей пользователя';

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `app_collection_offer_price`
--
ALTER TABLE `app_collection_offer_price`
  ADD CONSTRAINT `app_collection_offer_price_ibfk_1` FOREIGN KEY (`id_offer`) REFERENCES `app_collection_offer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_collection_offer_price_ibfk_2` FOREIGN KEY (`id_typeprice`) REFERENCES `app_section_pricetype` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_fields`
--
ALTER TABLE `app_fields`
  ADD CONSTRAINT `app_fields_ibfk_2` FOREIGN KEY (`id_group`) REFERENCES `app_fieldsgroup` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `app_fields_ibfk_3` FOREIGN KEY (`id_service`) REFERENCES `app_fields_service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_fieldsgroup`
--
ALTER TABLE `app_fieldsgroup`
  ADD CONSTRAINT `app_fieldsgroup_ibfk_1` FOREIGN KEY (`id_service`) REFERENCES `app_fields_service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_fields_service`
--
ALTER TABLE `app_fields_service`
  ADD CONSTRAINT `app_fields_service_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_fields_service_ibfk_2` FOREIGN KEY (`id_request`) REFERENCES `app_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_fields_service_ibfk_3` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_image`
--
ALTER TABLE `app_image`
  ADD CONSTRAINT `FK_image_image_folder_id` FOREIGN KEY (`id_folder`) REFERENCES `app_image_folder` (`id`);

--
-- Ограничения внешнего ключа таблицы `app_ml`
--
ALTER TABLE `app_ml`
  ADD CONSTRAINT `app_ml_ibfk_1` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_ml_translate`
--
ALTER TABLE `app_ml_translate`
  ADD CONSTRAINT `app_ml_translate_ibfk_1` FOREIGN KEY (`id_ml`) REFERENCES `app_ml` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_ml_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_ml` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_nav`
--
ALTER TABLE `app_nav`
  ADD CONSTRAINT `FK_app_nav_id_app` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`);

--
-- Ограничения внешнего ключа таблицы `app_nav_url`
--
ALTER TABLE `app_nav_url`
  ADD CONSTRAINT `FK_app_nav_url_id_collection` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_app_nav_url_id_group` FOREIGN KEY (`id_group`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_app_nav_url_id_nav` FOREIGN KEY (`id_nav`) REFERENCES `app_nav` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_app_nav_url_id_parent` FOREIGN KEY (`id_parent`) REFERENCES `app_nav_url` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_app_nav_url_id_url` FOREIGN KEY (`id_url`) REFERENCES `app_urls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_nav_url_translate`
--
ALTER TABLE `app_nav_url_translate`
  ADD CONSTRAINT `app_nav_url_translate_ibfk_1` FOREIGN KEY (`id_nav_url`) REFERENCES `app_nav_url` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_nav_url_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_pages`
--
ALTER TABLE `app_pages`
  ADD CONSTRAINT `app_pages_ibfk_1` FOREIGN KEY (`id_permission`) REFERENCES `app_permission` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_page_permission`
--
ALTER TABLE `app_page_permission`
  ADD CONSTRAINT `app_page_permission_ibfk_1` FOREIGN KEY (`id_page`) REFERENCES `app_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_page_permission_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `app_permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_request_order`
--
ALTER TABLE `app_request_order`
  ADD CONSTRAINT `app_request_order_ibfk_1` FOREIGN KEY (`id_request`) REFERENCES `app_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_request_order_values`
--
ALTER TABLE `app_request_order_values`
  ADD CONSTRAINT `app_request_order_values_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `app_request_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_request_order_values_ibfk_2` FOREIGN KEY (`id_field`) REFERENCES `app_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section`
--
ALTER TABLE `app_section`
  ADD CONSTRAINT `app_section_ibfk_2` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_ibfk_3` FOREIGN KEY (`id_parent`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection`
--
ALTER TABLE `app_section_collection`
  ADD CONSTRAINT `app_section_collection_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_ibfk_2` FOREIGN KEY (`id_parent`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_comments`
--
ALTER TABLE `app_section_collection_comments`
  ADD CONSTRAINT `app_section_collection_comments_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_connect`
--
ALTER TABLE `app_section_collection_connect`
  ADD CONSTRAINT `app_section_collection_connect_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_connect_ibfk_2` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_connect_ibfk_3` FOREIGN KEY (`id_item`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_files`
--
ALTER TABLE `app_section_collection_files`
  ADD CONSTRAINT `app_section_collection_files_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_files_ibfk_2` FOREIGN KEY (`id_owner`) REFERENCES `se_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_group`
--
ALTER TABLE `app_section_collection_group`
  ADD CONSTRAINT `app_section_collection_group_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_group_ibfk_2` FOREIGN KEY (`id_group`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_image`
--
ALTER TABLE `app_section_collection_image`
  ADD CONSTRAINT `FK_app_section_collection_ima2` FOREIGN KEY (`id_image`) REFERENCES `app_image` (`id`),
  ADD CONSTRAINT `FK_app_section_collection_imag` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_image_translate`
--
ALTER TABLE `app_section_collection_image_translate`
  ADD CONSTRAINT `app_section_collection_image_translate_ibfk_1` FOREIGN KEY (`id_image`) REFERENCES `app_section_collection_image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_image_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_permission`
--
ALTER TABLE `app_section_collection_permission`
  ADD CONSTRAINT `app_section_collection_permission_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_permission_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `app_permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_region`
--
ALTER TABLE `app_section_collection_region`
  ADD CONSTRAINT `app_section_collection_region_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_region_ibfk_2` FOREIGN KEY (`id_region`) REFERENCES `app_regions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_reviews`
--
ALTER TABLE `app_section_collection_reviews`
  ADD CONSTRAINT `app_section_collection_reviews_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_reviews_votes`
--
ALTER TABLE `app_section_collection_reviews_votes`
  ADD CONSTRAINT `app_section_collection_reviews_votes_ibfk_1` FOREIGN KEY (`id_review`) REFERENCES `app_section_collection_reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_reviews_votes_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_similar`
--
ALTER TABLE `app_section_collection_similar`
  ADD CONSTRAINT `app_section_collection_similar_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_similar_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_translate`
--
ALTER TABLE `app_section_collection_translate`
  ADD CONSTRAINT `app_section_collection_translate_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_values`
--
ALTER TABLE `app_section_collection_values`
  ADD CONSTRAINT `app_section_collection_values_ibfk_1` FOREIGN KEY (`id_collection`) REFERENCES `app_section_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_values_ibfk_2` FOREIGN KEY (`id_field`) REFERENCES `app_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_collection_values_translate`
--
ALTER TABLE `app_section_collection_values_translate`
  ADD CONSTRAINT `app_section_collection_values_translate_ibfk_1` FOREIGN KEY (`id_values`) REFERENCES `app_section_collection_values` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_collection_values_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_groups`
--
ALTER TABLE `app_section_groups`
  ADD CONSTRAINT `app_section_groups_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_groups_ibfk_2` FOREIGN KEY (`id_parent`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_groups_image`
--
ALTER TABLE `app_section_groups_image`
  ADD CONSTRAINT `FK_app_section_groups_image_i2` FOREIGN KEY (`id_image`) REFERENCES `app_image` (`id`),
  ADD CONSTRAINT `FK_app_section_groups_image_id` FOREIGN KEY (`id_group`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_groups_image_translate`
--
ALTER TABLE `app_section_groups_image_translate`
  ADD CONSTRAINT `app_section_groups_image_translate_ibfk_1` FOREIGN KEY (`id_image`) REFERENCES `app_section_groups_image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_groups_image_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_groups_translate`
--
ALTER TABLE `app_section_groups_translate`
  ADD CONSTRAINT `app_section_groups_translate_ibfk_1` FOREIGN KEY (`id_group`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_groups_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_group_tree`
--
ALTER TABLE `app_section_group_tree`
  ADD CONSTRAINT `FK_group_tree_category_id_child` FOREIGN KEY (`id_child`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_group_tree_category_id_parent` FOREIGN KEY (`id_parent`) REFERENCES `app_section_groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_parametrs`
--
ALTER TABLE `app_section_parametrs`
  ADD CONSTRAINT `app_section_parametrs_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_permission`
--
ALTER TABLE `app_section_permission`
  ADD CONSTRAINT `app_section_permission_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_permission_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `app_permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_pricetype`
--
ALTER TABLE `app_section_pricetype`
  ADD CONSTRAINT `app_section_pricetype_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_section_translate`
--
ALTER TABLE `app_section_translate`
  ADD CONSTRAINT `app_section_translate_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_section_translate_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `app_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_setting`
--
ALTER TABLE `app_setting`
  ADD CONSTRAINT `FK_app_setting_app_setting_group_id` FOREIGN KEY (`id_group`) REFERENCES `app_setting_group` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `app_setting_value`
--
ALTER TABLE `app_setting_value`
  ADD CONSTRAINT `FK_app_setting_value_app_settings_id` FOREIGN KEY (`id_setting`) REFERENCES `app_setting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_app_setting_value_id_app` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`) ON DELETE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `app_urls`
--
ALTER TABLE `app_urls`
  ADD CONSTRAINT `app_urls_ibfk_1` FOREIGN KEY (`id_app`) REFERENCES `apps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_urls_ibfk_2` FOREIGN KEY (`id_page`) REFERENCES `app_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `app_urls_ibfk_3` FOREIGN KEY (`id_section`) REFERENCES `app_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_notice_log`
--
ALTER TABLE `se_notice_log`
  ADD CONSTRAINT `se_notice_log_ibfk_1` FOREIGN KEY (`id_notice`) REFERENCES `se_notice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_notice_trigger`
--
ALTER TABLE `se_notice_trigger`
  ADD CONSTRAINT `FK_notice_trigger_notice_id` FOREIGN KEY (`id_notice`) REFERENCES `se_notice` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_notice_trigger_trigger_id` FOREIGN KEY (`id_trigger`) REFERENCES `se_trigger` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_permission_object_role`
--
ALTER TABLE `se_permission_object_role`
  ADD CONSTRAINT `FK_permission_object_role_permission_object_id` FOREIGN KEY (`id_object`) REFERENCES `se_permission_object` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_permission_object_role_permission_role_id` FOREIGN KEY (`id_role`) REFERENCES `se_permission_role` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_permission_role_user`
--
ALTER TABLE `se_permission_role_user`
  ADD CONSTRAINT `FK_permission_role_user_permission_role_id` FOREIGN KEY (`id_role`) REFERENCES `se_permission_role` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_permission_role_user_se_user_id` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_userfields`
--
ALTER TABLE `se_userfields`
  ADD CONSTRAINT `se_userfields_ibfk_1` FOREIGN KEY (`id_group`) REFERENCES `se_userfields_group` (`id`) ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `se_user_file`
--
ALTER TABLE `se_user_file`
  ADD CONSTRAINT `se_user_file_ibfk_1` FOREIGN KEY (`id_file`) REFERENCES `app_section_collection_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `se_user_file_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_user_permission`
--
ALTER TABLE `se_user_permission`
  ADD CONSTRAINT `se_user_permission_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `se_user_permission_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `app_permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_user_region`
--
ALTER TABLE `se_user_region`
  ADD CONSTRAINT `se_user_region_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `se_user_region_ibfk_2` FOREIGN KEY (`id_region`) REFERENCES `app_regions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `se_user_values`
--
ALTER TABLE `se_user_values`
  ADD CONSTRAINT `se_user_values_ibfk_1` FOREIGN KEY (`id_userfield`) REFERENCES `se_userfields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `se_user_values_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `se_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
