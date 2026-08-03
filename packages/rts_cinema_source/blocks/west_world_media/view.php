<?php defined('C5_EXECUTE') or die('Access Denied.');

$c = \Concrete\Core\Page\Page::getCurrentPage();
if ($c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item" style="width:100%; height:100px;">
        <div style="padding:8px 0"><?php echo t('Legacy block — listing cache and checkout load automatically from other cinema blocks.'); ?></div>
    </div>
<?php }

View::element('cinema_listing_footer', [
    'errorMessage' => $errorMessage ?? null,
    'includeCheckoutModal' => $includeCheckoutModal ?? false,
], 'rts_cinema_source');
