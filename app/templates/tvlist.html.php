<?php 
  
foreach($files as $file){
    $id = $file->series_id;
    $href = $id ? "/tvlist/toggle/$id" : "";
    ?>
    <p><a href="<?= $href; ?>"><?= $file->title; ?></a></p>
    <?php
    if(isset($subfiles) && $tog && $tog === $id){
        $group = $subfiles;
        include TEMPLATE . 'series.html.php';
    }   
}
