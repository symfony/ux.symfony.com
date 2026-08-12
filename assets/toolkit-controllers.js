import kits from './toolkit-controllers.loader.js';

// Auto-registers every Stimulus controller shipped by a kit. The map is generated at build time by
// ToolkitControllersLoaderCompiler (glob over the vendored kits), so adding a component to a kit
// needs no change here and each controller is lazily imported on demand.
export function registerKitControllers(app, kitId) {
    for (const [identifier, load] of Object.entries(kits[kitId] ?? {})) {
        load().then((module) => app.register(identifier, module.default));
    }
}
