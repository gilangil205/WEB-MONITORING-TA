<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
    'suhu',
    'kelembapan_udara',
    'kelembapan_tanah',
    'nilai_fuzzy',
    'image',
    'deteksi',
    'deteksi_yolo',
    'confidence_yolo',
    ];
}