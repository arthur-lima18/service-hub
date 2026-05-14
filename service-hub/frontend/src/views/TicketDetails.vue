<template>
  <div class="ticket-details" v-if="ticket">
    <header class="header">
      <router-link to="/" class="btn-back">← Voltar</router-link>
      <div class="actions">
        <button v-if="canAssign" @click="assignToMe" class="btn-assign">Atribuir a Mim</button>
        <button v-if="ticket.status !== 'CLOSED'" @click="updateStatus('CLOSED')" class="btn-close">Fechar Ticket</button>
        <button v-else @click="updateStatus('OPEN')" class="btn-reopen">Reabrir Ticket</button>
      </div>
    </header>

    <div class="content-layout">
      <div class="main-info">
        <div class="ticket-card">
          <h1>{{ ticket.title }}</h1>
          <div class="meta">
            <span :class="'status-tag ' + ticket.status.toLowerCase()">{{ ticket.status }}</span>
            <span :class="'priority-tag ' + ticket.priority.toLowerCase()">Prioridade {{ ticket.priority }}</span>
            <span class="date">Aberto em {{ formatDate(ticket.createdAt) }}</span>
          </div>
          <p class="description">{{ ticket.description }}</p>
        </div>

        <div class="attachments-section">
          <h3>Anexos</h3>
          <div class="file-grid">
            <div v-for="file in attachments" :key="file.id" class="file-card">
              <span class="file-name">{{ file.filename }}</span>
              <a :href="file.filePath" target="_blank" class="btn-download">Download</a>
            </div>
          </div>
          <div class="upload-box">
            <input type="file" @change="handleUpload" />
            <p class="hint">Tamanho máximo: 5MB</p>
          </div>
        </div>

        <div class="comments-section">
          <h3>Interações</h3>
          <div class="comments-list">
            <div v-for="comment in comments" :key="comment.id" class="comment">
              <div class="comment-header">
                <strong>{{ comment.author?.name }}</strong>
                <span>{{ formatDate(comment.createdAt) }}</span>
              </div>
              <p>{{ comment.content }}</p>
            </div>
          </div>
          <div class="new-comment">
            <textarea v-model="newComment" placeholder="Escreva sua resposta..."></textarea>
            <button @click="postComment" :disabled="!newComment.trim()">Enviar Mensagem</button>
          </div>
        </div>
      </div>

      <aside class="sidebar-info">
        <div class="side-card">
          <h4>Responsável</h4>
          <p v-if="ticket.assignedTo">{{ ticket.assignedTo.name }}</p>
          <p v-else class="unassigned">Aguardando atribuição</p>
        </div>
        <div class="side-card">
          <h4>Histórico de Atividades</h4>
          <ul class="log-list">
            <li v-for="log in logs" :key="log.id">
              <strong>{{ log.action }}</strong>: {{ log.description }}
              <span>{{ formatDate(log.createdAt) }}</span>
            </li>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import AuthService from '../services/AuthService';

const route = useRoute();
const ticket = ref(null);
const comments = ref([]);
const attachments = ref([]);
const logs = ref([]);
const newComment = ref('');

const user = ref(AuthService.getUser());
const isAdmin = computed(() => user.value?.roles.includes('ROLE_ADMIN'));
const canAssign = computed(() => (isAdmin.value || user.value?.roles.includes('ROLE_TECH')) && !ticket.value.assignedTo);

const headers = { Authorization: `Bearer ${AuthService.getToken()}` };

const fetchData = async () => {
  const id = route.params.id;
  try {
    const [tRes, cRes, aRes, lRes] = await Promise.all([
      axios.get(`/api/tickets/${id}`, { headers }),
      axios.get(`/api/tickets/${id}/comments`, { headers }),
      axios.get(`/api/tickets/${id}/attachments`, { headers }),
      axios.get(`/api/tickets/${id}/logs`, { headers })
    ]);
    ticket.value = tRes.data;
    comments.value = cRes.data;
    attachments.value = aRes.data;
    logs.value = lRes.data;
  } catch (e) {
    console.error(e);
  }
};

const postComment = async () => {
  try {
    await axios.post(`/api/tickets/${ticket.value.id}/comments`, {
      content: newComment.value
    }, { headers });
    newComment.value = '';
    fetchData();
  } catch (e) { alert('Erro ao postar comentário'); }
};

const handleUpload = async (event) => {
  const file = event.target.files[0];
  const formData = new FormData();
  formData.append('file', file);
  try {
    await axios.post(`/api/tickets/${ticket.value.id}/attachments`, formData, {
      headers: { ...headers, 'Content-Type': 'multipart/form-data' }
    });
    fetchData();
  } catch (e) { alert('Erro no upload'); }
};

const updateStatus = async (status) => {
  try {
    await axios.patch(`/api/tickets/${ticket.value.id}`, { status }, { headers });
    fetchData();
  } catch (e) { alert(e.response?.data?.message || 'Erro ao atualizar'); }
};

const assignToMe = async () => {
  try {
    await axios.patch(`/api/tickets/${ticket.value.id}`, { assignedTo: user.value.id }, { headers });
    fetchData();
  } catch (e) { alert('Erro ao atribuir ticket'); }
};

const formatDate = (str) => new Date(str).toLocaleString('pt-BR');

onMounted(fetchData);
</script>

<style scoped>
.ticket-details { padding: 2rem 4rem; background: #f8fafc; min-height: 100vh; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.btn-back { color: #64748b; text-decoration: none; font-weight: 600; }
.content-layout { display: grid; grid-template-columns: 1fr 300px; gap: 2rem; }
.ticket-card, .attachments-section, .comments-section, .side-card { background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
h1 { margin: 0 0 1rem; font-size: 2rem; color: #0f172a; }
.meta { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
.description { line-height: 1.6; color: #334155; white-space: pre-wrap; }
.file-grid { display: grid; gap: 0.5rem; margin-bottom: 1rem; }
.file-card { display: flex; justify-content: space-between; padding: 0.75rem; background: #f1f5f9; border-radius: 8px; }
.comment { border-bottom: 1px solid #f1f5f9; padding: 1rem 0; }
.comment-header { display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 0.5rem; }
.new-comment textarea { width: 100%; height: 100px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 1rem; margin: 1rem 0; }
.log-list { list-style: none; padding: 0; font-size: 0.875rem; }
.log-list li { margin-bottom: 1rem; color: #64748b; }
.log-list span { display: block; font-size: 0.75rem; }
.unassigned { color: #94a3b8; font-style: italic; }
.status-tag { padding: 0.4rem 1rem; border-radius: 9999px; font-size: 0.875rem; font-weight: bold; }
.status-tag.open { background: #dcfce7; color: #166534; }
.status-tag.closed { background: #f1f5f9; color: #475569; }
.priority-tag { font-size: 0.875rem; font-weight: 600; color: #64748b; }
button { background: #3b82f6; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
.btn-close { background: #ef4444; }
.btn-reopen { background: #10b981; }
</style>
