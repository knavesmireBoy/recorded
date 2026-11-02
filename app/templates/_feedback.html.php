<?php 

$t = $titles[$file->id] ?? '';
if(empty($options) && $t) { ?>
        <em>found on Page <?= $titles[$file->id]; ?></em></li>
        <?php } else { ?>
    <a href="<?= $del; ?>">edit</a></li>
    <?php }

if(!empty($options) && $t) { ?>
<h6>an episode of <?= $t; ?></h6>
<?php  }
    