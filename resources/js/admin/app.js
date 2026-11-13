// Admin entry. ui.js registers its Alpine stores on alpine:init, so it is imported before Alpine starts.
// (Importing ../app.js would not work: it is a separate entry chunk and always evaluates first.)
import './ui';
import '../bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
