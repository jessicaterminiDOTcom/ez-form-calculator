DROP TABLE IF EXISTS `__PREFIX__ezfcf_elements`;
CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` tinytext NOT NULL,
  `type` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

INSERT IGNORE INTO `__PREFIX__ezfcf_elements` (`id`, `name`, `description`, `type`, `data`, `icon`) VALUES (1, 'Input', 'Basic input field with no restrictions', 'input', '{\r\n	"name": "Input",\r\n	"label": "Text",\r\n	"required": 0,\r\n	"value": "",\r\n	"placeholder": "",\r\n	"class": ""\r\n}', 'fa-pencil-square-o'), (2, 'Email', 'Email input field', 'email', '{\r\n	"name": "Email",\r\n	"label": "Email",\r\n	"required": 0,\r\n	"value": "",\r\n	"placeholder": "",\r\n	"class": ""\r\n}', 'fa-envelope-o'), (3, 'Textfield', 'Large text field', 'textfield', '{\r\n	"name": "Textfield",\r\n	"label": "Textfield",\r\n	"required": 0,\r\n	"value": "",\r\n	"placeholder": "",\r\n	"class": ""\r\n}', 'fa-align-justify'), (7, 'Numbers', 'Numbers only', 'numbers', '{\r\n	"name": "Numbers",\r\n	"label": "Numbers",\r\n	"required": 0,\r\n	"calculate_enabled": 1,\r\n	"factor": "",\r\n	"value": "",\r\n	"min": "0",\r\n	"max": "100",\r\n	"calculate": [],\r\n	"placeholder": "",\r\n	"class": ""\r\n}', 'fa-html5');

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `f_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `f_id` (`f_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms_options` (
  `f_id` int(10) unsigned NOT NULL,
  `o_id` int(10) unsigned NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`f_id`,`o_id`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `description_long` text NOT NULL,
  `type` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

INSERT IGNORE INTO `__PREFIX__ezfcf_options` (`id`, `name`, `value`, `description`, `description_long`, `type`) VALUES (1, 'email_recipient', '', 'Email recipient', 'Notifications will be sent to this email. Leave blank for no notifications.', ''), (2, 'success_text', 'Thank you for your submission!', 'Submission message', 'Frontend message after successful submission.', ''), (3, 'spam_time', '60', 'Spam protection in seconds', 'Every x seconds, a user (identified by IP address) can add an entry. Default: 60.', ''), (4, 'show_required_char', '1', 'Show required char', '', 'yesno'), (5, 'currency', '$', 'Currency', '', ''), (6, 'price_label', 'Price', 'Price label', 'Calculated field label (default: Price)', ''), (7, 'show_element_price', '0', 'Show element prices', '', 'yesno'), (8, 'submission_enabled', '1', 'Submission enabled', '', 'yesno'), (9, 'show_price_position', '1', 'Total price position', 'Price can be displayed above or below the form (or both)', 'price_position');

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `f_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  `content` mediumtext NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) NOT NULL,
  `ref_id` VARCHAR(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `f_id` (`f_id`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_templates` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_files` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`f_id` INT(10) UNSIGNED NOT NULL,
	`ref_id` VARCHAR(16) NOT NULL,
	`url` VARCHAR(2048) NOT NULL,
	`file` VARCHAR(2048) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT COLLATE="utf8_general_ci";