CREATE TABLE `eav_objects` (
  `id` int(11) NOT NULL auto_increment,
  `object_type` varchar(255) default NULL,
  `module_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `eav_properties` (
  `id` int(11) NOT NULL auto_increment,
  `id_object` int(11) default NULL,
  `key` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `value_text` text,
  `value_string` varchar(255) default NULL,
  `value_int` int(11) default NULL,
  `value_bool` tinyint(1) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
