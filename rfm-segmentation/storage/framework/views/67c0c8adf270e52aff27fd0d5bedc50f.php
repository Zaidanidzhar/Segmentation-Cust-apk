<?php if (isset($component)) { $__componentOriginaledc75c655a063d12a477f2c8d8f324fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaledc75c655a063d12a477f2c8d8f324fc = $attributes; } ?>
<?php $component = App\View\Components\Layouts\AdminLayout::resolve(['title' => 'Upload Dataset'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Layouts\AdminLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<div class="mb-5"><div class="app-label">RFM Engine / Data Preparation</div><h1 class="text-[22px] font700 mt-1">Upload Dataset</h1><p class="text-[11px] text-[#858b88] mt-1.5">Unggah hasil ekspor Shopee Seller Center untuk memulai proses RFM dan segmentasi pelanggan.</p></div>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
<div class="lg:col-span-3 app-card p-5">
 <div class="flex justify-between items-start"><div><div class="app-label">Dataset Transaksi</div><h2 class="text-[14px] font700 mt-1">Import data pelanggan</h2></div><span class="segment-badge segment-default">CSV / XLSX / TXT</span></div>
 <form action="<?php echo e(route('admin.upload.store')); ?>" method="POST" enctype="multipart/form-data" class="mt-5"><?php echo csrf_field(); ?>
  <label for="dataset" class="block border border-dashed border-[#cbd1ce] rounded-md bg-[#fafbfa] p-9 text-center cursor-pointer hover:bg-[#f5f7f5]">
   <div class="mx-auto w-10 h-10 rounded-md bg-[#eef0ef] flex items-center justify-center text-[#4f5955] text-lg">↑</div>
   <div class="mt-3 text-[12px] font700">Pilih file dataset</div><div class="text-[9px] text-[#858b88] mt-1">CSV, XLSX, atau TXT · maksimal 10 MB</div>
   <div id="fileName" class="mt-2 text-[10px] font-semibold"></div>
   <input id="dataset" name="dataset" type="file" accept=".csv,.txt,.xlsx" class="hidden" onchange="document.getElementById('fileName').textContent=this.files[0]?.name||''">
  </label>
  <?php $__errorArgs = ['dataset'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-[10px] text-[#ad7373]"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  <button class="app-btn w-full mt-4" type="submit">Unggah Dataset</button>
 </form>
 <form action="<?php echo e(route('admin.reset-data')); ?>" method="POST" class="mt-3" onsubmit="return confirm('Reset semua data transaksi, customer, dan segmentasi? Data yang dihapus tidak dapat dikembalikan.')">
  <?php echo csrf_field(); ?>
  <button class="w-full h-9 rounded border border-[#e1caca] bg-[#fffafa] text-[10px] font-semibold text-[#a06464] hover:bg-[#fdf0f0]" type="submit">Reset Semua Data</button>
 </form>
 <div class="mt-4 pt-4 border-t text-[9px] leading-5 text-[#858b88]">Kolom digunakan: <b>No. Pesanan</b>, <b>Status Pesanan</b>, <b>Waktu Pesanan Dibuat</b>, <b>Total Pembayaran</b>, dan <b>Username (Pembeli)</b>.</div>
</div>
<div class="lg:col-span-2 app-card p-5"><div class="app-label">Pipeline Data</div><h2 class="text-[14px] font700 mt-1">Alur Analisis</h2><p class="text-[9px] text-[#858b88] mt-1">Tahapan mengikuti rancangan penelitian.</p>
<div class="mt-5 space-y-2"><?php $__currentLoopData = ['Validasi & pembersihan data','Agregasi transaksi per pelanggan','Pembentukan Recency, Frequency, Monetary','Transformasi log1p + StandardScaler','Prediksi K-Means dengan model K=4']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i=>$step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="app-card-soft p-3 flex items-center gap-3"><span class="w-6 h-6 rounded bg-[#eef0ef] flex items-center justify-center text-[9px] font700 text-[#53605a]"><?php echo e(str_pad($i+1,2,'0',STR_PAD_LEFT)); ?></span><span class="text-[10px] font500"><?php echo e($step); ?></span></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
<a href="<?php echo e(route('admin.process.index')); ?>" class="app-btn-secondary w-full mt-4">Buka Proses Segmentasi</a>
</div></div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4"><?php $__currentLoopData = [['Input','Transaksi Shopee','Data transaksi mentah hasil ekspor Seller Center.'],['Preprocessing','RFM + StandardScaler','Monetary menggunakan transformasi log1p sebelum standardisasi.'],['Model','K-Means K=4','Assignment pelanggan menggunakan jarak Euclidean.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $x): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="app-card p-4"><div class="app-label"><?php echo e($x[0]); ?></div><div class="text-[11px] font700 mt-2"><?php echo e($x[1]); ?></div><p class="text-[9px] text-[#858b88] mt-1"><?php echo e($x[2]); ?></p></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
<?php if(isset($success)): ?><div class="app-alert success mt-4"><?php echo e($success); ?></div><?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaledc75c655a063d12a477f2c8d8f324fc)): ?>
<?php $attributes = $__attributesOriginaledc75c655a063d12a477f2c8d8f324fc; ?>
<?php unset($__attributesOriginaledc75c655a063d12a477f2c8d8f324fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaledc75c655a063d12a477f2c8d8f324fc)): ?>
<?php $component = $__componentOriginaledc75c655a063d12a477f2c8d8f324fc; ?>
<?php unset($__componentOriginaledc75c655a063d12a477f2c8d8f324fc); ?>
<?php endif; ?><?php /**PATH C:\Users\Zaidanidz\Documents\apk skripsi\rfm-segmentation\resources\views/admin/upload.blade.php ENDPATH**/ ?>