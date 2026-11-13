import './bootstrap';

import Alpine from 'alpinejs';
import { registerStorefront } from './storefront';

window.Alpine = Alpine;
registerStorefront(Alpine);
Alpine.start();
