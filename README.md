# Welcome to cIRCuitbot
While new to being published and open-sourced, the codebase behind cIRCuitbot started more than 10 years ago - as early as 2009, in fact. Originally created for very niche purposes for the #reddit-sysadmin channel on [Libera.Chat](https://libera.chat), over time the codebase was expanded, improved, overhauled, expanded again, overhauled some more, and eventually was finally turned into what you're looking at now.

## Addons
The official repo for addons is [cIRCuitbot-addons](https://github.com/mistiry/cIRCuitbot-addons).

## How it Works

Out of the box, there is very little functionality included - and this is on purpose! The entire idea is that the bot can be customized to your needs, by installing additional triggers and modules or writing your own! Read more below to understand the simple way functionality is added to the bot.

## The Core Bot

At it's core, cIRCuitbot can do very little. Just about the only things it does do is let you set modes on users when they join, keep track of users the bot sees, and tell the bot to ignore a user.

There are two ways in which more functionality can be added to the bot - triggers and modules. Follow the installation steps below to get started and see how you can add or even create your own triggers and modules.

## Installation

There isn't much needed to get started - clone the repo and you are ready to set up the configuration file and database. Granted, that assumes you have PHP installed and runnable from command-line, and MariaDB installed (or are able to build the database elsewhere). 

### Database Setup

There is one database, with one table, to set up. The database is available for additional modules or triggers to use, and additional tables may be required for those to work. Instructions for those tables are up to the module author to provide.
1. Create a database with a name of your choosing.
2. Add a user to MariaDB with all permissions on that database, with a password.

Once you have that, you can load the `initialize_database.sql` file to set up the tables. This is typically done like this: `mysql -u userName -p databaseName < /path/to/initialize_database.sql`

That file will do the following:
```
CREATE TABLE `known_users` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`hostname` varchar(255) DEFAULT NULL,
	`nick_aliases` varchar(1024) DEFAULT NULL,
	`last_datatype` varchar(12) DEFAULT NULL,
	`last_message` varchar(512) DEFAULT NULL,
	`last_location` varchar(64) DEFAULT NULL,
	`total_words` bigint(20) DEFAULT NULL,
	`total_lines` bigint(20) DEFAULT NULL,
	`bot_flags` text,
	`join_modes` varchar(8) DEFAULT NULL,
	`timestamp` varchar(128) DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
```

### Config File Setup
The next thing to do is set up a configuration file. There is an `example.conf` file included, but you should **not** edit that file directly, but rather copy it or create a new one and use that. 

The example file explains in more detail what each configuration option does.

## Triggers
Triggers are used to make the bot perform an action without a command being given. Typically, these are used to make the bot respond automatically when it sees a particular word, or to grab URL's from messages and return the title of the page. 

Triggers are installed by placing the root of the trigger's folders into the `triggers/` directory. The name of the folder is the name of the trigger, which you will need to add to the list of loaded triggers in the bot's config file. For example, if you wanted to use a trigger called `respondToOwnNick`, you would place the files at `triggers/respondToOwnNick` and add the name `respondToOwnNick` to the triggers section of the config file.
Inside of the `respondToOwnNick` folder should, at minimum, contain a `trigger.conf` and `trigger.php` file, which are verified upon starting the bot.

## Modules
Modules work very similarly to triggers but are only invoked in response to a command being given by a user in the channel. Modules can do all sorts of things - interact with a quote database, perform Google searches, and much more. 

Modules are installed by placing the root of the module's folders into the `modules/` directory. The name of the folder is the name of the module, which you will need to add to the list of loaded modulesin the bot's config file. For example, if you wanted to use a module called `repeatMessage`, you would place the files at `modules/repeatMessage` and add the name `repeatMessage` to the modules section of the config file.
Inside of the `repeatMessage` folder should, at minimum, contain a `module.conf` and `module.php` file, which are verified upon starting the bot.

# Miscellaneous
The main IRC channel where the bot is developed can be found in #cIRCuitbot on [Libera.Chat](https://libera.chat). 