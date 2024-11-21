<?= $this->partial('common/header', ['title' => 'Lawtrace 首頁']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="col-6 p-4">
      <p class="display-5">
      你想暸解什麼樣的法規沿革或法案討論現狀？
      </p>
    </div>
    <div class="col-6 bg-white rounded-4">
      <form action="/search" method="get">
        <div class="p-5">
          <div class="input-group">
            <span class="input-group-text material-symbols-outlined">search</span>
            <input type="text" class="form-control" placeholder="請輸入關鍵字" name="q" required>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
