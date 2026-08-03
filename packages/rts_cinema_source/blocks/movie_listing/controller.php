<?php

namespace Concrete\Package\RtsCinemaSource\Block\MovieListing;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btDescription = 'Movie listing block for use with Cinema Cafe and West World Media movie listings.';
    protected $btName = 'Movie Listing';
    protected $btInterfaceWidth = '350';
    protected $btInterfaceHeight = '300';

    public function view()
    {
        $html = $this->app->make('helper/html');
        $this->addFooterItem($html->javascript($this->getBlockPath() . '/js/script.js'));
    }
}
