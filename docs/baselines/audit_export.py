# Audit the downloaded old-format export (Bookings by check-in) row by row against eZee's revenue reports.
import json,csv,collections,sys,openpyxl
S='/private/tmp/claude-501/-Users-samkong/4b640e3a-edfd-4e50-8083-8110c5f429a0/scratchpad'
D='/Users/samkong/Downloads/'
B={b['id']:b for b in json.load(open(f'{S}/aug_bookings.json'))}
RESH={b['SubBookingId']:b['TransactionId'][:5] for b in B.values() if b['SubBookingId'] and b['TransactionId']}
def hotel_of(listing):
    l=str(listing).lower()
    for k,h in [('eko','19676'),('bell','20317'),('forum','20318'),('damai','20318'),('arte','20319'),('queensville','20319'),('kl gateway','20319'),('alinea','20320')]:
        if l.startswith(k): return h
    return ''
CM=json.load(open(f'{S}/cleaning_by_hotel_folio.json'))
files={'19676':'EKO_detailrevenuereport (1).csv','20317':'BS_detailrevenuereport.csv','20318':'FOR_detailrevenuereport.csv','20319':'CHE KLG_detailrevenuereport.csv','20320':'AL_detailrevenuereport.csv'}
def num(x):
    try: return float(str(x).replace(',','') or 0)
    except: return 0.0
def dmy(s):
    p=str(s).strip().split('/'); return f'{p[2]}-{p[1]}-{p[0]}' if len(p)==3 else ''
ez={}; ezres=collections.defaultdict(list)
for h,f in files.items():
    rows=list(csv.reader(open(D+f,encoding='utf-8-sig'))); hdr=rows[0]
    for r in rows[1:]:
        if len(r)<20 or not r[1].strip(): continue  # continuation rows carry no RES
        d=dict(zip(hdr,r)); d['_h']=h; ez.setdefault((h,d['Folio No'].strip()),d); ezres[(h,d['Reservation No'].strip())].append(d)
ws=openpyxl.load_workbook(f'{S}/aug_export.xlsx').active
rows=[dict(zip([c for c in next(ws.iter_rows(min_row=2,max_row=2,values_only=True))],r)) for r in ws.iter_rows(min_row=3,values_only=True)]
issues=collections.defaultdict(list); ok=0; unl=[]; seen=collections.Counter(); ratios=collections.defaultdict(collections.Counter)
srcmap={'Booking.com':'Booking.com','Walk In':'Website','PMS':'Website','Google':'Website','Book On Google':'Website','Internet Booking Engine':'Website','Monthly Rental':'Website','Agoda':'Agoda','Expedia':'Expedia','Airbnb':'Airbnb','Traveloka':'Traveloka','Trip.com':'Trip.com','Ctrip':'Trip.com','CTrip':'Trip.com','Tiket.com':'Tiket.com','Long Term Rental':'Long Term Rental','Owner':'Owner'}
for r in rows:
    bid=r['Booking Id']; b=B.get(bid)
    tag=f"#{bid} {r['RES'] or '-'} {r['Ezee Folio No'] or r['Folio No.'] or '-'} {str(r['Listing Name'])[:16]} {r['Arrival']}..{r['Departure']}"
    n=int(r['Nights'] or 0); rate=num(r['Price per Night']); clean=num(r['Cleaning Fee']); cft=num(r['SST(CF)']); sst=num(r['SST']); ota=num(r['OTA']); disc=num(r['Discount']); total=num(r['Total'])
    bad=False
    if b is None: issues['row not in DB dump (status<5?)'].append(tag); continue
    if not r['RES']: unl.append(f"{tag} | {r['Reservation Source']} | rate {rate} clean {clean} | {r['Remarks']}"); continue
    if not b['TransactionId']:  # hand-keyed piece printing its sibling's RES: hotel from the sibling
        h=RESH.get(r['RES']) or RESH.get(r['RES'].split('-')[0]) or hotel_of(r['Listing Name'])
        b=dict(b, TransactionId=h+'0'*14, Start=None)  # not the first piece: cleaning check skipped
    h=b['TransactionId'][:5]; folio=str(r['Ezee Folio No'] or '')
    seen[(h,r['RES'],folio,r['Arrival'])]+=1
    line=ez.get((h,folio))
    if line is None: issues['folio not in eZee August report'].append(f"{tag}")
    else:
        if line['Reservation No'].strip()!=r['RES'].split('-')[0]: issues['folio belongs to another RES in eZee'].append(f"{tag}: eZee {line['Reservation No']}"); bad=True
        within=dmy(line['Arrival'])>='2026-08-01' and dmy(line['Dept.'])<='2026-09-01'
        if within and int(num(line['Nights']))==n and n>0:
            exp=round(num(line['All Room Charges  (RM) (Exclusive of Tax)'])/n,2)
            if abs(rate-exp)>0.02: issues['rate/night vs eZee'].append(f"{tag}: MOKA {rate:.2f} vs eZee {exp:.2f}"); bad=True
        if within and (dmy(line['Arrival'])!=str(r['Arrival']) or dmy(line['Dept.'])!=str(r['Departure'])) and int(num(line['Nights']))==n: issues['dates differ from eZee'].append(f"{tag}: eZee {dmy(line['Arrival'])}..{dmy(line['Dept.'])}"); bad=True
        want=srcmap.get(line['Source'].strip(), line['Source'].strip())
        if r['Reservation Source']!=want: issues['channel text vs eZee source'].append(f"{tag}: MOKA '{r['Reservation Source']}' vs eZee '{line['Source']}'"); bad=True
        gn=str(line['Guest Name']).strip().lower(); mn=f"{r['First Name'] or ''} {r['Last Name'] or ''}".strip().lower()
        if gn and mn and gn.split()[0] not in mn and mn.split()[0] not in gn: issues['guest name differs'].append(f"{tag}: MOKA '{mn}' vs eZee '{gn}'")
    first=(b['Start'] or '')[:10]==str(r['Arrival'])
    cm=CM.get(f"{h}|{folio}")
    if first:
        if cm is None:
            if clean>0 and str(r['Departure'])<'2026-09-01': issues['cleaning in MOKA, none in eZee'].append(f"{tag}: {clean:.2f}"); bad=True
        else:
            if abs(clean-cm['amt'])>0.05: issues['cleaning vs eZee'].append(f"{tag}: MOKA {clean:.2f} vs eZee {cm['amt']:.2f}"); bad=True
            if abs(cft-cm['tax'])>0.05: issues['SST(CF) vs eZee'].append(f"{tag}: MOKA {cft:.2f} vs eZee {cm['tax']:.2f}"); bad=True
    elif clean>0: issues['cleaning on a non-first piece'].append(f"{tag}: {clean:.2f}")
    if n>0 and abs(sst-round(rate*n*0.08,2))>0.05: issues['SST not 8% of room'].append(f"{tag}: {sst:.2f} vs {round(rate*n*0.08,2):.2f}"); bad=True
    if n==0 and b.get('TotalAmountBeforeTax'):
        if abs(total-round(num(b['TotalAmountBeforeTax'])*1.08,2))>0.05: issues['day-use total'].append(f"{tag}: {total:.2f} vs {round(num(b['TotalAmountBeforeTax'])*1.08,2):.2f}"); bad=True
    elif abs(total-round(rate*n+sst+clean+cft-disc,2))>0.05: issues['Total formula'].append(f"{tag}: {total:.2f} vs {round(rate*n+sst+clean+cft-disc,2):.2f}"); bad=True
    if rate*n>0: ratios[r['Reservation Source']][round(ota/(rate*n)*100,1)]+=1
    ch=r['Reservation Source']
    if ch=='Website' and abs(ota-round(rate*n*0.08,2))>0.05: issues['Website fee not 8% of room'].append(f"{tag}: {ota:.2f} vs {round(rate*n*0.08,2):.2f}"); bad=True
    if ch in ('Agoda','Trip.com','Tiket.com','Long Term Rental','Owner') and ota>0: issues['fee on a fee-free channel'].append(f"{tag}: {ch} {ota:.2f}"); bad=True
    if not bad: ok+=1
for k,c in seen.items():
    if c>1: issues['duplicate row (same RES, folio, arrival)'].append(f"{k} x{c}")
# completeness: eZee report lines arriving in August with revenue, missing from the export
have={((B[r['Booking Id']]['TransactionId'] or '')[:5] or RESH.get(str(r['RES']).split('-')[0],'') or hotel_of(r['Listing Name']),str(r['Ezee Folio No'])) for r in rows if r['RES'] and B.get(r['Booking Id'])}
for (h,f),d in ez.items():
    if '2026-08-01'<=dmy(d['Arrival'])<'2026-09-01' and num(d['All Room Charges  (RM) (Exclusive of Tax)'])>0 and (h,f) not in have:
        issues['in eZee report but not in export'].append(f"{files[h][:3]} {d['Reservation No']} {f} {d['Source']} {dmy(d['Arrival'])}..{dmy(d['Dept.'])} RM{d['All Room Charges  (RM) (Exclusive of Tax)']} {d['Guest Name'][:20]}")
print(f"export rows {len(rows)}; eZee-linked {len(rows)-len(unl)}; clean {ok}; hand-keyed {len(unl)}")
for k,v in issues.items():
    print(f"\n== {k}: {len(v)}")
    for l in v[:40]: print('  ',l)
print("\n== OTA fee as % of room charge, by channel (count)")
for ch,c in ratios.items(): print('  ',ch, dict(c.most_common(6)))
print("\n== hand-keyed rows (no RES)")
for l in unl: print('  ',l)
