export const initPublicPdfInputs = () => {
    document.querySelectorAll('[data-pdf-only]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;

            const form = input.closest('form');
            const certificateInput = form?.querySelector('[data-certificate-input]');
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (!isPdf) {
                input.value = '';
                if (certificateInput) {
                    certificateInput.value = '';
                    certificateInput.placeholder = 'Nomor sertifikat';
                }
                alert('Peringatan: Berkas tidak dapat digunakan. Pilih dokumen sertifikat dalam format PDF.');
                return;
            }

            if (certificateInput) {
                certificateInput.value = file.name;
                certificateInput.dataset.pdfFilename = file.name;
            }
        });
    });

    document.querySelectorAll('form[enctype="multipart/form-data"]').forEach((form) => {
        form.addEventListener('submit', () => {
            const fileInput = form.querySelector('[data-pdf-only]');
            const certificateInput = form.querySelector('[data-certificate-input]');

            if (fileInput?.files?.[0] && certificateInput?.value === certificateInput?.dataset?.pdfFilename) {
                certificateInput.disabled = true;
            }
        });
    });
};
