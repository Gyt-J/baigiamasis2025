import './bootstrap';
import { createApp } from 'vue';
import MapView from './components/MapView.vue';

const app = createApp({});
app.component('map-view', MapView);
app.mount('#app');
