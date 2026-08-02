<?php

return [
    'nav' => [
        'home' => 'Home',
        'about' => 'About',
        'standards' => 'Production Standards',
        'activities' => 'Activities',
        'verification' => 'Verification',
        'certificate' => 'Certificate',
        'location' => 'Location',
        'login' => 'Admin Login',
    ],
    'hero' => [
        'title' => 'An Integrated Modern Breeding Center for Superior Dairy Goats',
        'copy' => 'We produce high-quality dairy goat breeding stock with strong genetic traits through an integrated farming system, while also providing livestock management training programs.',
        'goat_alt' => 'Dairy goats at Bumiku Bumimu Hijau Farm',
    ],
    'core' => [
        'title' => 'Producing high-quality dairy goat breeding stock with trusted superior genetics',
        'points' => [
            ['Superior & Selected Genetics', 'Each dairy goat kid comes from selected parent stock with a clearly documented pedigree to support high productivity.'],
            ['Hygiene & Barn Health', 'Livestock are raised in a clean, modern farming system with strict biosecurity standards and quality feed management.'],
            ['Management Education & Training', 'We actively share livestock management knowledge with farmers, from breeding practices to intensive care management.'],
        ],
    ],
    'production' => [
        'title' => 'Commitment & Production Standards',
        'cards' => [
            ['Premium Dairy Genetics', 'Focused on developing selected dairy goat lines with strong resilience to support sustainable livestock production.', 'genetic'],
            ['Integrated Farming', 'Applying an end-to-end management system that connects forage cultivation, intensive livestock care, and environmental stewardship.', 'integrated'],
            ['Education & Training Center', 'Providing modern livestock management training programs for farmer groups and local breeders to improve competitiveness.', 'education'],
        ],
    ],
    'cta' => [
        'title' => '#TogetherWithBBH Developing Local Potential',
        'visit_message' => 'Hello BBH Farm Admin, we are planning a visit to the farm facility in Ajibarang. What is the procedure for submitting an official visit request?',
        'partnership_message' => 'Hello BBH Farm Admin, I would like to consult about ordering superior dairy goat breeding stock / joining a modern farm management training program.',
        'cards' => [
            ['Visit the Farm', 'Explore the barn area and dairy goat care activities directly for comparative studies or institutional visits.', 'Visit Details', 'visit'],
            ['Build a Partnership', 'Opening collaboration opportunities related to superior breeding stock orders, modern farm training programs, and local potential development.', 'Contact BBH', 'partnership'],
            ['Verify Certificate', 'Check the authenticity and integrity of official Bumiku Bumimu Hijau Farm documents. Each certificate uses cryptographic technology to validate data and prevent forgery.', 'Start Verification', 'verification'],
        ],
    ],
    'gallery' => [
        'title' => 'Activities at Bumiku Bumimu Hijau Farm begin with',
        'previous' => 'Previous activity',
        'next' => 'Next activity',
        'position' => 'Gallery position',
        'items' => [
            ['bbh-feed-management-enhanced.webp', 'Independent Feed Management', 'High-quality feed formulation is monitored regularly to support parent stock health and productivity.', 'object-[50%_58%]'],
            ['hero-landing-app.webp', 'Hygienic Raised Barns', 'Applying modern barn management with strict sanitation and biosecurity to support livestock comfort.', 'object-[51%_52%]'],
            ['bbh-kid-care-enhanced.webp', 'Premium Genetic Selection', 'Intensive early care to ensure the next generation of dairy goat breeding stock is resilient and superior.', 'object-[50%_62%]'],
        ],
    ],
    'ecosystem' => [
        'title' => 'We integrate agriculture and livestock systems for sustainable results',
        'cards' => [
            ['#00aa13', '#008c15', 'Livestock Ecosystem', ['Dairy Goat Development', 'Productive Parent Stock Management', 'Independent Forage Feed Management', 'Strict Sanitation & Biosecurity'], 'Livestock Governance'],
            ['#ee2737', '#af272f', 'Integrated Agriculture', ['Zero-Waste System Concept', 'Liquid Organic Fertilizer Utilization', 'Natural Solid Fertilizer Utilization', 'Sustainable Agriculture Synergy'], 'Organic Agriculture Synergy'],
            ['#93328e', '#80276c', 'Education & Technology', ['Farmer Management Training', 'Farmer Group Education Program', 'Cryptographic Digital Certificates', 'Document Verification System'], 'Training & Technology Center'],
        ],
    ],
    'verification' => [
        'title' => 'Verify Bumiku Bumimu Hijau Farm documents with ease',
        'copy' => 'Enter the certificate number or choose an official PDF file to view the document verification result.',
        'placeholder' => 'Enter certificate number or choose a PDF file for verification',
        'browse' => 'Browse PDF',
        'button' => 'Verify Now',
        'failed' => 'Failed',
    ],
    'location_page' => [
        'meta_title' => 'Location',
        'title' => 'Bumiku Bumimu Hijau Farm Location',
        'farm_name' => 'Bumiku Bumimu Hijau Farm Ajibarang',
        'address' => 'Darmakradenan Village, Ajibarang District, Banyumas Regency, Central Java',
        'maps_link' => 'Open in Google Maps',
    ],
    'certificate_page' => [
        'meta_title' => 'Electronic Certificate',
        'title' => 'About electronic certificates and digital signatures',
        'intro' => 'An electronic certificate is a digital document issued to formally state certain information. In the BBH Farm system, electronic certificates support the digital issuance of livestock certificates, so data validity and integrity can be verified through the system.',
        'paragraphs' => [
            'A digital signature is an electronic signature that uses cryptographic methods to validate a digital document. It is not a scanned or computer-drawn physical signature placed on a document, but a mathematical result generated using public key cryptography over the signed digital data.',
            'There are two main activities in the digital signature process:',
        ],
        'sign_verify' => [
            ['term' => 'Sign', 'copy' => 'the process of applying a digital signature to a certificate or digital document.'],
            ['term' => 'Verify', 'copy' => 'the process of determining whether the digital signature on a certificate or digital document remains valid.'],
        ],
        'goals_intro' => 'The following are the objectives of applying digital signatures to electronic certificates:',
        'goals' => [
            ['term' => 'Authentication', 'copy' => 'ensures that the certificate was truly issued by an authorized party or system.'],
            ['term' => 'Integrity', 'copy' => 'ensures that the certificate content has not changed after being signed. If certificate data or the document changes, the verification result becomes invalid.'],
            ['term' => 'Non-repudiation', 'copy' => 'ensures that the issuer cannot deny that the certificate was issued and signed by the system.'],
        ],
        'closing' => [
            'In the BBH Farm system, every issued certificate is signed digitally so the validity of its data and PDF document can be checked again. If the certificate data or PDF file changes after issuance, the verification result will show that the certificate is invalid.',
            'Digital signatures can only be verified through digital data or documents. If a certificate is printed, the digital signature on the printed document cannot be checked directly. However, the certificate can include a QR code that leads to the verification page, where users can check the status of superior breeding livestock certificates issued by BBH Farm.',
        ],
        'technical_title' => 'Technical Specifications',
        'technical_items' => [
            'Algorithm: RSA-SHA256',
            'Key length: 2048-bit',
            'Verification media: certificate number, QR code, and PDF file',
        ],
        'verification_link' => 'Start Verification',
    ],
    'footer' => [
        'farm' => 'Farm',
        'services' => 'Services',
        'contact' => 'Contact us',
        'location' => 'Our Location',
        'contact_link' => 'Contact',
        'copyright' => 'Bumiku Bumimu Hijau Farm',
        'contact_message' => 'Hello BBH Farm Admin, I would like to contact Bumiku Bumimu Hijau Farm.',
    ],
];
