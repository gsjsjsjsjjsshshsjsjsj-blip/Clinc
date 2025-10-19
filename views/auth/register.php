<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">إنشاء حساب</h2>
        <form method="post" action="/auth/register">
          <?php echo App\Core\Csrf::field(); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">الاسم الكامل</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">البريد الإلكتروني</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">كلمة المرور</label>
              <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="col-md-6">
              <label class="form-label">الدور</label>
              <select name="role" class="form-select" required>
                <option value="patient">مريض</option>
                <option value="doctor">طبيب</option>
                <option value="admin">مدير</option>
              </select>
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary w-100">إنشاء الحساب</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
