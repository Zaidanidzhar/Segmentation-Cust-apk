<x-layouts.admin-layout title="Dashboard">
@php
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
@endphp

<div class="mb-5 flex items-end justify-between gap-4">
 <div><div class="app-label">RFM Engine</div><h1 class="text-[21px] sm:text-[24px] font700 tracking-[-.03em] mt-1">Dashboard Overview</h1><p class="text-[11px] mt-1.5 text-[#858b88]">Real-time analytics from the latest RFM segmentation model.</p></div>
 <a href="{{route('admin.upload.index')}}" class="app-btn">+ Upload Dataset</a>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Customers</div><span class="metric-icon metric-green">◉</span></div><div class="stat-number mt-3">{{number_format($totalCustomers ?? 0)}}</div><div class="text-[9px] text-[#8b918e] mt-1">customers segmented</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Orders</div><span class="metric-icon metric-blue">▤</span></div><div class="stat-number mt-3">{{number_format($totalTransactions ?? 0)}}</div><div class="text-[9px] text-[#8b918e] mt-1">orders processed</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Avg. RFM Score</div><span class="metric-icon metric-amber">◈</span></div><div class="stat-number mt-3">{{ $sil !== null ? number_format($sil,2) : '—' }}</div><div class="text-[9px] text-[#8b918e] mt-1">silhouette quality</div></div>
 <div class="app-card p-4"><div class="flex justify-between"><div class="app-label">Total Revenue</div><span class="metric-icon metric-gray">Rp</span></div><div class="stat-number mt-3 text-[20px]">Rp {{number_format($totalRevenue ?? ($total_revenue ?? 0),0,',','.')}}</div><div class="text-[9px] text-[#8b918e] mt-1">transaction value</div></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_285px] gap-4 mb-4">
 <section class="app-card p-4">
   <div class="flex items-start justify-between"><div><h2 class="app-title text-[13px]">Customer Growth Trend</h2><p class="text-[9px] text-[#8b918e] mt-1">Visualisasi 3D pelanggan berdasarkan Recency, Frequency, dan Monetary log.</p></div><span class="metric-icon metric-blue">3D</span></div>
   @if(count($plot3dData))
   <div id="cluster3dChart" class="mt-3" style="height:430px"></div>
   @else
   <div id="cluster3dChart" class="empty-chart mt-2"><div class="text-center"><div class="text-[24px] opacity-40">◇</div><div class="mt-2">Data visualisasi 3D belum tersedia</div></div></div>
   @endif
 </section>
 <aside class="space-y-4">
   <div class="app-card p-4">
    <div class="flex justify-between items-center"><div><h2 class="app-title text-[13px]">Recent Analysis</h2><p class="text-[9px] text-[#8b918e] mt-1">Latest model run</p></div><span class="w-2 h-2 rounded-full bg-[#75a58c]"></span></div>
    <div class="mt-4 space-y-2.5">
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Model</span><b>K-Means K={{$k}}</b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Silhouette</span><b>{{ $sil!==null?number_format($sil,4):'—' }}</b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">DB Index</span><b>{{ $dbi!==null?number_format($dbi,4):'—' }}</b></div>
      <div class="flex justify-between text-[10px]"><span class="text-[#8b918e]">Last sync</span><b>{{ $lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d M Y H:i') : '—' }}</b></div>
    </div>
    <a href="{{route('admin.process.index')}}" class="app-btn-secondary w-full mt-4">View Full Report</a>
   </div>
   <div class="app-card p-4">
    <h2 class="app-title text-[13px]">Top Segments</h2>
    <div class="mt-4 space-y-3">
    @foreach($segments as $name=>$meta)
      @php $count=$counts[$name]??0; $total=array_sum($counts) ?: 1; $pct=round($count/$total*100); @endphp
      <div><div class="flex justify-between text-[9px] mb-1.5"><span>{{Str::limit($name,18)}}</span><b>{{$pct}}%</b></div><div class="mini-bar"><span style="width:{{$pct}}%"></span></div></div>
    @endforeach
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
  @if(count($elbowKRange) && count($elbowInertias))
  @php $elbowMin=min($elbowInertias); $elbowRange=max($elbowMax-$elbowMin,1); $elbowPoints=[]; $selectedElbowIndex=array_search((int)$k, array_map('intval', $elbowKRange), true); @endphp
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis Elbow Method">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
    @if($selectedElbowIndex !== false)
     @php $selectedX=28+($selectedElbowIndex*377/max(count($elbowKRange)-1,1)); @endphp
     <line x1="{{$selectedX}}" y1="15" x2="{{$selectedX}}" y2="125" stroke="#d94b4b" stroke-width="2" stroke-dasharray="6 5"/><text x="{{$selectedX+5}}" y="24" font-size="9" fill="#b43f3f">K optimal = {{$k}}</text>
    @endif
    @foreach($elbowKRange as $index=>$clusterCount)
     @php $inertia=(float)($elbowInertias[$index] ?? 0); $x=28+($index*377/max(count($elbowKRange)-1,1)); $y=125-(($inertia-$elbowMin)/$elbowRange*105); $elbowPoints[]=round($x,1).','.round($y,1); @endphp
     <text x="{{$x}}" y="145" text-anchor="middle" font-size="9" fill="#8b918e">K={{$clusterCount}}</text><circle cx="{{$x}}" cy="{{$y}}" r="4" fill="#789c89"><title>K={{$clusterCount}}, inertia={{number_format($inertia,1)}}</title></circle>
    @endforeach
    <polyline points="{{implode(' ',$elbowPoints)}}" fill="none" stroke="#789c89" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Inertia lebih rendah</span><span>Dipilih: K={{$k}}</span></div>
  @else
  <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">⌁</div><div class="mt-2">Data Elbow belum tersedia</div></div></div>
  @endif
 </section>

 <section class="app-card p-4">
  <div class="flex items-start justify-between gap-3">
  <div><h2 class="app-title text-[13px]">Euclidean Centroid Distance</h2><p class="text-[9px] text-[#8b918e] mt-1">Jarak Euclidean antar titik centroid setiap segmen.</p></div>
   <span class="metric-icon metric-amber">d</span>
  </div>
  @if(count($centroidDistances))
  @php $centroidMax=max(array_map(fn($item)=>(float)($item['distance'] ?? 0), $centroidDistances) ?: [1]); $centroidMin=min(array_map(fn($item)=>(float)($item['distance'] ?? 0), $centroidDistances) ?: [0]); $centroidRange=max($centroidMax-$centroidMin,1); $centroidPoints=[]; @endphp
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis jarak centroid Euclidean">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
   @foreach($centroidDistances as $item)
    @php $distance=(float)($item['distance'] ?? 0); $index=$loop->index; $x=28+($index*377/max(count($centroidDistances)-1,1)); $y=125-(($distance-$centroidMin)/$centroidRange*105); $label=($item['segment_i'] ?? 'C1').' / '.($item['segment_j'] ?? 'C2'); $centroidPoints[]=round($x,1).','.round($y,1); @endphp
    <text x="{{$x}}" y="145" text-anchor="middle" font-size="8" fill="#8b918e">{{Str::limit($label,13,'')}}</text><circle cx="{{$x}}" cy="{{$y}}" r="4" fill="#c39a54"><title>{{$label}}, distance={{number_format($distance,2)}}</title></circle>
   @endforeach
   <polyline points="{{implode(' ',$centroidPoints)}}" fill="none" stroke="#c39a54" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Centroid terjauh menunjukkan segmen paling berbeda</span><span>Euclidean</span></div>
  @elseif(count($euclideanByK))
  @php $distanceMax=max($euclideanValues ?: [1]); $distanceMin=min($euclideanValues ?: [0]); $distanceRange=max($distanceMax-$distanceMin,1); $distancePoints=[]; @endphp
  <div class="mt-5 overflow-x-auto">
   <svg viewBox="0 0 420 155" class="w-full min-w-[360px]" role="img" aria-label="Grafik garis jarak Euclidean">
    <line x1="28" y1="125" x2="405" y2="125" stroke="#dfe3e1"/><line x1="28" y1="15" x2="28" y2="125" stroke="#dfe3e1"/>
   @foreach($euclideanByK as $item)
    @php $distance=(float)($item['average_euclidean_distance'] ?? 0); $pointIndex=$loop->index; $x=28+($pointIndex*377/max(count($euclideanByK)-1,1)); $y=125-(($distance-$distanceMin)/$distanceRange*105); $distancePoints[]=round($x,1).','.round($y,1); @endphp
    <text x="{{$x}}" y="145" text-anchor="middle" font-size="9" fill="#8b918e">K={{$item['k'] ?? '—'}}</text><circle cx="{{$x}}" cy="{{$y}}" r="4" fill="#c39a54"><title>K={{$item['k'] ?? '—'}}, distance={{number_format($distance,2)}}</title></circle>
   @endforeach
   <polyline points="{{implode(' ',$distancePoints)}}" fill="none" stroke="#c39a54" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
   </svg>
  </div>
  <div class="mt-3 flex justify-between text-[8px] text-[#8b918e]"><span>Jarak rata-rata ke centroid per K</span><span>Euclidean</span></div>
  @else
  <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">⌁</div><div class="mt-2">Data Euclidean belum tersedia</div></div></div>
  @endif
 </section>
</div>

<section class="app-card p-4 mb-4">
 <div class="flex items-start justify-between gap-3">
  <div><h2 class="app-title text-[13px]">Strategi Segmentasi</h2><p class="text-[9px] text-[#8b918e] mt-1">Rekomendasi tindakan berdasarkan hasil segmentasi RFM.</p></div>
  <span class="metric-icon metric-green">↗</span>
 </div>
 @if(count($strategi))
 <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
  @foreach($strategi as $strategy)
  <div class="app-card-soft p-3">
   <div class="flex items-center justify-between gap-2"><span class="text-[10px] font-bold">{{ $strategy['segment'] ?? 'Segment' }}</span><span class="text-[9px] text-[#6f8f7e]">RFM action</span></div>
   <p class="text-[10px] font-semibold mt-2">{{ $strategy['focus'] ?? '' }}</p>
   <p class="text-[9px] text-[#8b918e] mt-1.5 leading-relaxed">{{ $strategy['action'] ?? '' }}</p>
  </div>
  @endforeach
 </div>
 @else
 <div class="empty-chart mt-4"><div class="text-center"><div class="text-[24px] opacity-40">↗</div><div class="mt-2">Strategi belum tersedia</div></div></div>
 @endif
</section>

<div class="app-card p-4">
 <div class="flex items-center justify-between gap-3"><div><h2 class="app-title text-[13px]">Top 10 Customers — Champions</h2><p class="text-[9px] text-[#8b918e] mt-1">Highest-value customers based on RFM profile.</p></div><a href="{{route('landing')}}#customer-table" target="_blank" class="app-btn-secondary">Public Dashboard ↗</a></div>
 <div class="mt-4 overflow-x-auto"><table class="w-full app-table"><thead><tr><th>#</th><th>Customer</th><th>Recency</th><th>Frequency</th><th>Monetary</th></tr></thead><tbody>
 @forelse(($m['topChampions'] ?? $topChampions ?? []) as $i=>$c)
 <tr><td class="text-[#8b918e]">{{$i+1}}</td><td class="font-semibold">{{ $c->name ?? $c->customer_code }}</td><td>{{ $c->recency ?? '—' }} days</td><td>{{ $c->frequency ?? '—' }} orders</td><td>Rp {{number_format($c->monetary??0,0,',','.')}}</td></tr>
 @empty<tr><td colspan="5" class="text-[#8b918e]">Belum ada hasil segmentasi.</td></tr>@endforelse
 </tbody></table></div>
</div>
</x-layouts.admin-layout>

@if(count($plot3dData))
<script src="https://cdn.plot.ly/plotly-2.35.2.min.js"></script>
<script>
const cluster3dPoints = @json($plot3dData);
const cluster3dCentroids = @json($plot3dCentroids);
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
@endif