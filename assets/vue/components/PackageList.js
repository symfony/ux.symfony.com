export default {
    props: ['packages'],
    template: `
            <div v-if="packages.length === 0">
                No packages found. Sad trombone...
            </div>

            <div v-else class="pt-3 grid gap-2 grid-cols-[repeat(auto-fill,minmax(260px,1fr))]">

                    <div v-for="package in packages" class="group relative flex gap-6 p-2 self-stretch border border-surface rounded-md bg-body" :style="{'--color': package.color, '--gradient': package.gradient || 'none', '--logo-size': '48px'} ">
                        <div class="flex items-center justify-center shrink-0 rounded-[14%] [background-image:var(--gradient)] bg-(--color) w-(--logo-size) h-(--logo-size) [&_img]:w-[calc(0.4*var(--logo-size))] [&_img]:h-auto [&_img]:filter-[drop-shadow(4px_4px_4px_rgba(0,0,0,0))]">
                            <img :src="package.imageUrl" :alt="package.humanName" :style="package.imageScale ? {width: 'calc(' + package.imageScale + ' * var(--logo-size))'} : null">
                        </div>
                        <h4 class="font-title font-semibold text-xl leading-normal text-body-text m-0">
                            <a :href="package.url">{{ package.humanName }}</a>
                        </h4>
                    </div>

            </div>
    `
};
