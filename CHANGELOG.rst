============================
Nextcloud JMAP Release Notes
============================

.. contents:: Topics

v1.4.0
======

Release summary
---------------
nextcloud-jmap has a new name and supports mail address login now

Details
-------
* Change name to nextcloud-jmap
* Remove some dead code
* Fix typo in Calendar name key (thanks Lennart S!)
* Support mail address as login
* Create default address book and calendar if missing

v1.3.2
======

Release summary
---------------
Update dependencies

Details
-------
* Update iCalendar/vCard to 0.5.0 and JMAP OpenXPort to 1.7.2 .

v1.3.1
======

Release summary
---------------
Update dependencies

Details
-------
* Update iCalendar/vCard to 0.4.0 and JMAP OpenXPort to 1.7.1 .

v1.3.0
=======

Release summary
---------------
JMAP Calendar and Contacts support for Nextcloud!

Details
-------
* Use audriga's iCalendar/vCard library #5561, #6088
* Fix app compatibility #6141
* Rudimentary admin auth support #5120
* Disable debug capability by default #6221
* Contacts: Support writing contacts #6120
* Calendars: Support reading and writing #6088
