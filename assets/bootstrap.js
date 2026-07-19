import { startStimulusApp } from '@symfony/stimulus-bundle';
import Clipboard from 'stimulus-clipboard';

// The UX Toolkit ships the Stimulus controllers + base CSS its Markdown extensions
// (tabs, popover, code-preview clipboard) render against, so a host renders RecipeDocRenderer
// output interactively without a build step. Register them under the identifiers the Toolkit's
// Twig templates reference.
import ToolkitTabs from '@symfony/ux-toolkit/assets/controllers/tabs_controller.js';
import ToolkitPopover from '@symfony/ux-toolkit/assets/controllers/popover_controller.js';
import ToolkitClipboard from '@symfony/ux-toolkit/assets/controllers/clipboard_controller.js';
import '@symfony/ux-toolkit/assets/styles/toolkit.css';

const app = startStimulusApp();

app.register('clipboard', Clipboard);
app.register('toolkit-tabs', ToolkitTabs);
app.register('toolkit-popover', ToolkitPopover);
app.register('toolkit-clipboard', ToolkitClipboard);
