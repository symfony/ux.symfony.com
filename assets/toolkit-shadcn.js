import './styles/toolkit-shadcn.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import { registerKitControllers } from './toolkit-controllers.js';

const app = startStimulusApp();
registerKitControllers(app, 'shadcn');
