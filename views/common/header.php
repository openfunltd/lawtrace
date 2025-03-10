<?php
if ($this->title ?? false) {
    $this->title = "{$this->title} | LawTrace 立法歷程查詢";
} else {
    $this->title = "LawTrace 立法歷程查詢";
}

?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $this->escape($this->title ?? $this->app_name) ?></title>
  <meta name="description" content="<?= $this->escape($this->description ?? '') ?>">
  <meta name="og:type" content="website">
  <meta name="og:title" content="<?= $this->escape($this->og_title ?? $this->title ?? '') ?>">
  <meta name="og:description" content="<?= $this->escape($this->og_description ?? $this->description ?? '') ?>">
  <meta name="og:site_name" content="">
  <meta name="og:image" content="<?= $this->escape($this->og_image ?? '/static/images/logo_b.svg') ?>">
  <link rel="shortcut icon" href="/static/images/lawtrace_favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/static/css/common.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="/static/js/common.js"></script>
</head>
<body class="<?= $this->body_class ?? '' ?>">
  <div class="wrapper">
    <nav class="lt-navbar">
      <a class="logo" href="/">
        <img src="/static/images/logo_b.svg" alt="LawTrace">
        立法歷程查詢
      </a>
      <a href="/about" class="lt-nav-items">
        關於我們
      </a>
    </nav>
