<?php if (isset($component)) { $__componentOriginaledc75c655a063d12a477f2c8d8f324fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaledc75c655a063d12a477f2c8d8f324fc = $attributes; } ?>
<?php $component = App\View\Components\Layouts\AdminLayout::resolve(['title' => 'Dashboard'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Layouts\AdminLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php
$m=$clusteringResult ?? [];
$sil=$m['silhouette_score'] ?? null; $dbi=$m['davies_bouldin_index'] ?? null;
$k=$m['k_optimal'] ?? $m['chosen_k'] ?? 4;
$segments=['Champions'=>['class'=>'segment-champions','tone'=>'metric-green'], 'Loyal Customers'=>['class'=>'segment-loyal','tone'=>'metric-blue'], 'At Risk'=>['class'=>'segment-atrisk','tone'=>'metric-amber'], 'Hibernating/Churn'=>['class'=>'segment-hibernating','tone'=>'metric-gray']];
$counts=$m['segment_counts'] ?? [];
$elbowKRange=$m['elbow_k_range'] ?? [];
$elbowInertias=$m['elbow_inertias'] ?? [];
$euclideanByK=$m['euclidean_distance_by_k'] ?? [];
$centroidDistances=$m['centroid_distances'] ?? [];
if (empty($euclideanByK) && empty($centroidDistances) && count($elbowKRange) && count($elbowInertias)) {
 $euclideanByK=array_map(fn($index)=>[
  'k'=>$elbowKRange[$index] ?? null,
  'average_euclidean_distance'=>$elbowInertias[$index] ?? 0,
 ], array_keys($elbowKRange));
}
$elbowMax=max($elbowInertias ?: [1]);
$euclideanValues=array_map(fn($item)=>(float)($item['average_euclidean_distance'] ?? 0), $euclideanByK);
$euclideanMax=max($euclideanValues ?: [1]);
$strategi=$m['strategi'] ?? [];
$plot3dData=$m['plot_3d_data'] ?? [];
$plot3dCentroids=$m['plot_3d_centroids'] ?? [];
?>

<div class="mb-5 flex items-end justify-between gap-4">
 <div><div class="app-label">RFM Engine</div><h1 class="text-[21px] sm:text-[24px] font700 tracking-[-.03em] mt-1">Dashboard Overview</h1><p class="text-[11px] mt-1.5 text-[#858b88]">Real-time analytics from the latest RFM segmentation model.</p></div>
 <a href="<?php echo e(route('admin.upload.index')); ?>" class="app-btn">+ Upload Dataset</a>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Customers</div><span class="metric-icon metric-green">◉</span></div><div class="stat-number mt-3"><?php echo e(number_format($totalCustomers ?? 0)); ?></div><div class="text-[9px] text-[#8b918e] mt-1">customers segmented</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Orders</div><span class="metric-icon metric-blue">▤</span></div><div class="stat-number mt-3"><?php echo e(number_format($totalTransactions ?? 0)); ?></div><div class="text-[9px] text-[#8b918e] mt-1">orders processed</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Avg. RFM Score</div><span class="metric-icon metric-amber">◈</span></div><div class="stat-number mt-3"><?php echo e($sil !== null ? number_format($sil,2) : '—'); ?></div><div class="text-[9px] text-[#8b918e] mt-1">silhouette quality</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Revenue</div><span class="metric-icon metric-gray">Rp</span></div><div class="stat-number mt-3 text-[20px]">Rp <?php echo e(number_format($totalRevenue ?? ($total_revenue ?? 0),0,',','.')); ?></div><div class="text-[9px] text-[#8b918e] mt-1">transaction value</div></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_285px] gap-4 mb-4">
 <section class="app-card p-4">
   <div class="flex items-start justify-between"><div><h2 class="app-title text-[13px]">Customer Growth Trend</h2><p class="text-[9px] text-[#8b918e] mt-1">Visualisasi 3D pelanggan berdasarkan Recency, Frequency, dan Monetary log.</p></div><span class="metric-icon metric-blue">3D</span></div>
   <?php if(count($plot3dData)): ?>
   <div id="cluster3dChart" class="mt-3" style="height:430px"></div>
   <?php else: ?>
   <div id="cluster3dChart" class="empty-chart mt-2"><div class="text-center"><div class="text-[24px] opacity-40">◇</div><div class="mt-2">Data visualisasi 3D belum tersedia</div></div></div>
   <?php endif; ?>
 </section>
 <aside class="space-y-4">
   <div class="app-card p-4">
    <div class="flex justify-between items-center"><div><h2 class="app-title text-[13px]">Recent Analysis</h2><p class="text-[9px] text-[#8b918e] mt-1">Latest model run</p></div><span class="w-2 h-2 rounded-full bg-[#75a58c]"></span></div>
    <div class="mt-4 space-y-2.5">
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Model</span><b>K-Means K=<?php echo e($k); ?></b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Silhouette</span><b><?php echo e($sil!==null?number_format($sil,4):'—'); ?></b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">DB Index</span><b><?php echo e($dbi!==null?number_format($dbi,4):'—'); ?></b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Last sync</span><b><?php echo e($lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d M Y H:i') : '—'); ?></b></div>
    </div>
    <a href="<?php echo e(route('admin.process.index')); ?>" class="app-btn-secondary w-full mt-4">View Full Report</a>
   </div>
   <div class="app-card p-4">
    <h2 class="app-title text-[13px]">Top Segments</h2>
    <div class="mt-4 space-y-3">
    <?php $__currentLoopData = $segments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name=>$meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php $count=$counts[$name]??0; $total=array_sum($counts) ?: 1; $pct=round($count/$total*100); ?>
      <div><div class="flex justify-between text-[9px] mb-1.5"><span><?php echo e(Str::limit($name,18)); ?></span><b><?php echo e($pct); ?>%</b></div><div class="mini-bar"><span style="width:<?php echo e($pct); ?>%"></span></div></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
   </div>
 </aside>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
 <section class="app-card p-4">
  <div class="flex items-start justify-between gap-3">
   <div><h2 class="app-title text-[13px]">Elbow Method</h2><p class="text-[9px] text-[#8b918e] mt-1">WCSS / inertia untuk menentukan jumlah cluster yang optimal.</p></div>
   <span class="metric-icon metric-blue">K</span>
  </div>
  <?php if(count($elbowKRange) && count($elbowInertias)): ?>
  <?php $elbowMin=min($elbowInertias); $elbowRange=max($elbowMax-$elbowMin,1); $elbowPoints=[]; $selectedElbowIndex=array_search((int)$k, array_map('intval', $elbowKRange), true); ?>
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis Elbow Method">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
    <?php if($selectedElbowIndex !== false): ?>
     <?php $selectedX=28+($selectedElbowIndex*377/max(count($elbowKRange)-1,1)); ?>
     <line x1="<?php echo e($selectedX); ?>" y1="15" x2="<?php echo e($selectedX); ?>" y2="125" stroke="#d94b4b" stroke-width="2" stroke-dasharray="6 5"/><text x="<?php echo e($selectedX+5); ?>" y="24" font-size="9" fill="#b43f3f">K optimal = <?php echo e($k); ?></text>
    <?php endif; ?>
    <?php $__currentLoopData = $elbowKRange; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$clusterCount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
     <?php $inertia=(float)($elbowInertias[$index] ?? 0); $x=28+($index*377/max(count($elbowKRange)-1,1)); $y=125-(($inertia-$elbowMin)/$elbowRange*105); $elbowPoints[]=round($x,1).','.round($y,1); ?>
     <text x="<?php echo e($x); ?>" y="145" text-anchor="middle" font-size="9" fill="#8b918e">K=<?php echo e($clusterCount); ?></text><circle cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="4" fill="#789c89"><title>K=<?php echo e($clusterCount); ?>, inertia=<?php echo e(number_format($inertia,1)); ?></title></circle>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <polyline points="<?php echo e(implode(' ',$elbowPoints)); ?>" fill="none" stroke="#789c89" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Inertia lebih rendah</span><span>Dipilih: K=<?php echo e($k); ?></span></div>
  <?php else: ?>
  <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">⌁</div><div class="mt-2">Data Elbow belum tersedia</div></div></div>
  <?php endif; ?>
 </section>

 <section class="app-card p-4">
  <div class="flex items-start justify-between gap-3">
  <div><h2 class="app-title text-[13px]">Euclidean Centroid Distance</h2><p class="text-[9px] text-[#8b918e] mt-1">Jarak Euclidean antar titik centroid setiap segmen.</p></div>
   <span class="metric-icon metric-amber">d</span>
  </div>
  <?php if(count($centroidDistances)): ?>
  <?php $centroidMax=max(array_map(fn($item)=>(float)($item['distance'] ?? 0), $centroidDistances) ?: [1]); $centroidMin=min(array_map(fn($item)=>(float)($item['distance'] ?? 0), $centroidDistances) ?: [0]); $centroidRange=max($centroidMax-$centroidMin,1); $centroidPoints=[]; ?>
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis jarak centroid Euclidean">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
   <?php $__currentLoopData = $centroidDistances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $distance=(float)($item['distance'] ?? 0); $index=$loop->index; $x=28+($index*377/max(count($centroidDistances)-1,1)); $y=125-(($distance-$centroidMin)/$centroidRange*105); $label=($item['segment_i'] ?? 'C1').' / '.($item['segment_j'] ?? 'C2'); $centroidPoints[]=round($x,1).','.round($y,1); ?>
    <text x="<?php echo e($x); ?>" y="145" text-anchor="middle" font-size="8" fill="#8b918e"><?php echo e(Str::limit($label,13,'')); ?></text><circle cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="4" fill="#c39a54"><title><?php echo e($label); ?>, distance=<?php echo e(number_format($distance,2)); ?></title></circle>
   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   <polyline points="<?php echo e(implode(' ',$centroidPoints)); ?>" fill="none" stroke="#c39a54" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Centroid terjauh menunjukkan segmen paling berbeda</span><span>Euclidean</span></div>
  <?php elseif(count($euclideanByK)): ?>
  <?php $distanceMax=max($euclideanValues ?: [1]); $distanceMin=min($euclideanValues ?: [0]); $distanceRange=max($distanceMax-$distanceMin,1); $distancePoints=[]; ?>
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis jarak Euclidean">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
   <?php $__currentLoopData = $euclideanByK; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $distance=(float)($item['average_euclidean_distance'] ?? 0); $pointIndex=$loop->index; $x=28+($pointIndex*377/max(count($euclideanByK)-1,1)); $y=125-(($distance-$distanceMin)/$distanceRange*105); $distancePoints[]=round($x,1).','.round($y,1); ?>
    <text x="<?php echo e($x); ?>" y="145" text-anchor="middle" font-size="9" fill="#8b918e">K=<?php echo e($item['k'] ?? '—'); ?></text><circle cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="4" fill="#c39a54"><title>K=<?php echo e($item['k'] ?? '—'); ?>, distance=<?php echo e(number_format($distance,2)); ?></title></circle>
   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   <polyline points="<?php echo e(implode(' ',$distancePoints)); ?>" fill="none" stroke="#c39a54" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Jarak rata-rata ke centroid per K</span><span>Euclidean</span></div>
  <?php else: ?>
  <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">⌁</div><div class="mt-2">Data Euclidean belum tersedia</div></div></div>
  <?php endif; ?>
 </section>
</div>

<section class="app-card p-4 mb-4">
 <div class="flex items-start justify-between gap-3">
  <div><h2 class="app-title text-[13px]">Strategi Segmentasi</h2><p class="text-[9px] text-[#8b918e] mt-1">Rekomendasi tindakan berdasarkan hasil segmentasi RFM.</p></div>
  <span class="metric-icon metric-green">↗</span>
 </div>
 <?php if(count($strategi)): ?>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
  <?php $__currentLoopData = $strategi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $strategy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="app-card-soft p-3">
   <div class="flex items-center justify-between gap-2"><span class="text-[10px] font-bold"><?php echo e($strategy['segment'] ?? 'Segment'); ?></span><span class="text-[9px] text-[#6f8f7e]">RFM action</span></div>
   <p class="text-[10px] font-semibold mt-2"><?php echo e($strategy['focus'] ?? ''); ?></p>
   <p class="text-[9px] text-[#8b918e] mt-1.5 leading-relaxed"><?php echo e($strategy['action'] ?? ''); ?></p>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </div>
 <?php else: ?>
 <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">↗</div><div class="mt-2">Strategi belum tersedia</div></div></div>
 <?php endif; ?>
</section>

<div class="app-card p-4">
 <div class="flex items-center justify-between gap-3"><div><h2 class="app-title text-[13px]">Top 10 Customers — Champions</h2><p class="text-[9px] text-[#8b918e] mt-1">Highest-value customers based on RFM profile.</p></div><a href="<?php echo e(route('landing')); ?>#customer-table" target="_blank" class="app-btn-secondary">Public Dashboard ↗</a></div>
 <div class="mt-4 overflow-x-auto"><table class="w-full app-table"><thead><tr><th>#</th><th>Customer</th><th>Recency</th><th>Frequency</th><th>Monetary</th></tr></thead><tbody>
 <?php $__empty_1 = true; $__currentLoopData = ($m['topChampions'] ?? $topChampions ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i=>$c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <tr><td class="text-[#8b918e]"><?php echo e($i+1); ?></td><td class="font-semibold"><?php echo e($c->name ?? $c->customer_code); ?></td><td><?php echo e($c->recency ?? '—'); ?> days</td><td><?php echo e($c->frequency ?? '—'); ?> orders</td><td>Rp <?php echo e(number_format($c->monetary??0,0,',','.')); ?></td></tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5" class="text-[#8b918e]">Belum ada hasil segmentasi.</td></tr><?php endif; ?>
 </tbody></table></div>
</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaledc75c655a063d12a477f2c8d8f324fc)): ?>
<?php $attributes = $__attributesOriginaledc75c655a063d12a477f2c8d8f324fc; ?>
<?php unset($__attributesOriginaledc75c655a063d12a477f2c8d8f324fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaledc75c655a063d12a477f2c8d8f324fc)): ?>
<?php $component = $__componentOriginaledc75c655a063d12a477f2c8d8f324fc; ?>
<?php unset($__componentOriginaledc75c655a063d12a477f2c8d8f324fc); ?>
<?php endif; ?>

<?php if(count($plot3dData)): ?>
<script src="https://cdn.plot.ly/plotly-2.35.2.min.js"></script>
<script>
const cluster3dPoints = <?php echo json_encode($plot3dData, 15, 512) ?>;
const cluster3dCentroids = <?php echo json_encode($plot3dCentroids, 15, 512) ?>;
const segmentColors = {'Champions':'#5fce94','Loyal Customers':'#5aa7df','At Risk':'#f0b34f','Hibernating/Churn':'#a978e8','Unassigned':'#8b918e'};
const pointTraces = Object.entries(cluster3dPoints.reduce((groups, point) => {
  (groups[point.cluster] ||= []).push(point);
  return groups;
}, {})).map(([segment, points]) => ({
  x: points.map(point => point.x), y: points.map(point => point.y), z: points.map(point => point.z),
  text: points.map(point => point.name), name: segment, type: 'scatter3d', mode: 'markers',
  marker: {size: 4, color: segmentColors[segment] || '#8b918e', opacity: .78}
}));
pointTraces.push({
  x: cluster3dCentroids.map(point => point.x), y: cluster3dCentroids.map(point => point.y), z: cluster3dCentroids.map(point => point.z),
  text: cluster3dCentroids.map(point => point.cluster), name: 'Centroid', type: 'scatter3d', mode: 'markers+text',
  textposition: 'top center', marker: {size: 10, color: '#111827', symbol: 'diamond'}
});
Plotly.newPlot('cluster3dChart', pointTraces, {
  margin: {l: 0, r: 0, t: 8, b: 0}, paper_bgcolor: 'transparent', plot_bgcolor: 'transparent',
  legend: {orientation: 'h', y: 1.08}, scene: {
    xaxis: {title: 'Recency (hari)'}, yaxis: {title: 'Frequency (kali)'}, zaxis: {title: 'Monetary log'},
    aspectmode: 'auto'
  }, font: {family: 'DM Sans, sans-serif', size: 10, color: '#4d5551'}
}, {responsive: true, displaylogo: false});
</script>
<?php endif; ?><?php /**PATH C:\Users\Zaidanidz\Documents\apk skripsi\rfm-segmentation\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>