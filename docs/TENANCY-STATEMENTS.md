# Tenancy settlement statements (Finance)

Admin → Finance → **Tenancy statements**. Replaces the stand-alone "Moka Tenancy Bill
Calculator" HTML file (15 Oct 2026). Same calculation, same PDF; now numbered and kept.

## Who
- `tenancy.manage` (issue, void): Finance Team, Finance Manager, Admin.
- `tenancy.view` (list, open, download PDF): the above plus Operation Manager.

## How Finance uses it
1. **+ New statement**. Key in tenant, booking ref, property; one block per unit with the
   dates the tenant was in *that* unit, and that unit's bills with the period printed on
   each bill. Fixed charges are qty × rate (Indah Water months fill in from the stay).
   Deposit held turns the answer into "refund due" or "still owed".
2. The right-hand card previews live. Orange warnings (bill period not touching the stay,
   missing period, dates backwards) block finalising.
3. **Finalise, store and download PDF**. The server recomputes every line (source of
   truth), gives the statement the next number `MKS-YYYY-NNNN`, stores the inputs and
   computed lines in `tenancy_statements`, then the browser draws the PDF from the
   server's figures, uploads it once (`storage/app/tenancy-statements/YYYY/NO.pdf`,
   size + sha1 recorded) and downloads it. A statement is final after this step.
4. Wrong statement? Open it → **Void this statement** (reason required). It stays on file
   marked VOID. **Copy as new statement** pre-fills a fresh calculator from its inputs.
5. If the PDF upload failed (Missing PDF tile on the list), open the statement and press
   **Create and store the PDF**: it is re-drawn from the stored figures, so it is identical.

## Rules the calculation follows
- Billing period in whole days, both ends included; occupied days = overlap of the bill
  period with that unit's dates (check-out day charged unless "per night" is unticked).
- Share = bill ÷ period days × occupied days, rounded to the sen per line.
- Fixed charges never split; Indah Water = calendar months touched × rate (RM12 default).
- Rounding uses PHP `round(x, 2)`; the preview uses the same half-up rule.

## Code
- `App\Support\TenancyBill::compute()` / `blocker()` — the calculation and the issue gate.
- `Admin\TenancyStatementController` — index, create, store (JSON), attachPdf, show,
  download, void. `App\Models\TenancyStatement`.
- Views `admin/tenancy/{index,create,show,_pdfjs}.blade.php`; the PDF is jsPDF 2.5.1
  from cdnjs, drawn by `window.MokaStatementPDF(company, data, no, issued)`.
- Company block for the PDF masthead: `config('moka.company')`.
- Audit: `data_logs` rows "Tenancy statement issued" / "voided".
- Backups: the table is in the nightly dump; the PDFs are under `storage/app`, which the
  lock tar includes.
