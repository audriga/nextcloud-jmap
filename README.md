# Nextcloud JMAP

> ⚠️  This version is still in its early stages. This API exposes user data. It is not recommended to expose this API to untrusted networks for now. Please consider contacting us before using this in production.

The JMAP App for [Nextcloud](https://nextcloud.com) provides [JMAP](https://jmap.io/) support for Nextcloud systems by exposing a RESTful API Endpoint which speaks the JMAP Protocol.

Please note that this version is still in its early stages.

The following data types are currently supported by the JMAP Plugin for Nextcloud:

* Contacts over the JMAP for Contacts protocol ([RFC 9610](https://datatracker.ietf.org/doc/html/rfc9610))
* Calendars over the JMAP for Calendars protocol ([draft-ietf-jmap-calendars-22](https://datatracker.ietf.org/doc/html/draft-ietf-jmap-calendars-22)), built on top of the JSCalendar ([RFC 8984](https://datatracker.ietf.org/doc/html/rfc8984)) format

## 🏗 Installation

1. ☁ Clone this app into the `apps` folder of your Nextcloud: `git clone https://github.com/audriga/jmap-nextcloud jmap` (Make sure the folder is named `jmap`).
2. 👩‍💻 In the folder of the app, run the command `make` to install dependencies and build the Javascript.
3. ✅ Enable the app through the app management of your Nextcloud
4. 🎉 Partytime! Help fix [some issues](https://github.com/audriga/nextcloud-jmap/issues) and [send us some pull requests](https://github.com/audriga/nextcloud-jmap/pulls) 👍

## Usage

Set up your favorite client to talk to Nextcloud's JMAP API.

## Development

For debugging purposes it makes sense to throw some cURL calls at the API. For example, this is how you tell the JMAP API to return all `CalendarEvent`s:

```
curl -u username:password <nextcloud-address>/index.php/apps/jmap/jmap -d '{"using":["urn:ietf:params:jmap:calendars"],"methodCalls":[["CalendarEvent/get",{"accountId":"<username>"},"0"]]}'
```

### Tested against

PHP: 8.1 and 8.2

Nextcloud: between 24 and 29
