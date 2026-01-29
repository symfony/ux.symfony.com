import './styles/toolkit-shadcn.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import Accordion from '@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js';
import AlertDialog from '@symfony/ux-toolkit/kits/shadcn/alert-dialog/assets/controllers/alert_dialog_controller.js';
import Dialog from '@symfony/ux-toolkit/kits/shadcn/dialog/assets/controllers/dialog_controller.js';
import Tooltip from '@symfony/ux-toolkit/kits/shadcn/tooltip/assets/controllers/tooltip_controller.js';

const app = startStimulusApp();
app.register('accordion', Accordion);
app.register('alert-dialog', AlertDialog);
app.register('dialog', Dialog);
app.register('tooltip', Tooltip);
