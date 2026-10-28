<ul>
    <?php 

foreach($group as $file){

    $id = $file->id;
    $del = "/tvlist/delete/$id";
    ?>
    <li><a href="<?= $del; ?>"><?= $file->title; ?></a></li>
    <?php
    }
    ?>
    </ul>
