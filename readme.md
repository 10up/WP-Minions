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

`// Make sure gearman works with multisite, when invoked directly with php-cli

if ( ! isset( $_SERVER['HTTP_HOST'] ) && defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
	$_SERVER['HTTP_HOST'] = DOMAIN_CURRENT_SITE;
}`

= Gearman Backend - CentOS =

// todo need to put the instructions here, yo.
// These instructions are not quite complete. Plan on finishing them on Monday!
// todo probably don't need gearman* on ubuntu - revisit and figure out which are actually needed. Does gearman* install the pecl part already??

1. yum install gearmand php-pecl-gearman python-pip supervisor

1. pip install supervisor --pre

1. Need to copy supervisor conf to /etc/init.d/supervisord

= Ubuntu =

1. apt-get install gearman* python-pip

1. pip install supervisor --pre

1. pecl install gearman