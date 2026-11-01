{{-- Batch 43: statement styling shared by the on-screen reports and the print pages (no Tailwind needed). --}}
<style>
    .st { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #1f2937; font-size: 14px; }
    .st-head { text-align: center; margin-bottom: 18px; }
    .st-head .org { font-size: 17px; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    .st-head .title { font-size: 15px; font-weight: 700; margin-top: 4px; text-transform: uppercase; }
    .st-head .sub { color: #6b7280; font-size: 13px; margin-top: 2px; }
    .st table { width: 100%; border-collapse: collapse; }
    .st th { font-size: 12px; color: #6b7280; text-align: left; padding: 6px 8px; border-bottom: 1px solid #d1d5db; font-weight: 600; }
    .st td { padding: 5px 8px; vertical-align: top; }
    .st .n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .st .muted { color: #9ca3af; }
    .st .code { color: #9ca3af; font-size: 12px; width: 60px; }
    .st tr.h td { font-weight: 700; padding-top: 14px; text-transform: uppercase; font-size: 12px; letter-spacing: .04em; color: #374151; }
    .st tr.i td:first-child { padding-left: 22px; }
    .st tr.sub td { font-weight: 600; border-top: 1px solid #9ca3af; }
    .st tr.sub td:first-child { padding-left: 8px; }
    .st tr.tot td { font-weight: 700; border-top: 1px solid #374151; border-bottom: 3px double #374151; }
    .st tr.big td { font-size: 15px; }
    .st a { color: inherit; text-decoration: none; }
    .st a:hover { text-decoration: underline; }
    .st .warn { color: #b91c1c; font-weight: 600; }
    .st .ok { color: #15803d; font-weight: 600; }
    .st .foot { margin-top: 14px; font-size: 12px; color: #6b7280; }
</style>
