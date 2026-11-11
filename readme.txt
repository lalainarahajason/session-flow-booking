=== Session Flow Bookings ===
Contributors: lalainarahajason
Tags: bookings, training
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Infrastructure for training sessions: installation, protected administration and durable background processing.

== Description ==
This first increment provides the technical foundation and protected administration.
Booking, checkout and payment workflows are planned for later increments.

= Privacy and external services =
This increment does not call external services or transmit visitor data.
Technical audit records contain identifiers and actions, without secrets or copied personal profiles.

= Data retention =
Deactivation preserves data. Uninstall preserves data by default.
An administrator can explicitly enable permanent deletion under Settings before uninstalling.
WordPress users and unrelated data are never deleted.

== Installation ==
1. Upload the complete session-flow-bookings directory, including runtime Composer autoload files.
2. Activate on a single WordPress site with PHP 8.3 or newer.
3. Open Session Flow Bookings in the administration menu.

== Frequently Asked Questions ==
= Are network activation and payments available? =
Network activation is refused. Payments are not included in this increment.

== Changelog ==
= 0.1.0 =
* Initial infrastructure increment under development.
