<?php
use App\Core\Config;
?><!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(Config::get('app.name', 'Medical Appointments')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
      <div class="container">
        <a class="navbar-brand" href="/"><?= htmlspecialchars(Config::get('app.name', 'نظام المواعيد')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="/login">تسجيل الدخول</a></li>
            <li class="nav-item"><a class="nav-link" href="/register">إنشاء حساب</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <main class="container">
