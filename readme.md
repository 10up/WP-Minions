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

1. Upload wp-gears-runner.php to the root of the WordPress install.

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

= Gearman Backend - CentOS =

// todo need to put the instructions here, yo.
