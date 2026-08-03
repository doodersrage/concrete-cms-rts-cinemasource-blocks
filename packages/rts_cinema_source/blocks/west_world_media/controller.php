<?php

namespace Concrete\Package\RtsCinemaSource\Block\WestWorldMedia;

use Concrete\Core\Block\BlockController;
use RtsCinemaSource\Block\BootstrapsCinemaListing;

/**
 * @deprecated Listing cache and checkout are bootstrapped automatically by the other cinema blocks.
 */
class Controller extends BlockController
{
    use BootstrapsCinemaListing;

    protected $btDescription = 'Deprecated. Listing cache and checkout load automatically with other cinema blocks.';
    protected $btName = 'West World Media (Legacy)';
    protected $btInterfaceWidth = '600';
    protected $btInterfaceHeight = '200';

    public function getBlockTypeDescription()
    {
        return t('Deprecated. Other cinema blocks now load listing data and checkout automatically.');
    }

    public function view()
    {
        $this->bootstrapCinemaListing();
    }
}
