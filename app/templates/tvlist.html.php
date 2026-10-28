<?php 
  
foreach($files as $file){
    $series = $file->series_id;
    $href = $series ? "/tvlist/toggle/$series" : '';
    $tog = intval($_COOKIE['tog'] ?? null);
    $myid = $file->id;
    $del = "/tvlist/delete/$myid";
    ?>
    <p><a href="<?= $href; ?>"><?= $file->title; ?></a> <a href="<?= $del; ?>">delete</a></p>
    <?php  
        if($tog === $series){
            include 'series.html.php';
        }
}
    

