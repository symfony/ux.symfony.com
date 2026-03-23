import resolve from '@rollup/plugin-node-resolve';
import terser from '@rollup/plugin-terser';

export default [
    {
        input: {
            'svelte.index': 'lib/svelte.js',
            'internal/client': 'lib/internal.js',
        },
        output: {
            dir: '../../vendor/svelte',
            format: 'esm',
            entryFileNames: '[name].js',
            chunkFileNames: '[name].js',
        },
        plugins: [
            resolve({browser: true}),
            terser(),
        ],
    }
];
