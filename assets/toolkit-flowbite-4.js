import './styles/toolkit-flowbite-4.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import Modal from '@symfony/ux-toolkit/kits/flowbite-4/modal/assets/controllers/modal_controller.js';

const app = startStimulusApp();
app.register('modal', Modal);
