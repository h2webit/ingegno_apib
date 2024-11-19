// Prefisso EAN per l'Italia
const ITALY_PREFIX = '80';

function generateItalianEAN13() {
    // Genera la parte centrale del codice (7 cifre)
    const randomPart = Math.floor(Math.random() * 10000000).toString().padStart(7, '0');

    // Genera il codice produttore (3 cifre)
    const manufacturerCode = Math.floor(Math.random() * 1000).toString().padStart(3, '0');

    // Combina prefix + manufacturer code + random part
    const ean12 = ITALY_PREFIX + manufacturerCode + randomPart;

    // Calcola il checksum
    const checksum = calculateChecksum(ean12);

    return ean12 + checksum;
}

function calculateChecksum(ean) {
    ean = '' + ean;
    if (!ean || ean.length !== 12) {
        throw new Error('EAN-13 non valido, deve avere 12 cifre: ' + ean.length);
    }

    let sum = 0;
    for (let i = 0; i < 12; i++) {
        const digit = parseInt(ean[i], 10);
        // Moltiplica per 1 se posizione pari, per 3 se dispari
        sum += digit * (i % 2 === 0 ? 1 : 3);
    }

    const checksum = (10 - (sum % 10)) % 10;
    return checksum;
}

// Inizializzazione jQuery
$(() => {
    $('.js-barcode_add').on('click', function () {
        const barcode_container = $('.js-barcode_container').first().clone();
        $(':input', barcode_container).val('');
        $('.js-barcode_container').last().after(barcode_container);
    });

    const barcodes_container = $('.js-barcodes_container');

    barcodes_container.on('click', '.js-create_barcode', function () {
        const barcode_container = $(this).closest('.js-barcode_container');

        try {
            const barcode = generateItalianEAN13();
            $('.fw_products_barcode', barcode_container).val(barcode);
        } catch (error) {
            console.error('Errore nella generazione del codice EAN-13:', error);
            alert('Errore nella generazione del codice a barre: ' + error.message);
        }
    });
});