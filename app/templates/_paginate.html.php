<?php

function myvalidate($arr, $tgt)
{
    [$x] = $arr[1] ?? [0];
    return !empty($arr) && $x;
}

if (myvalidate($paginate, $increment)) { ?>
    <ul class="pp">
      <?php
      if (isset($prev)) { ?>
        <li>
          <a href="<?= TV_DISPLAYER . $prev ?>">Previous</a>
        </li>
      <?php }
  
      foreach ($paginate as $p) {
          $txt = $p[0];
          $o = $p[1];
          $klas = $o === $offset ? 'active' : '';
          ?>
        <li class="<?= $klas; ?>">
          <?php
          if ($klas) {  ?>
            <span><?= $txt ?></span>
              <?php
          } else { ?>
            <a href="<?= TV_DISPLAYER . $txt ?>"><?= $txt ?></a>
              <?php
          }
          ?>
        </li>
      <?php }
  }
  if ($next && isset($next)) { ?>
      <li>
        <a href="<?= TV_DISPLAYER . $next ?>">Next</a>
      </li>
  <?php } ?>
    </ul>