<template>
  <div id="app">
    <!-- O router-view é essencial para mostrar as páginas de Login e Dashboard -->
    <router-view />
    
    <!-- Toast de Notificações Global -->
    <div v-if="notification" class="global-toast" @click="notification = null">
      <div class="toast-content">
        <strong>🔔 Novo Evento: {{ notification.event }}</strong>
        <p>Verifique os detalhes no sistema.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { initNotifications } from './services/NotificationService';

const notification = ref(null);

onMounted(() => {
  initNotifications((data) => {
    notification.value = data;
    // Esconde o alerta após 5 segundos
    setTimeout(() => {
      notification.value = null;
    }, 5000);
  });
});
</script>

<style>
/* Estilos globais reset */
* { box-sizing: border-box; }
body { margin: 0; font-family: 'Inter', -apple-system, sans-serif; background: #f8fafc; }

.global-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #0f172a;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  z-index: 9999;
  border-left: 4px solid #3b82f6;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

.toast-content strong { display: block; margin-bottom: 0.25rem; }
.toast-content p { margin: 0; font-size: 0.875rem; color: #94a3b8; }
</style>
