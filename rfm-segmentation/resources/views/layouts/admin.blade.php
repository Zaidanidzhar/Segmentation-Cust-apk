<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Dashboard' }} · Segmentasi Pelanggan</title>
@vite(['resources/css/app.css','resources/js/app.js'])
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{--ink:#1f2422;--muted:#7b817e;--line:#e6e9e7;--page:#f6f7f6;--card:#fff;--accent:#5b5f61;--accent-soft:#eef0ef;--nav:#252a2c;--nav-2:#303537;--nav-text:#c7ccca;--green:#5f8d78;--green-soft:#edf5f1;--blue:#6f8da5;--blue-soft:#eef3f7;--amber:#b49460;--amber-soft:#f7f1e6;--red:#ad7373;--red-soft:#f8eeee}
*{box-sizing:border-box}html{font-size:14px}body{margin:0;background:var(--page);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}a{text-decoration:none;color:inherit}.app-shell{min-height:100vh}.app-sidebar{background:var(--nav);color:var(--nav-text);width:230px}.app-sidebar .brand{border-bottom:1px solid #3a3f41}.app-sidebar .nav-link{display:flex;align-items:center;gap:10px;border-radius:6px;padding:10px 12px;font-size:12px;color:#aeb5b2}.app-sidebar .nav-link:hover{background:var(--nav-2);color:#fff}.app-sidebar .nav-link.active{background:#3a4042;color:#fff;font-weight:600}.app-sidebar .nav-icon{width:16px;text-align:center;opacity:.85}.app-topbar{height:58px;background:#fff;border-bottom:1px solid var(--line)}.app-main{max-width:1260px;margin:auto}.app-card{background:#fff;border:1px solid var(--line);border-radius:6px;box-shadow:0 1px 4px rgba(30,35,32,.025)}.app-card-soft{background:#fafbfa;border:1px solid var(--line);border-radius:6px}.app-label{font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#858b88}.app-title{font-weight:650;letter-spacing:-.01em}.app-input{width:100%;height:38px;border:1px solid #dfe3e1;border-radius:5px;background:#fff;padding:0 11px;font-size:12px;outline:none}.app-input:focus{border-color:#9aa09d;box-shadow:0 0 0 2px #f0f2f1}.app-btn,.app-btn-secondary{display:inline-flex;align-items:center;justify-content:center;height:36px;border-radius:5px;padding:0 14px;font-size:11px;font-weight:650}.app-btn{background:#303537;color:#fff}.app-btn:hover{background:#202426}.app-btn-secondary{border:1px solid #dfe3e1;background:#fff;color:#4d5551}.app-table{border-collapse:collapse;font-size:11px}.app-table th{text-align:left;font-size:9px;letter-spacing:.04em;text-transform:uppercase;color:#8b918e;font-weight:700;padding:9px 10px;border-bottom:1px solid var(--line)}.app-table td{padding:10px;border-bottom:1px solid #eef0ef}.app-table tr:last-child td{border-bottom:0}.segment-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:9px;font-weight:700}.segment-champions{background:#e9f3ee;color:#4d7965}.segment-loyal{background:#edf3f7;color:#58758c}.segment-atrisk{background:#f7f0e5;color:#947848}.segment-hibernating{background:#f2eeee;color:#906b6b}.segment-default{background:#eff1f0;color:#626966}.metric-icon{width:28px;height:28px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:12px}.metric-green{background:var(--green-soft);color:var(--green)}.metric-blue{background:var(--blue-soft);color:var(--blue)}.metric-amber{background:var(--amber-soft);color:var(--amber)}.metric-gray{background:#eff1f0;color:#6f7673}.mini-bar{height:5px;border-radius:99px;background:#edf0ee;overflow:hidden}.mini-bar>span{display:block;height:100%;border-radius:99px;background:#789c89}.empty-chart{height:255px;display:flex;align-items:center;justify-content:center;color:#a0a6a3;font-size:11px}.app-alert{border:1px solid var(--line);border-radius:6px;padding:11px 13px;font-size:11px}.app-alert.success{background:var(--green-soft);color:#50745f;border-color:#d4e5dc}.app-alert.error{background:var(--red-soft);color:#925e5e;border-color:#ecd5d5}.stat-number{font-size:24px;line-height:1.1;font-weight:700;letter-spacing:-.03em}.sidebar-footer{border-top:1px solid #3a3f41}.mobile-nav{background:#fff;border-bottom:1px solid var(--line)}@media(max-width:1023px){.app-main{max-width:none}.app-sidebar{display:none}.desktop-only{display:none!important}}@media(min-width:1024px){.mobile-only{display:none!important}}
</style>
<style>
@media (min-width:768px) {
  .app-sidebar.desktop-only { display:flex !important; }
  .desktop-only { display:block !important; }
  .mobile-only { display:none !important; }
  .mobile-nav { display:none !important; }
  .lg\:ml-\[230px\] { margin-left:230px !important; }
}
</style>
</head>
<body>
<div class="app-shell flex">
<aside class="app-sidebar desktop-only flex flex-col shrink-0 min-h-screen fixed inset-y-0 left-0">
  <div class="brand px-5 py-4">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
      <span class="w-7 h-7 rounded flex items-center justify-center bg-white text-[#252a2c] text-[11px] font800">V</span>
      <span><div class="text-[12px] font700 text-white">Visscotton</div><div class="text-[9px] mt-0.5 text-[#89918e]">Customer intelligence</div></span>
    </a>
  </div>
  <nav class="px-3 py-4 flex-1">
    <div class="px-3 pb-2 text-[8px] uppercase tracking-[.16em] text-[#717977] font-bold">Main menu</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="nav-icon">▦</span>Dashboard</a>
    <a href="{{ route('admin.upload.index') }}" class="nav-link {{ request()->routeIs('admin.upload.*') ? 'active' : '' }}"><span class="nav-icon">↑</span>Upload Data</a>
    <a href="{{ route('admin.process.index') }}" class="nav-link {{ request()->routeIs('admin.process.*') ? 'active' : '' }}"><span class="nav-icon">◌</span>Proses Segmentasi</a>
    <div class="px-3 pt-6 pb-2 text-[8px] uppercase tracking-[.16em] text-[#717977] font-bold">Account</div>
    @auth
    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"><span class="nav-icon">○</span>Profil</a>
    @endauth
  </nav>
  <div class="sidebar-footer p-4">
    <div class="flex items-center gap-2.5">
      <div class="w-7 h-7 rounded-full bg-[#454b4d] text-white flex items-center justify-center text-[10px] font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</div>
      <div class="min-w-0"><div class="text-[10px] font-semibold text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</div><div class="text-[9px] text-[#858d8a] truncate">{{ auth()->user()->email ?? '' }}</div></div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="text-[10px] text-[#9da4a1] hover:text-white">Keluar</button></form>
  </div>
</aside>
<div class="flex-1 min-w-0 lg:ml-[230px]">
<header class="app-topbar sticky top-0 z-20 flex items-center justify-between px-4 sm:px-6">
  <div class="mobile-only text-[12px] font700">Segmentasi Pelanggan</div><div class="desktop-only text-[10px] text-[#8b918e]">Admin Panel / {{ $title ?? 'Dashboard' }}</div>
  <div class="flex items-center gap-4 ml-auto"><span class="text-[10px] text-[#8b918e] hidden sm:block">RFM Analytics</span><a href="{{ route('landing') }}" target="_blank" class="app-btn">Dashboard Publik ↗</a></div>
</header>
<div class="mobile-only mobile-nav px-3 py-2 flex gap-1 overflow-x-auto">
<a href="{{ route('admin.dashboard') }}" class="nav-link !text-[10px] { request()->routeIs('admin.dashboard')?'!bg-[#eef0ef] !text-[#303537]':'' }">Dashboard</a>
<a href="{{ route('admin.upload.index') }}" class="nav-link !text-[10px] { request()->routeIs('admin.upload.*')?'!bg-[#eef0ef] !text-[#303537]':'' }">Upload</a>
<a href="{{ route('admin.process.index') }}" class="nav-link !text-[10px] { request()->routeIs('admin.process.*')?'!bg-[#eef0ef] !text-[#303537]':'' }">Proses</a>
</div>
<main class="app-main px-4 sm:px-6 lg:px-7 py-6">
@if(session('success'))<div class="app-alert success mb-4">{{ session('success') }}</div>@endif
@if(session('error'))<div class="app-alert error mb-4">{{ session('error') }}</div>@endif
{{ $slot ?? '' }}
@yield('content')
</main>
@stack('scripts')
</div></div></body></html>