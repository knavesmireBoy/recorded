<ul>
    <?php 

foreach($group as $file){

    $id = $file->id;
    $href = "/tvlist/episode/$id";
    $del = "/tvlist/delete/$id";
    ?>
    <li><a href="<?= $href; ?>"><?= $file->title; ?></a> <a href="<?= $del; ?>">delete</a></li>
    <?php
    }
    ?>
    </ul>
