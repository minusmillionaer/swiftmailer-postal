<?php

namespace NETZFABRIK\Mail\Manager;

use NETZFABRIK\Mail\Transport\PostalTransport;

class PostalTransportManager extends TransportManager
{
    /**
     * Create an instance of the Postal Swift Transport driver.
     *
     * @return \NETZFABRIK\Mail\Transport\PostalTransport
     */
    protected function createPostalDriver()
    {
        return new PostalTransport(
            $this->app['config']->get('services.postal.endpoint'),
            $this->app['config']->get('services.postal.key')
        );
    }

}