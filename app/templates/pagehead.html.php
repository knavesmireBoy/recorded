<!doctype html>
<html class="no-js">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <base href="/recorded/app">
  <link href="<?= CSS . 'main.css' ?>" media="all" rel="stylesheet">
  <link href="<?= CSS . 'print.css' ?>" media="print" rel="stylesheet" />
  <title><?= ucwords($TITLE); ?></title>
  <script>
    if(screen && screen.width){
      document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
    }
  </script>
</head>