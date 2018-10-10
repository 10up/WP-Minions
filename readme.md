WP Minions [![Build Status](https://travis-ci.org/10up/WP-Minions.svg?branch=master)](https://travis-ci.org/10up/WP-Minions)
========

Provides a framework for using job queues with [WordPress](http://wordpress.org/) for asynchronous task running.
Provides an integration with [Gearman](http://gearman.org/) and [RabbitMQ](https://www.rabbitmq.com) out of the box.

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

## Background & Purpose

As WordPress becomes a more popular publishing platform for increasingly large publishers, with complex workflows, the need for increasingly complex and resource-intensive tasks has only increased. Things like generating reports, expensive API calls, syncing users to mail providers, or even ingesting content from feeds all take a lot of time or a lot of memory (or both), and commonly can't finish within common limitations of web servers, because things like timeouts and memory limits get in the way.

WP Minions provides a few helper functions that allow you to add tasks to a queue, and specify an action that should be called to trigger the task, just hook a callback into the action using ```add_action()```

During configuration, a number of minions (workers) are specified. As minions are free, they will take the next task from the queue, call the action, and any callbacks hooked into the action will be run.

In the situation of needing more ram or higher timeouts, a separate server to process the tasks is ideal - Just set up WordPress on that server like the standard web servers, and up the resources. Make sure not to send any production traffic to the server, and it will exclusively handle tasks from the queue.

## Installation

1. Install the plugin in WordPress. If desired, you can [download a zip](http://github.com/10up/WP-Minions/archive/master.zip) and install via the WordPress plugin installer.

2. Create a symlink at the site root (the same directory as ```wp-settings.php```) that points to the ```wp-minions-runner.php``` file in the plugin (or copy the file, but a symlink will ensure it is updated if the plugin is updated)

3. Define a unique salt in `wp-config.php` so that multiple installs don't conflict.
```php
define( 'WP_ASYNC_TASK_SALT', 'my-unique-salt-1' );
```

**Note:** If you are using multisite, you'll also have to add the following to your ```wp-config.php``` file, _after_ the block with the multisite definitions. This is due to the fact that multisite relies on ```HTTP_HOST``` to detect the site/blog it is running under. You'll also want to make sure you are actually defining ```DOMAIN_CURRENT_SITE``` in the multisite configuration.
```php
if ( ! isset( $_SERVER['HTTP_HOST'] ) && defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
  $_SERVER['HTTP_HOST'] = DOMAIN_CURRENT_SITE;
}
```

4. Next, you'll need to choose your job queue system. Gearman and RabbitMQ are supported out of the box.

### Gearman

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

#### Backend Setup - CentOS 7.x

1. You'll need the [EPEL](https://fedoraproject.org/wiki/EPEL) repo for gearman, and the [REMI](http://rpms.famillecollet.com/) repo for some of the php packages. 
     - ```yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm```
    - ```yum install http://rpms.famillecollet.com/enterprise/remi-release-7.rpm```
1. ```yum install gearmand php-pecl-gearman --enablerepo=remi-php<php version on your system>```. For example, if you are using php 7.2 your command would look like this ```yum install gearmand php-pecl-gearman --enablerepo=remi-php72```
1. Optionally, install supervisord if you prefer it
    - ```yum install python-pip```
    - ```easy_install supervisor```
1. If everything is running on one server, I would recommend limiting connections to localhost only. If not, you'll want to set up firewall rules to only allow certain clients to connect on the Gearman port (Default 4730)
    - edit ```/etc/sysconfig/gearmand``` - set ```OPTIONS="--listen=localhost"```
1. ```systemctl enable gearmand```
1. ```systemctl start gearmand```

#### Backend Setup - Ubuntu

As you go through this, you may need to install additional packages, if you do not have them already, such as php-pear or a php*-dev package

1. ```apt-get update```
1. ```apt-get install gearman python-pip libgearman-dev supervisor```
1. ```pecl install gearman```
1. Once pecl install is complete, it will tell you to place something like ```extension=gearman.so``` into your php.ini file - Do this.
1. ```update-rc.d gearman-job-server defaults && update-rc.d supervisor defaults```
1. If everything is running on one server, I would recommend limiting connections to localhost only. If not, you'll want to set up firewall rules to only allow certain clients to connect on the Gearman port (Default 4730)
  - edit ```/etc/default/gearman-job-server``` - set ```PARAMS="--listen=localhost"```
1. ```service gearman-job-server restart```

#### Supervisor Configuration

Filling in values in ```<brackets>``` as required, add the following config to either ```/etc/supervisord.conf``` (CentOS) or ```/etc/supervisor/supervisord.conf``` (Ubuntu)

```sh
[program:my_wp_minions_workers]
command=/usr/bin/env php <path_to_wordpress>/wp-minions-runner.php
process_name=%(program_name)s-%(process_num)02d
numprocs=<number_of_minions>
directory=<path_to_temp_directory>
autostart=true
autorestart=true
killasgroup=true
user=<user>
```

* path_to_wordpress: Absolute path to the root of your WordPress install, ex: ```/var/www/html/wordpress```
* number_of_minions: How many minions should be spawned (How many jobs can be running at once).
* path_to_temp_directory: probably should just be the same as path_to_wordpress.
* user: The system user to run the processes under, probably apache (CentOS), nginx (CentOS), or www-data (Ubuntu).
* You can optionally change the "my_wp_minions_workers" text to something more descriptive, if you'd like.

After updating the supervisor configuration, restart the service (CentOS or Ubuntu)

```
systemctl restart supervisord
```
```
service supervisor restart
```

#### Systemd Configuration (CentOS 7.x)

Filling in values in ```<brackets>``` as required, add the following to /etc/systemd/system/wp-minions-runner@.service

```
[Unit]
Description=WP-Minions Runner %i
After=network.target

[Service]
PIDFile=/var/run/wp-minions-runner.%i.pid
User=<user>
Type=simple
ExecStart=/usr/bin/env php <path_to_wordpress>/wp-minions-runner.php
Restart=always

[Install]
WantedBy=multi-user.target
```
* path_to_wordpress: Absolute path to the root of your WordPress install, ex: ```/var/www/html/wordpress```
* user: The system user to run the processes under, probably apache (CentOS), nginx (CentOS), or www-data (Ubuntu).

Reload systemd:

```systemctl daemon-reload```

Enable and start as many runners as you'd like to have running:

```
systemctl enable wp-minions-runner@{1..n}
systemctl start wp-minions-runner@{1..n}
```

Where 'n' is the number of processes you want. 

#### WordPress Configuration

Define the `WP_MINIONS_BACKEND` constant in your ```wp-config.php```.  Valid values are `gearman` or `rabbitmq`.  If left blank, it will default to a cron client.
```
define( 'WP_MINIONS_BACKEND', 'gearman' );
```

If your job queue service not running locally or uses a non-standard port, you'll need define your servers in ```wp-config.php```

```:php
# Gearman config
global $gearman_servers;
$gearman_servers = array(
  '127.0.0.1:4730',
);
```

```:php
# RabbitMQ config
global $rabbitmq_server;
$rabbitmq_server = array(
  'host'     => '127.0.0.1' ),
  'port'     => 5672,
  'username' => 'guest',
  'password' => 'guest',
);

Note: On RabbitMQ the guest/guest account is the default administrator account, RabbitMQ will only allow connections connections on that account from localhost. Connections to any non-loopback address will be denied. See the RabbitMQ manual on [user management](https://www.rabbitmq.com/rabbitmqctl.8.html#User_Management) and [Access Control](https://www.rabbitmq.com/rabbitmqctl.8.html#Access_Control) for information on adding users and allowing them access to RabbitMQ resources.

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

Note: For some setups, the above will not work as ```/etc/default/gearman-job-server``` does not get read.  If you don't see the persistent queue setup then:

1. Create a `gearman` db in mysql (the database must be present in the database, but when gearmand is initialized the first time it will create the table).
2. Create a file in `/etc/gearmand.conf`
3. In the file paste the configuration all on one line:

```sh
-q MySQL --mysql-host=localhost --mysql-port=3306 --mysql-user=<user> --mysql-password=<password> --mysql-db=gearman --mysql-table=gearman_queue
```

Then restart the gearman-job-server: ```sudo service gearman-job-server restart```.


## Verification

Once everything is installed, you can quickly make sure your job queue is accepting jobs with the ```test-client.php``` and ```test-worker.php``` files located in the `system-tests/YOURQUEUE` directory. The worker is configured to reverse any text passed to it. In the client file, we pass "Hello World" to the worker.

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

The following constants can be used to customize the behaviour of WP Minions.

1. `WP_MINIONS_JOBS_PER_WORKER` - The number of jobs to execute per Worker,
   default is 1. Running multiple jobs per worker will reduce the number
   workers spawned, and can significantly boost performance. However too
   large a value will cause issues if you have memory leaks. Use with
   caution.

2. `WP_MINIONS_CLIENT_CLASS` - You can also alter the Client class used to
   send jobs to your job queue. It should match the interface of
   `\WpMinions\Client`.

3. `WP_MINIONS_WORKER_CLASS` - Similarly you can alter the Worker class used
   to execute jobs. It should match the interface of `\WpMinions\Worker`.

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/10up/WP-Minions/issues). We're excited to see what the community thinks of this project, and we would love your input!

## License

WP Minions is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
