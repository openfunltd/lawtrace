<div class="lt-footer">
  <div class="container">
      footer
      <?php if ($_GET['debug'] ?? false) { ?>
      <ul>
          <?php foreach (LYAPI::getLogs() as $log) { ?>
          <li><?= $this->escape(implode(':', $log)) ?></li>
          <?php } ?>
      </ul>
      <?php } ?>
  </div>
</div>
</div>
</body>
</html>
