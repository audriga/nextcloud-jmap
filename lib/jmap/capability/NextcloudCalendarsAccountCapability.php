<?php

namespace OCA\JMAP\JMAP\Capability;

use OpenXPort\Jmap\Calendar\CalendarsAccountCapability;

// Upstream stores options under getProperties() instead of getCapabilities(), so they never reach the session response.
class NextcloudCalendarsAccountCapability extends CalendarsAccountCapability
{
    public function __construct()
    {
        parent::__construct();

        $options = $this->getProperties();
        $options['maxDateTime'] = "2100-12-31T23:59:59";

        $this->capabilities = $options;
    }
}
