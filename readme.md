=== Plugin Name ===
Contributors: cmmarslender
Tags: gearman, async
Requires at least: 4/0.0
Tested up to: 4.0
Stable tag: 1.0

Provides methods to schedule async tasks using gearman

== Description ==

blah blah blah. Should probably come up with a better description.

== Installation ==

1. Upload wp-gears-runner.php to the root of the WordPress install (or create a symlink).

1. Get the gearman backend working. See below.

1. Add this line the top of `wp-config.php` to activate WP Gears:

`define('WP_GEARS', true);`

1. Define your gearman servers in `wp-config.php` if not using default server (127.0.0.1:4730)

`global $gearman_servers;

$gearman_servers = array(
	'127.0.0.1:4730',
);`

1. Define a unique salt in `wp-config.php` so that multiple sites on the same server don't conflict.

`define( 'WP_ASYNC_TASK_SALT', 'my-unique-salt' );`

1. If running the workers with php-cli AND using multisite, you'll have to add the following to your wp-config.php file, after
   the block with the multisite definitions (to make sure that DOMAIN_CURRENT_SITE is set). Multisite relies on HTTP_HOST
   being set in order detect the initial site/blog

`if ( ! isset( $_SERVER['HTTP_HOST'] ) && defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
	$_SERVER['HTTP_HOST'] = DOMAIN_CURRENT_SITE;
}`


// todo need to put the instructions here, yo.
// These instructions are not quite complete. Plan on finishing them on Monday!
// todo probably don't need gearman* on ubuntu - revisit and figure out which are actually needed. Does gearman* install the pecl part already??



= Gearman Backend - CentOS =

1. yum install gearmand php-pecl-gearman python-pip supervisor

1. pip install supervisor --pre (older versions of pip won't use `--pre` - that's fine)

1. Need to copy supervisor conf to /etc/init.d/supervisord

1. `chkconfig supervisord on && chkconfig gearmand on`



= Gearman Backend - Ubuntu =

1. apt-get install gearman* python-pip

1. pip install supervisor --pre

1. pecl install gearman

1. update-rc.d gearman-job-server defaults && update-rc.d supervisor defaults



= Configuring Supervisor =

Supervisor is used to make sure that we always have worker processes running, and is responsible for restarting each worker after a job completes.

Add the following to the supervisor config file (either `/etc/supervisord.conf` or `/etc/supervisor/supervisord.conf`). If you have a `/etc/supervisor/conf.d` directory, you can also create a new config file there.

`
[program:my_wp_gears_workers]
command=/usr/bin/php <path_to_wordpress>/wp-gears-runner.php
process_name=%(program_name)s-%(process_num)02d
numprocs=<number_of_workers>
directory=<path_to_temp_directory>
autostart=true
autorestart=true
killasgroup=true
user=<user>
`

* You can change "my_wp_gears_workers" to whatever you want (after "program:") above
* Ensure that the path to php for the "command" is correct, and fill in the path to the root of the wordpress install
* numprocs can be changed to the number of workers you want to have running at once
* directory should be changed to a temp working directory, that is writable by the user
* user should be updated to the user you want your workers to run as (probably the same as your webserver user)



= MySQL Persistent Job Queue - Ubuntu =

Edit the gearman default config to add persistent storage options `/etc/default/gearman-job-server`

Add the following, replacing values as applicable:

`PARAMS="--listen=localhost -q MySQL --mysql-host=localhost --mysql-port=3306 --mysql-user=<user> --mysql-password=<password> --mysql-db=gearman --mysql-table=gearman_queue"`



= MySQL Persistent Job Queue - CentOS =

Edit the gearman default config to add persistent storage options `/etc/sysconfig/gearmand`

Add the following, replacing values as applicable:

`OPTIONS="--listen=localhost -q MySQL --mysql-host=localhost --mysql-port=3306 --mysql-user=<user> --mysql-password=<password> --mysql-db=gearman --mysql-table=gearman_queue"`



== Other Cool Things ==

[Gearman UI](http://gaspaio.github.com/gearmanui) is nice for viewing current queue/worker status. Doesn't give a TON of detail, but you get an overview of jobs names, how many are queued, and how many available workers for each job there are.