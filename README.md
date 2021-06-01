# Nextcloud JMAP
The JMAP App for Nextcloud provides [JMAP](https://jmap.io/) support for Nextcloud systems by exposing a RESTful-like API Endpoint which speaks the JMAP Protocol.

Currently the JMAP App for Nextcloud only offers support for the following:

* Contacts over the JMAP for Contacts protocol
* Calendars over the JMAP for Calendars protocol, built on top of the [JSCalendar](https://tools.ietf.org/html/draft-ietf-calext-jscalendar-32) format

## 🏗 Installation

1. ☁ Clone this app into the `apps` folder of your Nextcloud: `git clone https://github.com/audriga/nextcloud-jmap jmap` (Make sure the folder is named `jmap`)
2. 👩‍💻 In the folder of the app, run the command `make` to install dependencies and build the Javascript.
3. ✅ Enable the app through the app management of your Nextcloud
4. 🎉 Partytime! Help fix [some issues](https://github.com/audriga/nextcloud-jmap/issues) and [review pull requests](https://github.com/audriga/nextcloud-jmap/pulls) 👍

## Usage

Setup your favorite client to talk to Nextclouds JMAP API.

## Development

For debugging purposes it makes sense to throw some curl calls at the API. For example, this is how you tell the JMAP API to return all CalendarEvents:
```
curl -H 'Authorization: Basic <base64-encoded username:password>' http://<path-to-nextcloud>/index.php/apps/jmap/jmap \
  -H 'Content-Type: application/json' -d '{"using":["urn:ietf:params:jmap:calendars"],"methodCalls":[["CalendarEvent/get",{},"0"]]}'
```
