export const initPublicPdfInputs = () => {
    document.querySelectorAll('[data-pdf-only]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;

            const form = input.closest('form');
            const wrapper = form?.parentElement;
            const filenameLabel = wrapper?.querySelector('[data-pdf-filename]');
            const errorLabel = wrapper?.querySelector('[data-pdf-error]');
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (errorLabel) {
                errorLabel.textContent = '';
                errorLabel.classList.add('hidden');
            }

            if (!isPdf) {
                input.value = '';
                if (filenameLabel) {
                    filenameLabel.textContent = '';
                    filenameLabel.classList.add('hidden');
                }
                if (errorLabel) {
                    errorLabel.textContent = 'Peringatan: Berkas tidak dapat digunakan. Pilih dokumen sertifikat dalam format PDF.';
                    errorLabel.classList.remove('hidden');
                }
                return;
            }

            if (filenameLabel) {
                filenameLabel.textContent = `PDF terpilih: ${file.name}`;
                filenameLabel.classList.remove('hidden');
            }
        });
    });
};
