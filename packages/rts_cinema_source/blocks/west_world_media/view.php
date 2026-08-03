<?php 
defined('C5_EXECUTE') or die("Access Denied.");
$c = Page::getCurrentPage();
if ($c->isEditMode()) { ?>
	<div class="ccm-edit-mode-disabled-item" style="width:100%; height:100px; ">
		<div style="padding:8px 0px"><?php echo t('RTS & West World Media feed block')?></div>
	</div>
<?php  } ?>
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Buy Movie Tickets</h4>
      </div>
      <div class="modal-body">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary">Continue</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
