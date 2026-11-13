<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractSignature extends Model
{
    // Status padronizados — todo provedor é traduzido para estes valores
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_PARTIALLY_SIGNED = 'partially_signed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ERROR = 'error';

    // Documentos que podem ser enviados para assinatura (cada um é um envio separado)
    public const DOCUMENT_CONTRACT = 'contrato';
    public const DOCUMENT_PROPOSAL = 'proposta';
    public const DOCUMENT_RCI = 'rci';

    public const DOCUMENT_LABELS = [
        self::DOCUMENT_CONTRACT => 'Contrato',
        self::DOCUMENT_PROPOSAL => 'Proposta',
        self::DOCUMENT_RCI => 'RCI',
    ];

    // Status em que ainda faz sentido consultar o provedor
    public const OPEN_STATUSES = [self::STATUS_SENT, self::STATUS_VIEWED, self::STATUS_PARTIALLY_SIGNED];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Rascunho',
        self::STATUS_SENT => 'Enviado',
        self::STATUS_VIEWED => 'Visualizado',
        self::STATUS_PARTIALLY_SIGNED => 'Parcialmente assinado',
        self::STATUS_SIGNED => 'Assinado',
        self::STATUS_REFUSED => 'Recusado',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_EXPIRED => 'Expirado',
        self::STATUS_ERROR => 'Erro',
    ];

    protected $fillable = [
        'sales_service_id',
        'document_type',
        'provider',
        'external_id',
        'status',
        'document_name',
        'delivery_method',
        'sandbox',
        'original_file_path',
        'signed_file_path',
        'error_message',
        'log',
        'sent_by',
        'sent_at',
        'signed_at',
        'cancelled_at',
        'last_synced_at',
    ];

    protected $casts = [
        'sandbox' => 'boolean',
        'log' => 'array',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'document_label'];

    public function salesService()
    {
        return $this->belongsTo(SalesService::class);
    }

    public function signers()
    {
        return $this->hasMany(ContractSignatureSigner::class)->orderBy('order');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function getDocumentLabelAttribute(): string
    {
        return self::DOCUMENT_LABELS[$this->document_type] ?? (string) $this->document_type;
    }

    /**
     * Arquivo assinado mais recente de um documento do atendimento (null se não houver).
     */
    public static function latestSignedFile(int $salesServiceId, string $documentType): ?self
    {
        return static::where('sales_service_id', $salesServiceId)
            ->where('document_type', $documentType)
            ->where('status', self::STATUS_SIGNED)
            ->whereNotNull('signed_file_path')
            ->latest('signed_at')
            ->first();
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /**
     * Registra um evento no histórico do envio.
     */
    public function addLog(string $message, array $context = []): void
    {
        $log = $this->log ?? [];
        $log[] = array_filter([
            'at' => now()->toIso8601String(),
            'message' => $message,
            'context' => $context ?: null,
        ]);
        $this->log = array_slice($log, -100);
    }
}
