<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClusterSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'cluster_label',
        'segment_name',
        'color',
        'icon',
    ];
}
