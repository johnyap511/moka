{{-- Shared look of the two Sales lists (estimate requests, contact messages): one row per enquiry in three
     blocks that fit any screen and stack on a phone. No wide table, so nothing is cut off or scrolls sideways. --}}
<style>
.sl-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center}
.sl-filters .sl-spam{margin-left:auto;font-size:13px;color:#6b7280}
.sl-list{border-top:1px solid #e5e7eb}
.sl-row{display:grid;grid-template-columns:minmax(200px,260px) minmax(0,1fr) minmax(170px,200px);gap:14px 24px;padding:16px 20px;border-bottom:1px solid #e5e7eb;align-items:start}
.sl-row:last-child{border-bottom:0}
.sl-who{min-width:0}
.sl-name{font-weight:600;color:#111827;overflow-wrap:anywhere}
.sl-line{font-size:13px;color:#374151;margin-top:3px;overflow-wrap:anywhere}
.sl-line a{color:inherit;text-decoration:none;border-bottom:1px dotted #9ca3af}.sl-line a:hover{color:#0f766e;border-color:#0f766e}
.sl-wa{margin-left:8px;font-size:12px;color:#0f766e!important;border:0!important;font-weight:600}
.sl-when{font-size:12px;color:#6b7280;margin-top:6px}
.sl-body{min-width:0;font-size:14px;line-height:1.5;color:#1f2937;white-space:pre-line;overflow-wrap:anywhere}
.sl-body .sl-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;margin-bottom:3px;white-space:normal}
.sl-body .sl-sub{display:block;font-size:13px;color:#6b7280;margin-top:4px;white-space:normal}
.sl-do{display:flex;flex-direction:column;gap:8px;align-items:flex-start}
.sl-do form{display:inline}.sl-btns{display:flex;gap:6px;flex-wrap:wrap}
.sl-by{font-size:12px;color:#6b7280}
@media (max-width:900px){
  .sl-row{grid-template-columns:minmax(0,1fr);gap:10px;padding:14px 16px}
  .sl-do{flex-direction:row;flex-wrap:wrap;align-items:center}
  .sl-filters .sl-spam{margin-left:0;width:100%}
}
</style>
