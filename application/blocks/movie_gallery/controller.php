<?php

namespace Application\Block\MovieGallery;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btDescription = 'Movie gallery block for use with Cinema Cafe and West World Media movie listings.';
    protected $btName = 'Movie Gallery';
    protected $btInterfaceWidth = '350';
    protected $btInterfaceHeight = '300';

    public function on_start()
    {
        $html = $this->app->make('helper/html');
        $blockPath = $this->getBlockPath();
        $scrollPath = $blockPath . '/js/Smooth-Div-Scroll';

        $this->addHeaderItem($html->css($scrollPath . '/css/smoothDivScroll.css'));
        $this->addFooterItem($html->javascript($scrollPath . '/js/jquery.kinetic.js'));
        $this->addFooterItem($html->javascript($scrollPath . '/js/jquery.mousewheel.min.js'));
        $this->addFooterItem($html->javascript($scrollPath . '/js/jquery.smoothDivScroll-1.3.js'));
        $this->addFooterItem($html->javascript($blockPath . '/js/script.js'));
    }

    public function view()
    {
    }
}
