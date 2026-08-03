<?php

namespace RtsCinemaSource\Block;

use RtsCinemaSource\Service\ListingBootstrap;

trait BootstrapsCinemaListing
{
    protected function bootstrapCinemaListing(): void
    {
        $error = $this->app->make(ListingBootstrap::class)->register($this);

        if ($error) {
            $this->set('errorMessage', $error);
        }
    }
}
