<?php 
defined('C5_EXECUTE') or die("Access Denied.");
?>
<div class="movie-listing">
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="listing-options">
                <select name="listing-date" id="listing-date" class="form-control"></select>
            </div>
        </div>
    </div>
    <div class="listing-output">
    </div>
</div>
<?php
View::element('cinema_listing_footer', [
    'errorMessage' => $errorMessage ?? null,
    'includeCheckoutModal' => $includeCheckoutModal ?? false,
], 'rts_cinema_source');
?>