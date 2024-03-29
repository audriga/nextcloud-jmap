<?php

return array(
    /**
     * Default Parameters
     *
     * These parameters are required for your JMAP server to operate.
     * Do not edit this file. Copy + paste it to config/config.php and then edit instead.
     */

    // ********************** //
    /// General configuration
    // ********************** //

    // Admin groups for admin auth / impersonation.
    //   These groups will be able to impersonate users they are allowed to administrate.
    //   In case you want admin auth, you probably want to change this to at least:
    //     'adminGroups' => array('admin'),
    'adminGroups' => array(''),

    // Enabled capabilities for this endpoint
    'capabilities' => array('jscontact', 'calendars'),

    // ********************** //
    /// Logging configuration
    // ********************** //
    // NOTE: Only a single logger will be used

    // Allow FileLogger (also as fallback in case no other is working)
    'allowFileLog' => false,

    // Allow Graylog logger (in case gelf-php included under vendor/)
    'allowGraylog' => false,

    // PSR 3 minimum log level
    'logLevel' => \Psr\Log\LogLevel::WARNING,

    // FileLogger's path to log file relative to this dir
    'fileLogPath' => __DIR__ . '/../log.log',

    // Graylog endpoint to use
    'graylogEndpoint' => '',

    // Graylog Port to use
    'graylogPort' => 12201,

    // Allow self-signed certs
    'graylogAllowSelfSigned' => false,

    // Use TLS
    'graylogUseTls' => true,
);
