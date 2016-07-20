/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;

/*!40000 ALTER TABLE `eav_attributes_bool`
  DISABLE KEYS */;
INSERT INTO `eav_attributes_bool` (`id`, `id_entity`, `name`, `value`) VALUES
  (1, 2, 'IsDisabled', 0),
  (2, 2, 'IsDefault', 0),
  (3, 2, 'AllowChangeAdminEmail', 1),
  (4, 2, 'AllowChangeAdminPassword', 1),
  (5, 2, 'IsTrial', 0),
  (10, 3, 'IsDisabled', 0),
  (11, 3, 'SipEnable', 1),
  (12, 3, 'DesktopNotifications', 0),
  (13, 3, 'EnableOpenPgp', 1),
  (14, 3, 'AutosignOutgoingEmails', 1),
  (15, 3, 'FilesEnable', 1),
  (23, 4, 'IsDisabled', 0),
  (24, 7, 'IsDisabled', 0),
  (25, 7, 'SipEnable', 1),
  (26, 7, 'DesktopNotifications', 0),
  (27, 7, 'EnableOpenPgp', 1),
  (28, 7, 'AutosignOutgoingEmails', 1),
  (29, 7, 'FilesEnable', 1),
  (37, 8, 'IsDisabled', 0);
/*!40000 ALTER TABLE `eav_attributes_bool`
  ENABLE KEYS */;

/*!40000 ALTER TABLE `eav_attributes_datetime`
  DISABLE KEYS */;
INSERT INTO `eav_attributes_datetime` (`id`, `id_entity`, `name`, `value`) VALUES
  (1, 4, 'LastModified', '2016-06-17 13:55:22'),
  (2, 5, 'Date', '2016-06-17 10:56:07'),
  (4, 8, 'LastModified', '2016-06-23 12:14:51');
/*!40000 ALTER TABLE `eav_attributes_datetime`
  ENABLE KEYS */;

/*!40000 ALTER TABLE `eav_attributes_int`
  DISABLE KEYS */;
INSERT INTO `eav_attributes_int` (`id`, `id_entity`, `name`, `value`) VALUES
  (1, 2, 'IdTenant', 0),
  (2, 2, 'IdChannel', 1),
  (3, 2, 'AllocatedSpaceInMB', 0),
  (4, 2, 'FilesUsageInMB', 0),
  (5, 2, 'FilesUsageDynamicQuotaInMB', 0),
  (6, 2, 'QuotaInMB', 0),
  (7, 2, 'UserCountLimit', 0),
  (8, 2, 'DomainCountLimit', 0),
  (9, 2, 'Expared', 0),
  (12, 3, 'IdTenant', 2),
  (13, 3, 'IdSubscription', 0),
  (14, 3, 'Role', 1),
  (15, 3, 'ContactsPerPage', 0),
  (16, 3, 'AutoRefreshInterval', 0),
  (17, 3, 'LoginsCount', 0),
  (18, 3, 'DefaultTimeZone', 0),
  (19, 3, 'DefaultTimeFormat', 0),
  (21, 4, 'IdUser', 3),
  (22, 5, 'UserId', 3),
  (24, 7, 'IdTenant', 2),
  (25, 7, 'IdSubscription', 0),
  (26, 7, 'Role', 0),
  (27, 7, 'ContactsPerPage', 0),
  (28, 7, 'AutoRefreshInterval', 0),
  (29, 7, 'LoginsCount', 0),
  (30, 7, 'DefaultTimeZone', 0),
  (31, 7, 'DefaultTimeFormat', 0),
  (33, 8, 'IdUser', 7);
/*!40000 ALTER TABLE `eav_attributes_int`
  ENABLE KEYS */;

/*!40000 ALTER TABLE `eav_attributes_string`
  DISABLE KEYS */;
INSERT INTO `eav_attributes_string` (`id`, `id_entity`, `name`, `value`) VALUES
  (1, 1, 'Login', 'ch1'),
  (2, 1, 'Password', '98781def246ae4d1356c0fc518b5888e'),
  (3, 1, 'Description', 'Channel 1'),
  (4, 2, 'Name', 't1'),
  (5, 2, 'Description', 'Tenant 1'),
  (6, 2, 'FilesUsageInBytes', '0'),
  (7, 2, 'Capa', ''),
  (8, 2, 'PayUrl', ''),
  (9, 2, 'LoginStyleImage', ''),
  (10, 2, 'AppStyleImage', ''),
  (11, 2, 'CalendarNotificationEmailAccount', ''),
  (12, 2, 'InviteNotificationEmailAccount', ''),
  (24, 3, 'Name', 'u1'),
  (25, 3, 'CreatedTime', ''),
  (26, 3, 'LastLogin', ''),
  (27, 3, 'LastLoginNow', ''),
  (28, 3, 'DefaultSkin', ''),
  (29, 3, 'DefaultLanguage', ''),
  (30, 3, 'DefaultDateFormat', ''),
  (31, 3, 'ClientTimeZone', ''),
  (32, 3, 'Question1', ''),
  (33, 3, 'Question2', ''),
  (34, 3, 'Answer1', ''),
  (35, 3, 'Answer2', ''),
  (36, 3, 'SipImpi', ''),
  (37, 3, 'SipPassword', ''),
  (38, 3, 'Capa', ''),
  (39, 3, 'CustomFields', ''),
  (40, 3, 'EmailNotification', ''),
  (41, 3, 'PasswordResetHash', ''),
  (42, 3, 'Twilio::TwilioNumber', ''),
  (43, 4, 'Login', 'test1'),
  (44, 4, 'Password', 'BOEEC/H9dBpqiuDOOdvUs4ruUWgdp/jp+UiZzOYMZwY='),
  (45, 7, 'Name', 'u2'),
  (46, 7, 'CreatedTime', ''),
  (47, 7, 'LastLogin', ''),
  (48, 7, 'LastLoginNow', ''),
  (49, 7, 'DefaultSkin', ''),
  (50, 7, 'DefaultLanguage', ''),
  (51, 7, 'DefaultDateFormat', ''),
  (52, 7, 'ClientTimeZone', ''),
  (53, 7, 'Question1', ''),
  (54, 7, 'Question2', ''),
  (55, 7, 'Answer1', ''),
  (56, 7, 'Answer2', ''),
  (57, 7, 'SipImpi', ''),
  (58, 7, 'SipPassword', ''),
  (59, 7, 'Capa', ''),
  (60, 7, 'CustomFields', ''),
  (61, 7, 'EmailNotification', ''),
  (62, 7, 'PasswordResetHash', ''),
  (63, 7, 'Twilio::TwilioNumber', ''),
  (64, 8, 'Login', 'admin'),
  (65, 8, 'Password', 'BOEEC/H9dBpqiuDOOdvUs4ruUWgdp/jp+UiZzOYMZwY=');
/*!40000 ALTER TABLE `eav_attributes_string`
  ENABLE KEYS */;

/*!40000 ALTER TABLE `eav_attributes_text`
  DISABLE KEYS */;
INSERT INTO `eav_attributes_text` (`id`, `id_entity`, `name`, `value`) VALUES
  (1, 5, 'Text', 'test');
/*!40000 ALTER TABLE `eav_attributes_text`
  ENABLE KEYS */;

/*!40000 ALTER TABLE `eav_entities`
  DISABLE KEYS */;
INSERT INTO `eav_entities` (`id`, `entity_type`, `module_name`) VALUES
  (1, 'CChannel', 'Core'),
  (2, 'CTenant', 'Core'),
  (3, 'CUser', 'Core'),
  (4, 'CAccount', 'Auth'),
  (5, 'CSimpleChatPost', 'SimpleChat'),
  (7, 'CUser', 'Core'),
  (8, 'CAccount', 'Auth');
/*!40000 ALTER TABLE `eav_entities`
  ENABLE KEYS */;

/*!40101 SET SQL_MODE = IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS = IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;