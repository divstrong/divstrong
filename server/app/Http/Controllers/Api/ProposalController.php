<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Mail\ProposalStatusChanged;
use App\Models\Client;
use App\Models\Proposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $proposals = Proposal::query()
            ->forUser()
            ->with('client:id,name,company')
            ->withCount('scopeItems', 'costItems')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn (Proposal $p) => $this->presentSummary($p));

        return response()->json(['data' => $proposals]);
    }

    public function show(Proposal $proposal): JsonResponse
    {
        $proposal->load([
            'client:id,name,email,company,phone,domain',
            'scopeItems' => fn ($q) => $q->orderBy('sort_order'),
            'costItems' => fn ($q) => $q->orderBy('sort_order'),
            'milestones' => fn ($q) => $q->orderBy('sort_order'),
            'roadmapPhases' => fn ($q) => $q->orderBy('sort_order'),
            'terms' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $this->presentDetail($proposal)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'project_title' => ['required', 'string', 'max:255'],
            'proposal_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'introduction' => ['nullable', 'string'],
            'cost_notes' => ['nullable', 'string'],
            'discount_enabled' => ['nullable', 'boolean'],
            'discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $data['client_name'] = $data['client_name'] ?? $client->name;
                $data['client_email'] = $data['client_email'] ?? $client->email;
                $data['client_company'] = $data['client_company'] ?? $client->company;
            }
        }

        $data['user_id'] = Auth::id();
        $data['proposal_date'] = $data['proposal_date'] ?? now()->toDateString();
        $data['valid_until'] = $data['valid_until'] ?? now()->addDays(60)->toDateString();
        $data['status'] = ProposalStatus::Draft;

        $proposal = Proposal::create($data);
        $proposal->load('client:id,name,email,company,phone,domain', 'scopeItems', 'costItems', 'milestones', 'roadmapPhases', 'terms');

        return response()->json(['data' => $this->presentDetail($proposal)], 201);
    }

    public function update(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'project_title' => ['nullable', 'string', 'max:255'],
            'proposal_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'introduction' => ['nullable', 'string'],
            'cost_notes' => ['nullable', 'string'],
            'discount_enabled' => ['nullable', 'boolean'],
            'discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $proposal->update($data);
        $proposal->load('client:id,name,email,company,phone,domain', 'scopeItems', 'costItems', 'milestones', 'roadmapPhases', 'terms');

        return response()->json(['data' => $this->presentDetail($proposal)]);
    }

    public function send(Proposal $proposal): JsonResponse
    {
        $proposal->update([
            'status' => ProposalStatus::Sent,
            'sent_at' => now(),
        ]);

        return response()->json(['data' => $this->presentSummary($proposal), 'message' => 'Proposal sent.']);
    }

    // --- Scope Items ---

    public function addScopeItem(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bullets' => ['nullable', 'array'],
            'bullets.*' => ['string'],
        ]);

        $data['sort_order'] = $proposal->scopeItems()->max('sort_order') + 1;
        $item = $proposal->scopeItems()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function updateScopeItem(Request $request, Proposal $proposal, int $itemId): JsonResponse
    {
        $item = $proposal->scopeItems()->findOrFail($itemId);
        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bullets' => ['nullable', 'array'],
            'bullets.*' => ['string'],
        ]);
        $item->update($data);

        return response()->json(['data' => $item]);
    }

    public function deleteScopeItem(Proposal $proposal, int $itemId): JsonResponse
    {
        $proposal->scopeItems()->findOrFail($itemId)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // --- Cost Items ---

    public function addCostItem(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['amount'] = $data['quantity'] * $data['unit_price'];
        $data['sort_order'] = $proposal->costItems()->max('sort_order') + 1;
        $item = $proposal->costItems()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function updateCostItem(Request $request, Proposal $proposal, int $itemId): JsonResponse
    {
        $item = $proposal->costItems()->findOrFail($itemId);
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
        if (isset($data['quantity']) || isset($data['unit_price'])) {
            $qty = $data['quantity'] ?? $item->quantity;
            $price = $data['unit_price'] ?? $item->unit_price;
            $data['amount'] = $qty * $price;
        }
        $item->update($data);

        return response()->json(['data' => $item->fresh()]);
    }

    public function deleteCostItem(Proposal $proposal, int $itemId): JsonResponse
    {
        $proposal->costItems()->findOrFail($itemId)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // --- Milestones ---

    public function addMilestone(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['sort_order'] = $proposal->milestones()->max('sort_order') + 1;
        $item = $proposal->milestones()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function updateMilestone(Request $request, Proposal $proposal, int $itemId): JsonResponse
    {
        $item = $proposal->milestones()->findOrFail($itemId);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_description' => ['nullable', 'string', 'max:255'],
        ]);
        $item->update($data);

        return response()->json(['data' => $item->fresh()]);
    }

    public function deleteMilestone(Proposal $proposal, int $itemId): JsonResponse
    {
        $proposal->milestones()->findOrFail($itemId)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function destroy(Proposal $proposal): JsonResponse
    {
        $proposal->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // --- Presenters ---

    private function presentSummary(Proposal $p): array
    {
        return [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'project_title' => $p->project_title,
            'client_name' => $p->client_name,
            'client_company' => $p->client_company,
            'status' => $p->status->value,
            'status_label' => $p->status->getLabel(),
            'status_color' => $p->status->getColor(),
            'total' => $p->total,
            'proposal_date' => $p->proposal_date?->toDateString(),
            'valid_until' => $p->valid_until?->toDateString(),
            'view_count' => $p->view_count,
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }

    private function presentDetail(Proposal $p): array
    {
        return array_merge($this->presentSummary($p), [
            'client_id' => $p->client_id,
            'client_email' => $p->client_email,
            'client_domain' => $p->client_domain,
            'introduction' => $p->introduction,
            'cost_notes' => $p->cost_notes,
            'discount_enabled' => $p->discount_enabled,
            'discount_type' => $p->discount_type,
            'discount_value' => (float) $p->discount_value,
            'subtotal' => $p->subtotal,
            'discount_amount' => $p->discount_amount,
            'public_url' => $p->public_url,
            'cover_image' => $p->cover_image ? Storage::disk('public')->url($p->cover_image) : null,
            'overview_image' => $p->overview_image ? Storage::disk('public')->url($p->overview_image) : null,
            'sent_at' => $p->sent_at?->toIso8601String(),
            'accepted_at' => $p->accepted_at?->toIso8601String(),
            'scope_items' => $p->scopeItems->map(fn ($s) => [
                'id' => $s->id,
                'category' => $s->category,
                'title' => $s->title,
                'description' => $s->description,
                'bullets' => $s->bullets ?? [],
                'sort_order' => $s->sort_order,
            ])->values(),
            'cost_items' => $p->costItems->map(fn ($c) => [
                'id' => $c->id,
                'description' => $c->description,
                'quantity' => $c->quantity,
                'unit_price' => (float) $c->unit_price,
                'amount' => (float) $c->amount,
                'sort_order' => $c->sort_order,
            ])->values(),
            'milestones' => $p->milestones->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'percentage' => (float) $m->percentage,
                'amount' => (float) $m->amount,
                'due_description' => $m->due_description,
                'payment_status' => $m->payment_status,
                'sort_order' => $m->sort_order,
            ])->values(),
            'terms' => $p->terms->map(fn ($t) => [
                'id' => $t->id,
                'content' => $t->content,
                'sort_order' => $t->sort_order,
            ])->values(),
        ]);
    }
}
