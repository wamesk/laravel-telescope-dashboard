import { createRouter, createWebHashHistory } from 'vue-router';

import RequestsPage from './components/pages/RequestsPage.vue';
import QueriesPage from './components/pages/QueriesPage.vue';
import ExceptionsPage from './components/pages/ExceptionsPage.vue';
import JobsPage from './components/pages/JobsPage.vue';
import LogsPage from './components/pages/LogsPage.vue';
import MailPage from './components/pages/MailPage.vue';
import EventsPage from './components/pages/EventsPage.vue';
import CachePage from './components/pages/CachePage.vue';
import CommandsPage from './components/pages/CommandsPage.vue';
import SchedulePage from './components/pages/SchedulePage.vue';
import ModelsPage from './components/pages/ModelsPage.vue';
import GatesPage from './components/pages/GatesPage.vue';
import DumpsPage from './components/pages/DumpsPage.vue';
import NotificationsPage from './components/pages/NotificationsPage.vue';
import RedisPage from './components/pages/RedisPage.vue';
import ClientRequestsPage from './components/pages/ClientRequestsPage.vue';
import BatchesPage from './components/pages/BatchesPage.vue';
import EntryDetailPage from './components/pages/EntryDetailPage.vue';

const routes = [
    { path: '/', redirect: '/requests' },
    { path: '/requests', name: 'requests', component: RequestsPage },
    { path: '/queries', name: 'queries', component: QueriesPage },
    { path: '/exceptions', name: 'exceptions', component: ExceptionsPage },
    { path: '/jobs', name: 'jobs', component: JobsPage },
    { path: '/logs', name: 'logs', component: LogsPage },
    { path: '/mail', name: 'mail', component: MailPage },
    { path: '/events', name: 'events', component: EventsPage },
    { path: '/cache', name: 'cache', component: CachePage },
    { path: '/commands', name: 'commands', component: CommandsPage },
    { path: '/schedule', name: 'schedule', component: SchedulePage },
    { path: '/models', name: 'models', component: ModelsPage },
    { path: '/gates', name: 'gates', component: GatesPage },
    { path: '/dumps', name: 'dumps', component: DumpsPage },
    { path: '/notifications', name: 'notifications', component: NotificationsPage },
    { path: '/redis', name: 'redis', component: RedisPage },
    { path: '/client-requests', name: 'client-requests', component: ClientRequestsPage },
    { path: '/batches', name: 'batches', component: BatchesPage },
    { path: '/:type/:id', name: 'entry-detail', component: EntryDetailPage, props: true },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

export default router;
