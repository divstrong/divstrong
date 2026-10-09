<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalCostItem extends Model
{
    protected $fillable = ['proposal_id', 'description', 'quantity', 'unit_price', 'amount', 'sort_order'];

    protected function casts(): array
    {
        return [
            // A float, not decimal:2: 1.5 should read "1.5", and 2 should read "2", not "2.00".
            'quantity' => 'float',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /** Quantity for display: "2", "1.5", "0.25" — never "2.00". */
    public function displayQuantity(): string
    {
        return rtrim(rtrim(number_format((float) $this->quantity, 2, '.', ''), '0'), '.');
    }

    /** The shape the proposal page's Investment table works with in the browser. */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
