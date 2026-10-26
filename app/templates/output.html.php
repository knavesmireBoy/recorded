<?php
include_once TEMPLATE . '/pagehead.html.php';

$connect = isset($klas) && $klas = 'connect' ? true : false;

?>
<body id="admin">
<h1><a href=".">Polo</a></h1>
<h2><?= date('l F j, Y'); ?></h2>
<?php if ($connect) { ?>
<?php } ?>
    <p>
        <?= $error; ?>
</p>
    <p><a href=".">Back</a></p>
</body>
</html>