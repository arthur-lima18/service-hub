<template>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="brand">S Hub</div>
      <nav>
        <a href="#" class="active">Tickets</a>
        <a href="#">Configurações</a>
      </nav>
      <div class="user-info">
        <p>{{ user?.name }}</p>
        <button @click="logout" class="btn-logout">Sair</button>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-bar">
        <h2>Meus Tickets</h2>
        <div class="search-box">
          <input v-model="search" @input="fetchTickets" placeholder="Buscar por título ou descrição..." />
        </div>
      </header>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total</h3>
          <p class="number">{{ meta.total }}</p>
        </div>
        <div class="stat-card">
          <h3>Abertos</h3>
          <p class="number">{{ openCount }}</p>
        </div>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Status</th>
              <th>Prioridade</th>
              <th>Criado em</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in tickets" :key="ticket.id">
              <td>#{{ ticket.id }}</td>
              <td class="ticket-title">{{ ticket.title }}</td>
              <td><span :class="'status-tag ' + ticket.status.toLowerCase()">{{ ticket.status }}</span></td>
              <td><span :class="'priority-tag ' + ticket.priority.toLowerCase()">{{ ticket.priority }}</span></td>
              <td>{{ formatDate(ticket.createdAt) }}</td>
              <td>
                <router-link :to="'/ticket/' + ticket.id" class="btn-view">Ver Detalhes</router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <button @click="changePage(meta.page - 1)" :disabled="meta.page === 1">Anterior</button>
        <span>Página {{ meta.page }} de {{ meta.pages }}</span>
        <button @click="changePage(meta.page + 1)" :disabled="meta.page === meta.pages">Próxima</button>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AuthService from '../services/AuthService';
import { useRouter } from 'vue-router';

const tickets = ref([]);
const meta = ref({ total: 0, page: 1, pages: 1 });
const search = ref('');
const user = ref(AuthService.getUser());
const router = useRouter();

const openCount = computed(() => tickets.value.filter(t => t.status === 'OPEN').length);

const fetchTickets = async () => {
  try {
    const response = await axios.get('/api/tickets', {
      params: { 
        search: search.value, 
        page: meta.value.page 
      },
      headers: { Authorization: `Bearer ${AuthService.getToken()}` }
    });
    tickets.value = response.data.items;
    meta.value = response.data.meta;
  } catch (e) {
    if (e.response?.status === 401) logout();
  }
};

const changePage = (newPage) => {
  meta.value.page = newPage;
  fetchTickets();
};

const logout = () => {
  AuthService.logout();
  router.push('/login');
};

const formatDate = (str) => new Date(str).toLocaleDateString('pt-BR');

onMounted(fetchTickets);
</script>

<style scoped>
.dashboard { display: flex; height: 100vh; background: #f8fafc; }
.sidebar { width: 260px; background: #0f172a; color: white; display: flex; flex-direction: column; padding: 2rem; }
.brand { font-size: 1.5rem; font-weight: bold; color: #3b82f6; margin-bottom: 3rem; }
nav a { display: block; color: #94a3b8; text-decoration: none; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.5rem; }
nav a.active { background: #1e293b; color: white; }
.user-info { margin-top: auto; padding-top: 2rem; border-top: 1px solid #1e293b; }
.main-content { flex: 1; overflow-y: auto; padding: 2rem 3rem; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.search-box input { padding: 0.75rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; width: 300px; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
.stat-card { background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.stat-card h3 { color: #64748b; font-size: 0.875rem; margin: 0 0 0.5rem; }
.number { font-size: 1.875rem; font-weight: bold; margin: 0; color: #0f172a; }
.table-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; background: #f1f5f9; padding: 1rem; font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
td { padding: 1rem; border-top: 1px solid #f1f5f9; color: #334155; }
.ticket-title { font-weight: 500; }
.status-tag { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.status-tag.open { background: #dcfce7; color: #166534; }
.status-tag.closed { background: #f1f5f9; color: #475569; }
.priority-tag { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
.priority-tag.high { color: #dc2626; }
.priority-tag.medium { color: #d97706; }
.btn-view { color: #3b82f6; text-decoration: none; font-size: 0.875rem; font-weight: 600; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 2rem; }
.btn-logout { background: transparent; border: 1px solid #dc2626; color: #dc2626; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; margin-top: 0.5rem; }
</style>
