<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\Workflowable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PropertyStatus;

class Property extends Model
{
    use HasFactory, SoftDeletes, Workflowable;
    use BelongsToOrganization;

    protected $fillable = [
        'asset_code', 'name', 'property_type', 'country', 'region', 'city',
        'address', 'gps_lat', 'gps_lng', 'size_sqm', 'number_of_units',
        'bedrooms', 'bathrooms', 'floor', 'building_info', 'developer',
        'ownership_type', 'price', 'description', 'amenities', 'status',
        'is_published', 'published_at', 'registered_by_user_id', 'generation_id',
    ];

    protected $casts = [
        'price'           => 'decimal:2',
        'size_sqm'        => 'decimal:2',
        'gps_lat'         => 'decimal:8',
        'gps_lng'         => 'decimal:8',
        'amenities'       => 'array',
        'is_published'    => 'boolean',
        'published_at'    => 'datetime',
        'status'          => PropertyStatus::class,
    ];

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(PropertyVerification::class);
    }

    public function asset(): HasOne
    {
        return $this->hasOne(PropertyAsset::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(PropertyPublication::class);
    }

    public function scopePublished($q) { return $q->where('is_published', true); }
}
