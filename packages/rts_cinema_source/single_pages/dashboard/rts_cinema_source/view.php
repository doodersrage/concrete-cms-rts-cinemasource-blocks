<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
$form = app('helper/form');
?>

<form method="post" action="<?php echo $view->action('save'); ?>">
    <?php echo $form->hidden('ccm_token', app('token')->generate('save_rts_cinema_source')); ?>

    <div class="card mb-4">
        <div class="card-header"><?php echo t('Cinema Source API'); ?></div>
        <div class="card-body">
            <div class="mb-3">
                <?php echo $form->label('cinema_source_base_url', t('Base URL')); ?>
                <?php echo $form->text('cinema_source_base_url', $cinemaSource['base_url'] ?? ''); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('cinema_source_api_version', t('API Version')); ?>
                <?php echo $form->text('cinema_source_api_version', $cinemaSource['api_version'] ?? '4.0'); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('cinema_source_api_key', t('API Key')); ?>
                <?php echo $form->text('cinema_source_api_key', $cinemaSource['api_key'] ?? ''); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('cinema_source_house_id', t('House ID')); ?>
                <?php echo $form->text('cinema_source_house_id', $cinemaSource['house_id'] ?? ''); ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo t('RTS POS'); ?></div>
        <div class="card-body">
            <div class="mb-3">
                <?php echo $form->label('rts_host', t('Host')); ?>
                <?php echo $form->text('rts_host', $rts['host'] ?? ''); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_port', t('Port')); ?>
                <?php echo $form->number('rts_port', $rts['port'] ?? 2235); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_username', t('Username')); ?>
                <?php echo $form->text('rts_username', $rts['username'] ?? ''); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_password', t('Password')); ?>
                <?php echo $form->password('rts_password', $rts['password'] ?? ''); ?>
            </div>
            <div class="mb-3">
                <label>
                    <?php echo $form->checkbox('rts_use_sandbox', 1, !empty($rts['use_sandbox'])); ?>
                    <?php echo t('Use RTS sandbox'); ?>
                </label>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_sandbox_host', t('Sandbox Host')); ?>
                <?php echo $form->text('rts_sandbox_host', $rts['sandbox_host'] ?? '5.formovietickets.com'); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_sandbox_username', t('Sandbox Username')); ?>
                <?php echo $form->text('rts_sandbox_username', $rts['sandbox_username'] ?? 'test'); ?>
            </div>
            <div class="mb-3">
                <?php echo $form->label('rts_sandbox_password', t('Sandbox Password')); ?>
                <?php echo $form->password('rts_sandbox_password', $rts['sandbox_password'] ?? 'test'); ?>
            </div>
            <div class="mb-3">
                <label>
                    <?php echo $form->checkbox('rts_verify_ssl', 1, !empty($rts['verify_ssl'])); ?>
                    <?php echo t('Verify RTS SSL certificates'); ?>
                </label>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo t('Checkout URLs'); ?></div>
        <div class="card-body">
            <div class="mb-3">
                <?php echo $form->label('site_process_complete_url', t('Payment Complete URL')); ?>
                <?php echo $form->url('site_process_complete_url', $site['process_complete_url'] ?? ''); ?>
                <div class="form-text"><?php echo t('Leave blank to use %s', $apiEndpoints['complete']); ?></div>
            </div>
            <div class="mb-3">
                <?php echo $form->label('site_return_url', t('Return URL')); ?>
                <?php echo $form->url('site_return_url', $site['return_url'] ?? ''); ?>
                <div class="form-text"><?php echo t('Page users return to after payment, typically with ?paymentRes=1'); ?></div>
            </div>
            <div class="mb-3">
                <?php echo $form->label('site_conv_fee', t('Convenience Fee')); ?>
                <?php echo $form->text('site_conv_fee', $site['conv_fee'] ?? 1.35); ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><?php echo t('Package API Endpoints'); ?></div>
        <div class="card-body">
            <ul class="list-unstyled mb-0">
                <?php foreach ($apiEndpoints as $name => $url) { ?>
                    <li><strong><?php echo h($name); ?>:</strong> <code><?php echo h($url); ?></code></li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?php echo t('Save Settings'); ?></button>
</form>
