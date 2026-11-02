<?php
include_once 'funcs.php';

if (isset($key)) {
  include '_key.html.php';
}
$id = $file->id ?? 0;
$edit = "/tvlist/edit/$id";

if (isset($perform)) {
  if (isset($message)) { ?>
    <h5><?= $message; ?></h5>
<?php }
    if ($perform === 'delete') { ?>
    <div class="affirm">
      <p>Are you sure you want to <strong>delete</strong> this record?</p>
      <p>You may <strong><a href="<?= TV_EDIT . $id ?>">UPDATE</a></strong> this record</p>
      <p>Or you can <strong><a href="<?= TV_ADD?>">ADD</a></strong> a record</p>
    </div>
    <?php }
    ?>
  <form class="delete" action="<?= $action ?>" method="post">
    <input type="submit" value="cancel" name="cancel">
    <input type="submit" value="submit" name="submit">
    <input id="pk" type="hidden" name="pk" value="<?= $id ?>">
  </form>
<?php } ?>
