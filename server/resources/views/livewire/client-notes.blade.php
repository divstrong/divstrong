<div>
    {{-- Add Note Form --}}
    <form wire:submit="addNote" style="margin-bottom: 1.5rem;">
        <textarea
            wire:model="newNote"
            rows="3"
            placeholder="Add a note..."
            style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; background-color: white; padding: 0.75rem 1rem; font-size: 0.875rem; color: #111827; resize: vertical; font-family: inherit; outline: none; box-sizing: border-box;"
            onfocus="this.style.borderColor='#ed2537'; this.style.boxShadow='0 0 0 1px #ed2537'"
            onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
        ></textarea>
        @error('newNote')
            <p style="font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem;">{{ $message }}</p>
        @enderror
        <div style="display: flex; justify-content: flex-end; margin-top: 0.75rem;">
            <button type="submit" style="display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 0.5rem; background-color: #ed2537; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; color: white; border: none; cursor: pointer; font-family: inherit;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem;">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                Add Note
            </button>
        </div>
    </form>

    {{-- Notes List --}}
    @if($notes->isEmpty())
        <div style="text-align: center; padding: 2rem 0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
            <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #111827;">No notes yet</h3>
            <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Add a note above to get started.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($notes as $note)
                <div style="border-radius: 0.75rem; border: 1px solid #e5e7eb; background-color: white; padding: 1rem;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                        <div style="min-width: 0; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">
                                    {{ $note->user?->name ?? 'Unknown User' }}
                                </span>
                                <span style="font-size: 0.75rem; color: #9ca3af;">
                                    {{ $note->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p style="font-size: 0.875rem; color: #374151; white-space: pre-wrap; margin: 0;">{{ $note->body }}</p>
                        </div>
                        <button
                            wire:click="deleteNote({{ $note->id }})"
                            wire:confirm="Are you sure you want to delete this note?"
                            style="flex-shrink: 0; border-radius: 0.5rem; padding: 0.375rem; color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0;"
                            onmouseover="this.style.color='#ef4444'"
                            onmouseout="this.style.color='#9ca3af'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem;">
                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 1 .7.798l-.2 4a.75.75 0 0 1-1.496-.076l.2-4a.75.75 0 0 1 .796-.722Zm3.638.798a.75.75 0 0 0-1.496-.076l-.2 4a.75.75 0 0 0 1.496.076l.2-4Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
