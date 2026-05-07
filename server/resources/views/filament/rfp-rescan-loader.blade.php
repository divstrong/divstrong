<div
    wire:loading.flex
    wire:target="mountedActions, mountedTableActions, callMountedAction, callMountedTableAction"
    style="position: fixed; inset: 0; z-index: 9999; flex-direction: column; align-items: center; justify-content: center; background-color: rgba(255, 255, 255, 0.95); padding: 2rem;"
>
    <svg
        style="width: 5rem; height: 5rem; color: #ef4444; animation: rfp-spin 1s linear infinite;"
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
    >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25;"></circle>
        <path fill="currentColor" style="opacity: 0.75;" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    <h3 style="margin-top: 1.5rem; margin-bottom: 0; font-size: 1.25rem; font-weight: 700; color: #111827;">
        Analyzing your RFP
    </h3>

    <p style="margin-top: 0.5rem; margin-bottom: 0; font-size: 0.875rem; color: #6b7280; text-align: center; max-width: 28rem; line-height: 1.5;">
        Claude is reading the document, scoring fit, extracting requirements, and flagging risks.
        This typically takes 30&ndash;60 seconds depending on document length.
    </p>

    <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
        <span style="position: relative; display: inline-flex; width: 0.625rem; height: 0.625rem;">
            <span style="position: absolute; inset: 0; border-radius: 9999px; background-color: #f87171; opacity: 0.75; animation: rfp-ping 1.4s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
            <span style="position: relative; display: inline-block; width: 0.625rem; height: 0.625rem; border-radius: 9999px; background-color: #ef4444;"></span>
        </span>
        <span>Processing &mdash; please don't close this window</span>
    </div>

    <style>
        @keyframes rfp-spin { to { transform: rotate(360deg); } }
        @keyframes rfp-ping {
            75%, 100% { transform: scale(2.2); opacity: 0; }
        }
    </style>
</div>
