create table pamm_broker (
	id int unsigned not null primary key auto_increment,
	name varchar(32) not null
) engine=InnoDB charset=utf8;

create table pamm (
	id int unsigned not null primary key auto_increment,
	vendor_id int unsigned not null unique key,
	broker_id int unsigned not null,
	name varchar(32) not null,
	started_at date not null,
	investor_percent tinyint unsigned not null,
	minimal_balance int unsigned not null,
	agent_deposit tinyint unsigned not null,
	agent_profit tinyint unsigned not null,
	start_capital int unsigned not null,
	current_capital int unsigned not null,
	full_capital int unsigned not null,
	created_at datetime not null,
	stats_updated_at datetime not null,
	foreign key (broker_id) references pamm_broker (id)
) engine=InnoDB charset=utf8;

create table pamm_stat_week (
	id int not null primary key auto_increment,
	pamm_id int unsigned not null,
	year year(4) not null,
	week tinyint unsigned not null,
	profit_percent DECIMAL(5,2) not null,
	unique key (pamm_id, week, year),
	foreign key (pamm_id) references pamm (id)
) engine=InnoDB charset=utf8;
