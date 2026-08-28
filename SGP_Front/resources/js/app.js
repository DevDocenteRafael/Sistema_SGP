import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import SearchableSelect from './components/SearchableSelect.vue';
import '../css/app.css';
import '../css/SearchableSelect.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const app = createApp(App);
app.component('SearchableSelect', SearchableSelect);
app.use(router);
app.mount('#app');
