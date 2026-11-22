import { Controller } from '@hotwired/stimulus';

/*
 * Builds sample files in the browser and hands them to file inputs, so a demo
 * works without searching the disk for a PDF or a photo.
 *
 * Files are deterministic: the same button always produces the same bytes, name,
 * and modification time. The Resumable demo relies on it to resume a transfer
 * after a reload.
 */

const NAMES = {
    pdf: ['world-domination-plan.pdf'],
    images: [
        'elephpant-family.jpg',
        'conference-badge.jpg',
        'hallway-track.jpg',
        'keynote-front-row.jpg',
        'speaker-dinner.jpg',
        'afterparty-blurry.jpg',
    ],
    large: ['dinosaur-dna-backup.zip'],
};

const CRC_TABLE = Uint32Array.from({ length: 256 }, (_, byte) => {
    let value = byte;
    for (let bit = 0; bit < 8; bit++) {
        value = value & 1 ? 0xedb88320 ^ (value >>> 1) : value >>> 1;
    }

    return value;
});

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        kind: String, // pdf, images, or large
        for: String, // CSS selector of the file inputs to fill
        size: { type: Number, default: 800 * 1024 }, // of the first file
        count: { type: Number, default: 3 }, // images taken from the default names
        names: Array, // one file per name, instead of the default names of the kind
    };

    async fill(event) {
        const button = event?.currentTarget;
        button?.setAttribute('aria-busy', 'true');
        if (button) button.disabled = true;

        try {
            const files = await this.#build();
            document.querySelectorAll(this.forValue).forEach((input) => {
                const transfer = new DataTransfer();
                (input.multiple ? files : files.slice(0, 1)).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        } finally {
            button?.removeAttribute('aria-busy');
            if (button) button.disabled = false;
        }
    }

    #build() {
        const named = this.namesValue.length > 0;
        const names = named ? this.namesValue : NAMES[this.kindValue];

        switch (this.kindValue) {
            case 'pdf':
                return Promise.resolve(names.map((name, index) => this.#pdf(name, index)));
            case 'images':
                return Promise.all(
                    (named ? names : names.slice(0, this.countValue)).map((name, index) => this.#image(name, index))
                );
            case 'large':
                return Promise.resolve([this.#zip(names[0])]);
            default:
                throw new Error(`Unknown sample kind "${this.kindValue}".`);
        }
    }

    // A valid PDF header, padded with comment lines. Each file after the first is smaller.
    #pdf(name, index) {
        const size = Math.round(this.sizeValue / (1 + index * 1.5));
        const head = '%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n';
        const tail = '\ntrailer << /Root 1 0 R >>\n%%EOF\n';
        const line = '% UX Upload sample document\n';
        const padding = line.repeat(Math.max(0, Math.ceil((size - head.length - tail.length) / line.length)));

        return new File([head, padding, tail], name, {
            type: 'application/pdf',
            lastModified: Date.UTC(2026, 0, 1),
        });
    }

    #image(name, index) {
        const canvas = document.createElement('canvas');
        canvas.width = 960;
        canvas.height = 640;
        const context = canvas.getContext('2d');
        const hue = (index * 67 + 210) % 360;

        context.fillStyle = `hsl(${hue} 55% 35%)`;
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = 'rgb(255 255 255 / 18%)';
        for (let circle = 0; circle < 5; circle++) {
            context.beginPath();
            context.arc(120 + circle * 190, 420 - (circle % 2) * 180, 70 + circle * 18, 0, Math.PI * 2);
            context.fill();
        }

        return new Promise((resolve) => {
            canvas.toBlob(
                (blob) =>
                    resolve(
                        new File([blob], name, {
                            type: 'image/jpeg',
                            lastModified: Date.UTC(2026, 0, 1),
                        })
                    ),
                'image/jpeg',
                0.85
            );
        });
    }

    /*
     * A real archive of the requested size, so the server detects application/zip:
     * one stored entry of pseudo-random bytes from a fixed seed, incompressible and
     * identical on every click.
     */
    #zip(name) {
        const entry = new TextEncoder().encode('dinosaur-dna.bin');
        const data = new Uint8Array(this.sizeValue - (30 + entry.length) - (46 + entry.length) - 22);
        const words = new Uint32Array(data.buffer, 0, Math.floor(data.length / 4));
        let seed = 0x5f3759df;
        for (let index = 0; index < words.length; index++) {
            seed = (seed + 0x6d2b79f5) | 0;
            let value = Math.imul(seed ^ (seed >>> 15), 1 | seed);
            value = (value + Math.imul(value ^ (value >>> 7), 61 | value)) ^ value;
            words[index] = value ^ (value >>> 14);
        }

        let crc = 0xffffffff;
        for (let index = 0; index < data.length; index++) {
            crc = CRC_TABLE[(crc ^ data[index]) & 0xff] ^ (crc >>> 8);
        }
        crc = (crc ^ 0xffffffff) >>> 0;

        const date = ((2026 - 1980) << 9) | (1 << 5) | 1; // 2026-01-01, MS-DOS format
        const header = (signature, fields) => {
            const view = new DataView(new ArrayBuffer(4 + fields.reduce((length, [bytes]) => length + bytes, 0)));
            view.setUint32(0, signature, true);
            let offset = 4;
            for (const [bytes, value] of fields) {
                bytes === 2 ? view.setUint16(offset, value, true) : view.setUint32(offset, value, true);
                offset += bytes;
            }

            return view.buffer;
        };
        // version, flags, method (0: stored), time, date, CRC, compressed size, size, name length, extra length
        const file = [[2, 20], [2, 0], [2, 0], [2, 0], [2, date], [4, crc], [4, data.length], [4, data.length], [2, entry.length], [2, 0]];
        const local = header(0x04034b50, file);
        // made by, then the same fields, comment length, disk, internal and external attributes, local header offset
        const central = header(0x02014b50, [[2, 20], ...file, [2, 0], [2, 0], [2, 0], [4, 0], [4, 0]]);
        // disk, directory disk, entries on disk, entries, directory size, directory offset, comment length
        const end = header(0x06054b50, [[2, 0], [2, 0], [2, 1], [2, 1], [4, 46 + entry.length], [4, 30 + entry.length + data.length], [2, 0]]);

        return new File([local, entry, data, central, entry, end], name, {
            type: 'application/zip',
            lastModified: Date.UTC(2026, 0, 1),
        });
    }
}
