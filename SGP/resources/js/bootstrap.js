import axios from 'axios';
import { clearSessao } from './scripts/auth';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common.Accept = 'application/json';

const token = localStorage.getItem('sgp_token');

if (token) {
  window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

window.axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearSessao();

      const skipRedirect = error.config?.skipAuthRedirect;
      const jaNoLogin = window.location.pathname.toLowerCase().startsWith('/login');

      if (!skipRedirect && !jaNoLogin) {
        window.location.replace('/login');
      }
    }

    return Promise.reject(error);
  }
);
