<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sertifikat Bibit Unggul</title>

  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color-adjust: exact;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      padding: 30px 20px;
      font-family: 'EB Garamond', Georgia, "Times New Roman", serif;
    }

    .certificate {
      background: #f9f5ec;
      width: 650px;
      position: relative;
      box-shadow: 0 6px 40px rgba(0, 0, 0, 0.18);
    }

    .corner-svg {
      position: absolute;
      width: 130px;
      height: 130px;
      z-index: 1;
    }

    .corner-tl {
      top: 0;
      left: 0;
    }

    .corner-tr {
      top: 0;
      right: 0;
      transform: scaleX(-1);
    }

    .corner-bl {
      bottom: 0;
      left: 0;
      transform: scaleY(-1);
    }

    .corner-br {
      bottom: 0;
      right: 0;
      transform: scale(-1, -1);
    }

    .content {
      padding: 40px 52px 32px;
      position: relative;
      z-index: 2;
    }

    .header {
      text-align: center;
      margin-bottom: 10px;
    }

    .logo-img {
      width: 100px;
      height: 100px;
      object-fit: contain;
      display: block;
      margin: 0 auto 6px;
    }

    .farm-name {
      font-weight: 700;
      font-size: 11.5px;
      letter-spacing: 2.5px;
      color: #1a1a1a;
      text-transform: uppercase;
      line-height: 1.5;
    }

    .title-section {
      text-align: center;
      margin-bottom: 10px;
    }

    .main-title {
      font-family: Georgia, "Times New Roman", serif;
      font-size: 32px;
      font-weight: 700;
      color: #c8831a;
      letter-spacing: 0.3px;
      line-height: 1.2;
      margin-bottom: 4px;
    }

    .subtitle {
      font-size: 11px;
      color: #444;
      letter-spacing: 0.6px;
      font-weight: 400;
    }

    .meta-line {
      text-align: center;
      font-size: 11px;
      color: #333;
      border-top: 1px solid #c5bfb0;
      border-bottom: 1px solid #c5bfb0;
      padding: 7px 0;
      margin-bottom: 20px;
      letter-spacing: 0.2px;
    }

    .table-header {
      background: #2d6e3e;
      color: #fff;
      text-align: center;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 2.5px;
      padding: 10px;
      border-radius: 3px 3px 0 0;
      text-transform: uppercase;
    }

    .info-table {
      border: 1.5px solid #2d6e3e;
      border-top: none;
      border-radius: 0 0 3px 3px;
      margin-bottom: 22px;
      overflow: hidden;
    }

    .info-row {
      display: flex;
      align-items: center;
      border-bottom: 1px solid #d8ead0;
      font-size: 12.5px;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-row:nth-child(even) {
      background: #f4f8f0;
    }

    .info-label {
      width: 150px;
      padding: 10px 16px;
      font-weight: 700;
      color: #1a1a1a;
      flex-shrink: 0;
      font-size: 12.5px;
    }

    .info-colon {
      padding: 10px 6px 10px 0;
      color: #333;
    }

    .info-value {
      padding: 10px;
      color: #333;
      font-weight: 400;
    }

    .description {
      font-size: 12px;
      color: #2a2a2a;
      line-height: 1.85;
      text-align: justify;
      margin-bottom: 28px;
    }

    .sign-qr-section {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 48px;
      margin-bottom: 22px;
    }

    .signature-block {
      text-align: center;
    }

    .sign-date {
      font-weight: 400;
      font-size: 12px;
      letter-spacing: 0.2px;
      color: #111;
      margin-bottom: 6px;
    }

    .sign-img-wrap {
      height: 90px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 4px;
    }

    .sign-img {
      max-height: 90px;
      max-width: 180px;
      object-fit: contain;
      mix-blend-mode: multiply;
    }

    .sign-name {
      font-weight: 400;
      font-size: 12px;
      color: #111;
      margin-bottom: 2px;
    }

    .sign-role {
      font-size: 9px;
      color: #666;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      line-height: 1.5;
    }

    .qr-block {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 7px;
    }

    .qr-wrapper {
      border: 2px solid #2d6e3e;
      padding: 6px;
      border-radius: 5px;
      background: #fff;
      display: inline-block;
    }

    .qr-label {
      font-size: 9px;
      color: #666;
      letter-spacing: 1.2px;
      text-transform: uppercase;
    }

    .footer-line {
      border: none;
      border-top: 1px solid #c5bfb0;
      margin-bottom: 10px;
    }

    .footer {
      text-align: center;
      font-size: 10px;
      color: #888;
      font-style: italic;
      padding-bottom: 4px;
    }

    @page {
      size: 650px 900px;
      margin: 0;
    }

    @media print {
      html,
      body {
        width: 650px;
        min-height: 0;
        height: auto;
        padding: 0;
        background: #ffffff;
      }

      body {
        align-items: flex-start;
        justify-content: center;
      }

      .certificate {
        width: 650px;
        min-height: 900px;
        box-shadow: none;
        margin: 0 auto;
      }
    }
  </style>
</head>

<body>

  <div class="certificate">

    <svg class="corner-svg corner-tl" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 130 Q0 0 130 0" stroke="#2d6e3e" stroke-width="28" fill="none" stroke-linecap="round" />
      <path d="M0 130 Q0 0 130 0" stroke="#5aab6e" stroke-width="12" fill="none" stroke-linecap="round" opacity="0.5" />
    </svg>
    <svg class="corner-svg corner-tr" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 130 Q0 0 130 0" stroke="#2d6e3e" stroke-width="28" fill="none" stroke-linecap="round" />
      <path d="M0 130 Q0 0 130 0" stroke="#5aab6e" stroke-width="12" fill="none" stroke-linecap="round" opacity="0.5" />
    </svg>
    <svg class="corner-svg corner-bl" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 130 Q0 0 130 0" stroke="#2d6e3e" stroke-width="28" fill="none" stroke-linecap="round" />
      <path d="M0 130 Q0 0 130 0" stroke="#5aab6e" stroke-width="12" fill="none" stroke-linecap="round" opacity="0.5" />
    </svg>
    <svg class="corner-svg corner-br" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 130 Q0 0 130 0" stroke="#2d6e3e" stroke-width="28" fill="none" stroke-linecap="round" />
      <path d="M0 130 Q0 0 130 0" stroke="#5aab6e" stroke-width="12" fill="none" stroke-linecap="round" opacity="0.5" />
    </svg>

    <div class="content">

      <div class="header">
        <img
          class="logo-img"
          src="{{ $assets['logo'] ?? url('/images/logo-bbh.png') }}"
          alt="Logo BBH Farm">
        <div class="farm-name">Bumiku Bumimu Hijau Farm</div>
      </div>

      <div class="title-section">
        <div class="main-title">Sertifikat Bibit Unggul</div>
        <div class="subtitle">Superior Livestock Breeding Certificate</div>
      </div>

      <div class="meta-line">
        No. Sertifikat: <strong>{{ $data['certificate_number'] }}</strong>
        &nbsp; | &nbsp;
        Diterbitkan: <strong>{{ $data['issue_date_full'] }}</strong>
      </div>

      <div class="table-header">Identitas Ternak</div>

      <div class="info-table">
        <div class="info-row">
          <div class="info-label">Nomor Tag</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_tag'] }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Jenis Kelamin</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_sex'] }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Ras / Generasi</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_generation_breed'] }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Tanggal Lahir</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_birth_date_full'] }} / {{ $data['animal_birth_place'] }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Status Reproduksi</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_reproductive_status'] }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Asal</div>
          <div class="info-colon">:</div>
          <div class="info-value">{{ $data['animal_source'] }}</div>
        </div>
      </div>

      <div class="description">
        Sertifikat ini menyatakan bahwa ternak dengan identitas sebagaimana tercantum
        pada dokumen ini merupakan ternak yang terdaftar dalam sistem pencatatan
        Bumiku Bumimu Hijau Farm. Sertifikat bibit unggul ternak dapat diverifikasi melalui
        QR code yang tersedia pada dokumen ini.
      </div>

      <div class="sign-qr-section">

        <div class="signature-block">
          <div class="sign-date">{{ $data['issue_day_date'] }}</div>
          <div class="sign-img-wrap">
            <img
              class="sign-img"
              src="{{ $assets['signature'] ?? url('/images/ttd-manajer.png') }}"
              alt="Tanda Tangan">
          </div>
          <div class="sign-name">Maalikul Mulki, S.Pt</div>
          <div class="sign-role">Manajer Bumiku Bumimu Hijau Farm</div>
        </div>

        <div class="qr-block">
          <div class="qr-wrapper">
            <img src="data:image/svg+xml;base64,{{ $qr }}" width="90" alt="QR Code Verifikasi">
          </div>
          <div class="qr-label">Scan untuk verifikasi</div>
        </div>

      </div>

      <hr class="footer-line">

      <div class="footer">
        Dokumen ini ditandatangani secara digital. Perubahan visual atau data sekecil apa pun dapat terdeteksi melalui proses verifikasi validitas dokumen.
      </div>

    </div>
  </div>

</body>

</html>
