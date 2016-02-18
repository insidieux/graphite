<?php

namespace Graphite\App;

/**
 * Class Events
 * @package Graphite\App
 */
class Events
{
    /**
     * Event called at the beginning application run()
     */
    const APP_INIT  = 'app:init';

    /**
     * Event called after application route
     */
    const APP_ROUTE = 'app:route';

    /**
     * Event called if dispatched controller->method return not response object
     */
    const APP_RESOLVE_RESPONSE = 'app:resolveResponse';

    /**
     * Event called before send response to client
     */
    const APP_BEFORE_RESPONSE = 'app:beforeResponse';
}
