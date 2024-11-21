<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <p class="display-6">LawTrace 進階搜尋</p>
      <form action="/search" method="get">
        <div class="input-group">
          <span class="input-group-text material-symbols-outlined">search</span>
          <input type="text" class="form-control" placeholder="請輸入關鍵字" name="q" required>
        </div>
      </form>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
