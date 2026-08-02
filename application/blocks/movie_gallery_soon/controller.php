<?php

namespace Application\Block\MovieGallerySoon;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btDescription = 'Coming soon movie gallery listing block for use with Cinema Cafe and West World Media movie listings.';
    protected $btName = 'Coming Soon Movie Gallery Listing';
    protected $btInterfaceWidth = '350';
    protected $btInterfaceHeight = '300';

    public function view()
    {
        $html = $this->app->make('helper/html');
        $this->addFooterItem($html->javascript($this->getBlockPath() . '/js/script.js'));
    }
}
