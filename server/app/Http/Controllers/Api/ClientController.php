<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->withCount('proposals')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()->map(fn (Client $c) => $this->presentSummary($c))]);
    }

    public function show(Client $client): JsonResponse
    {
        $client->loadCount('proposals');
        return response()->json(['data' => $this->presentDetail($client)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $client = Client::create($data);
        $client->loadCount('proposals');

        return response()->json(['data' => $this->presentDetail($client)], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate($this->rules());
        $client->update($data);
        $client->loadCount('proposals');

        return response()->json(['data' => $this->presentDetail($client)]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function presentSummary(Client $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'company' => $c->company,
            'phone' => $c->phone,
            'proposals_count' => $c->proposals_count ?? 0,
        ];
    }

    private function presentDetail(Client $c): array
    {
        return array_merge($this->presentSummary($c), [
            'title' => $c->title,
            'domain' => $c->domain,
            'address1' => $c->address1,
            'address2' => $c->address2,
            'city' => $c->city,
            'state' => $c->state,
            'zip' => $c->zip,
            'notes' => $c->notes,
            'created_at' => $c->created_at?->toIso8601String(),
        ]);
    }
}
