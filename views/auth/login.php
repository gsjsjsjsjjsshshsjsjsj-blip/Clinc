<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">تسجيل الدخول</h2>
        <form method="post" action="/auth/login">
          <?php echo App\Core\Csrf::field(); ?>
          <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary w-100">دخول</button>
        </form>
      </div>
    </div>
  </div>
</div>
