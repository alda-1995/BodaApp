import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'
import fs from 'node:fs';

// Cada plantilla trae su propio CSS y JS; entran solos siguiendo la convención
// resources/{css,js}/templates/{plantilla}/{template.css,index.js}.
const templateAssets = (type, file) => {
    const base = `resources/${type}/templates`;

    if (!fs.existsSync(base)) {
        return [];
    }

    return fs.readdirSync(base, { withFileTypes: true })
        .filter((entry) => entry.isDirectory() && fs.existsSync(`${base}/${entry.name}/${file}`))
        .map((entry) => `${base}/${entry.name}/${file}`);
};

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                // Panel (Tailwind).
                'resources/css/app.css',
                'resources/js/app.js',
                // Invitaciones (CSS3 propio de cada plantilla).
                ...templateAssets('css', 'template.css'),
                ...templateAssets('js', 'index.js'),
            ],
            refresh: true,
        }),
    ],
});
