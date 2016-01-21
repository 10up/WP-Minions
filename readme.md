WP Gears [![Build Status](https://travis-ci.org/10up/WP-Gears.svg?branch=master)](https://travis-ci.org/10up/WP-Gears)
========

Integrate [Gearman](http://gearman.org/) with [WordPress](http://wordpress.org/) for asynchronous task running.

## Background & Purpose

As WordPress becomes a more popular publishing platform for increasingly large publishers, with complex workflows, the need for increasingly complex and resource-intensive tasks has only increased. Things like generating reports, expensive API calls, syncing users to mail providers, or even ingesting content from feeds all take a lot of time or a lot of memory (or both), and commonly can't finish within common limitations of web servers, because things like timeouts and memory limits get in the way.

WP Gears provides a few helper functions that allow you to add tasks to a queue, and specify an action that should be called to trigger the task, just hook a callback into the action using ```add_action()```

During configuration, a number of workers are specified. As workers are free, they will take the next task from the queue, call the action, and any callbacks hooked into the action will be run.

In the situation of needing more ram or higher timeouts, a separate server to process the tasks is ideal - Just set up WordPress on that server like the standard web servers, and up the resources. Make sure not to send any production traffic to the server, and it will exclusively handle tasks from the queue.

## Installation

There are a few parts to get this all running. First, the Gearman backend needs to be setup - this part will vary depending on your OS. Once that is complete, we can install the WordPress plugin, and set the configuration options for WordPress.

#### Backend Setup - CentOS 6.x

1. You'll need the [EPEL](https://fedoraproject.org/wiki/EPEL) repo for gearman, and the [REMI](http://rpms.famillecollet.com/) repo for some of the php packages. Make sure to enable the appropriate remi repo for the version of php you are using.
  - ```wget http://dl.fedoraproject.org/pub/epel/6/i386/epel-release-6-8.noarch.rpm && rpm -Uvh epel-release-6*.rpm```
  - ```wget http://rpms.famillecollet.com/enterprise/remi-release-6.rpm && rpm -Uvh remi-release-6*.rpm```
  - ```rm *.rpm```
1. Make sure that remi is enabled, as well as any specific version of php you may want in ```/etc/yum.repos.d/remi.repo```
1. ```yum install gearmand php-pecl-gearman python-pip```
1. ```easy_install supervisor```
1. ```chkconfig supervisord on && chkconfig gearmand on```
1. If everything is running on one server, I would recommend limiting connections to localhost only. If not, you'll want to set up firewall rules to only allow certain clients to connect on the Gearman port (Default 4730)
  - edit ```/etc/sysconfig/gearmand``` - set ```OPTIONS="--listen=localhost"```
1. ```service gearmand start```

#### Backend Setup - Ubuntu

As you go through this, you may need to install additional packages, if you do not have them already, such as php-pear or a php*-dev package

1. ```apt-get update```
1. ```apt-get install gearman python-pip libgearman-dev supervisor```
1. ```pecl install gearman```
1. Once pecl install is complete, it will tell you to place something like ```extension=gearman.so``` into your php.ini file - Do this.
1. ```update-rc.d gearman-job-server defaults && update-rc.d supervisor defaults```
1. If everything is running on one server, I would recommend limiting connections to localhost only. If not, you'll want to set up firewall rules to only allow certain clients to connect on the Gearman port (Default 4730)
  - edit ```/etc/default/gearman-job-server``` - set ```PARAMS="--listen=localhost"```
1. ```service gearmand restart```

#### Supervisor Configuration

Filling in values in ```<brackets>``` as required, add the following config to either ```/etc/supervisord.conf``` (CentOS) or ```/etc/supervisor/supervisord.conf``` (Ubuntu)

```sh
[program:my_wp_gears_workers]
command=/usr/bin/php <path_to_wordpress>/wp-gears-runner.php
process_name=%(program_name)s-%(process_num)02d
numprocs=<number_of_workers>
directory=<path_to_temp_directory>
autostart=true
autorestart=true
killasgroup=true
user=<user>
```

* path_to_wordpress: Absolute path to the root of your WordPress install, ex: ```/var/www/html/wordpress```
* number_of_workers: How many workers should be spawned (How many jobs can be running at once).
* path_to_temp_directory: probably should just be the same as path_to_wordpress.
* user: The system user to run the processes under, probably apache, nginx, or www-data.
* You can optionally change the "my_wp_gears_workers" text to something more descriptive, if you'd like.

#### Configuring WordPress

* Install the plugin in WordPress. If desired, you can [download a zip](http://github.com/10up/WP-Gears/archive/master.zip) and install via the WordPress plugin installer.

* Create a symlink at the site root (the same directory as ```wp-settings.php```) that points to the ```wp-gears-runner.php``` file in the plugin (or copy the file, but a symlink will ensure it is updated if the plugin is updated)


* If your gearman service not running locally or uses a non-standard port, you'll need define your gearman servers in ```wp-config.php```
```php
global $gearman_servers;
$gearman_servers = array(
	'127.0.0.1:4730',
);
```

* Define a unique salt in `wp-config.php` so that multiple installs don't conflict.
```php
define( 'WP_ASYNC_TASK_SALT', 'my-unique-salt-1' );
```

**Note:** If you are using multisite, you'll also have to add the following to your ```wp-config.php``` file, _after_ the block with the multisite definitions. This is due to the fact that multisite relies on ```HTTP_HOST``` to detect the site/blog it is running under. You'll also want to make sure you are actually defining ```DOMAIN_CURRENT_SITE``` in the multisite configuration.
```php
if ( ! isset( $_SERVER['HTTP_HOST'] ) && defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
	$_SERVER['HTTP_HOST'] = DOMAIN_CURRENT_SITE;
}
```

## MySQL Persistent Queue (Recommended)

By default, gearman will store the job queue in memory. If for whatever reason the gearman service goes away, so does the queue. For persistence, you can optionally use a MySQL database for the job queue:

#### CentOS

Edit the gearman config at ```/etc/sysconfig/gearmand```, adding the following to the OPTIONS line (or creating the line, if it doesn't exist yet), inserting database credentials as necessary:
```sh
OPTIONS="-q MySQL --mysql-host=localhost --mysql-port=3306 --mysql-user=<user> --mysql-password=<password> --mysql-db=gearman --mysql-table=gearman_queue"
```

#### Ubuntu

Edit the gearman config at ```/etc/default/gearman-job-server```, adding the following to the PARAMS line (or creating the line, if it doesn't exist yet), inserting database credentials as necessary:
```sh
PARAMS="-q MySQL --mysql-host=localhost --mysql-port=3306 --mysql-user=<user> --mysql-password=<password> --mysql-db=gearman --mysql-table=gearman_queue"
```

## Verification

Once everything is installed, you can quickly make sure gearman is accepting jobs with the ```test-client.php``` and ```test-worker.php``` files. The worker is configured to reverse any text passed to it. In the client file, we pass "Hello World" to the worker.

In one window, run ```php test-worker.php``` - You'll now have one worker process running, waiting for jobs.

In another window, run ```php test-client.php "Hello World"``` - You should see "dlroW olleH" printed on your screen.

```ctrl-c``` will stop the worker once you are done testing.

## Usage

Once configured and activated, you'll have access to ```wp_async_task_add()```. If you are at all familiar with ```wp_schedule_single_event()```, the way ```wp_async_task_add()``` works should be very familiar to you.

The function takes up to three arguments, the first of which is required:

1. ```$hook```: This is the name of the action hook to execute when the job runs. Your callback function should hook into this with ```add_action( $hook, $callback )```
1. ```$args```: This is optional, and defaults to an empty array. You can pass an array of arbitrary data to this, and it will be passed to your callback function.
1. ```$priority```: This is optional, and defaults to "normal". Other valid options are "high" or "low". High priority jobs will be run before normal priority jobs, even if they normal priority job has been in the queue longer.

Set an option in the database, when a worker becomes available:

```php
// Add a task, that will call the "myplugin_update_option" action when it is run
wp_async_task_add( 'myplugin_update_option', array( 'mykey' => 'myvalue' ) );

function myplugin_update_option_callback( $args ) {
	// In reality, you are probably doing a lot of resource intensive work here
	update_option( 'my-option-name', $args['mykey'] );
}

// Add the action that links the task and the callback.
// Notice the hook below is the same as the hook provided to wp_async_task_add.
add_action( 'myplugin_update_option', 'myplugin_update_option_callback' );

```
Once a worker is free, and runs the above task, you'd have an option called "my-option-name" in the options table, with a value of "myvalue", since "myvalue" was passed in via the ```$args```

## Customization

The following constants can be used to customize the behaviour of WP Gears.

1. `WP_GEARS_JOBS_PER_WORKER` - The number of jobs to execute per Worker,
   default is 1. Running multiple jobs per worker will reduce the number
   workers spawned, and can significantly boost performance. However too
   large a value will cause issues if you have memory leaks. Use with
   caution.

2. `WP_GEARS_CLIENT_CLASS` - You can also alter the Client class used to
   send jobs to Gearman. It should match the interface of
   `\WpGears\Client`.

3. `WP_GEARS_WORKER_CLASS` - Similarly you can alter the Worker class used
   to execute jobs. It should match the interface of `\WpGears\Worker`.

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/10up/WP-Gears/issues). We're excited to see what the community thinks of this project, and we would love your input!

## License

WP Gears is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
