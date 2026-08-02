<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
$form = app('helper/form');
?>

<div class="form-group">
    <?php echo $form->label('cinemaSourceApiKey', t('Cinema Source API Key')); ?>
    <?php echo $form->text('cinemaSourceApiKey', $cinemaSourceApiKey ?? ''); ?>
</div>

<div class="form-group">
    <?php echo $form->label('cinemaSourceApiVersion', t('Cinema Source API Version')); ?>
    <?php echo $form->text('cinemaSourceApiVersion', $cinemaSourceApiVersion ?? '4.0'); ?>
    <div class="help-block"><?php echo t('Use the API version assigned by Cinema Source / Webedia (typically 4.0).'); ?></div>
</div>

<div class="form-group">
    <?php echo $form->label('cinemaSourceHouseId', t('Cinema Source House ID')); ?>
    <?php echo $form->text('cinemaSourceHouseId', $cinemaSourceHouseId ?? ''); ?>
</div>

<hr>

<div class="form-group">
    <?php echo $form->label('rtsHost', t('RTS Host')); ?>
    <?php echo $form->text('rtsHost', $rtsHost ?? ''); ?>
    <div class="help-block"><?php echo t('Production host, e.g. 72352.formovietickets.com'); ?></div>
</div>

<div class="form-group">
    <?php echo $form->label('rtsUsername', t('RTS Username')); ?>
    <?php echo $form->text('rtsUsername', $rtsUsername ?? ''); ?>
</div>

<div class="form-group">
    <?php echo $form->label('rtsPassword', t('RTS Password')); ?>
    <?php echo $form->password('rtsPassword', $rtsPassword ?? ''); ?>
</div>

<div class="form-group">
    <label>
        <?php echo $form->checkbox('rtsUseSandbox', 1, !empty($rtsUseSandbox)); ?>
        <?php echo t('Use RTS sandbox (5.formovietickets.com)'); ?>
    </label>
</div>

<hr>

<div class="form-group">
    <?php echo $form->label('processCompleteUrl', t('Payment Complete URL')); ?>
    <?php echo $form->url('processCompleteUrl', $processCompleteUrl ?? ''); ?>
    <div class="help-block"><?php echo t('Full URL to /rts/procComp.php on your site.'); ?></div>
</div>

<div class="form-group">
    <?php echo $form->label('returnUrl', t('Checkout Return URL')); ?>
    <?php echo $form->url('returnUrl', $returnUrl ?? ''); ?>
</div>

<p class="help-block"><?php echo t('Leave fields blank to use values from application/config/cinema_source.php'); ?></p>
