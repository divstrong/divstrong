<div>
    {{-- Chat Thread --}}
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; max-height: 500px; overflow-y: auto; padding: 1rem; border-radius: 0.75rem; background-color: #f9fafb; border: 1px solid #e5e7eb;" id="notes-thread">
        @if($notes->isEmpty())
            <div style="text-align: center; padding: 3rem 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #111827;">No notes yet</h3>
                <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Start the conversation below.</p>
            </div>
        @else
            @foreach($notes as $note)
                @php
                    $isMe = $note->user_id === $currentUserId;
                @endphp
                <div style="display: flex; {{ $isMe ? 'justify-content: flex-end;' : 'justify-content: flex-start;' }}">
                    <div style="max-width: 75%; position: relative;">
                        {{-- Bubble --}}
                        <div style="
                            padding: 0.75rem 1rem;
                            border-radius: 1rem;
                            {{ $isMe
                                ? 'background-color: #ed2537; color: white; border-bottom-right-radius: 0.25rem;'
                                : 'background-color: white; color: #111827; border: 1px solid #e5e7eb; border-bottom-left-radius: 0.25rem;'
                            }}
                        ">
                            <p style="font-size: 0.875rem; white-space: pre-wrap; margin: 0; line-height: 1.5;">{{ $note->body }}</p>
                        </div>
                        {{-- Meta --}}
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; {{ $isMe ? 'justify-content: flex-end;' : 'justify-content: flex-start;' }}">
                            <span style="font-size: 0.6875rem; font-weight: 600; color: #6b7280;">
                                {{ $note->user?->name ?? 'Unknown' }}
                            </span>
                            <span style="font-size: 0.625rem; color: #9ca3af;">
                                {{ $note->created_at->diffForHumans() }}
                            </span>
                            @if($isMe)
                                <button
                                    wire:click="deleteNote({{ $note->id }})"
                                    wire:confirm="Delete this note?"
                                    style="background: none; border: none; cursor: pointer; padding: 0; color: #9ca3af; line-height: 0;"
                                    onmouseover="this.style.color='#ef4444'"
                                    onmouseout="this.style.color='#9ca3af'"
                                    title="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width: 0.75rem; height: 0.75rem;">
                                        <path fill-rule="evenodd" d="M5 3.25V4H2.75a.75.75 0 0 0 0 1.5h.3l.815 8.15A1.5 1.5 0 0 0 5.357 15h5.285a1.5 1.5 0 0 0 1.493-1.35l.815-8.15h.3a.75.75 0 0 0 0-1.5H11v-.75A2.25 2.25 0 0 0 8.75 1h-1.5A2.25 2.25 0 0 0 5 3.25Zm2.25-.75a.75.75 0 0 0-.75.75V4h3v-.75a.75.75 0 0 0-.75-.75h-1.5ZM6.05 6a.75.75 0 0 1 .787.713l.275 5.5a.75.75 0 0 1-1.498.075l-.275-5.5A.75.75 0 0 1 6.05 6Zm3.9 0a.75.75 0 0 1 .712.787l-.275 5.5a.75.75 0 0 1-1.498-.075l.275-5.5A.75.75 0 0 1 9.95 6Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Input --}}
    <form wire:submit="addNote" style="display: flex; gap: 0.75rem; align-items: flex-end;">
        <div style="flex: 1;">
            <textarea
                wire:model="newNote"
                rows="2"
                placeholder="Type a note..."
                style="width: 100%; border-radius: 0.75rem; border: 1px solid #d1d5db; background-color: white; padding: 0.75rem 1rem; font-size: 0.875rem; color: #111827; resize: none; font-family: inherit; outline: none; box-sizing: border-box;"
                onfocus="this.style.borderColor='#ed2537'; this.style.boxShadow='0 0 0 1px #ed2537'"
                onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); $wire.addNote(); }"
            ></textarea>
            @error('newNote')
                <p style="font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; border-radius: 0.75rem; background-color: #ed2537; padding: 0.75rem; border: none; cursor: pointer; color: white; flex-shrink: 0;" title="Send">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" />
            </svg>
        </button>
    </form>
    <p style="font-size: 0.6875rem; color: #9ca3af; margin-top: 0.375rem;">Press Enter to send, Shift+Enter for new line</p>

    {{-- Auto-scroll to bottom --}}
    <script>
        document.addEventListener('livewire:init', () => {
            const scrollToBottom = () => {
                const thread = document.getElementById('notes-thread');
                if (thread) thread.scrollTop = thread.scrollHeight;
            };
            scrollToBottom();
            Livewire.hook('morph.updated', () => setTimeout(scrollToBottom, 50));
        });
    </script>
</div>
