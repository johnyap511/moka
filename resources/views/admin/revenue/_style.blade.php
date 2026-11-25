{{-- Shared look for the Revenue desk tabs. --}}
<style>
.rd{--teal:#0f766e;--red:#b91c1c;--red-bg:#fee2e2;--amber:#92400e;--amber-bg:#fef3c7;--green:#166534;--green-bg:#dcfce7;--blue:#075985;--blue-bg:#e0f2fe;--line:var(--border,#e5e7eb);--mute:var(--text-secondary,#6e6e73)}
.rd-head{margin-bottom:14px}.rd-head h1{font-size:22px;margin:0 0 2px;letter-spacing:-.2px}.rd-head p{font-size:13px;color:var(--mute);margin:0}
.rd-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}
.rd-kpi{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:13px 15px 13px 17px;min-width:0;display:grid;gap:2px;position:relative;overflow:hidden;align-content:start}.rd-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:transparent}.rd-kpi.hot::before{background:#ea580c}.rd-kpi.warm::before{background:#d97706}
.rd-kpi .l{font-size:11.5px;color:var(--mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.rd-kpi .v{font-size:26px;font-weight:700;letter-spacing:-.4px;line-height:1.15}.rd-kpi .v small{font-size:13px;font-weight:500;color:var(--mute);letter-spacing:0;margin-left:4px}.rd-kpi .v.sm{font-size:18px;line-height:1.3}.rd-kpi .s{font-size:12px;color:var(--mute)}
.rd-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);min-width:0;margin-bottom:14px}
.rd-card>h2{font-size:15px;margin:0;padding:14px 16px 4px}.rd-card>p.sub{font-size:12.5px;color:var(--mute);margin:0;padding:0 16px 12px;line-height:1.45}
.rd-wrap{overflow-x:auto}
.rd-table{width:100%;border-collapse:collapse;font-size:13px}.rd-table th,.rd-table td{padding:11px 9px;border-top:1px solid var(--line);text-align:left;vertical-align:top}.rd-table th:first-child,.rd-table td:first-child{padding-left:16px}.rd-table th:last-child,.rd-table td:last-child{padding-right:16px}
.rd-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;white-space:nowrap;padding-top:9px;padding-bottom:9px;background:#fafafa;vertical-align:middle}.rd-table th[data-k]{cursor:pointer;user-select:none}.rd-table th.sorted{color:var(--teal)}
.rd-table tbody tr:hover{background:#fafcfb}
.rd-b{font-weight:600;font-size:13.5px;white-space:nowrap}.rd-b a{color:inherit;text-decoration:none}.rd-b a:hover{color:var(--teal)}
.rd-sub{font-size:11.5px;color:var(--mute);margin-top:3px;line-height:1.35}
.rd-num{font-weight:600;font-size:13.5px;font-variant-numeric:tabular-nums;white-space:nowrap}
.rd-chip{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}.rd-chip.hot{background:#ffedd5;color:#9a3412}.rd-chip.warm{background:var(--amber-bg);color:var(--amber)}.rd-chip.soft{background:var(--blue-bg);color:var(--blue)}.rd-chip.red{background:var(--red-bg);color:#991b1b}.rd-chip.green{background:var(--green-bg);color:var(--green)}.rd-chip.grey{background:#f1f5f9;color:#475569}.rd-chip.teal{background:#ccfbf1;color:#115e59}
.rd-up{display:inline-block;font-size:14px;font-weight:700;padding:3px 10px;border-radius:8px;background:#ffedd5;color:#9a3412;font-variant-numeric:tabular-nums;white-space:nowrap}.rd-up.mild{background:var(--amber-bg);color:var(--amber)}.rd-up.down{background:var(--blue-bg);color:var(--blue)}.rd-up.none{background:#f1f5f9;color:#475569;font-weight:600;font-size:12.5px}
.rd-meter{position:relative;width:96px;height:7px;border-radius:999px;background:#eef2f5;margin-top:6px}.rd-meter i{position:absolute;inset:0 auto 0 0;border-radius:999px;background:var(--teal)}.rd-meter i.long{background:#94a3b8}.rd-meter u{position:absolute;top:-3px;bottom:-3px;width:2px;background:#1e293b;border-radius:1px}
.rd-note{font-size:13px;color:#44403c;line-height:1.55;padding:14px 16px}.rd-note summary{cursor:pointer;font-weight:600;color:var(--text,#1d1d1f)}.rd-note ul{margin:10px 0 0;padding-left:18px;display:grid;gap:5px}
.rd-insight{background:#f0fdfa;border:1px solid #99f6e4;color:#134e4a;border-radius:12px;padding:11px 15px;font-size:13.5px;line-height:1.45;margin-bottom:14px}.rd-insight b{font-weight:700}
.rd-empty{padding:26px 16px;text-align:center;color:var(--mute);font-size:13.5px;border-top:1px solid var(--line)}
.rd-mute{color:var(--mute)}
/* A table becomes a stack of cards when its own card is too narrow for it. Measured on the card,
   not the screen, because the sidebar takes 240px on a laptop. Older browsers fall back to the
   screen-width rule below, and to scrolling sideways inside the card. */
.rd-card{container-type:inline-size}
@container (max-width:980px){
  .rd-table.cards thead{display:none}.rd-table.cards,.rd-table.cards tbody{display:block}
  .rd-table.cards tbody tr{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px 10px;padding:14px 16px;border-top:1px solid var(--line)}.rd-table.cards tbody tr[hidden]{display:none}
  .rd-table.cards td{display:block;padding:0!important;border:0}.rd-table.cards td.wide{grid-column:1 / -1}
  .rd-table.cards td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;margin-bottom:4px}
  .rd-b{white-space:normal}
}
@container (max-width:1090px){
  .rd-table.cards.wide thead{display:none}.rd-table.cards.wide,.rd-table.cards.wide tbody{display:block}
  .rd-table.cards.wide tbody tr{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px 10px;padding:14px 16px;border-top:1px solid var(--line)}.rd-table.cards.wide tbody tr[hidden]{display:none}
  .rd-table.cards.wide td{display:block;padding:0!important;border:0}.rd-table.cards.wide td.wide{grid-column:1 / -1}
  .rd-table.cards.wide td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;margin-bottom:4px}
  .rd-b{white-space:normal}
}
@container (max-width:600px){
  .rd-table.cards tbody tr{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:900px){
  .rd-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.rd-kpi .v{font-size:22px}
}
@supports not (container-type:inline-size){
  @media (max-width:900px){
  .rd-table.cards thead{display:none}.rd-table.cards,.rd-table.cards tbody{display:block}
  .rd-table.cards tbody tr{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 10px;padding:14px 16px;border-top:1px solid var(--line)}.rd-table.cards tbody tr[hidden]{display:none}
  .rd-table.cards td{display:block;padding:0!important;border:0}.rd-table.cards td.wide{grid-column:1 / -1}
  .rd-table.cards td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;margin-bottom:4px}
  .rd-b{white-space:normal}
  }
}
</style>
