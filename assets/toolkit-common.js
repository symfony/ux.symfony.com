import './styles/toolkit-common.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import * as Turbo from '@hotwired/turbo';
import { registerKitControllers } from './toolkit-controllers.js';

// The `common` kit is design-system agnostic and ships no styling of its own.
// Its demo/example templates use a few Tailwind utility classes (compiled via
// toolkit-common.css) purely to look good in this preview.
const app = startStimulusApp();
registerKitControllers(app, 'common');

// The `common` kit previews (PostLink/LogoutLink) submit real forms to our
// /link capture endpoint, which responds 200 instead of redirecting. Turbo
// Drive would intercept that and leave the iframe unchanged, so disable Drive
// for this preview entrypoint only (other kits keep Turbo enabled).
Turbo.session.drive = false;
