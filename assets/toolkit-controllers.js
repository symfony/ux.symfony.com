const SUFFIX = '_controller.js';

// Auto-registers every Stimulus controller shipped by a kit. The UX Toolkit's controllers are all
// exposed as importmap entries (see ToolkitControllersImportMapConfigReader), which AssetMapper
// renders into the page's import map. We keep the ones under `kits/<kitId>/…/controllers/*_controller.js`
// and dynamically import + register each, so adding a component to a kit needs no change here.
export function registerKitControllers(app, kitId) {
    const importMap = JSON.parse(document.querySelector('script[type="importmap"]').textContent);
    const prefix = `@symfony/ux-toolkit/kits/${kitId}/`;

    for (const name of Object.keys(importMap.imports)) {
        if (!name.startsWith(prefix) || !name.endsWith(SUFFIX)) {
            continue;
        }

        const identifier = name.slice(name.lastIndexOf('/') + 1, -SUFFIX.length).replaceAll('_', '-');
        import(name).then((module) => app.register(identifier, module.default));
    }
}
