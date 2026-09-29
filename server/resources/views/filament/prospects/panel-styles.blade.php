{{--
    Styling for the three custom views in the Prospects area: the engagement chips on the
    list, the activity timeline on the edit page, and the live preview beside the template
    editor.

    Real CSS rather than Tailwind utilities, for a reason worth knowing: the Filament panel is
    served Filament's OWN pre-compiled stylesheet, not a bundle built from this app's Blade
    files. resources/css/app.css does scan resources/**, but that bundle belongs to the public
    site and the panel never loads it. So a utility class written in a panel view compiles to
    nothing and renders unstyled — which is exactly what happened twice here: the chips came
    out as the bare words "Agency Client General" in a row, and the email preview iframe
    collapsed to a sliver because h-[720px] and w-full did not exist.

    The alternative is a custom Filament theme, which means a build step and a rebuild every
    time a colour changes. A scoped stylesheet behind a render hook costs neither, and it is
    the pattern this panel already uses for its brand button styling.

    Injected per page by AdminPanelProvider, scoped to the pages that need it.
--}}
<style>
    /* ---------------------------------------------------------------------
       Engagement chips (Prospects list)

       Colour carries WHICH email, weight and icon carry HOW FAR it got:

           Agency green · Client blue · General amber

           not sent  faint dashed outline, no icon   — nothing has gone out
           sent      hue outline + check             — delivered, nothing back yet
           opened    hue tint + eye                  — they looked
           clicked   solid hue + raised hand         — they acted (loudest chip)
           bounced   solid red + warning             — never arrived, outranks everything

       Bounced is red for every email on purpose: it is a failure, and dressing it in the
       email's own colour would let it read as just another stage.

       The hue comes from a data attribute rather than a class per combination, so adding a
       fourth email is one rule rather than three.
       --------------------------------------------------------------------- */
    .ds-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.25rem;
    }

    .ds-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
        border: 1px solid transparent;
        border-radius: 0.375rem;
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1rem;
        font-weight: 500;
    }

    .ds-chip svg {
        width: 0.75rem;
        height: 0.75rem;
        flex: none;
    }

    /* --ds-ink is the readable-on-light text tone; --ds-ink-dark is its counterpart once the
       panel flips, where the light tone falls below contrast. */
    .ds-chip[data-hue='agency'] {
        --ds-hue: #16a34a;
        --ds-edge: #15803d;
        --ds-tint: rgba(22, 163, 74, 0.14);
        --ds-ink: #15803d;
        --ds-ink-dark: #4ade80;
    }

    .ds-chip[data-hue='client'] {
        --ds-hue: #2563eb;
        --ds-edge: #1d4ed8;
        --ds-tint: rgba(37, 99, 235, 0.16);
        --ds-ink: #1d4ed8;
        --ds-ink-dark: #60a5fa;
    }

    .ds-chip[data-hue='general'] {
        --ds-hue: #d97706;
        --ds-edge: #b45309;
        --ds-tint: rgba(217, 119, 6, 0.16);
        --ds-ink: #b45309;
        --ds-ink-dark: #fbbf24;
    }

    /* Campaign steps: one hue for the whole sequence, so the steps read as one campaign
       rather than as four more unrelated emails beside the outreach chips. */
    .ds-chip[data-hue='campaign'] {
        --ds-hue: #7c3aed;
        --ds-edge: #6d28d9;
        --ds-tint: rgba(124, 58, 237, 0.14);
        --ds-ink: #6d28d9;
        --ds-ink-dark: #a78bfa;
    }

    .ds-chip[data-state='none'] {
        border-style: dashed;
        border-color: #d4d4d8;
        color: #a1a1aa;
    }

    .ds-chip[data-state='sent'] {
        border-color: var(--ds-hue);
        color: var(--ds-ink);
    }

    .ds-chip[data-state='opened'] {
        border-color: var(--ds-hue);
        background-color: var(--ds-tint);
        color: var(--ds-ink);
    }

    /* The loudest chip: somebody actually did something. */
    .ds-chip[data-state='clicked'] {
        border-color: var(--ds-edge);
        background-color: var(--ds-hue);
        color: #ffffff;
    }

    /* Red whatever the email, because nothing arrived — or they asked us to stop. */
    .ds-chip[data-state='bounced'],
    .ds-chip[data-state='unsubscribed'] {
        border-color: #b91c1c;
        background-color: #dc2626;
        color: #ffffff;
    }

    .dark .ds-chip[data-state='none'] {
        border-color: #52525b;
        color: #71717a;
    }

    .dark .ds-chip[data-state='sent'],
    .dark .ds-chip[data-state='opened'] {
        border-color: var(--ds-ink-dark);
        color: var(--ds-ink-dark);
    }

    .dark .ds-chip[data-state='opened'] {
        background-color: var(--ds-tint);
    }

    /* ---------------------------------------------------------------------
       Activity timeline (Prospect edit)

       Capped and scrollable: in the right-hand column a long timeline would otherwise
       stretch the page far past the form it sits beside.
       --------------------------------------------------------------------- */
    .ds-timeline {
        max-height: 32rem;
        overflow-y: auto;
        padding-right: 0.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .ds-event {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        border-radius: 0.5rem;
        padding: 0.625rem 0.5rem;
    }

    .ds-event:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .dark .ds-event:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .ds-event__icon {
        margin-top: 0.125rem;
        display: flex;
        height: 2rem;
        width: 2rem;
        flex: none;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background-color: rgba(113, 113, 122, 0.14);
        color: #52525b;
    }

    .ds-event__icon svg {
        width: 1rem;
        height: 1rem;
    }

    .dark .ds-event__icon {
        color: #a1a1aa;
    }

    /* One tone per event kind, matching what ProspectActivity::presentation() returns. */
    .ds-event[data-tone='info'] .ds-event__icon {
        background-color: rgba(37, 99, 235, 0.16);
        color: #2563eb;
    }

    .dark .ds-event[data-tone='info'] .ds-event__icon {
        color: #60a5fa;
    }

    .ds-event[data-tone='success'] .ds-event__icon {
        background-color: rgba(22, 163, 74, 0.16);
        color: #16a34a;
    }

    .dark .ds-event[data-tone='success'] .ds-event__icon {
        color: #4ade80;
    }

    .ds-event[data-tone='warning'] .ds-event__icon {
        background-color: rgba(217, 119, 6, 0.16);
        color: #b45309;
    }

    .dark .ds-event[data-tone='warning'] .ds-event__icon {
        color: #fbbf24;
    }

    .ds-event[data-tone='danger'] .ds-event__icon {
        background-color: rgba(220, 38, 38, 0.16);
        color: #dc2626;
    }

    .dark .ds-event[data-tone='danger'] .ds-event__icon {
        color: #f87171;
    }

    .ds-event[data-tone='primary'] .ds-event__icon {
        background-color: rgba(237, 37, 55, 0.16);
        color: #ed2537;
    }

    .ds-event__body {
        min-width: 0;
        flex: 1 1 auto;
    }

    .ds-event__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .ds-event__title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #18181b;
        margin: 0;
    }

    .dark .ds-event__title {
        color: #f4f4f5;
    }

    .ds-event__when {
        flex: none;
        font-size: 0.75rem;
        color: #a1a1aa;
    }

    .ds-event__meta {
        margin-top: 0.125rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0 0.75rem;
        font-size: 0.75rem;
        color: #71717a;
    }

    .dark .ds-event__meta {
        color: #a1a1aa;
    }

    .ds-event__meta a {
        color: #ed2537;
        text-decoration: underline;
    }

    .ds-event__meta a:hover {
        text-decoration: none;
    }

    .ds-timeline__empty {
        border: 1px dashed #d4d4d8;
        border-radius: 0.5rem;
        padding: 2.5rem 1rem;
        text-align: center;
        color: #71717a;
        font-size: 0.875rem;
    }

    .dark .ds-timeline__empty {
        border-color: #3f3f46;
        color: #a1a1aa;
    }

    .ds-timeline__empty svg {
        width: 2rem;
        height: 2rem;
        margin: 0 auto 0.5rem;
        color: #d4d4d8;
    }

    .dark .ds-timeline__empty svg {
        color: #52525b;
    }

    /* ---------------------------------------------------------------------
       Live email preview (Email Template edit)

       The iframe needs an explicit height or it collapses to a sliver, which is precisely
       what a missing utility class did here.
       --------------------------------------------------------------------- */
    .ds-preview {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .ds-preview__label {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.75rem;
        color: #71717a;
    }

    .ds-preview__label strong {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .ds-preview__frame {
        overflow: hidden;
        border: 1px solid #e4e4e7;
        border-radius: 0.5rem;
        background: #ffffff;
    }

    .dark .ds-preview__frame {
        border-color: #3f3f46;
    }

    /* The subject line, shown the way an inbox shows it. */
    .ds-preview__subject {
        border-bottom: 1px solid #e4e4e7;
        background: #fafafa;
        padding: 0.5rem 0.75rem;
    }

    .dark .ds-preview__subject {
        border-color: #3f3f46;
        background: #27272a;
    }

    .ds-preview__subject p {
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ds-preview__subject p:first-child {
        font-size: 0.875rem;
        font-weight: 600;
        color: #18181b;
    }

    .dark .ds-preview__subject p:first-child {
        color: #f4f4f5;
    }

    .ds-preview__subject p:last-child {
        font-size: 0.75rem;
        color: #71717a;
    }

    /*
     * The email is a fixed 600px table plus padding — wider than this column ever gets — so at
     * 100% width the preview clipped the right-hand edge of every line and the reader had to
     * scroll sideways to check their own copy.
     *
     * Rendered at its true width and zoomed to fit instead. `zoom` rather than `transform:
     * scale()` because zoom scales the element's layout box, so the surrounding column sizes
     * itself correctly; a transform is visual only and would leave a gap the height of the
     * unscaled frame underneath.
     */
    .ds-preview__frame iframe {
        display: block;
        width: 640px;
        height: 1100px;
        border: 0;
        background: #ffffff;
        zoom: 0.66;
    }
</style>
