<?php

require __DIR__ . '/bootstrap.php';

$redirectUrl = $_GET['RedirectUrl'] ?? '';
if ($redirectUrl === '' || !preg_match('#^https://#i', $redirectUrl)) {
    http_response_code(400);
    echo 'Invalid redirect URL';
    exit;
}

?>
<form action="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" name="frm">
<?php
foreach ($_GET as $key => $value) {
    if ($key === 'RedirectUrl') {
        continue;
    }
    echo '<input type="hidden" name="' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
}
?>
</form>
<script>
document.frm.submit();
</script>
