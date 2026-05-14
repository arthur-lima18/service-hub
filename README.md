# 🚀 Service Hub - Sistema de Gestão de Chamados

O **Service Hub** é um projeto pessoal focado em oferecer uma plataforma centralizada para abertura, acompanhamento e resolução de tickets de suporte. O sistema utiliza uma arquitetura distribuída, garantindo escalabilidade e notificações em tempo real.

## 🏗️ Arquitetura do Projeto

O ecossistema é composto por três componentes principais:

### 1. 🐘 Backend (Core API)
*   **Framework**: [Symfony 6.4](https://symfony.com/) (PHP 8.2+)
*   **Banco de Dados**: PostgreSQL 16
*   **Segurança**: JWT (JSON Web Tokens) para autenticação sem estado.
*   **Servidor**: FrankenPHP (Containerizado)

### 2. ☕ Notification Service (Real-time Hub)
*   **Framework**: [Spring Boot 3.5](https://spring.io/projects/spring-boot) (Java 21)
*   **Comunicação**: WebSockets com protocolo STOMP.
*   **Função**: Recebe eventos do Symfony e os transmite instantaneamente para o Frontend.

### 3. ⚡ Frontend (UI Reativa)
*   **Framework**: [Vue.js 3](https://vuejs.org/) com [Vite](https://vitejs.dev/)
*   **Roteamento**: Vue Router
*   **Real-time**: Integração com SockJS e Webstomp para ouvir o serviço Java.
*   **Estilo**: CSS moderno com foco em UX/UI Premium.

---

## 🛠️ Tecnologias Utilizadas

### Infraestrutura & DevOps
- **Docker / Docker Compose**: Orquestração de containers.
- **Makefile**: Automação de comandos para ambiente Windows/Git Bash.
- **Git**: Controle de versão.

### Backend & Integrações
- **Doctrine ORM**: Mapeamento objeto-relacional.
- **Symfony Validator**: Garantia de integridade de dados via DTOs.
- **Spring Messaging**: Motor de WebSockets no Java.

### Frontend
- **Axios**: Cliente HTTP para consumo de APIs.
- **SockJS-Client**: Abstração de WebSocket para compatibilidade de navegadores.
- **Vite Define**: Correção de pollyfills globais para bibliotecas legadas.

---

## 🚀 Como Rodar o Projeto

O projeto utiliza um `Makefile` para facilitar a inicialização de todos os serviços.

1. **Pré-requisitos**:
   - Docker Desktop
   - PHP 8.2+
   - Java 21
   - Node.js 16+

2. **Instalação**:
   ```bash
   make install
   ```

3. **Execução**:
   ```bash
   make up
   ```

4. **Acessar**:
   - **Frontend**: http://localhost:3000
   - **API (Swagger/Docs)**: http://localhost:8000
   - **Java Hub**: http://localhost:8081

---

## 📋 Funcionalidades
- [x] Cadastro público e login de usuários.
- [x] Criação de tickets com anexos e níveis de prioridade.
- [x] Comentários em tempo real com histórico de logs.
- [x] Dashboard administrativa com filtros e paginação.
- [x] Notificações push (Toasts) automáticas.

---
*Este projeto é uma demonstração de arquitetura full-stack integrada.*
