import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import SearchableSelect from './components/SearchableSelect.vue';
import SgpTooltip from './components/ui/SgpTooltip.vue';
import SgpHelpLabel from './components/ui/SgpHelpLabel.vue';
import { initAcessibilidade } from './utils/acessibilidade';
import { initScrollHorizontalTabelas, aplicarScrollHorizontalTabelas } from './utils/tableScrollSticky';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/app.css';
import '../css/theme.css';
import '../css/ui-a11y.css';
import '../css/SearchableSelect.css';

initAcessibilidade();

const app = createApp(App);
app.component('SearchableSelect', SearchableSelect);
app.component('SgpTooltip', SgpTooltip);
app.component('SgpHelpLabel', SgpHelpLabel);
app.use(router);
app.mount('#app');

initScrollHorizontalTabelas();
router.afterEach(() => {
  requestAnimationFrame(() => aplicarScrollHorizontalTabelas(document));
  setTimeout(() => aplicarScrollHorizontalTabelas(document), 200);
  setTimeout(() => aplicarScrollHorizontalTabelas(document), 800);
});

if (import.meta.hot) {
  import.meta.hot.accept('./utils/tableScrollSticky.js', () => {
    aplicarScrollHorizontalTabelas(document);
  });
}
