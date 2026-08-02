<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Akta Kematian Ternak</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12px;
            background: #fff;
            color: #111;
            padding: 24px;
        }

        .page {
            width: 700px;
            margin: 0 auto;
            background: #fff;
            border: 0;
            padding: 24px 32px;
        }

        .header {
            text-align: center;
            border-bottom: 1.6px solid #111;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .header h1 .en {
            display: block;
            font-size: 12px;
            font-weight: normal;
            font-style: italic;
            text-transform: none;
            letter-spacing: 0;
            color: #555;
            margin-top: 2px;
        }

        .header p {
            font-size: 12px;
            margin-top: 3px;
        }

        .section-title {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1.4px solid #111;
            padding-bottom: 5px;
            margin: 12px 0 7px;
            letter-spacing: 0.5px;
            color: #000;
        }

        .section-title .en {
            font-size: 9px;
            font-weight: normal;
            font-style: italic;
            text-transform: none;
            letter-spacing: 0;
            color: #555;
            margin-left: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 7px 10px;
            border: 0.9px solid #222;
            vertical-align: top;
        }

        td.label {
            width: 50%;
            font-weight: 700;
            color: #222;
            background: #fff;
        }

        td.label .en {
            display: block;
            font-size: 9px;
            font-style: italic;
            color: #666;
            margin-top: 1px;
        }

        td.value {
            font-weight: 600;
            color: #222;
        }

        .footer {
            margin-top: 16px;
            text-align: right;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .sig-block {
            display: inline-block;
            text-align: center;
            width: 220px;
        }

        .sig-block .date {
            font-size: 12px;
            margin-bottom: 3px;
        }

        .sig-img-wrap {
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
        }

        .sig-img {
            max-height: 46px;
            max-width: 205px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .sig-block .line {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .sig-block .role {
            font-size: 10px;
            margin-top: 2px;
            font-style: italic;
        }

        @page {
            size: 700px 1040px;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 700px;
                min-height: 0;
                height: auto;
                padding: 0;
                background: #ffffff;
            }

            body {
                align-items: flex-start;
                justify-content: center;
            }

            .page {
                width: 700px;
                min-height: 0;
                box-shadow: none;
                margin: 0 auto;
            }
        }
    </style>
</head>

<body>

    <div class="page">

        <div class="header">
            <h1>
                Akta Kematian Ternak
                <span class="en">Livestock Death Certificate</span>
            </h1>
            <p>Bumiku Bumimu Hijau Farm</p>
            <p style="margin-top:8px; font-size:10.5px;">
                No. Sertifikat / <em>Certificate No.</em> :
                <strong>{{ $data['certificate_number'] }}</strong>
            </p>
        </div>

        <div class="section-title">
            Data Hewan <span class="en">/ Animal Data</span>
        </div>

        <table>
            <tr>
                <td class="label">
                    Nomor Tag <span class="en">Tag Number</span>
                </td>
                <td class="value">{{ $data['animal_tag'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Ras / Generasi <span class="en">Breed / Generation</span>
                </td>
                <td class="value">{{ $data['animal_generation_breed'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Jenis Kelamin <span class="en">Sex</span>
                </td>
                <td class="value">{{ $data['animal_sex'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Tanggal / Tempat Lahir <span class="en">Birth Date / Place</span>
                </td>
                <td class="value">{{ $data['animal_birth_date'] }} / {{ $data['animal_birth_place'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Koloni / Status Reproduksi <span class="en">Colony / Reproductive Status</span>
                </td>
                <td class="value">{{ $data['animal_current_pen'] }} / {{ $data['animal_reproductive_status'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Asal <span class="en">Source</span>
                </td>
                <td class="value">{{ $data['animal_source'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Status Hidup Hewan <span class="en">Life Status</span>
                </td>
                <td class="value">{{ $data['animal_life_status'] }}</td>
            </tr>
        </table>

        <div class="section-title">
            Informasi Kematian <span class="en">/ Death Information</span>
        </div>

        <table>
            <tr>
                <td class="label">
                    Tanggal Kematian <span class="en">Date of Death</span>
                </td>
                <td class="value">{{ $data['death_date'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Jam Kematian <span class="en">Time of Death</span>
                </td>
                <td class="value">{{ $data['death_time'] }}</td>
            </tr>

            <tr>
                <td class="label">
                    Penyebab Kematian <span class="en">Cause of Death</span>
                </td>
                <td class="value">{{ $data['cause_of_death'] }}</td>
            </tr>
        </table>

        <div class="footer">
            <div class="sig-block">
                <div class="date">
                    Diterbitkan / <em>Issued</em>, <span style="white-space: nowrap;">{{ $data['issue_date'] }}</span>
                </div>
                <div class="sig-img-wrap">
                    <img
                        class="sig-img"
                        src="{{ $assets['signature'] ?? url('/images/ttd-manajer.png') }}"
                        alt="Tanda Tangan">
                </div>
                <div class="line">Maalikul Mulki, S.Pt</div>
                <div class="role">Manajer Bumiku Bumimu Hijau Farm</div>
            </div>
        </div>

    </div>

</body>

</html>

