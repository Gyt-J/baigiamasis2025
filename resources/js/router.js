import { createRouter, createWebHistory } from 'vue-router'
import MapView from './components/MapView.vue'

const routes = [
    {
        path: '/',
        name: 'MapView',
        component: MapView
    }
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
})

export default router