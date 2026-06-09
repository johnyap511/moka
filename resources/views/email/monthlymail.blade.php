<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #14b8a6; padding: 28px 32px; text-align: center; }
  .header img { height: 40px; }
  .header h1 { color: #fff; margin: 12px 0 0; font-size: 18px; font-weight: 600; }
  .body { padding: 32px; color: #374151; font-size: 14px; line-height: 1.7; }
  .body h2 { font-size: 16px; font-weight: 600; margin: 0 0 12px; color: #111827; }
  .highlight { background: #f0fdf4; border-left: 4px solid #14b8a6; padding: 12px 16px; border-radius: 4px; margin: 20px 0; font-size: 13px; }
  .footer { background: #f9fafb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
  .footer a { color: #14b8a6; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Monthly Property Performance Report</h1>
  </div>
  <div class="body">
    <h2>Dear {{ $data22['user_name'] ?? 'Property Owner' }},</h2>
    <p>Please find attached your <strong>Monthly Property Performance Report</strong> for your unit managed by Moka.</p>
    <div class="highlight">
      The report includes a full breakdown of:<br>
      &bull; Booking revenue for the month<br>
      &bull; OTA/channel fees<br>
      &bull; Utility charges (water, electricity, internet, etc.)<br>
      &bull; Net profit calculation
    </div>
    <p>The PDF report is attached to this email. Please review the details and contact us if you have any questions.</p>
    <p>Thank you for partnering with Moka. We look forward to continuing to serve you.</p>
    <p style="margin-top:24px">Best regards,<br><strong>Moka Property Management Team</strong><br>
    <a href="https://www.homemoka.com">www.homemoka.com</a> &nbsp;|&nbsp; hello@homemoka.com</p>
  </div>
  <div class="footer">
    &copy; {{ date('Y') }} Moka Venture Sdn Bhd &nbsp;&bull;&nbsp; 11.1, Menara Lien Hoe, 8, Persiaran Tropicana, 47410 Petaling Jaya<br>
    This is a computer-generated email. Please do not reply directly to this message.
  </div>
</div>
</body>
</html>
