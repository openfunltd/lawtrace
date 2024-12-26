<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $this->escape($this->_data['title'] ?? $this->app_name) ?></title>
  <meta name=description content="">
  <meta name="og:type" content="website">
  <meta name="og:title" content="">
  <meta name="og:description" content="">
  <meta name="og:site_name" content="">
  <meta name="og:image" content="">
  <link rel="shortcut icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/static/css/common.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="/static/js/common.js"></script>
</head>
<body>
  <div class="wrapper">
    <nav class="lt-navbar">
      <a class="logo" href="/">
        LawTrace
      </a>
      
      <div class="dropdown">
        <a href="#" class="lt-nav-items" data-bs-toggle="dropdown">
          關於
          <i class="fa fa-chevron-down dropdown-icon"></i>
        </a>
        <ul class="dropdown-menu">
          <li>
            <a class="dropdown-item" href="#">
              Menu Item 1
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#">
              Menu Item 2
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item" href="#">
              Menu Item 3
            </a>
          </li>
        </ul>
      </div>
    </nav>
