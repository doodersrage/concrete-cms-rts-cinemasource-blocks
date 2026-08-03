<?php defined('C5_EXECUTE') or die('Access Denied.');

if (!empty($errorMessage)) { ?>
    <div class="alert alert-warning"><?php echo h($errorMessage); ?></div>
<?php }

if (!empty($includeCheckoutModal)) {
    View::element('checkout_modal', null, 'rts_cinema_source');
}
