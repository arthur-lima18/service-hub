<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">
        <div class="icon-hub">G</div>
        <h1>Criar Conta</h1>
      </div>
      <p class="subtitle">Cadastre-se no Service Hub para começar</p>
      
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label>Nome Completo</label>
          <input v-model="name" type="text" placeholder="Seu nome" required />
        </div>
        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="seu@email.com" required />
        </div>
        <div class="form-group">
          <label>Senha</label>
          <input v-model="password" type="password" placeholder="••••••••" required />
        </div>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Criando conta...' : 'Cadastrar' }}
        </button>
        <p class="switch-auth">
          Já tem uma conta? <router-link to="/login">Entrar</router-link>
        </p>
        <p v-if="error" class="error-msg">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../services/AuthService';

const name = ref('');
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

const handleRegister = async () => {
  loading.value = true;
  error.value = '';
  try {
    await AuthService.register(name.value, email.value, password.value);
    alert('Conta criada com sucesso! Agora faça o login.');
    router.push('/login');
  } catch (e) {
    error.value = 'Erro ao criar conta. Tente outro email.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Aproveitando os estilos do Login para manter a identidade */
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}
.login-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  padding: 3rem;
  border-radius: 24px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
}
.logo { text-align: center; margin-bottom: 1.5rem; }
.icon-hub {
  width: 50px; height: 50px; background: #3b82f6;
  border-radius: 12px; margin: 0 auto 1rem;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; font-weight: bold;
}
h1 { margin: 0; font-size: 1.25rem; }
.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 2rem; }
.form-group { margin-bottom: 1.25rem; }
label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; color: #cbd5e1; }
input {
  width: 100%; padding: 0.75rem 1rem; border-radius: 12px;
  background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
  color: white; outline: none; transition: all 0.2s;
}
input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
button {
  width: 100%; padding: 0.75rem; background: #3b82f6; color: white;
  border: none; border-radius: 12px; font-weight: 600; cursor: pointer; margin-top: 1rem;
}
.switch-auth { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #94a3b8; }
.switch-auth a { color: #3b82f6; text-decoration: none; font-weight: 600; }
.error-msg { color: #f87171; text-align: center; margin-top: 1rem; font-size: 0.875rem; }
</style>
