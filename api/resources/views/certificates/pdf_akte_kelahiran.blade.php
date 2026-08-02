<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Akta Kelahiran Ternak</title>
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

        .med-table th {
            border: 0.9px solid #222;
            padding: 7px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            background: #fff;
            color: #111;
        }

        .med-table th .en {
            display: block;
            font-size: 9px;
            font-weight: normal;
            font-style: italic;
            color: #555;
            margin-top: 1px;
        }

        .med-table td {
            color: #111;
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
                Akta Kelahiran Ternak
                <span class="en">Livestock Birth Certificate</span>
            </h1>
            <p>Bumiku Bumimu Hijau Farm</p>
            <p style="margin-top:8px; font-size:10.5px;">
                No. Sertifikat / <em>Certificate No.</em> :
                <strong>{{ $data['certificate_number'] }}</strong>
            </p>
        </div>

        <div class="section-title">
            Informasi Kelahiran <span class="en">/ Birth Information</span>
        </div>

        <table>
            <tr>
                <td class="label">Tanggal <span class="en">Date</span></td>
                <td class="value">{{ $data['birth_event_date'] }}</td>
            </tr>
            <tr>
                <td class="label">Pukul <span class="en">Time</span></td>
                <td class="value">{{ $data['birth_event_time'] }}</td>
            </tr>
            <tr>
                <td class="label">Proses Kelahiran <span class="en">Birthing Process</span></td>
                <td class="value">{{ $data['birth_process'] }}</td>
            </tr>
        </table>

        <div class="section-title">
            Data Pejantan & Induk <span class="en">/ Sire & Dam Data</span>
        </div>

        <table>
            <tr>
                <td class="label">Nomor Tag Jantan <span class="en">Sire Tag Number</span></td>
                <td class="value">{{ $data['sire_tag'] }} {{ $data['sire_generation_breed'] }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Tag Induk <span class="en">Dam Tag Number</span></td>
                <td class="value">{{ $data['dam_tag'] }} {{ $data['dam_generation_breed'] }}</td>
            </tr>
        </table>

        <div class="section-title">
            Data Anak yang Dilahirkan <span class="en">/ Offspring Data</span>
        </div>

        <table>
            <tr>
                <td class="label">Jenis Kelamin <span class="en">Sex</span></td>
                <td class="value">{{ $data['animal_sex'] }}</td>
            </tr>
            <tr>
                <td class="label">Berat Badan Lahir <span class="en">Birth Weight</span></td>
                <td class="value">{{ $data['birth_weight_kg'] }} Kg</td>
            </tr>
            <tr>
                <td class="label">Grade Anak <span class="en">Offspring Grade</span></td>
                <td class="value">{{ $data['offspring_grade'] }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal / Tempat Lahir Anak <span class="en">Offspring Birth Date / Place</span></td>
                <td class="value">{{ $data['animal_birth_date'] }} / {{ $data['animal_birth_place'] }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Tag <span class="en">Tag Number</span></td>
                <td class="value">
                    {{ $data['animal_tag'] }}
                </td>
            </tr>
            <tr>
                <td class="label">Ras / Generasi <span class="en">Breed / Generation</span></td>
                <td class="value">{{ $data['animal_generation_breed'] }}</td>
            </tr>
            <tr>
                <td class="label">Status Anak <span class="en">Offspring Status</span></td>
                <td class="value">{{ $data['animal_life_status'] }}</td>
            </tr>
        </table>

        <div class="section-title">
            Perawatan Pasca Lahir <span class="en">/ Postnatal Care</span>
        </div>

        <table class="med-table">
            <thead>
                <tr>
                    <th>Jenis Perawatan <span class="en">Type of Care</span></th>
                    <th>Keterangan <span class="en">Description</span></th>
                    <th>Volume / Dosis <span class="en">Volume / Dose</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['postnatal_cares'] as $care)
                <tr>
                    <td>{{ $care['care_name'] }}</td>
                    <td>{{ $care['administration_method'] }}</td>
                    <td>{{ $care['dose'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align:center;">
                        Tidak ada data perawatan pasca lahir
                    </td>
                </tr>
                @endforelse
            </tbody>
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

