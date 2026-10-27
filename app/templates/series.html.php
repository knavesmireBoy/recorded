<ul>
    <?php 

foreach($group as $file){

    $id = $file->id;
    $href = "/tvlist/episode/$id";
    ?>
    <li><a href="<?= $href; ?>"><?= $file->title; ?></a></li>
    <?php
    }
    ?>
    </ul>
