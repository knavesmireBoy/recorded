<h3>Recordings</h3>
<ul class="<?= $klas; ?>">
    <?php 

foreach($files as $file){
    $series = $file->series_id;
    $t = $file->title;
    $href = $series ? "/tvlist/toggle/$series" : '/tvlist/display';
    $tog = intval($_COOKIE['tog'] ?? null);
    $myid = $file->id;
    $del = "/tvlist/delete/$myid";
    $klas = $file->series_id ? 'series' : '';
    ?>
    <li class="<?= $klas; ?>">
    <?php if($series) : ?>
    <a href="<?= $href; ?>"><?= $t; ?></a>
    <?php else : ?>
        <span><?= $t; ?></span>
<?php
endif;
    include '_feedback.html.php';
    include 'series.html.php';
} ?>
</ul>
<?php
    include 'search.html.php';
    include '_paginate.html.php';