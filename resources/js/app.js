import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue'
import MapView from './components/MapView.vue';

const app = createApp(App);

app.component('MapView', MapView);
app.use(router);
app.mount('#app');
