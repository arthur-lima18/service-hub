# Caminhos dos projetos no Monorepo
SYMFONY_DIR = ./service-hub
JAVA_SERVICE_DIR = ./notificationservice
FRONTEND_DIR = $(SYMFONY_DIR)/frontend

.PHONY: help install up stop db-init create-user

help:
	@echo "Comandos disponíveis (Monorepo):"
	@echo "  make install      - Instala todas as dependências (PHP, JS, Java)"
	@echo "  make up           - Sobe todo o ecossistema (DB, Java, Symfony, Vue)"
	@echo "  make stop         - Para todos os serviços"
	@echo "  make db-init      - Inicializa o banco e as migrações"
	@echo "  make create-user  - Comando para criar um novo usuário"

install:
	cd $(SYMFONY_DIR) && composer install
	cd $(FRONTEND_DIR) && npm install
	cd $(JAVA_SERVICE_DIR) && ./mvnw.cmd install

up:
	cd $(SYMFONY_DIR) && docker-compose up database -d
	@echo "Aguardando banco de dados..."
	sleep 5
	# Inicia o Symfony
	cd $(SYMFONY_DIR) && symfony serve -d
	# Inicia Java e Vue em janelas separadas via PowerShell
	powershell.exe -Command "Start-Process cmd -ArgumentList '/c ./mvnw.cmd spring-boot:run' -WorkingDirectory '$(JAVA_SERVICE_DIR)'"
	powershell.exe -Command "Start-Process cmd -ArgumentList '/c npm run dev' -WorkingDirectory '$(FRONTEND_DIR)'"
	@echo "🚀 Tudo iniciado! Verifique as novas janelas."

stop:
	cd $(SYMFONY_DIR) && docker-compose stop
	cd $(SYMFONY_DIR) && symfony server:stop
	taskkill //F //IM java.exe //T || true
	taskkill //F //IM node.exe //T || true

db-init:
	cd $(SYMFONY_DIR) && php bin/console doctrine:migrations:migrate --no-interaction

create-user:
	@mkdir -p $(SYMFONY_DIR)/var/tmp
	@read -p "Email: " email; \
	read -p "Nome: " nome; \
	read -p "Senha: " senha; \
	cd $(SYMFONY_DIR) && php -d sys_temp_dir=var/tmp bin/console app:create-user $$email $$nome $$senha
