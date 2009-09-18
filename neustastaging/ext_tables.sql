#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_neustastaging_staging tinyint(3) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_neustastaging_files'
#
CREATE TABLE tx_neustastaging_files (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	path tinytext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_neustastaging_log'
#
CREATE TABLE tx_neustastaging_log (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	time int(11) DEFAULT '0' NOT NULL,
	user int(11) DEFAULT '0' NOT NULL,
	pages text NOT NULL,
	files text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);