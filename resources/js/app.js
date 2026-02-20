import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import '../css/app.css';

const app = createApp(App);
app.use(router);
app.mount('#telescope-dashboard');
