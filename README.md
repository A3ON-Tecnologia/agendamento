# Sistema de Agendamento de Reuniões

Sistema completo de agendamento de reuniões desenvolvido em PHP com MySQL no backend e HTML + TailwindCSS no frontend.

## 🚀 Características

- **Interface única**: Todo o sistema funciona em uma única tela sem scroll
- **Calendário interativo**: Visualização mensal com navegação
- **Status visuais**: Indicadores coloridos para diferentes status de reuniões
- **Modais responsivos**: Criação/edição de reuniões e relatórios
- **Validações**: Prevenção de conflitos de horário e agendamentos no passado
- **Filtros avançados**: Relatórios com múltiplos filtros
- **Porta personalizada**: Configurado para rodar na porta 2000

## 📋 Funcionalidades

### 🎯 Tela Principal
- Barra superior com título e guia de status
- Botões para "Agendar Reunião" e "Relatórios"
- Calendário mensal com navegação (← →)
- Indicadores de status nas datas com reuniões

### 📅 Calendário
- Visualização apenas do mês atual
- Bolinhas coloridas indicando reuniões:
  - 🟢 Em andamento
  - 🔴 Finalizada  
  - 🟠 Agendada
- Tooltips informativos ao passar o mouse
- Clique nas datas para agendar reuniões
- Clique nas bolinhas para editar reuniões

### 🧾 Modal de Reuniões
- Criação e edição de reuniões
- Campos: Participantes, Data, Horários, Assunto, Descrição, Status
- Validações automáticas
- Prevenção de conflitos de horário

### 📋 Modal de Relatórios
- Lista completa de reuniões
- Filtros por: Status, Data, Participante
- Visualização e edição através do ícone de olho
- Interface tabular responsiva

## 🛠️ Instalação

### Pré-requisitos
- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno
- Apache configurado para porta 2000

### Passos de Instalação

1. **Configure o Apache para porta 2000**
   ```
   - Edite httpd.conf no XAMPP
   - Altere "Listen 80" para "Listen 2000"
   - Reinicie o Apache
   ```

2. **Inicie o XAMPP**
   ```
   - Abra o XAMPP Control Panel
   - Inicie Apache (porta 2000) e MySQL
   ```

3. **Configure o Banco de Dados**
   ```
   - Acesse: http://localhost:2000/agendamento/setup/init_database.php
   - Aguarde a mensagem de confirmação
   ```

4. **Acesse o Sistema**
   ```
   - Abra: http://localhost:2000/agendamento/
   ```

## 🗃️ Estrutura do Banco de Dados

### Banco: cadastro_empresas

### Tabelas Utilizadas
- **users**: Usuários do sistema
  - Colunas: id, name, email, etc.
- **reunioes**: Dados das reuniões
  - Colunas: id, assunto, descricao, data_reuniao, hora_inicio, hora_fim, status
- **reuniao_participantes**: Relacionamento reunião-participantes
  - Colunas: reuniao_id, usuario_id, status_participacao, data_criacao

### Configuração de Porta
O sistema está configurado para MySQL na porta 2000. Certifique-se de que:
- Apache está rodando na porta 2000
- MySQL está acessível através desta porta
- As tabelas existem e estão populadas

## 🎨 Interface

### Status das Reuniões
- **Agendada** (🟠): Reunião planejada
- **Em andamento** (🟢): Reunião acontecendo
- **Finalizada** (🔴): Reunião concluída

### Navegação
- **Calendário**: Clique nas setas para navegar entre meses
- **Agendamento**: Clique em uma data ou no botão "Agendar Reunião"
- **Edição**: Clique nas bolinhas de status ou no ícone de olho nos relatórios
- **Relatórios**: Use os filtros para encontrar reuniões específicas

## 🔧 Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL (porta 2000)
- **Frontend**: HTML5, TailwindCSS, Alpine.js
- **Servidor**: Apache (porta 2000)

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:
- Desktops
- Tablets
- Smartphones

## 🛡️ Validações

- Prevenção de agendamentos no passado
- Verificação de conflitos de horário
- Validação de campos obrigatórios
- Tratamento de erros do servidor

## 🎯 Fuso Horário

O sistema utiliza UTC-3 (horário de Brasília) para todas as operações de data e hora.

## 🔧 Configuração de Porta 2000

### Arquivos Atualizados:
- `config/database.php`: Conexão MySQL porta 2000
- `api/meetings.php`: Endpoints ajustados para estrutura real do banco
- `index.php`: URLs atualizadas para porta 2000
- `.htaccess`: Configurações Apache e CORS

### URLs do Sistema:
- **Principal**: http://localhost:2000/agendamento/
- **API**: http://localhost:2000/agendamento/api/meetings.php
- **Setup**: http://localhost:2000/agendamento/setup/init_database.php

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique se Apache está rodando na porta 2000
2. Confirme se MySQL está acessível na porta configurada
3. Verifique se as tabelas existem no banco cadastro_empresas
4. Verifique o console do navegador para erros JavaScript

---

**Sistema configurado para porta 2000 e estrutura de banco existente** ✅
