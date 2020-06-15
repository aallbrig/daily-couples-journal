<!doctype html>
<html lang="en">
  <head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $_ENV["GOOGLE_ANALYTICS_ID"]; ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', '<?php echo $_ENV["GOOGLE_ANALYTICS_ID"]; ?>');
    </script>

    <link rel="manifest" href="site.webmanifest">
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <!-- CSS from other bootstrap pages -->
    <link rel="stylesheet" href="https://getbootstrap.com/docs/4.5/examples/product/product.css" crossorigin="anonymous">

    <title><?php
        if (!empty($title)) {
            echo $title;
        }
    ?></title>
  </head>
  <body>
