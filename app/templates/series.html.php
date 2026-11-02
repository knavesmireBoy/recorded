
    <?php 
if($tog === $series){ ?>
<ol>
    <?php 
foreach($options as $file){
    $id = $file->id;
    $del = "/tvlist/delete/$id";
    ?>
    <li><a href="<?= $del; ?>"><?= $file->title; ?></a></li>
    <?php
    } ?>
    </ol>
<?php }
    ?>
   
