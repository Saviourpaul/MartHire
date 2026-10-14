import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        await window.MarthireRecovery?.handle(error.response, error.response?.data);
        return Promise.reject(error);
    },
);
