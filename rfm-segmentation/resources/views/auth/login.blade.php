@extends('layouts.guest')
@section('content')
<div class="auth-wrap">
 <section class="auth-visual"><div><div class="flex items-center gap-3"><div class="w-9 h-9 rounded bg-white text-[#272c2e] flex items-center justify-center font700">V</div><div><div class="text-[13px] font700">Visscotton</div><div class="text-[9px] text-[#929a97]">Customer intelligence</div></div></div>
 <div class="mt-24 max-w-md"><div class="text-[10px] uppercase tracking-[.18em] text-[#8f9995] font700">RFM Analytics Platform</div><h1 class="text-[31px] leading-tight font700 mt-3">Understand your customers.<br>Grow with insight.</h1><p class="text-[11px] leading-6 text-[#aeb5b2] mt-4">Kelola dataset, jalankan segmentasi RFM, dan pantau kualitas model K-Means dalam satu dashboard.</p></div></div>
 <div class="text-[9px] text-[#747d79]">Customer Segmentation · {{date('Y')}}</div></section>
 <section class="auth-panel"><div class="auth-box"><div class="mb-5"><div class="text-[9px] uppercase tracking-[.16em] font700 text-[#858b88]">Admin Access</div><h2 class="text-[22px] font700 mt-1">Masuk ke dashboard</h2><p class="text-[10px] text-[#858b88] mt-1">Gunakan akun administrator untuk melanjutkan.</p></div>
 <div class="auth-card p-6">
 @if(session('status'))<div class="auth-alert mb-4">{{session('status')}}</div>@endif
 <form method="POST" action="{{route('login')}}" class="space-y-4">@csrf
 <div><label for="email" class="auth-label">Email / Username</label><input id="email" name="email" type="text" required autofocus autocomplete="username" value="{{old('email')}}" class="auth-input mt-2" placeholder="admin@example.com">@error('email')<p class="text-[10px] text-[#a56868] mt-1">{{$message}}</p>@enderror</div>
 <div><div class="flex justify-between"><label for="password" class="auth-label">Password</label>@if(Route::has('password.request'))<a class="auth-link" href="{{route('password.request')}}">Lupa password?</a>@endif</div><input id="password" name="password" type="password" required autocomplete="current-password" class="auth-input mt-2" placeholder="••••••••">@error('password')<p class="text-[10px] text-[#a56868] mt-1">{{$message}}</p>@enderror</div>
 <label class="flex items-center gap-2 text-[10px] text-[#7d8581]"><input type="checkbox" name="remember" class="rounded border-gray-300"> Tetap masuk di perangkat ini</label>
 <button class="auth-btn" type="submit">Masuk ke Dashboard</button>
 </form></div>
 <div class="text-center mt-4"><a href="{{route('landing')}}" class="auth-link">← Kembali ke dashboard publik</a></div>
 </div></section>
</div>
@endsection