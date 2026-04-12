<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false; // only created_at, set by DB default

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'meta',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => '—']);
    }

    /**
     * Convenience factory — call from anywhere:
     *   AuditLog::record('game.create', $game);
     */
    public static function record(
        string  $action,
        ?Model  $entity = null,
        ?array  $meta = null,
    ): self {
        $request = request();

        return self::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id'   => $entity?->getKey(),
            'meta'        => $meta,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request?->userAgent() ? substr($request->userAgent(), 0, 512) : null,
            'created_at'  => now(),
        ]);
    }
}
