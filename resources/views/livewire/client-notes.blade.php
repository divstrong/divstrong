<div class="space-y-6">
    {{-- Add Note Form --}}
    <form wire:submit="addNote" class="space-y-3">
        <textarea
            wire:model="newNote"
            rows="3"
            placeholder="Add a note..."
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500 dark:focus:border-primary-500"
        ></textarea>
        @error('newNote')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <x-heroicon-m-plus class="h-4 w-4" />
                Add Note
            </button>
        </div>
    </form>

    {{-- Notes List --}}
    @if($notes->isEmpty())
        <div class="text-center py-8">
            <x-heroicon-o-chat-bubble-bottom-center-text class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No notes yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a note above to get started.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($notes as $note)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $note->user?->name ?? 'Unknown User' }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $note->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $note->body }}</p>
                        </div>
                        <button
                            wire:click="deleteNote({{ $note->id }})"
                            wire:confirm="Are you sure you want to delete this note?"
                            class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-500 dark:hover:bg-white/10 dark:hover:text-red-400"
                        >
                            <x-heroicon-m-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
