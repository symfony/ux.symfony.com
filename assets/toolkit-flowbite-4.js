import './styles/toolkit-flowbite-4.css';
import 'flowbite';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import Alert from '@symfony/ux-toolkit/kits/flowbite-4/alert/assets/controllers/alert_controller.js';
import Dropdown from '@symfony/ux-toolkit/kits/flowbite-4/dropdown/assets/controllers/dropdown_controller.js';
import Modal from '@symfony/ux-toolkit/kits/flowbite-4/modal/assets/controllers/modal_controller.js';
import Tabs from '@symfony/ux-toolkit/kits/flowbite-4/tabs/assets/controllers/tabs_controller.js';

const app = startStimulusApp();
app.register('alert', Alert);
app.register('dropdown', Dropdown);
app.register('modal', Modal);
app.register('tabs', Tabs);
